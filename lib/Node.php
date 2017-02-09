<?php
declare(strict_types = 1);

class Node
{

    static function addClass(DOMElement $node, string $className): void
    {
        $existingClassString = $node->getAttribute('class');
        if (!in_array($className, explode(' ', $existingClassString))) {
            $node->setAttribute('class', trim($existingClassString . ' ' . $className));
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

}
