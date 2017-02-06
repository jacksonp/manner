<?php
declare(strict_types = 1);

class Roff_di implements Roff_Template
{

    static function evaluate(array $request, array &$lines, ?array $macroArguments)
    {
        array_shift($lines);

        // We don't want to handle the lines at this stage as a fresh call to .di call a new Roff_di, so don't iterate
        // with Request::getLine()
        while ($line = array_shift($lines)) {
            if (Request::peepAt($line)['name'] === 'di') {
                return [];
            }
        }
        throw new Exception('.di with no end .di');

    }

}
