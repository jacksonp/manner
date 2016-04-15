<?php


class HybridNode extends DOMElement
{

    public $manLines = [];

    function __construct($name, $value = null)
    {
        parent::__construct($name, $value);
    }

    function addManLine($line)
    {
        $this->manLines[] = $line;
    }

    function isOrInTag($tagNames)
    {
        $tagNames = (array)$tagNames;
        $parentNode = $this;

        while ($parentNode instanceof DOMElement) {
            if (in_array($parentNode->tagName, $tagNames)) {
                return true;
            }
            $parentNode = $parentNode->parentNode;
        }

        return false;
    }

    function hasContent () {
        return $this->childNodes->length > 1 || $this->firstChild->nodeValue !== '';
    }


}
