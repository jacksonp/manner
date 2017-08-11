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

    public static function checkPrecedingNodes(DOMXPath $xpath): void
    {
        $dls = $xpath->query('//dl');
        foreach ($dls as $dl) {
            if (!$dl->previousSibling || !$dl->previousSibling->previousSibling) {
                continue;
            }

            $p   = $dl->previousSibling->previousSibling;
            $div = $dl->previousSibling;

            $certainty = self::isPotentialDTFollowedByDD($p);

            if ($certainty === 100) {
                $dt = $dl->ownerDocument->createElement('dt');
                while ($p->firstChild) {
                    $dt->appendChild($p->firstChild);
                }

                $dd = $dl->ownerDocument->createElement('dd');
                while ($div->firstChild) {
                    $dd->appendChild($div->firstChild);
                }
                $dl->insertBefore($dd, $dl->firstChild);
                $dl->insertBefore($dt, $dl->firstChild);

                $dl->parentNode->removeChild($p);
                $dl->parentNode->removeChild($div);

                Massage_DT::postProcess($dt); // Only do this after the $dt has been added to the DOM.
            }
        }
    }

    public static function isPotentialDTFollowedByDD(?DomNode $p): int
    {

        if (!DOM::isTag($p, 'p') || Indentation::isSet($p)) {
            return 0;
        }

        /** @var DOMElement $div , $p */

        $pChild = $p->firstChild;
        while ($pChild) {
            if (
                DOM::isTag($pChild, 'br') &&
                $pChild->previousSibling && preg_match('~^[A-Z].*\.$~u', $pChild->previousSibling->textContent)
            ) {
                return 0;
            }
            $pChild = $pChild->nextSibling;
        }

        $div = $p->nextSibling;

        if (
            !DOM::isTag($div, 'div') ||
            !$div->hasAttribute('indent') ||
            !DOM::isTag($div->firstChild, ['p', 'div', 'ul'])
        ) {
            return 0;
        }

        $divIndent = Indentation::get($div);

        if ($divIndent <= 0) {
            return 0;
        }

        $pText = $p->textContent;

        if ($divIndent > 0) {

            // Exclude sentences in $p
            if (
                $pText === 'or' ||
                preg_match('~(^|\.\s)[A-Z][a-z]*(\s[a-z]+){3,}~u', $pText) ||
                preg_match('~(\s[a-z]{2,}){5,}~u', $pText) ||
                preg_match('~(\s[a-z]+){3,}[:\.]$~ui', $pText)
            ) {
                return 0;
            }

            if (preg_match('~^(--?|\+)~u', $pText)) {
                return 100;
            }

            if (!preg_match('~^\s*[\(a-z]~ui', $div->textContent)) {
                return 0;
            }

            if (preg_match('~^\S$~ui', $pText)) {
                return 100;
            }

            if (preg_match('~^[^\s]+(?:, [^\s]+)*?$~u', $pText)) {
                return 100;
            }

            if (preg_match('~^[A-Z_]{2,}[\s\(\[]~u', $pText)) {
                return 100;
            }

            if (mb_strlen($pText) < 9) {
                return 100;
            }

//        if (preg_match('~^[A-Z][a-z]* ["A-Za-z][a-z]+~u', $pText)) {
//            return 50;
//        }

            return 50;

        } else {

            if (preg_match('~^(--?|\+)~u', $pText)) {
                return 100;
            }

            return 0;

        }

    }

}
