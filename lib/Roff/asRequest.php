<?php
declare(strict_types=1);

namespace Manner\Roff;

use Manner\Man;

// Can't just be called "as"
class asRequest implements Template
{

    public static function evaluate(array $request, array &$lines, ?array $macroArguments): void
    {
        array_shift($lines);

        if (count($request['arguments']) === 2) {
            $man        = Man::instance();
            $stringName = $request['arguments'][0];
            $appendVal  = $man->applyAllReplacements($request['arguments'][1]);
            $appendVal  = Macro::applyReplacements($appendVal, $macroArguments);
            $string     = $man->getString($stringName);
            $man->addString($stringName, $string . $appendVal);
        }
    }

}
