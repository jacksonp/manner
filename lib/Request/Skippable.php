<?php


class Request_Skippable implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        ?array $request = null,
        $needOneLineOnly = false
    ): bool {
        array_shift($lines);
        return true;
    }

}
