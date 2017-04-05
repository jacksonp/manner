<?php
declare(strict_types=1);

class Massage_DIV
{

    static function getNextNonBRNode(DOMNode $element, bool $removeBRs = false): ?DOMNode
    {
        $nextSibling = $element->nextSibling;
        while (DOM::isTag($nextSibling, 'br')) {
            $nextSibling = $nextSibling->nextSibling;
            if ($removeBRs) {
                $element->parentNode->removeChild($element->nextSibling);
            }
        }
        return $nextSibling;
    }

    private static function isPotentialLI(?DOMElement $div)
    {
        return
            DOM::isTag($div, 'div') &&
            $div->getAttribute('class') === 'indent-4' &&
            in_array(mb_substr(ltrim($div->textContent), 0, 1), Massage_UL::CHAR_PREFIXES);
    }

    private static function getFirstNonEmptyTextNode(?DOMNode $domNode): ?DOMText
    {

        if ($domNode instanceof DOMText) {
            if (trim($domNode->textContent) === '') {
                $domNode->parentNode->removeChild($domNode);
                return null;
            }
            return $domNode;
        }

        foreach ($domNode->childNodes as $node) {
            if ($el = self::getFirstNonEmptyTextNode($node)) {
                return $el;
            }
        }

        return null;

    }

    static function postProcess(DOMElement $div): ?DOMNode
    {

        $doc = $div->ownerDocument;

        $nextNonBR = self::getNextNonBRNode($div);

        if (!DOM::isTag($nextNonBR, 'div')) {
            return $div->nextSibling;
        }

        /* @var DOMElement $nextNonBR */

        if (
            self::isPotentialLI($div) &&
            self::isPotentialLI($nextNonBR)
        ) {

            $ul = $doc->createElement('ul');
            $ul = $div->parentNode->insertBefore($ul, $div);

            while (self::isPotentialLI($div)) {

                /* @var DOMElement $li */
                $li = $ul->appendChild($doc->createElement('li'));

                if ($div->childNodes->length === 1 && $div->firstChild->tagName === 'p') {
                    DOM::extractContents($li, $div->firstChild);
                } else {
                    DOM::extractContents($li, $div);
                }

                $firstTextNode = self::getFirstNonEmptyTextNode($li);
                if ($firstTextNode) {
                    $firstTextNode->textContent = ltrim(mb_substr(ltrim($firstTextNode->textContent), 1));
                }
                $div->parentNode->removeChild($div);
                $div = self::getNextNonBRNode($ul, true);

            }

            return $ul->nextSibling;

        }

        return $div->nextSibling;

    }

}
