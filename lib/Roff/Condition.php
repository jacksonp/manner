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
                return ['lines' => [], $i => $if['i']];
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
            $i         = $if[1] + 1;

            $line = $lines[$i];

            if (!preg_match('~^\.el \\\\{(.*)$~', $line, $matches)) {
                throw new Exception('.ie - not followed by expected .el on line ' . $i . '.');
            }

            $else     = self::ifBlock($lines, $i, $matches[1]);
            $newLines = self::test($condition) ? $if['lines'] : $else['lines'];

            return ['lines' => $newLines, $else['i']];

        }

        return false;

    }

    private static function test(string $condition)
    {

        $alwaysTrue = [
          'n',       // "Formatter is nroff." ("for TTY output" - try changing to 't' sometime?)
          '\\n[.g]', // Always 1 in GNU troff.  Macros should use it to test if running under groff.
          '\\n(.g',  // as above
        ];

        if (in_array($condition, $alwaysTrue)) {
            return true;
        }

        $alwaysFalse = [
          't', // "Formatter is troff."
          'v', // vroff
          '\\nF>0',
          '\\nF',
          '(\\n(rF:(\\n(.g==0))',
          '\\n(.H>23', // part of a check for low resolution devices, e.g. frogatto.6
        ];

        if (in_array($condition, $alwaysFalse)) {
            return false;
        }

        throw new Exception('Unhandled condition: ' . $condition);

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
            if ($openBraces > 1) {
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
                    $recurseLines = array_merge($recurseLines, $result[0]);
                    $j            = $result[1];
                } else {
                    $recurseLines[] = $replacementLines[$j];
                }
            }
            $replacementLines = $recurseLines;
        }

        return ['lines' => $replacementLines, 'i' => $i];

    }

}
