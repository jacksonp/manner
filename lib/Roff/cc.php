<?php
declare(strict_types=1);

class Roff_cc implements Roff_Template
{

    static function evaluate(array $request, array &$lines, ?array $macroArguments): void
    {
        array_shift($lines);

        $char = count($request['arguments']) ? $request['arguments'][0] : '.';

        Man::instance()->control_char = $char;

    }

}
