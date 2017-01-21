<?php


class Roff_Rename implements Roff_Template
{

    static function evaluate(array $request, array &$lines, int $i, ?array $macroArguments)
    {

        return ['i' => $i]; // Just ignore for now!

    }

}
