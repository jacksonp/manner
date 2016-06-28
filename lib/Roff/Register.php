<?php


class Roff_Register
{

    static function checkEvaluate(array $lines, int $i)
    {

        if (!preg_match('~^\.nr (?<name>[-\w]+) (?<val>.+)$~u', $lines[$i], $matches)) {
            return false;
        }

        $man = Man::instance();
        $man->addRegister($matches['name'], $matches['val']);

        return ['i' => $i];

    }

}
