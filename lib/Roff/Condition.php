<?php


class Roff_Condition
{

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

        // Previously explicitly skipped:
        $alwaysFalse = [
          't', // "Formatter is troff."
          'v', // vroff
          '\\nF>0',
          '\\nF',
          '(\\n(rF:(\\n(.g==0))',
        ];

        if (in_array($condition, $alwaysFalse)) {
            return false;
        }

        throw new Exception('Unhandled condition: ' . $condition);

    }

    private static function ifBlock(array $lines, int $i, string $condition, string $firstLine)
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

        if (!self::test($condition)) {
            return [[], $i];
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

        return [$replacementLines, $i];

    }

    static function checkEvaluate(array $lines, int $i)
    {

        if (preg_match('~^\.if ([^\s]+) \\\\{(.*)$~u', $lines[$i], $matches)) {
            return self::ifBlock($lines, $i, $matches[1], $matches[2]);
        }

        return false;

    }

}
