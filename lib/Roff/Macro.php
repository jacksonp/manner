<?php
declare(strict_types = 1);

class Roff_Macro
{

    static function applyReplacements(string $string, array &$arguments, bool $fullLine = false): string
    {

        if ($fullLine && Request::peepAt($string)['name'] === 'shift') {
            array_shift($arguments);
            return '.';
        }

        // \$x - Macro or string argument with one-digit number x in the range 1 to 9.
        for ($n = 1; $n < 10; ++$n) {
            $string = str_replace('\\$' . $n, @$arguments[$n - 1] ?: '', $string);
        }

        // \$* : In a macro or string, the concatenation of all the arguments separated by spaces.
        $string = str_replace('\\$*', implode(' ', $arguments), $string);

        // \$@ : In a macro or string, the concatenation of all the arguments with each surrounded by double quotes, and separated by spaces.
        $string = str_replace('\\$@', '"' . implode('" "', $arguments) . '"', $string);

        return $string;
    }

}
