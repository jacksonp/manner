<?php

declare(strict_types=1);

namespace Manner\Massage;

use DOMElement;
use DOMNode;
use DOMXPath;
use Manner\DOM;
use Manner\Indentation;

class DL
{

    /**
     * @param DOMXPath $xpath
     */
    public static function mergeAdjacentAndConvertLoneDD(DOMXPath $xpath): void
    {
        $dls = $xpath->query('//dl');
        foreach ($dls as $dl) {
            while (DOM::isTag($dl->nextSibling, 'dl')) {
                DOM::extractContents($dl, $dl->nextSibling);
                $dl->parentNode->removeChild($dl->nextSibling);
            }

            if ($dl->childNodes->length === 1 && $dl->firstChild->tagName === 'dd') {
                $div = $dl->ownerDocument->createElement('div');
                DOM::extractContents($div, $dl->firstChild);
                Indentation::addElIndent($div, $dl->firstChild);
                $dl->parentNode->insertBefore($div, $dl);
                $dl->parentNode->removeChild($dl);
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

                DT::postProcess($dt); // Only do this after the $dt has been added to the DOM.
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

        if (is_null($div) || !Indentation::isSet($div)) {
            return 0;
        }

        if (DOM::isTag($div, 'p')) {
            $okCertainty = 50;
        } elseif (DOM::isTag($div, 'div') && DOM::isTag($div->firstChild, ['p', 'div', 'ul'])) {
            $okCertainty = 100;
        } else {
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
              preg_match('~(\s[a-z]+){3,}[:.]$~ui', $pText)
            ) {
                return 0;
            }

            if (preg_match('~^(--?|\+)~u', $pText)) {
                return $okCertainty;
            }

            if (!preg_match('~^\s*[(a-z]~ui', $div->textContent)) {
                return 0;
            }

            if (preg_match('~^\S$~ui', $pText)) {
                return $okCertainty;
            }

            if (preg_match('~^[^\s]+(?:, [^\s]+)*?$~u', $pText)) {
                return $okCertainty;
            }

            if (preg_match('~^[A-Z_]{2,}[\s(\[]~u', $pText)) {
                return $okCertainty;
            }

            if (mb_strlen($pText) < 9) {
                return $okCertainty;
            }

//        if (preg_match('~^[A-Z][a-z]* ["A-Za-z][a-z]+~u', $pText)) {
//            return 50;
//        }

            return 50;
        } else {
            if (preg_match('~^(--?|\+)~u', $pText)) {
                return $okCertainty;
            }

            return 0;
        }
    }

    public static function CreateOLs(DOMXpath $xpath)
    {
        $dls = $xpath->query('//dl');
        foreach ($dls as $dl) {
            $i  = 1;
            $dt = $dl->firstChild;
            while (Dom::isTag($dt, 'dt')) {
                $dtStr = trim($dt->textContent, " \t\n\r\0\x0B." . html_entity_decode('&nbsp;'));
                // !is_numeric($dtStr) check needed because: "1 foo" == 1
                if (!is_numeric($dtStr) || $dtStr != $i) {
                    continue 2;
                }
                $dt = $dt->nextSibling;
                if ($dt) {
                    $dt = $dt->nextSibling;
                }
                ++$i;
            }
            if ($i > 2) {
                // If we get here, <dl> should be an <ol>
                $ol = $dl->ownerDocument->createElement('ol');
                $dl->parentNode->insertBefore($ol, $dl);
                $dds = $xpath->query('./dd', $dl);
                foreach ($dds as $dd) {
                    $li = $dd->ownerDocument->createElement('li');
                    $ol->appendChild($li);
                    Dom::extractContents($li, $dd);
                }
                $dl->parentNode->removeChild($dl);
                HTMLList::removeLonePs($ol);
            }
        }
    }

}
