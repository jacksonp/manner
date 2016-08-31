<?php


class Roff_Macro
{

    static function applyReplacements(string $string, &$arguments): string
    {

        if (is_null($arguments)) {
            return $string;
        }

        if (Request::is($string, 'shift')) {
            array_shift($arguments);

            return '.';
        }

        // \$x - Macro or string argument with one-digit number x in the range 1 to 9.
        for ($n = 1; $n < 10; ++$n) {
            $string = str_replace('\\$' . $n, @$arguments[$n - 1] ?: '', $string);
        }

        // \$* : In a macro or string, the concatenation of all the arguments separated by spaces.
        $string = str_replace('\\$*', implode(' ', $arguments), $string);

        // Other \$ things are also arguments...
        if (mb_strpos($string, '\\$') !== false) {
            throw new Exception($string . ' - can not handle macro that specifies arguments.');
        }

        return $string;
    }

}
