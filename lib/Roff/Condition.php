<?php


class Roff_Condition implements Roff_Template
{

    // Tcl_RegisterObjType.3 condition: ""with whitespace"
    const CONDITION_REGEX = '(!?[ntv]|!?[cdmrFS]\s?[^\s]+|!?"[^"]*"[^"]*"|!?\'[^\']*\'[^\']*\'|[^"][^\s]*)';

    static function evaluate(array $request, array &$lines, int $i, ?array $macroArguments)
    {

        if ($request['request'] === 'if') {

            if (mb_strlen($request['raw_arg_string']) === 0) {
                return ['i' => $i]; // Just skip
            }

            if (preg_match(
              '~^' . self::CONDITION_REGEX . ' [\.\']if\s+' . self::CONDITION_REGEX . ' \\\\{\s*(.*)$~u',
              $request['raw_arg_string'], $matches)
            ) {
                return self::ifBlock($lines, $i, $matches[3],
                  self::test($matches[1], $macroArguments) && self::test($matches[2], $macroArguments),
                  $macroArguments);
            }

            if (preg_match('~^' . self::CONDITION_REGEX . ' \\\\{\s*(.*)$~u', $request['raw_arg_string'], $matches)) {
                return self::ifBlock( $lines, $i, $matches[2], self::test($matches[1], $macroArguments),
                  $macroArguments);
            }

            if (preg_match('~^' . self::CONDITION_REGEX . '\s?(.*?)$~u', $request['raw_arg_string'], $matches)) {
                if (self::test($matches[1], $macroArguments)) {
                    $lines[$i] = $matches[2]; // i.e. just remove .if <condition> prefix and go again.
                    return ['i' => $i - 1];
                } else {
                    return ['lines' => [], 'i' => $i];
                }
            }

        } elseif ($request['request'] === 'ie') {

            if (preg_match('~^' . self::CONDITION_REGEX . '\s?\\\\{\s*(.*)$~u', $request['raw_arg_string'], $matches)) {
                $useIf = self::test($matches[1], $macroArguments);
                $if    = self::ifBlock($lines, $i, $matches[2], $useIf, $macroArguments);
                $i     = $if['i'];

                $result = Roff_Comment::checkEvaluate($lines, $i);
                if ($result !== false) {
                    $i = $result['i'];
                }

                return self::handleElse($lines, $i + 1, $useIf, $if['lines'], $macroArguments);
            }

            if (preg_match('~^' . self::CONDITION_REGEX . '\s?(.*)$~u', $request['raw_arg_string'], $ifMatches)) {
                $useIf  = self::test($ifMatches[1], $macroArguments);
                $result = Roff_Comment::checkEvaluate($lines, $i + 1);
                if ($result !== false) {
                    $i = $result['i'];
                }

                if ($useIf) {
                    $lineArray = [Roff_Macro::applyReplacements($ifMatches[2], $macroArguments)];
                    //TODO: checkthis
                    //Roff::parse($parentNode, $lineArray, $macroArguments);
                } else {
                    $lineArray = [];
                }

                return self::handleElse($lines, $i + 1, $useIf, $lineArray, $macroArguments);
            }
        }

        throw new Exception('Unexpected request "' . $request['request'] . '" in Roff_Condition:' . $lines[$i]);

    }

    private static function handleElse(
      array $lines,
      int $i,
      bool $useIf,
      array $ifLines,
      $macroArguments
    ): array
    {

        $request = Request::getLine($lines, $i);

        if ($request['request'] === 'el') {

            if (preg_match('~^\\\\{(.*)$~u', $request['raw_arg_string'], $matches)) {
                $else     = self::ifBlock($lines, $i, $matches[1], !$useIf, $macroArguments);
                $newLines = $useIf ? $ifLines : $else['lines'];

                return ['lines' => $newLines, 'i' => $else['i']];
            }

        } else {
            //throw new Exception('.ie condition - not followed by expected pattern on line ' . $i . ' (got "' . $lines[$i] . '").');
            // Just skip the ie and el lines:
            return ['lines' => [], 'i' => $i];
        }

        if ($useIf) {
            return ['lines' => $ifLines, 'i' => $i];
        } else {
            $lineArray = [Roff_Macro::applyReplacements($request['arg_string'], $macroArguments)];
//TODO: checkthis
//            Roff::parse($parentNode, $lineArray, $macroArguments);

            return ['lines' => $lineArray, 'i' => $i];
        }

    }

    private static function test(string $condition, $macroArguments)
    {
        $man       = Man::instance();
        $condition = $man->applyAllReplacements($condition);

        return self::testRecursive($condition, $macroArguments);
    }

    private static function testRecursive(string $condition, $macroArguments)
    {

        if (mb_strpos($condition, '!') === 0) {
            return !self::testRecursive(mb_substr($condition, 1), $macroArguments);
        }


        $alwaysTrue = [
          'n',     // "Formatter is nroff." ("for TTY output" - try changing to 't' sometime?)
        ];

        if (in_array($condition, $alwaysTrue, true)) {
            return true;
        }

        $alwaysFalse = [
          '\n()P',
          't', // "Formatter is troff."
          'v', // vroff
          'require_index',
          'c \[shc]', // see man.1
          '\'po4a.hide\'',
        ];

        if (in_array($condition, $alwaysFalse, true)) {
            return false;
        }

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

        if (preg_match('~^[Fc]~u', $condition)) {
            // Ffont: True if there exists a font named font.
            // cch: True if there is a glyph ch available.
            return true; // Assume we have all the glyphs and fonts
        }

        if (preg_match('~^d\s*(\w+)$~u', $condition, $matches)) {
            // dname: True if there is a string, macro, diversion, or request called name.
            // Hack (all other checks are against "d pdfmarks", hopefully that's should be false.
            return in_array($matches[1], ['TE', 'TS', 'URL']);
        }

        if (preg_match('~^r\s*([-\w]+)$~u', $condition, $matches)) {
            return $man->issetRegister($matches[1]);
        }

        $condition = Roff_Unit::normalize($condition);

        $condition = Replace::preg('~:~u', ' or ', $condition);
        $condition = Replace::preg('~&~u', ' and ', $condition);

        if (preg_match('~^([-\+\*/\d\(\)><=\.\s]| or | and )+$~u', $condition)) {
            $condition = Replace::preg('~(?<=[\d\s])=(?=[\d\s])~', '==', $condition);
            try {
                return eval('return ' . $condition . ';');
            } catch (ParseError $e) {
                throw new Exception($e->getMessage());
            }
        }

        // If we can't figure it out, assume false. We could also do this: throw new Exception('Unhandled condition: "' . $condition . '".');

        return false;

    }

    private static function ifBlock(
      array &$lines,
      int $i,
      string $firstLine,
      bool $processContents,
      $macroArguments
    ) {

        $foundEnd         = false;
        $replacementLines = [];

        $line = $firstLine;

        $openBraces = 1;
        $recurse    = false;

        for ($ifIndex = $i; $ifIndex < count($lines);) {

            $openBraces += substr_count($line, '\\{');
            if ($openBraces > 1 ||
              ($i !== $ifIndex && preg_match('~^\.\s*i[fe] ~u', $line))
            ) {
                $recurse = true;
            }
            $openBraces -= substr_count($line, '\\}');
            if (preg_match('~^(.*)\\\\}(.*)$~u', $line, $matches) && $openBraces === 0) {
                $foundEnd = true;
                if (!empty($matches[1]) && $matches[1] !== '\'br') {
                    $replacementLines[] = $matches[1];
                }
                if (!empty($matches[2])) {
                    --$ifIndex;
                    $lines[$ifIndex] = $matches[2];
                }
                break;
            } elseif ($line !== '') {
                $replacementLines[] = $line;
            }
            ++$ifIndex;
            if ($ifIndex < count($lines)) {
                $line = $lines[$ifIndex];
            }
        }

        if (!$foundEnd) {
            throw new Exception('.if condition \\{ - not followed by expected pattern on line ' . $ifIndex . '.');
        }

        if (!$processContents) {
            return ['lines' => [], 'i' => $ifIndex];
        }

        if ($recurse) {
            $recurseLines = [];
            for ($j = 0; $j < count($replacementLines); ++$j) {
                if (
                  $request = Request::getLine($replacementLines, $j) and
                  in_array($request['request'], ['if', 'ie']) and
                  $result = self::evaluate($request, $replacementLines, $j, $macroArguments) and
                  $result !== false
                ) {
                    if (array_key_exists('lines', $result)) {
                        $recurseLines = array_merge($recurseLines, $result['lines']);
                    }
                    $j = $result['i'];
                } else {
                    $recurseLines[] = $replacementLines[$j];
                }
            }
            $replacementLines = $recurseLines;
        }

        foreach ($replacementLines as $k => $v) {
            $replacementLines[$k] = Roff_Macro::applyReplacements($v, $macroArguments);
        }

        return ['lines' => $replacementLines, 'i' => $ifIndex];

    }

}
