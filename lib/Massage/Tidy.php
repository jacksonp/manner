<?php
declare(strict_types=1);

class Massage_Tidy
{

    /**
     * @param DOMXPath $xpath
     * @throws Exception
     */
    public static function doAll(DOMXPath $xpath): void
    {
        /** @var DOMElement $el */

        // NB: we do not want <dd>s here:
        $els = $xpath->query('//div[starts-with(@indent, "-")] | //p[starts-with(@indent, "-")] | //pre[starts-with(@indent, "-")] | //ul[starts-with(@indent, "-")] | //dl[starts-with(@indent, "-")] | //table[starts-with(@indent, "-")]');
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
                    continue;
                } elseif (!$el->getAttribute('class')) {
                    Node::remove($el);
                }
            } elseif ($oneChild && DOM::isTag($el->firstChild, ['pre', 'ul', 'dl'])) {
                Indentation::addElIndent($el->firstChild, $el);
                Node::remove($el);
            }
        }

        Massage_DL::mergeAdjacentAndConvertLoneDD($xpath);

        Node::removeAttributeAll($xpath->query('//dd[@indent]'), 'indent');

        $ps = $xpath->query('//p');
        foreach ($ps as $p) {
            Massage_P::tidy($p);
        }

        // NB: can't really do the same for <dd>s they need the inner <p> to have some sanity in how they are rendered.
        $els = $xpath->query('//ul | //ol');
        foreach ($els as $el) {
            Massage_List::removeLonePs($el);
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
        $els = $xpath->query('//div[@indent] | //p[@indent] | //dl[@indent] | //dt[@indent] | //pre[@indent] | //ul[@indent] | //table[@indent]');
        foreach ($els as $el) {
            if ($el->tagName === 'ul') {
                // Remove indent on <ul>s as they get indented by default in html anyway
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
