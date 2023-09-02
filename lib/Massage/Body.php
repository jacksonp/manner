<?php

declare(strict_types=1);

namespace Manner\Massage;

use DOMXPath;
use Exception;
use Manner\DOM;

class Body
{

    /**
     * @param DOMXPath $xpath
     * @throws Exception
     */
    public static function trimNodesBeforeH1(DOMXPath $xpath): void
    {
        $bodies = $xpath->query('//body');
        if ($bodies->length !== 1) {
            throw new Exception('Found more than one body');
        }
        $body = $bodies->item(0);
        while ($body->firstChild && !DOM::isTag($body->firstChild, 'h1')) {
            $body->removeChild($body->firstChild);
        }
    }

}