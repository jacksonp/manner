<?php


class Roff_di implements Roff_Template
{

    static function evaluate(array $request, array &$lines, ?array $macroArguments)
    {
        array_shift($lines);
        while ($request = Request::getLine($lines)) {
            array_shift($lines);
            if ($request['request'] === 'di') {
                return [];
            }
        }
        throw new Exception('.di with no end .di');

    }

}
