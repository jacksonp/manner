<?php

declare(strict_types=1);

namespace Manner\Roff;

interface Template
{

    public static function evaluate(array $request, array &$lines, ?array $macroArguments): void;

}
