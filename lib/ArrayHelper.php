<?php


class ArrayHelper
{

    static function rtrim(array &$arrayToTrim, array $unwantedValues)
    {

        for ($i = count($arrayToTrim) - 1; $i >= 0; --$i) {
            if (in_array($arrayToTrim[$i], $unwantedValues)) {
                unset($arrayToTrim[$i]);
            } else {
                break;
            }
        }

    }

}
