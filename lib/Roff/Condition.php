<?php
declare(strict_types=1);

class Roff_Condition implements Roff_Template
{

    // Tcl_RegisterObjType.3 condition: ""with whitespace"
    // For the nested parens bit, see http://stackoverflow.com/a/3851098
    // For the last quotes bit, see http://stackoverflow.com/a/366532
    const CONDITION_REGEX = '(!?[ntv]|!?[cdmrFS]\s?[^\s]+|!?"[^"]*"[^"]*"|!?\'[^\']*\'[^\']*\'|\((?>[^()]+|(?1))*\)|(?:[^\s"\']|"[^"]*"|\'[^\']*\')+)';

    private static function isBalancedCondition(string $string): bool
    {

        $len   = mb_strlen($string);
        $stack = [];
        for ($i = 0; $i < $len; $i++) {
            switch ($string[$i]) {
                case '\\':
                    ++$i; // skip next char, it's escaped
                    break;
                case '(':
                    array_push($stack, 0);
                    break;
                case ')':
                    if (array_pop($stack) !== 0) {
                        return false;
                    }
                    break;
//                case '[':
//                    array_push($stack, 1);
//                    break;
//                case ']':
//                    if (array_pop($stack) !== 1) {
//                        return false;
//                    }
//                    break;
                default:
                    break;
            }
        }

        return (empty($stack));
    }

    private static function getNextCondition(array &$argChars): string
    {
        $condition = Request::getNextArgument($argChars, true);
        if (in_array($condition, ['c', 'd', 'm', 'r', 'F', 'S', '!c', '!d', '!m', '!r', '!F', '!S'])) {
            $condition .= Request::getNextArgument($argChars, true);
        }
        while (!self::isBalancedCondition($condition)) {
            if (count($argChars)) {
                $condition .= Request::getNextArgument($argChars, true);
            } else {
                return '0';
            }
        }

        return $condition;
    }

    /**
     * @param array $request
     * @param array $lines
     * @param array|null $macroArguments
     * @throws Exception
     */
    static function evaluate(array $request, array &$lines, ?array $macroArguments): void
    {

        array_shift($lines);

        $argChars = Request::getArgChars($request['arg_string']);

        if (!count($argChars)) {
            return; // Just skip
        }

        $condition = self::getNextCondition($argChars);

        if ($condition === '\{') { // See e.g. whatsup.1
            $newLines = self::ifBlock($lines, '', true);
            array_splice($lines, 0, 0, $newLines);

            return;
        }

        if (is_null($condition)) {
            return; // Just skip
        }

        $conditionTrue = self::test($condition, $macroArguments);

        if (!count($argChars)) {
            if (!$conditionTrue) {
                array_shift($lines);
            }

            return; // Just skip
        }

        $nextArg = Request::getNextArgument($argChars, true);

        if ($nextArg === '.if' || $nextArg === '\'if') {
            $condition2    = self::getNextCondition($argChars);
            $conditionTrue = $conditionTrue && self::test($condition2, $macroArguments);
            $nextArg       = Request::getNextArgument($argChars, true);
        }

        $postConditionString = $nextArg;
        if (count($argChars)) {
            $postConditionString .= ' ' . implode('', $argChars);
        }

        $postConditionString = ltrim($postConditionString); // cougar.1alc has extra leading space
        $postConditionBlock = strpos($postConditionString, '\\{') === 0;
        if ($postConditionBlock) {
            $postConditionString = substr($postConditionString, 2);
        }
        $postConditionString = ltrim($postConditionString);

        if ($request['request'] === 'if') {

            if ($postConditionBlock) {
                $newLines = self::ifBlock($lines, $postConditionString, $conditionTrue);
                array_splice($lines, 0, 0, $newLines);

                return;
            } else {
                if ($conditionTrue) {
                    array_unshift($lines, $postConditionString); // just remove .if <condition> prefix and go again.

                    return;
                } else {
                    return;
                }
            }

        } elseif ($request['request'] === 'ie') {

            if ($postConditionBlock) {
                $ifLines   = self::ifBlock($lines, $postConditionString, $conditionTrue);
                $elseLines = self::handleElse($lines, $conditionTrue);
                array_splice($lines, 0, 0, $conditionTrue ? $ifLines : $elseLines);

                return;
            } else {
                $elseLines = self::handleElse($lines, $conditionTrue);
                if ($conditionTrue) {
                    array_unshift($lines, $postConditionString);
                } else {
                    array_splice($lines, 0, 0, $elseLines);
                }

                return;
            }
        }

        throw new Exception('Unexpected request "' . $request['request'] . '" in Roff_Condition:' . $request['raw_line']);

    }

    /**
     * @param array $lines
     * @param bool $useIf
     * @return array
     * @throws Exception
     */
    private static function handleElse(array &$lines, bool $useIf): array
    {

        $request = Request::getLine($lines);

        // Do comments first
        if (Roff_Comment::checkLine($lines)) { // Roff_Comment::checkLine() can alter $lines
            // We want another look at the same line:
            return self::handleElse($lines, $useIf);
        }

        array_shift($lines);

        if ($request['request'] === 'el') {
            if (preg_match('~^\\\\{(.*)$~u', $request['raw_arg_string'], $matches)) {
                return self::ifBlock($lines, $matches[1], !$useIf);
            }
        } else {
            // throw new Exception('.ie condition - not followed by expected pattern (got "' . $lines[0] . '").');
            // Just skip the ie and el lines:
            return [];
        }

        if ($useIf) {
            return [];
        } else {
            return [$request['arg_string']];
        }

    }

    /**
     * @param string $condition
     * @param $macroArguments
     * @return bool
     * @throws Exception
     */
    static function test(string $condition, $macroArguments): bool
    {
        $man       = Man::instance();
        $condition = $man->applyAllReplacements($condition);

        return self::testRecursive($condition, $macroArguments);
    }

    /**
     * @param string $condition
     * @param $macroArguments
     * @return bool
     * @throws Exception
     */
    private static function testRecursive(string $condition, $macroArguments): bool
    {

        if (mb_strpos($condition, '!') === 0) {
            return !self::testRecursive(mb_substr($condition, 1), $macroArguments);
        }

        $alwaysTrue = [
          't', // "Formatter is troff."
        ];

        if (in_array($condition, $alwaysTrue, true)) {
            return true;
        }

        $alwaysFalse = [
          '\\(.g',
          '\n()P',
          'n',     // "Formatter is nroff." ("for TTY output" - try changing to 't' sometime?)
          'v', // vroff
          'require_index',
          'c\[shc]', // see man.1
          '\'po4a.hide\'',
        ];

        if (in_array($condition, $alwaysFalse, true)) {
            return false;
        }

        $condition = Replace::preg('~^\(((?:[^()]|\((?1)\))*+)\)$~u', '$1', $condition);

        if (
          preg_match('~^\'([^\']*)\'([^\']*)\'$~u', $condition, $matches) ||
          preg_match('~^"([^"]*)"([^"]*)"$~u', $condition, $matches)
        ) {
            return Roff_Macro::applyReplacements($matches[1], $macroArguments) ===
              Roff_Macro::applyReplacements($matches[2], $macroArguments);
        }

        // Don't do this earlier to not add " into $condition which could break string comparison check above.
        $condition = Roff_Macro::applyReplacements($condition, $macroArguments);
        // Do this again for the swapped-in strings:
        $man       = Man::instance();
        $condition = $man->applyAllReplacements($condition);

        if (preg_match('~^m\s*\w+$~u', $condition)) {
            // mname: True if there is a color called name.
            return false; // No colours for now.
        }

        if (preg_match('~^F\s?(.*)$~u', $condition, $matches)) {
            // Ffont: True if there exists a font named font.
            // Check below is based on contents of /usr/share/groff/1.22.3/font/devascii/
            return in_array($matches[1], ['R', 'B', 'I', 'BI']);
        }

        if (preg_match('~^c~u', $condition)) {
            // cch: True if there is a glyph ch available.
            return true; // Assume we have all the glyphs
        }

        if (preg_match('~^d\s*(\w+)$~u', $condition, $matches)) {
            // dname: True if there is a string, macro, diversion, or request called name.
            // Hack (all other checks are against "d pdfmarks", hopefully that's should be false.
            return in_array($matches[1], ['TE', 'TS', 'URL', 'MTO']);
        }

        if (preg_match('~^r\s*([-\w]+)$~u', $condition, $matches)) {
            return $man->issetRegister($matches[1]);
        }

        // Handle : (logical or) and & (logical and), see http://dev.mankier.com/7/groff#Numerical_Expressions
        if (preg_match('~^(.+?)([:&])(.+)$~u', $condition, $matches)) {
            if ($matches[2] === '&') {
                return self::testRecursive($matches[1], $macroArguments) &&
                  self::testRecursive($matches[3], $macroArguments);
            } else {
                return self::testRecursive($matches[1], $macroArguments) ||
                  self::testRecursive($matches[3], $macroArguments);
            }
        }

        $condition = Roff_Unit::normalize($condition, 'u', 'u');

        if (preg_match('~^([-\+\*/\d\(\)><=\.\s]| or | and )+$~u', $condition)) {
            $condition = Replace::preg('~(?<=[\d\s])=(?=[\d\s])~', '==', $condition);
            try {
                return eval('return ' . $condition . ';') > 0;
            } catch (ParseError $e) {
                throw new Exception($e->getMessage());
            }
        }

        // If we can't figure it out, assume false. We could also do this: throw new Exception('Unhandled condition: "' . $condition . '".');

        return false;

    }

    /**
     * @param array $lines
     * @param string $firstLine
     * @param bool $processContents
     * @return array
     * @throws Exception
     */
    static function ifBlock(array &$lines, string $firstLine, bool $processContents): array
    {

        $foundEnd         = false;
        $replacementLines = [];
        $line             = $firstLine;
        $openBraces       = 1;

        while (true) {

            $openBraces += substr_count($line, '\\{');
            $openBraces -= substr_count($line, '\\}');

            if (preg_match('~^(.*)\\\\}(.*)$~u', $line, $matches) && $openBraces === 0) {
                $foundEnd = true;
                if (!empty($matches[1]) && $matches[1] !== '\'br') {
                    $replacementLines[] = $matches[1];
                }
                if (!empty($matches[2])) {
                    array_unshift($lines, $matches[2]);
                }
                break;
            } elseif ($line !== '') {
                $replacementLines[] = $line;
            }

            if (count($lines) === 0) {
                break;
            }

            $line = array_shift($lines);

        }

        if (!$foundEnd) {
            throw new Exception('.if condition \\{ - not followed by expected pattern.');
        }

        if (!$processContents) {
            return [];
        }

        if (count($replacementLines) && !is_null(Man::instance()->escape_char)) {
            // Catch any trailing line continuations which would have just put closing \} on same line. See SbCylinder.3iv
            $lastLine = array_pop($replacementLines);
            if (mb_substr($lastLine, -1, 1) === '\\') {
                $lastLine = mb_substr($lastLine, 0, -1);
            }
            array_push($replacementLines, $lastLine);
        }

        return $replacementLines;

    }

}
