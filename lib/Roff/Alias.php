<?php

declare(strict_types=1);

namespace Manner\Roff;

use Manner\Man;

class Alias implements Template
{

    public static function evaluate(array $request, array &$lines, ?array $macroArguments): void
    {
        array_shift($lines);
        Man::instance()->addAlias($request['arguments'][0], $request['arguments'][1]);
    }

    public static function check(string $requestName): string
    {
        $aliases = Man::instance()->getAliases();
        if (array_key_exists($requestName, $aliases)) {
            $requestName = $aliases[$requestName];
        }

        return $requestName;
    }

}
