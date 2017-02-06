<?php
declare(strict_types = 1);

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

        if ($parentNode->isOrInTag('pre')) {
            return null;
        }

        $parentNode = Blocks::getBlockContainerParent($parentNode, true);

        $pre = $parentNode->ownerDocument->createElement('pre');

        $pre = $parentNode->appendChild($pre);

        return $pre;

    }

}
