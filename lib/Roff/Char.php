<?php


class Roff_Char
{

    static function evaluate(DOMElement $parentNode, array $request, array &$lines, int $i)
    {

        if (!preg_match('~^(.+)\s+(.+)$~u', $request['arg_string'], $matches)) {
            throw new Exception('Unexpected arguments received in Roff_Char:' . $request['arg_string']);
        }

        Man::instance()->setEntity($matches[1], $matches[2]);

        return ['i' => $i];

    }

}
