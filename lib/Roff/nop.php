<?php

class Roff_nop implements Roff_Template
{

    static function evaluate(array $request, array &$lines, ?array $macroArguments)
    {

        $lines[0] = $request['arg_string'];
        return [];

    }

}
