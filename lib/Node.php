<?php

declare(strict_types=1);

namespace Manner;

use DOMElement;
use DOMException;
use DOMNode;

use Manner\Roff\Glyph;

class Node
{

    public static function hasContent(DOMElement $el): bool
    {
        return $el->childNodes->length > 1 || ($el->firstChild && $el->firstChild->nodeValue !== '');
    }

    public static function ancestor(DOMElement $el, string $tagName): ?DOMElement
    {
        while ($el->tagName !== $tagName) {
            if (!$el->parentNode) {
                return null;
            }
            $el = $el->parentNode;
            if ($el->nodeType === XML_DOCUMENT_NODE) {
                return null;
            }
        }

        return $el;
    }

    public static function isOrInTag(DOMElement $el, $tagNames): bool
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

    public static function addClass(DOMElement $node, $classes): void
    {
        $classes = (array)$classes;
        foreach ($classes as $class) {
            if (!self::hasClass($node, $class)) {
                $node->setAttribute('class', trim($node->getAttribute('class') . ' ' . $class));
            }
        }
    }

    public static function hasClass(DOMElement $node, string $className): bool
    {
        $existingClassString = $node->getAttribute('class');

        return in_array($className, explode(' ', $existingClassString));
    }

    public static function removeClass(DOMElement $node, string $className): void
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
          in_array(trim($node->textContent), ['', Text::ZERO_WIDTH_SPACE_UTF8]);
    }

    /**
     * @throws DOMException
     */
    public static function changeTag(DOMElement $node, string $name, bool $preserveAttributes = true): DOMElement
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

    public static function removeAttributeAll($nodes, $attributes): void
    {
        /** @var DOMElement $node */
        $attributes = (array)$attributes;
        foreach ($nodes as $node) {
            foreach ($attributes as $attribute) {
                $node->removeAttribute($attribute);
            }
        }
    }

    public static function removeIds(DOMNode $domNode): void
    {
        if (!DOM::isElementNode($domNode)) {
            return;
        }
        /* @var DomElement $domNode */
        $domNode->removeAttribute("id");
        foreach ($domNode->childNodes as $node) {
            self::removeIds($node);
        }
    }

    public static function replaceGlyphs(DOMNode $domNode): void
    {
        if (Dom::isTextNode($domNode)) {
            $domNode->textContent = Glyph::substitute(htmlspecialchars_decode($domNode->textContent));
        }
        if (DOM::isElementNode($domNode)) {
            foreach ($domNode->childNodes as $node) {
                self::replaceGlyphs($node);
            }
        }
    }

}
