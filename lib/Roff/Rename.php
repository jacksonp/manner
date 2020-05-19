<?php

declare(strict_types=1);

namespace Manner\Roff;

class Rename implements Template
{

    public static function evaluate(array $request, array &$lines, ?array $macroArguments): void
    {
        array_shift($lines); // Just ignore for now!
    }

}
