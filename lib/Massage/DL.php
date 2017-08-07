<?php
declare(strict_types=1);

class Massage_DL
{

    static function mergeAdjacent(DOMXPath $xpath): void
    {
        $dls = $xpath->query('//dl');
        foreach ($dls as $dl) {
            while (DOM::isTag($dl->nextSibling, 'dl')) {
                DOM::extractContents($dl, $dl->nextSibling);
                $dl->parentNode->removeChild($dl->nextSibling);
            }
        }
    }

}
