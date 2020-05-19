<?php

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
