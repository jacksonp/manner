<?php
declare(strict_types = 1);

class HybridNode extends DOMElement
{

    function __construct($name, $value = null)
    {
        parent::__construct($name, $value);
    }

    function isOrInTag($tagNames)
    {
        $tagNames   = (array)$tagNames;
        $parentNode = $this;

        while ($parentNode instanceof DOMElement) {
            if (in_array($parentNode->tagName, $tagNames)) {
                return true;
            }
            $parentNode = $parentNode->parentNode;
        }

        return false;
    }

    function ancestor(string $tagName): ?DOMElement
    {
        $node = $this;
        while ($node->tagName !== $tagName) {
            if (!$node->parentNode) {
                return null;
            }
            $node = $node->parentNode;
            if ($node->nodeType === XML_DOCUMENT_NODE) {
                return null;
            }
        }
        return $node;
    }

}
