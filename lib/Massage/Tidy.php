<?php
declare(strict_types=1);

class Massage_Tidy
{

    public static function doAll(DOMXPath $xpath): void
    {
        /** @var DOMElement $el */

        $divs = $xpath->query('//div');
        foreach ($divs as $el) {

            $oneChild = $el->childNodes->length === 1;

            $indentation = Indentation::get($el);

            if ($indentation < 0 && !$el->nextSibling && Indentation::get($el->parentNode) === -$indentation) {
                if (DOM::isTag($el->parentNode, 'dd')) {
                    $el->parentNode->parentNode->parentNode->insertBefore($el, $el->parentNode->parentNode->nextSibling);
                } else {
                    $el->parentNode->parentNode->insertBefore($el, $el->parentNode->nextSibling);
                }
                Node::remove($el);
                continue;
            }

            if ($indentation === 0) {
                if (
                    $oneChild &&
                    $el->firstChild->tagName === 'p' &&
                    !$el->firstChild->hasAttribute('indent') &&
                    $el->firstChild->hasAttribute('implicit') &&
                    DOM::isTag($el->previousSibling, 'p')
                ) {
                    if (!DOM::isTag($el->previousSibling->lastChild, 'br')) {
                        $el->previousSibling->appendChild($el->ownerDocument->createElement('br'));
                    }
                    DOM::extractContents($el->previousSibling, $el->firstChild);
                    $el->parentNode->removeChild($el);
                    continue;
                } else {
                    Node::remove($el);
                }
            } elseif ($oneChild && DOM::isTag($el->firstChild, ['pre', 'ul', 'dl'])) {
                Indentation::addElIndent($el->firstChild, $el);
                Node::remove($el);
            }
        }

        Massage_DL::mergeAdjacent($xpath);

        Node::removeAttributeAll($xpath->query('//dd[@indent]'), 'indent');

        $ps = $xpath->query('//p');
        foreach ($ps as $p) {
            Massage_P::tidy($p);
        }

        $els = $xpath->query('//ul');
        foreach ($els as $el) {
            Massage_UL::removeLonePs($el);
        }

        $els = $xpath->query('//li');
        foreach ($els as $el) {
            Massage_LI::tidy($el);
        }

        $els = $xpath->query('//dt');
        foreach ($els as $el) {
            Massage_DT::tidy($el);
        }

    }

    static function indentAttributeToClass(DOMXPath $xpath)
    {
        $els = $xpath->query('//div[@indent] | //p[@indent] | //dl[@indent] | //pre[@indent] | //ul[@indent]');
        foreach ($els as $el) {
            $indentVal = Indentation::get($el);
            if ($indentVal !== 0) {
                $el->setAttribute('class', 'indent-' . $indentVal);
            }
            $el->removeAttribute('indent');
            if ($indentVal === 0 && $el->tagName === 'div') {
                Node::remove($el);
            }
        }
        $els = $xpath->query('//p[@implicit]');
        foreach ($els as $el) {
            $el->removeAttribute('implicit');
        }
    }

}
