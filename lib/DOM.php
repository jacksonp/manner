<?php
declare(strict_types = 1);

class DOM
{

    private static function extractContents(DOMElement $containerNode, DOMNode $appendNode)
    {

        if ($appendNode instanceof DOMText) {
            $containerNode->appendChild($appendNode);
        } else {
            while ($child = $appendNode->firstChild) {
                $containerNode->appendChild($child);
            }
        }

    }

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

    /*
    private static function hasImmediateChild(DOMElement $node, string $tag): bool
    {
        $child = $node->firstChild;
        while ($child) {
            if ($child->nodeType === XML_ELEMENT_NODE && $child->tagName === $tag) {
                return true;
            }
            $child = $child->nextSibling;
        }
        return false;
    }
    */

    private static function isParagraphFollowedByIndentedDiv(?DomNode $p): int
    {
        if (!self::isTag($p, 'p') || $p->getAttribute('class') !== '') {
            return 0;
        }

        $div = $p->nextSibling;

        if (
            !self::isTag($div, 'div') ||
            strpos($div->getAttribute('class'), 'indent-') !== 0 ||  // doesn't start with indent-
            !self::isTag($div->firstChild, 'p')
        ) {
            return 0;
        }

        // exclude e.g. "· <fork>" in dbus-daemon.1
        if (!preg_match('~^[a-z]~ui', $div->textContent)) {
            return 0;
        }

        if (preg_match('~^[^\s]+(?:, [^\s]+)*?$~u', $p->textContent)) {
            return 100;
        }

        if (preg_match('~^[A-Z][a-z]* ["A-Za-z][a-z]+~u', $p->textContent)) {
            return 0;
        }

        return 50;

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
            ($myTag !== 'pre' && Node::isTextAndEmpty($firstChild)) || self::isTag($firstChild, 'br')
        ) {
            $element->removeChild($firstChild);
        }

        while (
            $previousSibling = $element->previousSibling and
            (Node::isTextAndEmpty($previousSibling) || self::isTag($previousSibling, 'br'))
        ) {
            $element->parentNode->removeChild($previousSibling);
        }

        while (
            $nextSibling = $element->nextSibling and
            (Node::isTextAndEmpty($nextSibling) || self::isTag($nextSibling, 'br'))
        ) {
            $element->parentNode->removeChild($nextSibling);
        }

        while (
            $lastChild = $element->lastChild and
            (Node::isTextAndEmpty($lastChild) || self::isTag($lastChild, 'br'))
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
            if (self::isTag($element->previousSibling, 'dl')) {
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
                    ['section', 'p', 'dl', 'dt', 'dd', 'div', 'pre', 'table', 'tr', 'th', 'td'])
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

        $child = $element->firstChild;
        while ($child) {

            $certainty = self::isParagraphFollowedByIndentedDiv($child);

            if (
                $certainty > 50 ||
                (
                    $certainty > 0 &&
                    $child->nextSibling &&
                    self::isParagraphFollowedByIndentedDiv($child->nextSibling->nextSibling) !== 0
                )
            ) {
                $dl = $child->ownerDocument->createElement('dl');
                $child->parentNode->insertBefore($dl, $child);
                $nextElementToCheck = $child;
                while (self::isParagraphFollowedByIndentedDiv($nextElementToCheck)) {
                    $child = $nextElementToCheck;
                    $dt    = $child->ownerDocument->createElement('dt');
                    while ($child->firstChild) {
                        $dt->appendChild($child->firstChild);
                    }
                    $dl->appendChild($dt);
                    $dd = $child->ownerDocument->createElement('dd');
                    while ($child->nextSibling->firstChild) {
                        $dd->appendChild($child->nextSibling->firstChild);
                    }
                    $dl->appendChild($dd);
                    $nextElementToCheck = $child->nextSibling->nextSibling;
                    $child->parentNode->removeChild($child->nextSibling);
                    $child->parentNode->removeChild($child);
                }
                $child = $dl->nextSibling;
            } else {
                $child = $child->nextSibling;
            }

        }

        $child = $element->firstChild;
        while ($child) {
            if (self::isTag($child, 'dl')) {
                $dtChar       = null;
                $shouldBeList = true;
                foreach ($child->childNodes as $dlChild) {
                    if ($dlChild->tagName === 'dt') {
                        if (mb_strlen($dlChild->textContent) !== 1) {
                            $shouldBeList = false;
                            break;
                        }
                        if (is_null($dtChar)) {
                            $dtChar = $dlChild->textContent;
                        } elseif ($dtChar !== $dlChild->textContent) {
                            $shouldBeList = false;
                            break;
                        }
                    }
                }
                if ($shouldBeList && in_array($dtChar, ['*', 'o'])) {
                    $ul = $element->ownerDocument->createElement('ul');
                    $ul = $element->insertBefore($ul, $child);
                    if ($child->getAttribute('class') !== '') {
                        $ul->setAttribute('class', $child->getAttribute('class'));
                    }
                    foreach ($child->childNodes as $dlChild) {
                        if ($dlChild->tagName === 'dd') {
                            $li = $ul->appendChild($element->ownerDocument->createElement('li'));
                            self::extractContents($li, $dlChild);
                        }
                    }
                    $element->removeChild($child);
                    $child = $ul->nextSibling;
                    continue;
                }
            }
            $child = $child->nextSibling;
        }

        return self::massageNode($element);
    }

}
