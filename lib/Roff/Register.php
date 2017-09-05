<?php
declare(strict_types=1);

class Roff_Register implements Roff_Template
{

    static function evaluate(array $request, array &$lines, ?array $macroArguments): void
    {

        $man = Man::instance();
        array_shift($lines);

        // Remove register
        if ($request['request'] === 'rr') {
            if (count($request['arguments']) === 1) {
                $man->unsetRegister($request['arguments'][0]);
            }
            return;
        }

        // .nr register ±N [M]
        // Define or modify register using ±N with auto-increment M

        if (count($request['arguments']) < 2) {
            return;
        }

        // Step might be in $request['arguments'][2] - but we just assume step is 1 for now.

        // Normalize here: a unit value may be concatenated when the register is used.
        $registerValue = Roff_Unit::normalize($request['arguments'][1], 'u', 'u');
        $man->setRegister($request['arguments'][0], $registerValue);

    }

    static function substitute(string $string, array &$replacements): string
    {
        return Replace::pregCallback(
            '~(?J)(?<!\\\\)(?<bspairs>(?:\\\\\\\\)*)\\\\n(?:\[(?<reg>[^\]]+)\]|\((?<reg>..)|(?<reg>.))~u',
            function ($matches) use (&$replacements) {
                if (isset($replacements[$matches['reg']])) {
                    return $matches['bspairs'] . $replacements[$matches['reg']];
                } else {
                    // Match groff's behaviour: unset registers are 0
                    return $matches['bspairs'] . '0';
                }
            },
            $string);

    }

}
