<?php

declare(strict_types=1);

class Massage_Body
{

    static function trimNodesBeforeH1(DOMXPath $xpath)
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