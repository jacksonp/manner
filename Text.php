<?php


class Text
{

    static function massage($str)
    {
        return str_replace('\\-', '-', $str);
    }

    static function isText($line)
    {
        return !empty($line) && $line[0] !== '.';
    }

    static function mergeTextLines($lines)
    {

        $numLines = count($lines);

        $newLines = [];

        for ($i = 0; $i < $numLines; ++$i) {
            $line = $lines[$i];

            $newLines[$i] = $line;

            // If this line is text, merge in any following lines of text.
            if (self::isText($line)) {
                for ($j = $i; $j < $numLines; ++$j) {
                    if (!self::isText($lines[$j + 1])) {
                        $i = $j;
                        break;
                    }
                    $newLines[$i] .= ' ' . $lines[$j + 1];
                }
            }

        }

        return $newLines;

    }

    static function toCommonMark ($lines)
    {

        $numLines = count($lines);

        $newLines = [];

        for ($i = 0; $i < $numLines; ++$i) {
            $line = $lines[$i];

            if (preg_match('~^\.I (.*)$~', $line, $matches)) {
                $newLines[$i] = '*' . $matches[1] . '*';
                continue;
            } elseif (preg_match('~^\.B (.*)$~', $line, $matches)) {
                $newLines[$i] = '**' . $matches[1] . '**';
                continue;
            }

            $newLines[$i] = $line;


        }

        return $newLines;

    }

}
