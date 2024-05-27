<?php

/**
 * manner: convert troff man pages to semantic HTML
 * Copyright (C) 2024  Jackson Pauls
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
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
        return str_replace('\\$@', '"' . implode('" "', $arguments) . '"', $string);
    }

}
