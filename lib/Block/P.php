<?php


class Block_P implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        if ($parentNode->tagName === 'p' && !$parentNode->hasContent()) {
            return null; // Use existing parent node for content that will follow.
        } else {
            $parentNode = Blocks::getBlockContainerParent($parentNode);
            if ($parentNode->tagName === 'dd') {
                $parentNode = $parentNode->parentNode->parentNode;
            }
            $p = $parentNode->ownerDocument->createElement('p');
            $p = $parentNode->appendChild($p);
            return $p;
        }

    }

}
