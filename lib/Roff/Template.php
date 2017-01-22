<?php


interface Roff_Template
{

    static function evaluate(array $request, array &$lines, ?array $macroArguments);

}
