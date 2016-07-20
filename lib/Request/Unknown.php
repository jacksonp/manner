<?php


class Request_Unknown
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i, $arguments) {
        throw new exception('Unknown request ' . $lines[$i]);
    }

}
