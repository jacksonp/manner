<?php
declare(strict_types=1);

class Massage_Tidy
{

    public static function doAll(DOMXPath $xpath): void
    {
        /** @var DOMElement $el */

        $divs = $xpath->query('//div');
        foreach ($divs as $el) {
            if (!Indentation::get($el)) {
                Node::remove($el);
            }
            if ($el->childNodes->length === 1 && DOM::isTag($el->firstChild, ['pre', 'ul', 'dl'])) {
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

    }

    static function indentAttributeToClass (DOMXPath $xpath) {
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
    }

}
