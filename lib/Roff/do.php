<?php
declare(strict_types=1);

/**
 * .do: "Interpret .name with compatibility mode disabled."  (e.g. .do if ... )
 * NB: we many pick up new .do calls e.g. in conditional statements.
 */
class Roff_do implements Roff_Template
{

    static function evaluate(array $request, array &$lines, ?array $macroArguments)
    {

        $lines[0] = '.' . $request['raw_arg_string'];
        return [];

    }

}
