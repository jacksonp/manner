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

        $divIndent = $div->getAttribute('indent');

        $pText = $p->textContent;

        if ($divIndent !== '') {

            // Exclude sentences in $p
            if (
                $pText === 'or' ||
                preg_match('~(^|\.\s)[A-Z][a-z]*(\s[a-z]+){3,}~u', $pText) ||
                preg_match('~(\s[a-z]{2,}){5,}~u', $pText) ||
                preg_match('~(\s[a-z]+){3,}[:\.]$~u', $pText)
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
