<?php
declare(strict_types = 1);

class Roff_Condition implements Roff_Template
{

    // Tcl_RegisterObjType.3 condition: ""with whitespace"
    // For the nested parens bit, see http://stackoverflow.com/a/3851098
    // For the last quotes bit, see http://stackoverflow.com/a/366532
    const CONDITION_REGEX = '(!?[ntv]|!?[cdmrFS]\s?[^\s]+|!?"[^"]*"[^"]*"|!?\'[^\']*\'[^\']*\'|\((?>[^()]+|(?1))*\)|(?:[^\s"\']|"[^"]*"|\'[^\']*\')+)';

    static function evaluate(array $request, array &$lines, ?array $macroArguments)
    {

        array_shift($lines);

        $man       = Man::instance();
        $condition = $man->applyAllReplacements($request['raw_arg_string']);

        if ($request['request'] === 'if') {

            if (mb_strlen($request['raw_arg_string']) === 0) {
                return []; // Just skip
            }

            if (preg_match(
                '~^' . self::CONDITION_REGEX . ' [\.\']if\s+' . self::CONDITION_REGEX . ' \\\\{\s*(.*)$~u',
                $condition, $matches)
            ) {
                $newLines = self::ifBlock($lines, $matches[3],
                    self::test($matches[1], $macroArguments) && self::test($matches[2], $macroArguments));
                array_splice($lines, 0, 0, $newLines);
                return [];
            }

            if (preg_match('~^' . self::CONDITION_REGEX . ' \\\\{\s*(.*)$~u', $condition, $matches)) {
                $newLines = self::ifBlock($lines, $matches[2], self::test($matches[1], $macroArguments));
                array_splice($lines, 0, 0, $newLines);
                return [];
            }

            if (preg_match('~^' . self::CONDITION_REGEX . '\s?(.*?)$~u', $condition, $matches)) {
                if (self::test($matches[1], $macroArguments)) {
                    array_unshift($lines, $matches[2]); // i.e. just remove .if <condition> prefix and go again.
                    return [];
                } else {
                    return [];
                }
            }

        } elseif ($request['request'] === 'ie') {

            if (preg_match('~^' . self::CONDITION_REGEX . '\s?\\\\{\s*(.*)$~u', $condition, $matches)) {
                $useIf     = self::test($matches[1], $macroArguments);
                $ifLines   = self::ifBlock($lines, $matches[2], $useIf);
                $elseLines = self::handleElse($lines, $useIf);
                if ($useIf) {
                    array_splice($lines, 0, 0, $ifLines);
                } else {
                    array_splice($lines, 0, 0, $elseLines);
                }
                return [];
            }

            if (preg_match('~^' . self::CONDITION_REGEX . '\s?(.*)$~u', $condition, $ifMatches)) {
                $useIf     = self::test($ifMatches[1], $macroArguments);
                $elseLines = self::handleElse($lines, $useIf);
                if ($useIf) {
                    array_unshift($lines, $ifMatches[2]);
                } else {
                    array_splice($lines, 0, 0, $elseLines);
                }
                return [];
            }
        }

        throw new Exception('Unexpected request "' . $request['request'] . '" in Roff_Condition:' . $request['raw_line']);

    }

    private static function handleElse(array &$lines, bool $useIf): array
    {

        $request = Request::getLine($lines);
        array_shift($lines);

        if ($request['request'] === 'el') {
            if (preg_match('~^\\\\{(.*)$~u', $request['raw_arg_string'], $matches)) {
                return self::ifBlock($lines, $matches[1], !$useIf);
            }
        } else {
            //throw new Exception('.ie condition - not followed by expected pattern on line ' . $i . ' (got "' . $lines[$i] . '").');
            // Just skip the ie and el lines:
            return [];
        }

        if ($useIf) {
            return [];
        } else {
            return [$request['arg_string']];
        }

    }

    static function test(string $condition, $macroArguments): bool
    {
        $man       = Man::instance();
        $condition = $man->applyAllReplacements($condition);
        return self::testRecursive($condition, $macroArguments);
    }

    private static function testRecursive(string $condition, $macroArguments): bool
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
            return in_array($matches[1], ['TE', 'TS', 'URL']);
        }

        if (preg_match('~^r\s*([-\w]+)$~u', $condition, $matches)) {
            return $man->issetRegister($matches[1]);
        }

        $condition = Roff_Unit::normalize($condition, 'u', 'u');

        $condition = Replace::preg('~:~u', ' or ', $condition);
        $condition = Replace::preg('~&~u', ' and ', $condition);

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
