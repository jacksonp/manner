<?php


class Roff_Alias
{

    static function checkEvaluate(array $lines, int $i)
    {

        if (!preg_match('~^\.\s*als (?<new>\w+) (?<old>\w+)$~u', $lines[$i], $matches)) {
            return false;
        }

        $man = Man::instance();
        $man->addAlias($matches['new'], $matches['old']);

        return ['i' => $i];

    }

}
