<?php

declare(strict_types=1);

namespace Manner\Massage;

use DOMElement;
use Manner\DOM;
use Manner\Node;

class DT
{

    public static function postProcess(DOMElement $dt): void
    {
        $child = $dt->lastChild;
        while ($child) {
            if (DOM::isTag($child, 'br')) {
                $newDT = $dt->ownerDocument->createElement('dt');
                $newDT = $dt->parentNode->insertBefore($newDT, $dt->nextSibling);

                $nextChild = $child->nextSibling;
                $dt->removeChild($child);
                while ($nextChild) {
                    $sib = $nextChild->nextSibling;
                    $newDT->appendChild($nextChild);
                    $nextChild = $sib;
                }
                self::postProcess($dt);
            }
            $child = $child->previousSibling;
        }
    }

    public static function tidy(DOMElement $dt)
    {
        while ($dt->lastChild && (Node::isTextAndEmpty($dt->lastChild) || DOM::isTag($dt->lastChild, 'br'))) {
            $dt->removeChild($dt->lastChild);
        }

        if (trim($dt->textContent) === '') {
            $dt->parentNode->removeChild($dt);
        }

        if (Dom::isTag($dt->firstChild, 'pre')) {
            // <pre>s can't go inside <dt>s (tho we put them there for convenience now).
            // TODO: remove this once we handle .nf and .EX by setting flag rather than creating <pre> element.
            Node::remove($dt->firstChild);
            self::tidy($dt);
        }
    }

}
