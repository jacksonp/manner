<?php


class Block_Vb implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        $parentNode = Blocks::getBlockContainerParent($parentNode);

        $pre = $parentNode->ownerDocument->createElement('pre');

        $pre = $parentNode->appendChild($pre);

        return $pre;

    }

}
