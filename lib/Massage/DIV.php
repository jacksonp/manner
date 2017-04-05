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
            Massage_UL::startsWithBullet($div->textContent);
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

                Massage_UL::pruneBulletChar($li);

                Massage_UL::checkLIForLIs($li);

                $div->parentNode->removeChild($div);
                $div = self::getNextNonBRNode($ul, true);

            }

            return $ul->nextSibling;

        }

        return $div->nextSibling;

    }

}
