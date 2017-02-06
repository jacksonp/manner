<?php
declare(strict_types = 1);

class HybridNode extends DOMElement
{

    const BLOCK_TAGS = ['p', 'div', 'pre', 'table', 'blockquote', 'dl'];

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

    function hasContent()
    {
        return $this->childNodes->length > 1 || ($this->firstChild && $this->firstChild->nodeValue !== '');
    }

    function ancestor (string $tagName) {
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

    function getLastBlock()
    {
        if (!$this->hasChildNodes()) {
            return null;
        }
        $lastChild = $this->lastChild;
        do {
            if ($lastChild instanceof DOMElement && in_array($lastChild->tagName, self::BLOCK_TAGS)) {
                return $lastChild;
            }
        } while ($lastChild = $lastChild->previousSibling);

        return null;
    }

}
