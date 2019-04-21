<?php
declare(strict_types=1);

class Roff_am implements Roff_Template
{

    /**
     * @param array $request
     * @param array $lines
     * @param array|null $macroArguments
     * @throws Exception
     */
    static function evaluate(array $request, array &$lines, ?array $macroArguments): void
    {
        array_shift($lines);

        if (count($request['arguments']) && ($request['arguments'][0] === 'URL' || $request['arguments'][0] === 'MTO')) {
            // do nothing
        } else {
            throw new Exception('Unexpected .am arguments: ' . print_r($request['arguments'], true));
        }

    }

}
