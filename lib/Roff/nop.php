<?php

declare(strict_types=1);

namespace Manner\Roff;

class nop implements Template
{

    public static function evaluate(array $request, array &$lines, ?array $macroArguments): void
    {
        $lines[0] = $request['raw_arg_string'];
    }

}
