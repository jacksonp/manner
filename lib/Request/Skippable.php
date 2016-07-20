<?php


class Request_Skippable
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i, $arguments)
    {
        return $i;
    }

}
