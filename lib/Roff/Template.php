<?php
declare(strict_types=1);

interface Roff_Template
{

    static function evaluate(array $request, array &$lines, ?array $macroArguments): void;

}
