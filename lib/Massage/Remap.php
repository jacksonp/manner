<?php
declare(strict_types=1);

class Massage_Remap
{

    /**
     * @param DOMXPath $xpath
     * @throws Exception
     */
    public static function doAll(DOMXPath $xpath)
    {

        $blocks = ['p', 'pre', 'div', 'dl', 'ul', 'table'];

        $divs = $xpath->query('//div[@remap]');
        /** @var DOMElement $div */
        /** @var DOMElement $p */
        foreach ($divs as $div) {
            if ($div->getAttribute('remap') === 'IP') {
                $indentVal = Indentation::get($div);

                $sibling = $div->firstChild;
                if ($sibling) {
                    do {
                        if (DOM::isTag($sibling, $blocks)) {
                            $next = $sibling->nextSibling;
                            $sibling->removeAttribute('implicit');
                            $indentVal && Indentation::add($sibling, $indentVal);
                            $div->parentNode->insertBefore($sibling, $div);
                        } else {
                            $p = $div->ownerDocument->createElement('p');
                            $p = $div->parentNode->insertBefore($p, $div);
                            $indentVal && Indentation::add($p, $indentVal);
                            while ($sibling && !DOM::isTag($sibling, $blocks)) {
                                $next = $sibling->nextSibling;
                                $p->appendChild($sibling);
                                $sibling = $next;
                            }
                        }
                    } while ($sibling = $next);
                }

                $div->parentNode->removeChild($div);

            } else {
                throw new Exception('Unexpected value for remap.');
            }
        }

    }
}
