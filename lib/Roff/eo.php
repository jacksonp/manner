<?php

declare(strict_types=1);

namespace Manner\Roff;

use Manner\Man;

/**
 * Turn off escape character mechanism.
 */
class eo implements Template
{

    public static function evaluate(array $request, array &$lines, ?array $macroArguments): void
    {
        array_shift($lines);
        Man::instance()->escape_char = null;
    }

}
