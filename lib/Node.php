<?php
declare(strict_types=1);

class Node
{

    static function hasContent(DOMElement $el): bool
    {
        return $el->childNodes->length > 1 || ($el->firstChild && $el->firstChild->nodeValue !== '');
    }

    static function isOrInTag(DOMElement $el, $tagNames): bool
    {
        $tagNames = (array)$tagNames;

        while ($el instanceof DOMElement) {
            if (in_array($el->tagName, $tagNames)) {
                return true;
            }
            $el = $el->parentNode;
        }

        return false;
    }

    static function addClass(DOMElement $node, string $className): void
    {
        if (!self::hasClass($node, $className)) {
            $node->setAttribute('class', trim($node->getAttribute('class') . ' ' . $className));
        }
    }

    static function hasClass(DOMElement $node, string $className): bool
    {
        $existingClassString = $node->getAttribute('class');
        return in_array($className, explode(' ', $existingClassString));
    }

    static function removeClass(DOMElement $node, string $className): void
    {
        $existingClassString = $node->getAttribute('class');
        $existingClasses     = explode(' ', $existingClassString);
        if (($key = array_search($className, $existingClasses)) !== false) {
            unset($existingClasses[$key]);
        }
        if (count($existingClasses)) {
            $node->setAttribute('class', implode(' ', $existingClasses));
        } else {
            $node->removeAttribute('class');
        }
    }

    public static function remove(DOMNode $from, $preserveChildren = true): void
    {
        if ($preserveChildren) {
            $sibling = $from->firstChild;
            if ($sibling) { // ->firstChild is null is there isn't one
                do {
                    $next = $sibling->nextSibling;
                    $from->parentNode->insertBefore($sibling, $from);
                } while ($sibling = $next);
            }
        }
        $from->parentNode->removeChild($from);
    }

    public static function isTextAndEmpty(DOMNode $node): bool
    {
        return
            $node->nodeType === XML_TEXT_NODE &&
            in_array(trim($node->textContent), ['', Char::ZERO_WIDTH_SPACE_UTF8]);
    }

    static function changeTag(DOMElement $node, string $name, bool $preserveAttributes = true): DOMElement
    {

        $renamed = $node->ownerDocument->createElement($name);

        if ($preserveAttributes) {
            foreach ($node->attributes as $attribute) {
                $renamed->setAttribute($attribute->nodeName, $attribute->nodeValue);
            }
        }

        while ($node->firstChild) {
            $renamed->appendChild($node->firstChild);
        }

        $node->parentNode->replaceChild($renamed, $node);

        return $renamed;

    }

    public static function removeAttributeAll($nodes, $attributes)
    {

        /** @var DOMElement $node */
        $attributes = (array)$attributes;
        foreach ($nodes as $node) {
            foreach ($attributes as $attribute) {
                $node->removeAttribute($attribute);
            }
        }

    }

}
