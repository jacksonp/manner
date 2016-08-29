<?php


class Roff_Rename
{

    static function checkEvaluate(array $lines, int $i)
    {

        if (!preg_match('~^\.\s*rn\s+(.*)$~u', $lines[$i], $matches)) {
            return false;
        }

        // Just ignore these for now:
        if (in_array($matches[1], ["'' }`", "}` ''"])) {
            return ['i' => $i];
        }

        throw new Exception('Unhandled .rn line: ' . $lines[$i]);

    }

}
