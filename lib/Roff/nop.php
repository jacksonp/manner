<?php
declare(strict_types=1);

class Roff_nop implements Roff_Template
{

    static function evaluate(array $request, array &$lines, ?array $macroArguments)
    {

        $lines[0] = $request['raw_arg_string'];
        return [];

    }

}
