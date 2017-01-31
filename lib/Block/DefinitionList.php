<?php

class Block_DefinitionList
{

    static function getParentDL(DOMElement $parentNode): ?DOMElement
    {
        do {
            if ($parentNode->tagName === 'dl') {
                return $parentNode;
            }
            if (in_array($parentNode->tagName, ['body', 'div'])) {
                return null;
            }
        } while ($parentNode = $parentNode->parentNode);
        return null;
    }

}
