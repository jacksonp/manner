<?php


class HybridNode extends DOMElement
{

    const BLOCK_TAGS = ['p', 'div', 'pre', 'table', 'blockquote'];

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
        return $this->childNodes->length > 1 || $this->firstChild->nodeValue !== '';
    }

    function appendBlockIfHasContent(HybridNode $block)
    {
        if ($block->hasChildNodes()) {
            if ($block->childNodes->length > 1 || trim($block->firstChild->textContent) !== '') {
                $this->appendChild($block);
            }
        }
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
