<?php
declare(strict_types=1);

class Roff_Rename implements Roff_Template
{

    static function evaluate(array $request, array &$lines, ?array $macroArguments): void
    {
        array_shift($lines); // Just ignore for now!
    }

}
