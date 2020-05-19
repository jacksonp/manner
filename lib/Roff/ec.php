<?php

declare(strict_types=1);

namespace Manner\Roff;

use Manner\Man;

/**
 * Sets/resets the escape character, only ever used to reset after not being changed in body of man pages.
 */
class ec implements Template
{

    public static function evaluate(array $request, array &$lines, ?array $macroArguments): void
    {
        array_shift($lines);
        Man::instance()->escape_char = count($request['arguments']) ? $request['arguments'][0] : '\\';
    }

}
