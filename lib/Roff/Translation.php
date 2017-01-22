<?php


class Roff_Translation implements Roff_Template
{

    static function evaluate(array $request, array &$lines, ?array $macroArguments)
    {
        array_shift($lines);
        $man = Man::instance();
//
//        $roffStrings = $man->getStrings();
//        $translate   = Roff_String::substitute($matches[1], $roffStrings);
        $translate = $request['arg_string'];
        $translate = TextContent::interpretString($translate, false);

        $chrArray = preg_split('~~u', $translate, -1, PREG_SPLIT_NO_EMPTY);

        for ($j = 0; $j < count($chrArray); $j += 2) {
            //  "If there is an odd number of arguments, the last one is translated to an unstretchable space (‘\ ’)."
            $man->setCharTranslation($chrArray[$j], $j === count($chrArray) - 1 ? ' ' : $chrArray[$j + 1]);
        }

        return [];

    }

}
