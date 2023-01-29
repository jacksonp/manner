<?php

declare(strict_types=1);

namespace Manner\Massage;

use DOMElement;
use DOMXPath;
use Exception;
use Manner\DOM;
use Manner\Indentation;
use Manner\Node;

class Tidy
{

    /**
     * @param DOMXPath $xpath
     * @throws Exception
     */
    public static function doAll(DOMXPath $xpath): void
    {
        /** @var DOMElement $el */

        // NB: we do not want <dd>s here:
        $els = $xpath->query(
          '//div[starts-with(@indent, "-")] | //p[starts-with(@indent, "-")] | //pre[starts-with(@indent, "-")] | //ul[starts-with(@indent, "-")] | //dl[starts-with(@indent, "-")] | //table[starts-with(@indent, "-")]'
        );
        foreach ($els as $el) {
            Indentation::popOut($el);
        }

        $divs = $xpath->query('//div');
        foreach ($divs as $el) {
            $oneChild = $el->childNodes->length === 1;

            $indentation = Indentation::get($el);

            if (!$indentation) {
                if (
                  $oneChild &&
                  $el->firstChild->tagName === 'p' &&
                  !Indentation::isSet($el->firstChild) &&
                  $el->firstChild->hasAttribute('implicit') &&
                  DOM::isTag($el->previousSibling, 'p')
                ) {
                    if (!DOM::isTag($el->previousSibling->lastChild, 'br')) {
                        $el->previousSibling->appendChild($el->ownerDocument->createElement('br'));
                    }
                    DOM::extractContents($el->previousSibling, $el->firstChild);
                    $el->parentNode->removeChild($el);
                } elseif (!$el->getAttribute('class')) {
                    Node::remove($el);
                }
            } elseif ($oneChild && DOM::isTag($el->firstChild, ['pre', 'ul', 'ol', 'dl'])) {
                DIV::removeDIVWithSingleChild($el);
            }
        }

        DL::mergeAdjacentAndConvertLoneDD($xpath);

        Node::removeAttributeAll($xpath->query('//dd[@indent]'), 'indent');

        $ps = $xpath->query('//p');
        foreach ($ps as $p) {
            P::tidy($p);
        }

        // NB: can't really do the same for <dd>s they need the inner <p> to have some sanity in how they are rendered.
        $els = $xpath->query('//ul | //ol');
        foreach ($els as $el) {
            HTMLList::removeLonePs($el);
        }

        $els = $xpath->query('//li');
        foreach ($els as $el) {
            LI::tidy($el);
        }

        $els = $xpath->query('//dt');
        foreach ($els as $el) {
            DT::tidy($el);
        }

        $els = $xpath->query('//pre');
        foreach ($els as $el) {
            PRE::tidy($el);
        }
    }

    public static function indentAttributeToClass(DOMXPath $xpath): void
    {
        $els = $xpath->query(
          '//div[@indent] | //p[@indent] | //dl[@indent] | //dt[@indent] | //pre[@indent] | //ul[@indent] | //ol[@indent] | //table[@indent]'
        );
        foreach ($els as $el) {
            if (DOM::isTag($el, ['ul', 'ol'])) {
                // Remove indent on lists as they get indented by default in html anyway
                $el->removeAttribute('indent');
            } else {
                $indentVal = Indentation::get($el);
                if ($indentVal) {
                    $el->setAttribute('class', 'indent-' . $indentVal);
                }
                Indentation::remove($el);
                if (!$indentVal && $el->tagName === 'div') {
                    Node::remove($el);
                }
            }
        }
        $els = $xpath->query('//p[@implicit]');
        foreach ($els as $el) {
            $el->removeAttribute('implicit');
        }
    }

}
