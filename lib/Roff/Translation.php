<?php


class Roff_Translation
{

    static function evaluate(DOMElement $parentNode, array $request, array &$lines, int $i)
    {

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

        return ['i' => $i];

    }

}
