<?php
declare(strict_types=1);

namespace Manner\Roff;

/**
 * .do: "Interpret .name with compatibility mode disabled."  (e.g. .do if ... )
 * NB: we many pick up new .do calls e.g. in conditional statements.
 */
class doRequest implements Template
{

    public static function evaluate(array $request, array &$lines, ?array $macroArguments): void
    {
        $lines[0] = '.' . $request['raw_arg_string'];
    }

}
