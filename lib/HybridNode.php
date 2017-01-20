<?php


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

    // TODO: consider moving this into appendBlockIfHasContent()
    function trimTrailingBrs()
    {
        while (
            $lastChild = $this->lastChild and
            ($lastChild->nodeType === XML_ELEMENT_NODE && $lastChild->tagName === 'br')
        ) {
            $this->removeChild($lastChild);
        }
    }

    function appendBlockIfHasContent(HybridNode $block)
    {
        if ($block->hasChildNodes()) {
            if ($block->childNodes->length > 1 || trim($block->firstChild->textContent) !== '') {
                if (in_array($block->tagName, self::BLOCK_TAGS)) {
                    // If we are about to append a block, we can prune any trailing <br>s from the element before it.
                    $lastChild = $this->lastChild;
                    if ($lastChild) {
                        $lastChildOfLastChild = $lastChild->lastChild;
                        while (
                            $lastChildOfLastChild &&
                            $lastChildOfLastChild->nodeType === XML_ELEMENT_NODE &&
                            $lastChildOfLastChild->tagName === 'br'
                        ) {
                            $lastChild->removeChild($lastChild->lastChild);
                            $lastChildOfLastChild = $lastChild->lastChild;
                        }
                    }
                }

                return $this->appendChild($block);
            }
        }

        return $block;
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
