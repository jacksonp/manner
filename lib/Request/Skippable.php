<?php
declare(strict_types = 1);

class Request_Skippable implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement {
        array_shift($lines);
        return null;
    }

}
