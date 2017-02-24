<?php
declare(strict_types = 1);

/**
 * Class Roff_eo
 *
 * Turn off escape character mechanism.
 */
class Roff_eo implements Roff_Template
{

    static function evaluate(array $request, array &$lines, ?array $macroArguments)
    {
        array_shift($lines);

        Man::instance()->escape_char = null;

    }

}
