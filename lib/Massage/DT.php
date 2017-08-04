<?php
declare(strict_types=1);

class Massage_DT
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

}
