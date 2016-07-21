<?php


class Util
{

    static function rtrim(string $string): string
    {
        return rtrim($string, " \t\n\r\0\x0B" . html_entity_decode('&nbsp;'));
    }
}
