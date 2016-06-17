<?php


class ArrayHelper
{

    static function ltrim(array &$arrayToTrim, array $unwantedValues)
    {

        foreach ($arrayToTrim as $line) {
            if (in_array($line, $unwantedValues)) {
                array_shift($arrayToTrim);
            } else {
                break;
            }
        }

    }


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
