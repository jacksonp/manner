<?php

/**
 * manner: convert troff man pages to semantic HTML
 * Copyright (C) 2024  Jackson Pauls
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Manner;

use Dom\Element;
use Throwable;

use Manner\Roff\Glyph;

class Node
{

    public static function hasContent(Element $el): bool
    {
        return $el->childNodes->length > 1 || ($el->firstChild && $el->firstChild->nodeValue !== '');
    }

    public static function ancestor(Element $el, string $tagName): ?Element
    {
        while ($el instanceof Element && $el->localName !== $tagName) {
            if (!$el->parentNode) {
                return null;
            }
            $el = $el->parentNode;
            if ($el instanceof \Dom\Document) {
                return null;
            }
        }

        return $el instanceof Element ? $el : null;
    }

    public static function isOrInTag(Element $el, $tagNames): bool
    {
        $tagNames = (array)$tagNames;

        while ($el instanceof Element) {
            if (in_array($el->localName, $tagNames)) {
                return true;
            }
            $el = $el->parentNode;
        }

        return false;
    }

    public static function addClass(Element $node, $classes): void
    {
        $classes = (array)$classes;
        foreach ($classes as $class) {
            if (!self::hasClass($node, $class)) {
                $node->setAttribute('class', mb_trim(($node->getAttribute('class') ?? '') . ' ' . $class));
            }
        }
    }

    public static function hasClass(Element $node, string $className): bool
    {
        $existingClassString = $node->getAttribute('class') ?? '';

        return in_array($className, explode(' ', $existingClassString));
    }

    public static function removeClass(Element $node, string $className): void
    {
        $existingClassString = $node->getAttribute('class') ?? '';
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

    public static function remove(\Dom\Node $from, $preserveChildren = true): void
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

    public static function isTextAndEmpty(\Dom\Node $node): bool
    {
        return
          $node instanceof \Dom\Text &&
          in_array(mb_trim($node->textContent), ['', Text::ZERO_WIDTH_SPACE_UTF8]);
    }

    /**
     * @throws Throwable
     */
    public static function changeTag(Element $node, string $name, bool $preserveAttributes = true): Element
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
        /** @var Element $node */
        $attributes = (array)$attributes;
        foreach ($nodes as $node) {
            foreach ($attributes as $attribute) {
                $node->removeAttribute($attribute);
            }
        }
    }

    public static function removeIds(\Dom\Node $domNode): void
    {
        if (!DOM::isElementNode($domNode)) {
            return;
        }
        /* @var Element $domNode */
        $domNode->removeAttribute("id");
        foreach ($domNode->childNodes as $node) {
            self::removeIds($node);
        }
    }

    public static function replaceGlyphs(\Dom\Node $domNode): void
    {
        if (DOM::isTextNode($domNode)) {
            $domNode->textContent = Glyph::substitute(htmlspecialchars_decode($domNode->textContent));
        }
        if (DOM::isElementNode($domNode)) {
            foreach ($domNode->childNodes as $node) {
                self::replaceGlyphs($node);
            }
        }
    }

}
