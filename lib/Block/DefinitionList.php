<?php
declare(strict_types=1);

class Block_DefinitionList
{

    static function getParentDL(DOMElement $parentNode): ?DOMElement
    {
        do {
            $tag = $parentNode->tagName;
            if ($tag === 'dl') {
                return $parentNode;
            }
            if (in_array($tag, ['body']) || ($tag === 'div' && !$parentNode->hasAttribute('remap'))) {
                return null;
            }
        } while ($parentNode = $parentNode->parentNode);
        return null;
    }

}
