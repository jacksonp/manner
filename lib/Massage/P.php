<?php
declare(strict_types=1);

class Massage_P
{

    static function removeEmpty(DOMXPath $xpath)
    {
        $ps = $xpath->query('//p');
        foreach ($ps as $p) {
            if (!$p->firstChild || trim($p->textContent) === '') {
                $p->parentNode->removeChild($p);
            }
        }
    }

    static function tidy(DOMElement $p)
    {

        // Change two br tags in a row to a new paragraph.

        $indent = $p->getAttribute('indent');
        $pChild = $p->firstChild;

        while ($pChild) {
            if (DOM::isTag($pChild, 'br') && DOM::isTag($pChild->nextSibling, 'br')) {
                $newP = $p->ownerDocument->createElement('p');
                if ($indent !== '') {
                    $newP->setAttribute('indent', $indent);
                }
                while ($p->firstChild) {
                    if ($p->firstChild === $pChild) {
                        break;
                    }
                    $newP->appendChild($p->firstChild);
                }
                $p->parentNode->insertBefore($newP, $p);
                $p->removeChild($p->firstChild); // 1st <br>
                $p->removeChild($p->firstChild); // 2nd <br>
                self::tidy($p);
                self::tidy($newP);
                return;
            }
            $pChild = $pChild->nextSibling;
        }

        while ($p->lastChild && (Node::isTextAndEmpty($p->lastChild) || DOM::isTag($p->lastChild, 'br'))) {
            $p->removeChild($p->lastChild);
        }

        if (trim($p->textContent) === '') {
            $p->parentNode->removeChild($p);
            return;
        }

    }

}
