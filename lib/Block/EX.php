<?php


class Block_EX implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        $parentNode = Blocks::getBlockContainerParent($parentNode, true);

        $pre = $parentNode->ownerDocument->createElement('pre');

        $pre = $parentNode->appendChild($pre);

        return $pre;

    }

}
