<?php

declare(strict_types=1);

namespace Manner\Roff;

use Exception;
use Manner\Request;

class di implements Template
{

    /**
     * @param array $request
     * @param array $lines
     * @param array|null $macroArguments
     * @throws Exception
     */
    public static function evaluate(array $request, array &$lines, ?array $macroArguments): void
    {
        array_shift($lines);

        // We don't want to handle the lines at this stage as a fresh call to .di call a new \Request\di, so don't iterate
        // with Request::getLine()
        while ($line = array_shift($lines)) {
            if (Request::peepAt($line)['name'] === 'di') {
                return;
            }
        }
        throw new Exception('.di with no end .di');
    }

}
