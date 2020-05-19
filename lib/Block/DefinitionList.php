<?php

declare(strict_types=1);

namespace Manner\Block;

use DOMElement;

class DefinitionList
{

    public static function getParentDL(DOMElement $parentNode): ?DOMElement
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
