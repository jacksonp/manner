<?php

declare(strict_types=1);

namespace Manner\Massage;

use DOMElement;
use DOMNode;
use DOMXPath;
use Manner\DOM;
use Manner\Indentation;
use Manner\Node;

class DIV
{

    public static function removeDIVsWithSingleChild(DOMXPath $xpath)
    {
        $divs = $xpath->query('//div');
        foreach ($divs as $div) {
            // TODO add other blocks below? see Dom handling line 106 or so.
            if ($div->childNodes->length === 1 && Dom::isTag($div->firstChild, 'pre')) {
                Indentation::addElIndent($div->firstChild, $div);
                Node::remove($div);
            }
        }
    }


    public static function getNextNonBRNode(DOMNode $element, bool $removeBRs = false): ?DOMNode
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
          !DOM::isTag($div->firstChild, ['pre', 'ul']) &&
          HTMLList::startsWithBullet($div->textContent);
    }

    public static function postProcess(DOMElement $div): ?DOMNode
    {
        $doc = $div->ownerDocument;

        /* @var DOMElement $nextNonBR */

        if (self::isPotentialLI($div)) {
            $nextNonBR = self::getNextNonBRNode($div);

            if (
              (is_null($nextNonBR) || !DOM::isTag($nextNonBR, 'div') || !self::isPotentialLI($nextNonBR)) &&
              Dom::isTag($div->firstChild, 'p') &&
              HTMLList::checkElementForLIs($div->firstChild)
            ) {
                $ul = $doc->createElement('ul');
                $ul = $div->parentNode->insertBefore($ul, $div);

                while (DOM::isTag($div->firstChild, 'li')) {
                    $ul->appendChild($div->firstChild);
                }

                /* @var DOMElement $ul */
                /* @var DOMElement $li */
                $li = $ul->appendChild($doc->createElement('li'));

                DOM::extractContents($li, $div);

                $div->parentNode->removeChild($div);

                HTMLList::pruneBulletChar($ul->firstChild);

                return $ul->nextSibling;
            } elseif (is_null($nextNonBR) || (DOM::isTag($nextNonBR, 'div') && self::isPotentialLI($nextNonBR))) {
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

                    HTMLList::pruneBulletChar($li);

                    HTMLList::checkElementForLIs($li);

                    $div->parentNode->removeChild($div);
                    $div = self::getNextNonBRNode($ul, true);
                }

                return $ul->nextSibling;
            }
        }

        return $div->nextSibling;
    }

}
