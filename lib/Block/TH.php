<?php

/**
 * Some pages  have multiple .THs for different commands in one page, just had a horizontal line when we hit .THs with
 * content after the first (empty .TH also used in tbl tables).
 */
class Block_TH
{

    static function checkAppend(DOMElement $parentNode, array $lines, int $i)
    {
        if (!preg_match('~^\.\s*TH\s(.*)$~u', $lines[$i])) {
            return false;
        }

        $hr = $parentNode->ownerDocument->createElement('hr');
        $parentNode->appendChild($hr);

        return $i;

    }

}
