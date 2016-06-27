<?php


class Roff_Condition
{

    static function checkEvaluate(array $lines, int $i)
    {

        if (
          preg_match('~^\.if ([^\s]+) \.if [^\s]+ \\\\{(.*)$~u', $lines[$i], $matches) or
          preg_match('~^\.if ([^\s]+) \\\\{(.*)$~u', $lines[$i], $matches)
        ) {
            // TODO: fix. Just skipping 2nd .if for now
            $if = self::ifBlock($lines, $i, $matches[2]);
            if (self::test($matches[1])) {
                return $if;
            } else {
                return ['lines' => [], 'i' => $if['i']];
            }
        }

        if (preg_match('~^\.if ([^\s]+) \.if ([^\s]+) (.*)$~u', $lines[$i], $matches)) {
            if (self::test($matches[1]) and self::test($matches[2])) {
                return ['lines' => [$matches[3]], 'i' => $i];
            } else {
                return ['lines' => [], 'i' => $i];
            }
        }

        if (preg_match('~^\.if ([^\s]+) (.*)$~u', $lines[$i], $matches)) {
            if (self::test($matches[1])) {
                return ['lines' => [$matches[2]], 'i' => $i];
            } else {
                return ['lines' => [], 'i' => $i];
            }
        }

        if (preg_match('~^\.ie ([^\s]+) \\\\{(.*)$~u', $lines[$i], $matches)) {
            $condition = $matches[1];
            $if        = self::ifBlock($lines, $i, $matches[2]);
            $i         = $if['i'] + 1;

            $line = $lines[$i];

            if (!preg_match('~^\.el\s?\\\\{(.*)$~', $line, $matches)) {
                throw new Exception('.ie - not followed by expected .el on line: "' . $line . '".');
            }

            $else     = self::ifBlock($lines, $i, $matches[1]);
            $newLines = self::test($condition) ? $if['lines'] : $else['lines'];

            return ['lines' => $newLines, 'i' => $else['i']];

        }

        return false;

    }

    private static function test(string $condition)
    {

        $alwaysTrue = [
          'n',       // "Formatter is nroff." ("for TTY output" - try changing to 't' sometime?)
          '\\n[.g]', // Always 1 in GNU troff.  Macros should use it to test if running under groff.
          '\\n(.g',  // as above
          '!\\nF==2', // F register != 0 used to signal we should generate index entries. See e.g. frogatto.6
        ];

        if (in_array($condition, $alwaysTrue)) {
            return true;
        }

        $alwaysFalse = [
          't', // "Formatter is troff."
          'v', // vroff
          'rF',
          '\\nF>0',
          '\\nF',
          '(\\n(rF:(\\n(.g==0))',
          '\\n(.H>23', // part of a check for low resolution devices, e.g. frogatto.6
          '(\\n(.H=4u)&(1m=24u)', // ? e.g. frogatto.6
          '(\\n(.H=4u)&(1m=20u)', // ? e.g. frogatto.6
        ];

        if (in_array($condition, $alwaysFalse)) {
            return false;
        }

        throw new Exception('Unhandled condition: "' . $condition . '".');

    }

    private static function ifBlock(array $lines, int $i, string $firstLine)
    {

        $numLines         = count($lines);
        $foundEnd         = false;
        $replacementLines = [];

        if ($firstLine !== '') {
            $replacementLines[] = Macro::massageLine($firstLine);
        }

        ++$i;
        $openBraces = 1;
        $recurse    = false;

        for (; $i < $numLines; ++$i) {
            $line = $lines[$i];
            $openBraces += substr_count($line, '\\{');
            if ($openBraces > 1 or preg_match('~\.\s+i[fe] ~u', $line)) {
                $recurse = true;
            }
            $openBraces -= substr_count($line, '\\}');
            if (preg_match('~^(.*)\\\\}$~u', $line, $matches) and $openBraces === 0) {
                $foundEnd = true;
                if (!empty($matches[1]) && $matches[1] !== '\'br') {
                    $replacementLines[] = Macro::massageLine($matches[1]);
                }
                break;
            } elseif ($line !== '') {
                $replacementLines[] = Macro::massageLine($line);
            }
        }

        if (!$foundEnd) {
            throw new Exception('.if condition \\{ - not followed by expected pattern on line ' . $i . '.');
        }

        if ($recurse) {
            $recurseLines = [];
            for ($j = 0; $j < count($replacementLines); ++$j) {
                $result = self::checkEvaluate($replacementLines, $j);
                if ($result !== false) {
                    $recurseLines = array_merge($recurseLines, $result['lines']);
                    $j            = $result['i'];
                } else {
                    $recurseLines[] = $replacementLines[$j];
                }
            }
            $replacementLines = $recurseLines;
        }

        return ['lines' => $replacementLines, 'i' => $i];

    }

}
