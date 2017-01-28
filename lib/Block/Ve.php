<?php


class Block_Ve implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        Block_Preformatted::end();

        if ($parentNode->tagName === 'pre') {
            $parentNode = $parentNode->parentNode;
        }

        return $parentNode;

    }

}
