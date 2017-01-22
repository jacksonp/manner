<?php


class Roff_di implements Roff_Template
{

    static function evaluate(array $request, array &$lines, ?array $macroArguments)
    {
        array_shift($lines);
        while (count($lines)) {
            $request = Request::getLine($lines, 0);
            array_shift($lines);
            if ($request['request'] === 'di') {
                return [];
            }
        }
        throw new Exception('.di with no end .di');

    }

}
