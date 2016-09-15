<?php

class Roff_nop
{

    static function evaluate(DOMElement $parentNode, array $request, array &$lines, int $i)
    {

        $lines[$i] = $request['arg_string'];
        --$i;

        return ['i' => $i]; // Just ignore for now!

    }

}
