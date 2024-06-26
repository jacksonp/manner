<?php

/**
 * manner: convert troff man pages to semantic HTML
 * Copyright (C) 2024  Jackson Pauls
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Manner;

use DOMElement;
use DOMException;
use DOMNode;
use DOMText;
use Exception;
use Manner\Massage\DL;

class DOM
{

    public static function extractContents(DOMElement $target, DOMNode $source): void
    {
        if ($source instanceof DOMText) {
            $target->appendChild($source);
        } else {
            while ($child = $source->firstChild) {
                $target->appendChild($child);
            }
        }
    }

    public static function isInlineElement(?DOMNode $node): bool
    {
        return self::isTag($node, Blocks::INLINE_ELEMENTS);
    }

    public static function isTextNode(?DOMNode $node): bool
    {
        return $node && $node->nodeType === XML_TEXT_NODE;
    }

    public static function isElementNode(?DOMNode $node): bool
    {
        return $node && $node->nodeType === XML_ELEMENT_NODE;
    }

    public static function isTag(?DOMNode $node, $tag): bool
    {
        if (!self::isElementNode($node)) {
            return false;
        }

        /* @var DomElement $node */
        return in_array($node->tagName, (array)$tag);
    }

    /*
    private static function hasImmediateChild(DOMElement $node, string $tag): bool
    {
        $child = $node->firstChild;
        while ($child) {
            if ($child->nodeType === XML_ELEMENT_NODE && $child->tagName === $tag) {
                return true;
            }
            $child = $child->nextSibling;
        }
        return false;
    }
    */

    /**
     * @param DOMElement $element
     * @return DOMNode|null The element we should look at next.
     * @throws DOMException
     * @throws Exception
     */
    private static function massageNode(DOMElement $element): ?DOMNode
    {
        $myTag = $element->tagName;
        $doc   = $element->ownerDocument;

        if ($myTag === 'pre') {
            if ($element->lastChild && $element->lastChild->nodeType === XML_ELEMENT_NODE) {
                $codeNode = $element->lastChild;
                if ($codeNode->lastChild && $codeNode->lastChild->nodeType === XML_TEXT_NODE) {
                    $codeNode->lastChild->textContent = rtrim($codeNode->lastChild->textContent, "\n");
                }
            }
        }

        while (
          $firstChild = $element->firstChild and
          ($myTag !== 'pre' && Node::isTextAndEmpty($firstChild)) || self::isTag($firstChild, 'br')
        ) {
            $element->removeChild($firstChild);
        }

        Massage\Block::removeAdjacentEmptyTextNodesAndBRs($element);

        while ($lastChild = $element->lastChild and (Node::isTextAndEmpty($lastChild))) {
            $element->removeChild($lastChild);
        }

        // This takes out rows with an <hr>, which we don't want.
//        if ($myTag === 'tr' && trim($element->textContent) === '') {
//            $nextSibling = $element->nextSibling;
//            $element->parentNode->removeChild($element);
//            return $nextSibling;
//        }

        if ($myTag === 'pre' && in_array($element->parentNode->tagName, ['pre'])) {
            $nextSibling = $element->nextSibling;
            Node::remove($element);

            return $nextSibling;
        }

        if ($myTag === 'div') {
            $firstChild = $element->firstChild;

            if ($element->childNodes->length === 1 && $element->firstChild->nodeType === XML_ELEMENT_NODE) {
                if ($firstChild->tagName === 'div') {
                    Indentation::addElIndent($element, $firstChild);
                    Node::remove($firstChild);
                    // NB: we carry on processing rather than returning here.
                }
            }

            if (!$firstChild) {
                $previousSibling = $element->previousSibling;
                $nextSibling     = $element->nextSibling;
                $element->parentNode->removeChild($element);
                if ($previousSibling) {
                    Massage\Block::removeFollowingEmptyTextNodesAndBRs($previousSibling);

                    return $previousSibling->nextSibling;
                } else {
                    return $nextSibling;
                }
            }

            if (
              $element->childNodes->length === 1 &&
              self::isTag($firstChild, 'p') &&
              preg_match('~^\t~', $element->textContent)
            ) {
                /* @var DomElement $pre */
                $pre = $element->parentNode->insertBefore($doc->createElement('pre'), $element);
                self::extractContents($pre, $firstChild);
                $element->parentNode->removeChild($element);

                return $pre->nextSibling;
            }

            if ($element->parentNode->tagName === 'pre') {
                if ($element->parentNode->childNodes->length === 1) {
                    Indentation::addElIndent($element->parentNode, $element);
                }
                $nextSibling = $element->nextSibling;
                Node::remove($element);

                return $nextSibling;
            }

            if (!Indentation::isSet($element)) {
                if (in_array($element->parentNode->tagName, ['dd'])) {
                    $nextSibling = Massage\DIV::getNextNonBRNode($element);
                    Node::remove($element);

                    return $nextSibling;
                }
            }
        }

        if (
          self::isTag($element->previousSibling, 'dl') &&
          self::isTag($element->previousSibling->lastChild, 'dd')
        ) {
            $elIndent = Indentation::get($element);
            $ddIndent = Indentation::get($element->previousSibling->lastChild);

            if ($elIndent === $ddIndent) {
                $nextSibling = $element->nextSibling;
                if ($myTag === 'div') {
                    self::extractContents($element->previousSibling->lastChild, $element);
                    $element->parentNode->removeChild($element);
                } else {
                    Indentation::remove($element);
                    $element->previousSibling->lastChild->appendChild($element);
                }

                return $nextSibling;
            }

            if ($elIndent > $ddIndent) {
                $nextSibling = $element->nextSibling;
                Indentation::set($element, $elIndent - $ddIndent);
                $element->previousSibling->lastChild->appendChild($element);

                return $nextSibling;
            }
        }

        if (!in_array($myTag, ['th', 'td']) && !$element->hasChildNodes()) {
            $nextSibling = $element->nextSibling;
            $element->parentNode->removeChild($element);

            return $nextSibling;
        }

        if ($myTag === 'table') {
            if ($element->firstChild) {
                $tr          = $element->firstChild;
                $convertToTH = true;
                foreach ($tr->childNodes as $td) {
                    if (!Node::hasClass($td, 'bold')) {
                        $convertToTH = false;
                    }
                }
                if ($convertToTH) {
                    $child = $tr->firstChild;
                    while ($child) {
                        $td    = $child;
                        $child = $child->nextSibling;
                        Node::removeClass($td, 'bold');
                        Node::changeTag($td, 'th');
                    }
                }
            }
        }

        if ($myTag === 'dl') {
            $everyChildIsDT = true;
            foreach ($element->childNodes as $elementChild) {
                if (!self::isTag($elementChild, 'dt')) {
                    $everyChildIsDT = false;
                    break;
                }
            }

            if ($everyChildIsDT) {
                for ($j = 0; $j < $element->childNodes->length; ++$j) {
                    $strayDT = $element->childNodes->item($j);
                    $p       = $doc->createElement('p');
                    while ($strayDT->firstChild) {
                        $p->appendChild($strayDT->firstChild);
                    }
                    $element->parentNode->insertBefore($p, $element);
                }
                $nextSibling = $element->nextSibling;
                $element->parentNode->removeChild($element);

                return $nextSibling;
            }

            while ($element->lastChild && $element->lastChild->tagName === 'dt') {
                $p       = $doc->createElement('p');
                $strayDT = $element->lastChild;
                while ($strayDT->firstChild) {
                    $p->appendChild($strayDT->firstChild);
                }
                $element->removeChild($strayDT);
                $element->parentNode->insertBefore($p, $element->nextSibling);
            }
        }

        if ($myTag === 'dt') {
            Massage\DT::postProcess($element);
        }

        return $element->nextSibling;
    }

    /**
     * @param DOMElement $element
     * @return DOMNode|null
     * @throws Exception
     */
    public static function massage(DOMElement $element): ?DOMNode
    {
        $child = $element->firstChild;
        while ($child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $myTag = $child->tagName;

                if (in_array($myTag, ['section', 'dd', 'div', 'td'])) {
                    Massage\Block::coalesceAdjacentChildDIVs($child);
                }

                if (in_array($myTag, ['section', 'p', 'dl', 'dt', 'dd', 'div', 'pre', 'table', 'tr', 'th', 'td'])) {
                    $child = self::massage($child);
                    continue;
                }

                if (in_array($myTag, ['h2', 'h3'])) {
                    while (
                      $nextSibling = $child->nextSibling and
                      (Node::isTextAndEmpty($nextSibling) || self::isTag($nextSibling, 'br'))
                    ) {
                        $element->removeChild($nextSibling);
                    }
                }
            }

            // NB: we don't want to remove the space in the <em> in cases e.g.:
            // <strong>e</strong><em> </em><strong>f</strong>
            if (self::isInlineElement($child)) {
                if (
                  $child->tagName !== 'code' &&
                  $child->firstChild &&
                  $child->firstChild->nodeType == XML_TEXT_NODE &&
                  preg_match('~^(\s+)(.*?)$~u', $child->firstChild->textContent, $matches)
                ) {
                    $child->replaceChild($child->ownerDocument->createTextNode($matches[2]), $child->firstChild);
                    $child->parentNode->insertBefore($child->ownerDocument->createTextNode($matches[1]), $child);
                }

                if (
                  $child->lastChild &&
                  $child->lastChild->nodeType == XML_TEXT_NODE &&
                  preg_match('~^(.*?)(\s+)$~u', $child->lastChild->textContent, $matches)
                ) {
                    $child->replaceChild($child->ownerDocument->createTextNode($matches[1]), $child->lastChild);
                    $child->parentNode->insertBefore(
                      $child->ownerDocument->createTextNode($matches[2]),
                      $child->nextSibling
                    );
                }

                if ($child->textContent === '') {
                    $nextSibling = $child->nextSibling;
                    $child->parentNode->removeChild($child);
                    $child = $nextSibling;
                    continue;
                }

                // Hack for cases like this: <dt><strong>-</strong><strong>-eps-file</strong>=&lt;<em>file</em>&gt;</dt>
                if ($child->textContent === '-') {
                    if (
                      $child->firstChild instanceof DOMText &&
                      $child->nextSibling instanceof DOMElement &&
                      $child->nextSibling->childNodes->length === 1 &&
                      $child->nextSibling->firstChild instanceof DOMText &&
                      $child->tagName === $child->nextSibling->tagName
                    ) {
                        $child->nextSibling->firstChild->textContent = '-' . $child->nextSibling->firstChild->textContent;
                        $nextSibling                                 = $child->nextSibling;
                        $child->parentNode->removeChild($child);
                        $child = $nextSibling;
                        continue;
                    }
                }
            }

            $child = $child->nextSibling;
        }

        $child = $element->firstChild;
        while ($child) {
            $certainty = Massage\DL::isPotentialDTFollowedByDD($child);

            if ($certainty === 0) {
                $go = false;
            } elseif ($certainty > 50) {
                $go = true;
            } else {
                $go        = false;
                $nextP     = $child->nextSibling->nextSibling;
                $iteration = 0;
                while ($nextP) {
                    $followingCertainty = Massage\DL::isPotentialDTFollowedByDD($nextP);
                    if ($followingCertainty === 100) {
                        $go = true;
                        break;
                    }
                    if ($followingCertainty === 0) {
                        break;
                    }
                    ++$iteration;
                    if ($iteration > 2) {
                        $go = true;
                        break;
                    }
                    $nextP = $nextP->nextSibling->nextSibling;
                }
            }

            if ($go) {
                $dl = $element->ownerDocument->createElement('dl');
                $element->insertBefore($dl, $child);
                while (Massage\DL::isPotentialDTFollowedByDD($dl->nextSibling)) {
                    $dt = $element->ownerDocument->createElement('dt');
                    DOM::extractContents($dt, $dl->nextSibling);
                    /** @var DOMElement $dt */
                    $dt = $dl->appendChild($dt);
                    Massage\DT::postProcess($dt);
                    $element->removeChild($dl->nextSibling);

                    $dd = $element->ownerDocument->createElement('dd');
                    Indentation::addElIndent($dd, $dl->nextSibling);
                    if (self::isTag($dl->nextSibling, 'div')) {
                        DOM::extractContents($dd, $dl->nextSibling);
                        $element->removeChild($dl->nextSibling);
                    } else {
                        Indentation::remove($dl->nextSibling);
                        $dd->appendChild($dl->nextSibling);
                    }
                    $dl->appendChild($dd);

                    $ddIndent = Indentation::get($dl) + Indentation::get($dd);

                    while (
                      Dom::isElementNode($dl->nextSibling) &&
                      Indentation::get($dl->nextSibling) >= $ddIndent
                    ) {
                        if (Indentation::get($dl->nextSibling) === $ddIndent) {
                            Indentation::remove($dl->nextSibling);
                        } else {
                            Indentation::subtract($dl->nextSibling, $ddIndent);
                        }
                        $dd->appendChild($dl->nextSibling);
                    }
                }
                $child = $dl->nextSibling;
            } else {
                $child = $child->nextSibling;
            }
        }

        $child = $element->firstChild;
        while ($child) {
            if (self::isTag($child, 'dl')) {
                $ul = DL::MaybeChangeToUL($child);
                if ($ul) {
                    $child = $ul->nextSibling;
                    continue;
                }
            }
            $child = $child->nextSibling;
        }

        $child = $element->firstChild;
        while ($child) {
            if (self::isTag($child, 'div')) {
                $child = Massage\DIV::postProcess($child);
            } else {
                $child = $child->nextSibling;
            }
        }

        return self::massageNode($element);
    }


}
