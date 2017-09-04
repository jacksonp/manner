<?php
declare(strict_types=1);

class Roff_Char implements Roff_Template
{

    static function evaluate(array $request, array &$lines, ?array $macroArguments)
    {

        array_shift($lines);
        if (count($request['arguments']) === 2) {
            Man::instance()->setEntity($request['arguments'][0], $request['arguments'][1]);
        }
        return [];

    }

}
