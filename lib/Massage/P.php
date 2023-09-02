<?php

declare(strict_types=1);

namespace Manner\Massage;

use DOMElement;
use DOMXPath;
use Exception;
use Manner\DOM;
use Manner\Indentation;
use Manner\Node;

class P
{

    public static function removeEmpty(DOMXPath $xpath): void
    {
        $ps = $xpath->query('//p');
        foreach ($ps as $p) {
            if (!$p->firstChild || trim($p->textContent) === '') {
                $p->parentNode->removeChild($p);
            }
        }
    }

    /**
     * @param DOMElement $p
     * @throws Exception
     */
    public static function tidy(DOMElement $p): void
    {
        // Change two br tags in a row to a new paragraph.

        $indent = Indentation::get($p);
        $pChild = $p->firstChild;

        while ($pChild) {
            if (DOM::isTag($pChild, 'br') && DOM::isTag($pChild->nextSibling, 'br')) {
                $newP = $p->ownerDocument->createElement('p');
                if ($indent) {
                    Indentation::set($newP, $indent);
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
        }
    }

}
