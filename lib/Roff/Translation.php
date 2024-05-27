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
use Manner\TextContent;

class Translation implements Template
{

    public static function evaluate(array $request, array &$lines, ?array $macroArguments): void
    {
        array_shift($lines);

        if (count($request['arguments']) === 1) {
            $man = Man::instance();

            $translate = $request['arguments'][0];
            $translate = TextContent::interpretString($translate, false);

            $chrArray = preg_split('~~u', $translate, -1, PREG_SPLIT_NO_EMPTY);

            for ($j = 0; $j < count($chrArray); $j += 2) {
                //  "If there is an odd number of arguments, the last one is translated to an unstretchable space (‘\ ’)."
                $man->setCharTranslation($chrArray[$j], $j === count($chrArray) - 1 ? ' ' : $chrArray[$j + 1]);
            }
        }
    }

}
