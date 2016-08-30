<?php


class Roff_Char
{

    static function checkEvaluate(array $lines, int $i)
    {

        if (!preg_match('~^\.char\s+(.+)\s+(.+)$~u', $lines[$i], $matches)) {
            return false;
        }

        $man = Man::instance();
        $man->setEntity($matches[1], $matches[2]);

        return ['i' => $i];

    }

}
