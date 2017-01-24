<?php


class Request_Skippable implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        ?array $request = null,
        $needOneLineOnly = false
    ): ?DOMElement {
        array_shift($lines);
        return null;
    }

}
