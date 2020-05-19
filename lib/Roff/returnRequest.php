<?php
declare(strict_types=1);

namespace Manner\Roff;

class returnRequest implements Template
{

    public static function evaluate(array $request, array &$lines, ?array $macroArguments): void
    {
        array_shift($lines);

        while (count($lines) && !is_null($lines[0])) {
            array_shift($lines);
        }

        // shift the null
        array_shift($lines);
    }

}
