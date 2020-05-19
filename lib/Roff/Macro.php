<?php

declare(strict_types=1);

namespace Manner\Roff;

use Manner\Man;
use Manner\Request;

class Macro
{

    public static function applyReplacements(string $string, array &$arguments, bool $fullLine = false): string
    {
        if ($fullLine) {
            $request = Request::peepAt($string);
            if ($request['name'] === 'shift') {
                $argsToShift = 1;
                if (preg_match('~^\d+$~', $request['raw_arg_string'])) {
                    $argsToShift = (int)$request['raw_arg_string'];
                }
                for ($i = 0; $i < $argsToShift; ++$i) {
                    array_shift($arguments);
                }
                Man::instance()->setRegister('.$', (string)count($arguments));

                return '.';
            }
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
