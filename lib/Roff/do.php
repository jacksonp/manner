<?php

/**
 * .do: "Interpret .name with compatibility mode disabled."  (e.g. .do if ... )
 * NB: we many pick up new .do calls e.g. in conditional statements.
 */
class Roff_do
{

    static function evaluate(array $request, array &$lines, int $i)
    {

        $lines[$i] = '.' . $request['arg_string'];
        --$i;

        return ['i' => $i]; // Just ignore for now!

    }

}