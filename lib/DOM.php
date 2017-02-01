<?php

class DOM
{

    private static function setIndentClass(DOMElement $remainingNode, DOMElement $leavingNode): void
    {
        $remainingNodeClass = $remainingNode->getAttribute('class');
        $leavingNodeClass   = $leavingNode->getAttribute('class');

        if (in_array($leavingNodeClass, ['', 'indent'])) {
            // Do nothing
        } elseif (in_array($remainingNodeClass, ['', 'indent'])) {
            $remainingNode->setAttribute('class', $leavingNodeClass);
        } else {
            $remainingNodeIndentVal = abs((int)filter_var($remainingNodeClass, FILTER_SANITIZE_NUMBER_INT));
            $leavingNodeIndentVal   = abs((int)filter_var($leavingNodeClass, FILTER_SANITIZE_NUMBER_INT));
            $remainingNode->setAttribute('class', 'indent-' . ($remainingNodeIndentVal + $leavingNodeIndentVal));
        }
    }

    static function removeNode(DOMNode $from, $preserveChildren = true): void
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

    /**
     * @param DOMElement $element
     * @return DOMElement|DOMNode|null The element we should look at next.
     */
    private static function massageNode(DOMElement $element): ?DOMNode
    {

        $myTag = $element->tagName;

        if ($myTag === 'pre') {
            if ($element->lastChild && $element->lastChild->nodeType === XML_ELEMENT_NODE) {
                $codeNode = $element->lastChild;
                if ($codeNode->lastChild->nodeType === XML_TEXT_NODE) {
                    $codeNode->lastChild->textContent = preg_replace('~\n+$~', '', $codeNode->lastChild->textContent);
                }
            }
        }

        while (
            $firstChild = $element->firstChild and
            (
                ($myTag !== 'pre' && $firstChild->nodeType === XML_TEXT_NODE && trim($firstChild->textContent) === '') ||
                ($firstChild->nodeType === XML_ELEMENT_NODE && $firstChild->tagName === 'br')
            )
        ) {
            $element->removeChild($firstChild);
        }

        while (
            $previousSibling = $element->previousSibling and
            (
                ($previousSibling->nodeType === XML_TEXT_NODE && trim($previousSibling->textContent) === '') ||
                ($previousSibling->nodeType === XML_ELEMENT_NODE && $previousSibling->tagName === 'br')
            )
        ) {
            $element->parentNode->removeChild($previousSibling);
        }

        while (
            $nextSibling = $element->nextSibling and
            (
                ($nextSibling->nodeType === XML_TEXT_NODE && trim($nextSibling->textContent) === '') ||
                ($nextSibling->nodeType === XML_ELEMENT_NODE && $nextSibling->tagName === 'br')
            )
        ) {
            $element->parentNode->removeChild($nextSibling);
        }

        while (
            $lastChild = $element->lastChild and
            (
                ($lastChild->nodeType === XML_TEXT_NODE && trim($lastChild->textContent) === '') ||
                ($lastChild->nodeType === XML_ELEMENT_NODE && $lastChild->tagName === 'br')
            )
        ) {
            $element->removeChild($lastChild);
        }

        if ($element->childNodes->length === 1 && $element->firstChild->nodeType === XML_ELEMENT_NODE) {

            $firstChild = $element->firstChild;

            // TODO: could also do <p>s here, but need to handle cases like amaddclient.8 where the option handling then gets messed up.
            if ($myTag === 'div' && in_array($firstChild->tagName, ['dl'])) {
                self::setIndentClass($firstChild, $element);
                $nextSibling = $element->nextSibling;
                self::removeNode($element);
                return $nextSibling;
            }

            // Could sum indents if both elements have indent-X
            if ($myTag === 'div' && $firstChild->tagName === 'div') {
                self::setIndentClass($element, $firstChild);
                self::removeNode($firstChild);
            }

            if ($myTag === 'dd' && $firstChild->tagName === 'p') {
                self::removeNode($firstChild);
            }

        }

        if ($myTag === 'pre' && in_array($element->parentNode->tagName, ['pre'])) {
            $nextSibling = $element->nextSibling;
            self::removeNode($element);
            return $nextSibling;
        }

        if ($myTag === 'div' && $element->parentNode->tagName === 'pre') {
            if ($element->parentNode->childNodes->length === 1) {
                self::setIndentClass($element->parentNode, $element);
            }
            $nextSibling = $element->nextSibling;
            self::removeNode($element);
            return $nextSibling;
        }

        if ($myTag === 'div' && $element->getAttribute('class') === 'indent') {
            if (in_array($element->parentNode->tagName, ['dd'])) {
                $nextSibling = $element->nextSibling;
                self::removeNode($element);
                return $nextSibling;
            }
        }

        if (!in_array($myTag, ['th', 'td']) && !$element->hasChildNodes()) {
            $nextSibling = $element->nextSibling;
            $element->parentNode->removeChild($element);
            return $nextSibling;
        }

        if ($myTag === 'dl') {
            if ($element->previousSibling && $element->previousSibling->nodeType === XML_ELEMENT_NODE && $element->previousSibling->tagName === 'dl') {
                if ($element->getAttribute('class') === $element->previousSibling->getAttribute('class')) { // matching indent level
                    $nextSibling = $element->nextSibling;
                    while ($element->firstChild) {
                        $element->previousSibling->appendChild($element->firstChild);
                    }
                    $element->parentNode->removeChild($element);
                    return $nextSibling;
                }
            }


            $everyChildIsDT = true;
            for ($j = 0; $j < $element->childNodes->length; ++$j) {
                $elementChild = $element->childNodes->item($j);
                if ($elementChild->tagName !== 'dt') {
                    $everyChildIsDT = false;
                    break;
                }
            }

            if ($everyChildIsDT) {
                for ($j = 0; $j < $element->childNodes->length; ++$j) {
                    $strayDT = $element->childNodes->item($j);
                    $p       = $element->ownerDocument->createElement('p');
                    $class   = $element->getAttribute('class');
                    if (!in_array($class, ['', 'indent'])) {
                        $p->setAttribute('class', $class);
                    }
                    while ($strayDT->firstChild) {
                        $p->appendChild($strayDT->firstChild);
                    }
                    $element->parentNode->insertBefore($p, $element);

                }
                $nextSibling = $element->nextSibling;
                $element->parentNode->removeChild($element);
                return $nextSibling;
            }

            while ($element->lastChild && $element->lastChild->tagName === 'dt') {
                $p     = $element->ownerDocument->createElement('p');
                $class = $element->getAttribute('class');
                if (!in_array($class, ['', 'indent'])) {
                    $p->setAttribute('class', $class);
                }
                $strayDT = $element->lastChild;
                while ($strayDT->firstChild) {
                    $p->appendChild($strayDT->firstChild);
                }
                $element->removeChild($strayDT);
                $element->parentNode->insertBefore($p, $element->nextSibling);
            }

        }

        return $element->nextSibling;

    }

    static function massage(DOMElement $element): ?DOMNode
    {
        $child = $element->firstChild;
        while ($child) {
            if (
                $child->nodeType === XML_ELEMENT_NODE &&
                in_array($child->tagName,
                    ['section', 'p', 'dl', 'dt', 'dd', 'div', 'blockquote', 'pre', 'table', 'tr', 'th', 'td'])
            ) {
                $child = self::massage($child);
            } elseif (
                $child->nodeType === XML_ELEMENT_NODE &&
                in_array($child->tagName, Blocks::INLINE_ELEMENTS) &&
                trim($child->textContent === '')
            ) {
                $nextSibling = $child->nextSibling;
                $child->parentNode->removeChild($child);
                $child = $nextSibling;
            } else {
                $child = $child->nextSibling;
            }
        }
        return self::massageNode($element);
    }

}
