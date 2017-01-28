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
            $p = $parentNode->ownerDocument->createElement('p');
            if ($parentNode->tagName === 'p') {
                $p = $parentNode->parentNode->appendChild($p);
            } else {
                $p = $parentNode->appendChild($p);
            }
            return $p;
        }

    }

}
