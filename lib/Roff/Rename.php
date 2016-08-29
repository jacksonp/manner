<?php


class Roff_Rename
{

    static function checkEvaluate(array $lines, int $i)
    {

        if (!preg_match('~^\.\s*rn\s+(.*)$~u', $lines[$i], $matches)) {
            return false;
        }

        return ['i' => $i]; // Ignore for now!

    }

}
