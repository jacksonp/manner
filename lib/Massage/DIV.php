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
            !DOM::isTag($div->firstChild, 'pre') &&
            Massage_List::startsWithBullet($div->textContent);
    }

    static function postProcess(DOMElement $div): ?DOMNode
    {

        $doc = $div->ownerDocument;

        /* @var DOMElement $nextNonBR */

        if (self::isPotentialLI($div)) {

            $nextNonBR = self::getNextNonBRNode($div);

            if (
                (is_null($nextNonBR) || !DOM::isTag($nextNonBR, 'div') || !self::isPotentialLI($nextNonBR)) &&
                Dom::isTag($div->firstChild, 'p') &&
                Massage_List::checkElementForLIs($div->firstChild)
            ) {

                /* @var DOMElement $ul */
                $ul = $doc->createElement('ul');
                $ul = $div->parentNode->insertBefore($ul, $div);

                while (DOM::isTag($div->firstChild, 'li')) {
                    $ul->appendChild($div->firstChild);
                }

                /* @var DOMElement $li */
                $li = $ul->appendChild($doc->createElement('li'));

                DOM::extractContents($li, $div);

                $div->parentNode->removeChild($div);

                Massage_List::pruneBulletChar($ul->firstChild);

                return $ul->nextSibling;

            } else {

                if (is_null($nextNonBR) || (DOM::isTag($nextNonBR, 'div') && self::isPotentialLI($nextNonBR))) {

                    /* @var DOMElement $ul */
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

                        Massage_List::pruneBulletChar($li);

                        Massage_List::checkElementForLIs($li);

                        $div->parentNode->removeChild($div);
                        $div = self::getNextNonBRNode($ul, true);

                    }

                    return $ul->nextSibling;

                }

            }
        }

        return $div->nextSibling;

    }

}
