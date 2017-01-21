<?php


interface Roff_Template
{

    static function evaluate(array $request, array &$lines, int $i, ?array $macroArguments);

}
