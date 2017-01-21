<?php

class Roff_nop implements Roff_Template
{

    static function evaluate(array $request, array &$lines, int $i, ?array $macroArguments)
    {

        $lines[$i] = $request['arg_string'];
        --$i;

        return ['i' => $i]; // Just ignore for now!

    }

}
