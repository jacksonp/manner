<?php


class Block_RE implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        if ($parentNode->isOrInTag('div')) {
            return $parentNode->ancestor('div')->parentNode;
        } else {
            return null;
        }

    }


}
