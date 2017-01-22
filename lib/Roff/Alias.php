<?php


class Roff_Alias implements Roff_Template
{

    static function evaluate(array $request, array &$lines, ?array $macroArguments)
    {

        array_shift($lines);
        Man::instance()->addAlias($request['arguments'][0], $request['arguments'][1]);
        return [];

    }

}
