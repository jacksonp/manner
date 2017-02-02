<?php


class Roff_Char implements Roff_Template
{

    static function evaluate(array $request, array &$lines, ?array $macroArguments)
    {

        array_shift($lines);
        if (!preg_match('~^(.+)\s+(.+)$~u', $request['arg_string'], $matches)) {
            throw new Exception('Unexpected arguments received in Roff_Char:' . $request['arg_string']);
        }
        Man::instance()->setEntity($matches[1], $matches[2]);
        return [];

    }

}
