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

namespace Manner\Massage;

use DOMElement;
use DOMException;
use DOMNode;
use DOMXPath;
use Manner\DOM;
use Manner\Node;

class Block
{

    public static function removeAdjacentEmptyTextNodesAndBRs(?DOMNode $blockElement): void
    {
        self::removePreviousEmptyTextNodesAndBRs($blockElement);
        self::removeFollowingEmptyTextNodesAndBRs($blockElement);
    }

    public static function removePreviousEmptyTextNodesAndBRs(?DOMNode $blockElement): void
    {
        if (is_null($blockElement)) {
            return;
        }

        while (
          $previousSibling = $blockElement->previousSibling and
          (
            Node::isTextAndEmpty($previousSibling) ||
            DOM::isTag($previousSibling, 'br')
          )
        ) {
            $blockElement->parentNode->removeChild($previousSibling);
        }
    }

    public static function removeFollowingEmptyTextNodesAndBRs(?DOMNode $blockElement): void
    {
        if (is_null($blockElement)) {
            return;
        }

        while (
          $nextSibling = $blockElement->nextSibling and
          (
            Node::isTextAndEmpty($nextSibling) ||
            DOM::isTag($nextSibling, 'br')
          )
        ) {
            $blockElement->parentNode->removeChild($nextSibling);
        }
    }

    /**
     * @throws DOMException
     */
    public static function coalesceAdjacentChildDIVs(DOMElement $divsContainer): void
    {
        $child = $divsContainer->firstChild;

        while ($child) {
            if (DOM::isTag($child, 'div')) {
                $nextSibling  = $child->nextSibling;
                $brsInBetween = [];

                while ($nextSibling && DOM::isTag($nextSibling, 'br')) {
                    $brsInBetween[] = $nextSibling;
                    $nextSibling    = $nextSibling->nextSibling;
                }

                if (
                  DOM::isTag($nextSibling, 'div') &&
                  $child->getAttribute('indent') === $nextSibling->getAttribute('indent') &&
                  $nextSibling->childNodes->length === 1
                ) {
                    if ($child->childNodes->length === 1 &&
                      DOM::isTag($child->firstChild, 'p') &&
                      DOM::isTag($nextSibling->firstChild, 'p')
//                      &&
//                      $child->firstChild->hasAttribute('implicit') &&
//                      $nextSibling->firstChild->hasAttribute('implicit')
                    ) {
                        if (count($brsInBetween) < 2) {
                            $child->firstChild->appendChild($divsContainer->ownerDocument->createElement('br'));
                        }
                        foreach ($brsInBetween as $brInBetween) {
                            $child->firstChild->appendChild($brInBetween);
                        }
                        while ($nextSibling->firstChild->firstChild) {
                            $child->firstChild->appendChild($nextSibling->firstChild->firstChild);
                        }
                        $divsContainer->removeChild($nextSibling);
                        continue;
                    } else {
                        foreach ($brsInBetween as $brInBetween) {
                            $child->appendChild($brInBetween);
                        }
                        $child->appendChild($nextSibling->firstChild);
                        $divsContainer->removeChild($nextSibling);
                        continue;
                    }
                }
            }

            $child = $child->nextSibling;
        }
    }

    public static function coalesceAdjacentChildren(DOMXPath $xpath): void
    {
        $tagsToMerge = ['ul'];

        $ulParents = $xpath->query('//section | //dd | //li | //td | //div');

        foreach ($ulParents as $ulParent) {
            $child = $ulParent->firstChild;

            while ($child) {
                if (
                  DOM::isTag($child, $tagsToMerge) &&
                  $child->nextSibling &&
                  DOM::isTag($child->nextSibling, $child->tagName) &&
                  $child->getAttribute('class') === $child->nextSibling->getAttribute('class')
                ) {
                    Dom::extractContents($child, $child->nextSibling);
                    Node::remove($child->nextSibling);
                } else {
                    $child = $child->nextSibling;
                }
            }
        }
    }

}