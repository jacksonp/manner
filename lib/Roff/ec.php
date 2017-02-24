<?php
declare(strict_types = 1);

/**
 * Class Roff_ec
 *
 * Sets/resets the escape character, only ever used to reset after not being changed in body of man pages.
 */
class Roff_ec implements Roff_Template
{

    static function evaluate(array $request, array &$lines, ?array $macroArguments)
    {
        array_shift($lines);

        $char = count($request['arguments']) ? $request['arguments'][0] : '\\';

        Man::instance()->escape_char = $char;

    }

}
