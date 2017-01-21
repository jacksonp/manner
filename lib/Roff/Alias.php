<?php


class Roff_Alias implements Roff_Template
{

    static function evaluate(array $request, array &$lines, int $i, ?array $macroArguments)
    {

        Man::instance()->addAlias($request['arguments'][0], $request['arguments'][1]);

        return ['i' => $i];

    }

}
