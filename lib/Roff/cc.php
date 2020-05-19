<?php

declare(strict_types=1);

namespace Manner\Roff;

use Manner\Man;

class cc implements Template
{

    public static function evaluate(array $request, array &$lines, ?array $macroArguments): void
    {
        array_shift($lines);

        $char = count($request['arguments']) ? $request['arguments'][0] : '.';

        Man::instance()->control_char = $char;
    }

}
