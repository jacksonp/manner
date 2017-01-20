<?php


interface Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        int $i,
        ?array $arguments = null,
        ?string $request = null,
        $needOneLineOnly = false
    );

}
