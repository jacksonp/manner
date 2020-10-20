<?php

declare(strict_types=1);

namespace Manner\Massage;

use DOMElement;
use DOMNode;
use DOMXPath;
use Manner\DOM;
use Manner\Node;

class Block
{

    public static function removeAdjacentEmptyTextNodesAndBRs(?DOMNode $blockElement)
    {
        self::removePreviousEmptyTextNodesAndBRs($blockElement);
        self::removeFollowingEmptyTextNodesAndBRs($blockElement);
    }

    public static function removePreviousEmptyTextNodesAndBRs(?DOMNode $blockElement)
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

    public static function removeFollowingEmptyTextNodesAndBRs(?DOMNode $blockElement)
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

    public static function coalesceAdjacentChildDIVs(DOMElement $divsContainer)
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

    public static function coalesceAdjacentChildren(DOMXPath $xpath)
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