<?php
declare(strict_types = 1);

class Roff_Alias implements Roff_Template
{

    static function evaluate(array $request, array &$lines, ?array $macroArguments)
    {
        array_shift($lines);
        Man::instance()->addAlias($request['arguments'][0], $request['arguments'][1]);
        return [];
    }

    static function check(string $requestName): string
    {
        $aliases = Man::instance()->getAliases();
        if (array_key_exists($requestName, $aliases)) {
            $requestName = $aliases[$requestName];
        }
        return $requestName;
    }

}
