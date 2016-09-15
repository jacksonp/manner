<?php


class Roff_Alias
{

    static function evaluate(DOMElement $parentNode, array $request, array &$lines, int $i)
    {

        Man::instance()->addAlias($request['arguments'][0], $request['arguments'][1]);

        return ['i' => $i];

    }

}
