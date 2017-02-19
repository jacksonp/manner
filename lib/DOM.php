<?php
declare(strict_types = 1);

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

    private static function isTag(?DOMNode $node, string $tag): bool
    {
        return $node && $node->nodeType === XML_ELEMENT_NODE && $node->tagName === $tag;
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
                ($myTag !== 'pre' && Node::isTextAndEmpty($firstChild)) ||
                ($firstChild->nodeType === XML_ELEMENT_NODE && $firstChild->tagName === 'br')
            )
        ) {
            $element->removeChild($firstChild);
        }

        while (
            $previousSibling = $element->previousSibling and
            (
                Node::isTextAndEmpty($previousSibling) ||
                ($previousSibling->nodeType === XML_ELEMENT_NODE && $previousSibling->tagName === 'br')
            )
        ) {
            $element->parentNode->removeChild($previousSibling);
        }

        while (
            $nextSibling = $element->nextSibling and
            (
                Node::isTextAndEmpty($nextSibling) ||
                ($nextSibling->nodeType === XML_ELEMENT_NODE && $nextSibling->tagName === 'br')
            )
        ) {
            $element->parentNode->removeChild($nextSibling);
        }

        while (
            $lastChild = $element->lastChild and
            (
                Node::isTextAndEmpty($lastChild) ||
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
                Node::remove($element);
                return $nextSibling;
            }

            // Could sum indents if both elements have indent-X
            if ($myTag === 'div' && $firstChild->tagName === 'div') {
                self::setIndentClass($element, $firstChild);
                Node::remove($firstChild);
            }

        }

        // This takes out rows with an <hr>, which we don't want.
//        if ($myTag === 'tr' && trim($element->textContent) === '') {
//            $nextSibling = $element->nextSibling;
//            $element->parentNode->removeChild($element);
//            return $nextSibling;
//        }

        if ($myTag === 'pre' && in_array($element->parentNode->tagName, ['pre'])) {
            $nextSibling = $element->nextSibling;
            Node::remove($element);
            return $nextSibling;
        }

        if ($myTag === 'div') {

            if (self::isTag($element->previousSibling, 'div') &&
                $element->getAttribute('class') === $element->previousSibling->getAttribute('class') &&
                $element->childNodes->length === 1 &&
                $element->previousSibling->childNodes->length === 1 &&
                self::isTag($element->firstChild, 'p') &&
                self::isTag($element->previousSibling->firstChild, 'p')
            ) {
                $nextSibling = $element->nextSibling;
                $element->previousSibling->firstChild->appendChild($element->ownerDocument->createElement('br'));
                while ($element->firstChild->firstChild) {
                    $element->previousSibling->firstChild->appendChild($element->firstChild->firstChild);
                }
                $element->parentNode->removeChild($element);
                return $nextSibling;

            }

            if ($element->parentNode->tagName === 'pre') {
                if ($element->parentNode->childNodes->length === 1) {
                    self::setIndentClass($element->parentNode, $element);
                }
                $nextSibling = $element->nextSibling;
                Node::remove($element);
                return $nextSibling;
            }
        }

        if ($myTag === 'div' && $element->getAttribute('class') === 'indent') {
            if (in_array($element->parentNode->tagName, ['dd'])) {
                $nextSibling = $element->nextSibling;
                Node::remove($element);
                return $nextSibling;
            }
        }

        if (!in_array($myTag, ['th', 'td']) && !$element->hasChildNodes()) {
            $nextSibling = $element->nextSibling;
            $element->parentNode->removeChild($element);
            return $nextSibling;
        }

        if ($myTag === 'table') {
            if ($element->firstChild) {
                $tr          = $element->firstChild;
                $convertToTH = true;
                foreach ($tr->childNodes as $td) {
                    if (!Node::hasClass($td, 'bold')) {
                        $convertToTH = false;
                    }
                }
                if ($convertToTH) {
                    $child = $tr->firstChild;
                    while ($child) {
                        $td    = $child;
                        $child = $child->nextSibling;
                        Node::removeClass($td, 'bold');
                        Node::changeTag($td, 'th');
                    }
                }
            }
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
                continue;
            }

            // NB: we don't want to remove the space in the <em> in cases e.g.:
            // <strong>e</strong><em> </em><strong>f</strong>
            if ($child->nodeType === XML_ELEMENT_NODE && in_array($child->tagName, Blocks::INLINE_ELEMENTS)) {

                if (
                    $child->firstChild &&
                    $child->firstChild->nodeType == XML_TEXT_NODE &&
                    preg_match('~^(\s+)(.*?)$~u', $child->firstChild->textContent, $matches)
                ) {
                    $child->replaceChild($child->ownerDocument->createTextNode($matches[2]), $child->firstChild);
                    $child->parentNode->insertBefore($child->ownerDocument->createTextNode($matches[1]), $child);
                }

                if (
                    $child->lastChild &&
                    $child->lastChild->nodeType == XML_TEXT_NODE &&
                    preg_match('~^(.*?)(\s+)$~u', $child->lastChild->textContent, $matches)
                ) {
                    $child->replaceChild($child->ownerDocument->createTextNode($matches[1]), $child->lastChild);
                    $child->parentNode->insertBefore(
                        $child->ownerDocument->createTextNode($matches[2]),
                        $child->nextSibling
                    );
                }

                if ($child->textContent === '') {
                    $nextSibling = $child->nextSibling;
                    $child->parentNode->removeChild($child);
                    $child = $nextSibling;
                    continue;
                }

            }

            $child = $child->nextSibling;

        }
        return self::massageNode($element);
    }

}
