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

        for (; $i < $numLines; ++$i) {
            $line = $lines[$i];
            if (preg_match('~^(.*)\\\\}$~u', $line, $matches)) {
                $foundEnd = true;
                if (!empty($matches[1]) && $matches[1] !== '\'br') {
                    $replacementLines[] = Macro::massageLine($matches[1]);
                }
                break;
            } elseif (!empty($line)) {
                $replacementLines[] = Macro::massageLine($line);
            }
        }

        if (!$foundEnd) {
            throw new Exception('.if n \\{ - not followed by expected pattern on line ' . $i . '.');
        }

        return [$replacementLines, $i];

    }

}
