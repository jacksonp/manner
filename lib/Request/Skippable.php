<?php


class Request_Skippable implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        ?array $arguments = null,
        ?string $request = null,
        $needOneLineOnly = false
    ) {
        array_shift($lines);
        return 0;
    }

}
