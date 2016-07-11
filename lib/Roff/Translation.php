<?php


class Roff_Translation
{

    static function checkEvaluate(array $lines, int $i)
    {

        if (!preg_match('~^\.tr (.+)$~u', $lines[$i], $matches)) {
            return false;
        }

        $man = Man::instance();


        $translate = TextContent::interpretString($matches[1], false, false);
        $chrArray  = preg_split('~~u', $translate, -1, PREG_SPLIT_NO_EMPTY);
        var_dump($chrArray);
        for ($j = 0; $j < count($chrArray); $j += 2) {
            //  "If there is an odd number of arguments, the last one is translated to an unstretchable space (‘\ ’)."
            $man->setCharTranslation($chrArray[$j], $j === count($chrArray) - 1 ? ' ' : $chrArray[$j + 1]);
        }

        return ['i' => $i];

    }

}
