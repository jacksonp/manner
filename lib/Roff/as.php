<?php
declare(strict_types=1);

class Roff_as implements Roff_Template
{

    static function evaluate(array $request, array &$lines, ?array $macroArguments)
    {

        array_shift($lines);

        if (count($request['arguments']) === 2) {
            $man        = Man::instance();
            $stringName = $request['arguments'][0];
            $appendVal  = $man->applyAllReplacements($request['arguments'][1]);
            $appendVal  = Roff_Macro::applyReplacements($appendVal, $macroArguments);
            $string     = $man->getString($stringName);
            $man->addString($stringName, $string . $appendVal);
        }

        return [];

    }

}
