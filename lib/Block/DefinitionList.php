<?php


class Block_DefinitionList
{

    static function appendDL(DOMElement $parentNode, DOMElement $dl)
    {

        $dom = $parentNode->ownerDocument;

        // See pmdapipe.1:
        if ($dl->childNodes->length === 1 and $dl->firstChild->tagName === 'dd') {
            $dd    = $dl->firstChild;
            $class = $dl->getAttribute('class');
            if ($dd->childNodes->length === 1 and $dd->firstChild->tagName === 'pre') {
                if (!in_array($class, ['', 'indent'])) {
                    $dd->firstChild->setAttribute('class', $class);
                }
                $parentNode->appendBlockIfHasContent($dd->firstChild);
            } else {
                $blockquote = $dom->createElement('blockquote');
                if (!in_array($class, ['', 'indent'])) {
                    $blockquote->setAttribute('class', $class);
                }
                $strayDD = $dl->firstChild;
                while ($strayDD->firstChild) {
                    $blockquote->appendChild($strayDD->firstChild);
                }
                $parentNode->appendBlockIfHasContent($blockquote);
            }

            return;
        }

        $everyChildIsDT = true;
        for ($j = 0; $j < $dl->childNodes->length; ++$j) {
            if ($dl->childNodes->item($j)->tagName !== 'dt') {
                $everyChildIsDT = false;
                break;
            }
        }

        if ($everyChildIsDT) {
            for ($j = 0; $j < $dl->childNodes->length; ++$j) {
                $strayDT = $dl->childNodes->item($j);
                $p       = $dom->createElement('p');
                $class   = $dl->getAttribute('class');
                if (!in_array($class, ['', 'indent'])) {
                    $p->setAttribute('class', $class);
                }
                while ($strayDT->firstChild) {
                    $p->appendChild($strayDT->firstChild);
                }
                $parentNode->appendBlockIfHasContent($p);
            }

            return;
        }

        $dl = $parentNode->appendBlockIfHasContent($dl);

        $newParagraphs = [];

        while ($dl->lastChild and $dl->lastChild->tagName === 'dt') {
            $p     = $dom->createElement('p');
            $class = $dl->getAttribute('class');
            if (!in_array($class, ['', 'indent'])) {
                $p->setAttribute('class', $class);
            }
            $strayDT = $dl->lastChild;
            while ($strayDT->firstChild) {
                $p->appendChild($strayDT->firstChild);
            }
            $dl->removeChild($strayDT);
            array_unshift($newParagraphs, $p);
        }

        foreach ($newParagraphs as $p) {
            $parentNode->appendBlockIfHasContent($p);
        }

    }

}
