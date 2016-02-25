<?php


class Text
{

    static function massage ($str) {

        return str_replace('\\-', '-', $str);

    }

}
