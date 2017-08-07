<?php
declare(strict_types=1);

class DOM
{

    static function extractContents(DOMElement $target, DOMNode $source): void
    {

        if ($source instanceof DOMText) {
            $target->appendChild($source);
        } else {
            while ($child = $source->firstChild) {
                $target->appendChild($child);
            }
        }

    }

    static function isInlineElement(?DOMNode $node): bool
    {
        return $node && $node->nodeType === XML_ELEMENT_NODE && in_array($node->tagName, Blocks::INLINE_ELEMENTS);
    }

    static function isTag(?DOMNode $node, $tag): bool
    {
        $tag = (array)$tag;
        return $node && $node->nodeType === XML_ELEMENT_NODE && in_array($node->tagName, $tag);
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
        if (!self::isTag($p, 'p') || $p->getAttribute('indent') !== '') {
            return 0;
        }

        /** @var DOMElement $div , $p */

        $pChild = $p->firstChild;
        while ($pChild) {
            if (
                self::isTag($pChild, 'br') &&
                $pChild->previousSibling && preg_match('~^[A-Z].*\.$~u', $pChild->previousSibling->textContent)
            ) {
                return 0;
            }
            $pChild = $pChild->nextSibling;
        }

        $div = $p->nextSibling;

        if (
            !self::isTag($div, 'div') ||
            !$div->hasAttribute('indent') ||
            !self::isTag($div->firstChild, ['p', 'div', 'ul'])
        ) {
            return 0;
        }

        $divIndent = $div->getAttribute('indent');

        $pText = $p->textContent;

        if ($divIndent !== '') {

            // Exclude sentences in $p
            if (
                $pText === 'or' ||
                preg_match('~(^|\.\s)[A-Z][a-z]*(\s[a-z]+){3,}~u', $pText) ||
                preg_match('~(\s[a-z]{2,}){5,}~u', $pText) ||
                preg_match('~(\s[a-z]+){3,}[:\.]$~u', $pText)
            ) {
                return 0;
            }

            if (preg_match('~^(--?|\+)~u', $pText)) {
                return 100;
            }

            if (!preg_match('~^\s*[\(a-z]~ui', $div->textContent)) {
                return 0;
            }

            if (preg_match('~^\S$~ui', $pText)) {
                return 100;
            }

            if (preg_match('~^[^\s]+(?:, [^\s]+)*?$~u', $pText)) {
                return 100;
            }

            if (preg_match('~^[A-Z_]{2,}[\s\(\[]~u', $pText)) {
                return 100;
            }

            if (mb_strlen($pText) < 9) {
                return 100;
            }

//        if (preg_match('~^[A-Z][a-z]* ["A-Za-z][a-z]+~u', $pText)) {
//            return 50;
//        }

            return 50;

        } else {

            if (preg_match('~^(--?|\+)~u', $pText)) {
                return 100;
            }

            return 0;

        }

    }

    /**
     * @param DOMElement $element
     * @return DOMElement|DOMNode|null The element we should look at next.
     */
    private static function massageNode(DOMElement $element): ?DOMNode
    {

        $myTag = $element->tagName;
        $doc   = $element->ownerDocument;

        if ($myTag === 'pre') {
            if ($element->lastChild && $element->lastChild->nodeType === XML_ELEMENT_NODE) {
                $codeNode = $element->lastChild;
                if ($codeNode->lastChild && $codeNode->lastChild->nodeType === XML_TEXT_NODE) {
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

        Massage_Block::removeAdjacentEmptyTextNodesAndBRs($element);

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
                Indentation::addElIndent($firstChild, $element);
                Node::remove($element);
                Massage_Block::removeAdjacentEmptyTextNodesAndBRs($firstChild);
                return $firstChild->nextSibling;
            }

            if ($myTag === 'div' && $firstChild->tagName === 'div') {
                Indentation::addElIndent($element, $firstChild);
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

            $firstChild = $element->firstChild;

            if (!$firstChild) {
                $previousSibling = $element->previousSibling;
                $nextSibling     = $element->nextSibling;
                $element->parentNode->removeChild($element);
                if ($previousSibling) {
                    Massage_Block::removeFollowingEmptyTextNodesAndBRs($previousSibling);
                    return $previousSibling->nextSibling;
                } else {
                    return $nextSibling;
                }
            }

            if (
                $element->childNodes->length === 1 &&
                self::isTag($firstChild, 'p') &&
                preg_match('~^\t~', $element->textContent)
            ) {
                $pre = $element->parentNode->insertBefore($doc->createElement('pre'), $element);
                self::extractContents($pre, $firstChild);
                $element->parentNode->removeChild($element);
                Massage_Block::removeAdjacentEmptyTextNodesAndBRs($pre);
                return $pre->nextSibling;
            }

            if ($element->parentNode->tagName === 'pre') {
                if ($element->parentNode->childNodes->length === 1) {
                    Indentation::addElIndent($element->parentNode, $element);
                }
                $nextSibling = $element->nextSibling;
                Node::remove($element);
                return $nextSibling;
            }

            if ($element->getAttribute('indent') === '') {
                if (in_array($element->parentNode->tagName, ['dd'])) {
                    $nextSibling = Massage_DIV::getNextNonBRNode($element);
                    Node::remove($element);
                    Massage_Block::removeAdjacentEmptyTextNodesAndBRs($firstChild);
                    return $nextSibling;
                }
            }

        }

        if ($myTag === 'div' || $myTag === 'p') { // TODO: remove if?

            if (
                self::isTag($element->previousSibling, 'dl') &&
                self::isTag($element->previousSibling->lastChild, 'dd')
            ) {

                $elIndent = Indentation::get($element);
                $ddIndent = Indentation::get($element->previousSibling->lastChild);

                if ($elIndent === $ddIndent) {
                    $nextSibling = $element->nextSibling;
                    if ($myTag === 'div') {
                        self::extractContents($element->previousSibling->lastChild, $element);
                        $element->parentNode->removeChild($element);
                    } else {
                        $element->removeAttribute('indent');
                        $element->previousSibling->lastChild->appendChild($element);
                    }
                    return $nextSibling;
                }

                if ($elIndent > $ddIndent) {
                    $nextSibling = $element->nextSibling;
                    Indentation::set($element, $elIndent - $ddIndent);
                    $element->previousSibling->lastChild->appendChild($element);
                    return $nextSibling;
                }

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
                    $p       = $doc->createElement('p');
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
                $p       = $doc->createElement('p');
                $strayDT = $element->lastChild;
                while ($strayDT->firstChild) {
                    $p->appendChild($strayDT->firstChild);
                }
                $element->removeChild($strayDT);
                $element->parentNode->insertBefore($p, $element->nextSibling);
            }

        }

        if ($myTag === 'dt') {
            Massage_DT::postProcess($element);
        }

        return $element->nextSibling;

    }

    static function massage(DOMElement $element): ?DOMNode
    {

        $doc   = $element->ownerDocument;
        $child = $element->firstChild;
        while ($child) {

            if ($child->nodeType === XML_ELEMENT_NODE) {

                $myTag = $child->tagName;

                if (in_array($myTag, ['section', 'dd', 'div', 'td'])) {
                    Massage_Block::coalesceAdjacentChildDIVs($child);
                }

                if (in_array($myTag, ['section', 'p', 'dl', 'dt', 'dd', 'div', 'pre', 'table', 'tr', 'th', 'td'])) {
                    $child = self::massage($child);
                    continue;
                }

                if (in_array($myTag, ['h2', 'h3'])) {
                    while (
                        $nextSibling = $child->nextSibling and
                        (Node::isTextAndEmpty($nextSibling) || self::isTag($nextSibling, 'br'))
                    ) {
                        $element->removeChild($nextSibling);
                    }
                }

            }

            // NB: we don't want to remove the space in the <em> in cases e.g.:
            // <strong>e</strong><em> </em><strong>f</strong>
            if (self::isInlineElement($child)) {

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

                // Hack for cases like this: <dt><strong>-</strong><strong>-eps-file</strong>=&lt;<em>file</em>&gt;</dt>
                if ($child->textContent === '-') {
                    if (
                        $child->firstChild instanceof DOMText &&
                        $child->nextSibling &&
                        $child->nextSibling instanceof DOMElement &&
                        $child->nextSibling->childNodes->length === 1 &&
                        $child->nextSibling->firstChild instanceof DOMText &&
                        $child->tagName === $child->nextSibling->tagName
                    ) {
                        $child->nextSibling->firstChild->textContent = '-' . $child->nextSibling->firstChild->textContent;
                        $nextSibling                                 = $child->nextSibling;
                        $child->parentNode->removeChild($child);
                        $child = $nextSibling;
                        continue;
                    }
                }

            }

            $child = $child->nextSibling;

        }

        $child = $element->firstChild;
        while ($child) {

            $certainty = self::isParagraphFollowedByIndentedDiv($child);

            if ($certainty === 0) {
                $go = false;
            } elseif ($certainty > 50) {
                $go = true;
            } else {
                $go        = false;
                $nextP     = $child->nextSibling->nextSibling;
                $iteration = 0;
                while ($nextP) {
                    $followingCertainty = self::isParagraphFollowedByIndentedDiv($nextP);
                    if ($followingCertainty === 100) {
                        $go = true;
                        break;
                    }
                    if ($followingCertainty === 0) {
                        break;
                    }
                    ++$iteration;
                    if ($iteration > 2) {
                        $go = true;
                        break;
                    }
                    $nextP = $nextP->nextSibling->nextSibling;
                }
            }

            if ($go) {
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
                    Massage_DT::postProcess($dt);
                    $dd = $child->ownerDocument->createElement('dd');
                    while ($child->nextSibling->firstChild) {
                        $dd->appendChild($child->nextSibling->firstChild);
                    }
                    $dl->appendChild($dd);
                    $nextElementToCheck = $child->nextSibling->nextSibling;
                    $child->parentNode->removeChild($child->nextSibling);
                    $child->parentNode->removeChild($child);
                }
                Massage_Block::removeAdjacentEmptyTextNodesAndBRs($dl);
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
                if ($shouldBeList && in_array($dtChar, Massage_UL::CHAR_PREFIXES)) {
                    $ul = $doc->createElement('ul');
                    $ul = $element->insertBefore($ul, $child);
                    foreach ($child->childNodes as $dlChild) {
                        if ($dlChild->tagName === 'dd') {
                            $li = $ul->appendChild($doc->createElement('li'));
                            if ($dlChild->childNodes->length === 1 && $dlChild->firstChild->tagName === 'p') {
                                self::extractContents($li, $dlChild->firstChild);
                            } else {
                                self::extractContents($li, $dlChild);
                            }
                        }
                    }
                    $element->removeChild($child);
                    $child = $ul->nextSibling;
                    continue;
                }
            }
            $child = $child->nextSibling;
        }

        $child = $element->firstChild;
        while ($child) {
            if (self::isTag($child, 'div')) {
                $child = Massage_DIV::postProcess($child);
            } else {
                $child = $child->nextSibling;
            }
        }

        return self::massageNode($element);
    }

    static function tidy(DOMDocument $dom): void
    {

        $xpath = new DOMXpath($dom);

        $unindentedDIVs = $xpath->query('//div[not(@indent)]');
        foreach ($unindentedDIVs as $div) {
            Node::remove($div);
        }

        Massage_DL::mergeAdjacent($xpath);

        Node::removeAttributeAll($xpath->query('//dd[@indent]'), 'indent');

        /** @var DOMElement $el */
        $els = $xpath->query('//div[@indent] | //p[@indent] | //dl[@indent] | //pre[@indent]');
        foreach ($els as $el) {
            $indentVal = (int)$el->getAttribute('indent');
            if ($indentVal !== 0) {
                $el->setAttribute('class', 'indent-' . $indentVal);
            }
            $el->removeAttribute('indent');
            if ($indentVal === 0 && $el->tagName === 'div') {
                Node::remove($el);
            }
        }

        // Do this after changing @indent to @class
        $ps = $xpath->query('//p');
        foreach ($ps as $p) {
            Massage_P::tidy($p);
        }

    }

    public static function calcIndents(DOMDocument $dom)
    {

        $xpath = new DOMXpath($dom);

        $divs = $xpath->query('//div[@left-margin]');
        foreach ($divs as $div) {

            $leftMargin = (int)$div->getAttribute('left-margin');
            $parentNode = $div->parentNode;
            while ($parentNode) {
                if ($parentNode instanceof DOMDocument || $parentNode->tagName === 'div') {
                    break;
                }
                if ($parentNode->hasAttribute('indent')) {
                    $leftMargin -= (int)$parentNode->getAttribute('indent');
                }
                $parentNode = $parentNode->parentNode;
            }
            $div->setAttribute('indent', (string)$leftMargin);
            $div->removeAttribute('left-margin');

        }
    }

    public static function remap(DOMDocument $dom)
    {

        $xpath = new DOMXpath($dom);

        $divs = $xpath->query('//div[@remap]');
        /** @var DOMElement $div */
        foreach ($divs as $div) {
            if ($div->getAttribute('remap') === 'IP') {
                $indentVal = (int)$div->getAttribute('indent');
                if ($indentVal !== 0) {
                    $child = $div->firstChild;
                    while ($child) {
                        Indentation::add($child, $indentVal);
                        $child = $child->nextSibling;
                    }
                }
            }
            Node::remove($div);
        }


    }


}
