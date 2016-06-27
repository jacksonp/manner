<?php


class Roff_Condition
{

    static function checkEvaluate(array $lines, int $i)
    {

        if (!preg_match('~^\.if n \\\\{(.*)$~u', $lines[$i], $matches)) {
            return false;
        }

        $numLines         = count($lines);
        $foundEnd         = false;
        $replacementLines = [];

        if ($matches[1] !== '') {
            $replacementLines[] = Macro::massageLine($matches[1]);
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
            throw new Exception('.if n \\{ - not followed by expected pattern on line ' . $i . '.');
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

}
