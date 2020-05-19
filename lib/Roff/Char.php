<?php

declare(strict_types=1);

namespace Manner\Roff;

use Manner\Man;

class Char implements Template
{

    public static function evaluate(array $request, array &$lines, ?array $macroArguments): void
    {
        array_shift($lines);
        if (count($request['arguments']) === 2) {
            Man::instance()->setEntity($request['arguments'][0], $request['arguments'][1]);
        }
    }

}
