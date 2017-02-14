<?php
declare(strict_types = 1);

class Roff_as implements Roff_Template
{

    static function evaluate(array $request, array &$lines, ?array $macroArguments)
    {

        array_shift($lines);

        if (!preg_match('~^(.+?)\s+(.+)$~u', $request['arg_string'], $matches)) {
            // Just skip if no match
            return [];
        }

        $man = Man::instance();

        $stringName = $matches[1];
        $appendVal  = $matches[2];
        $appendVal  = $man->applyAllReplacements($appendVal);
        $appendVal  = Roff_Macro::applyReplacements($appendVal, $macroArguments);

        $string = $man->getString($stringName);

        $man->addString($stringName, $string . $appendVal);

        return [];

    }

}
