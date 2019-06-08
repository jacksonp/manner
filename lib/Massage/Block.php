<?php
declare(strict_types=1);

class Massage_Block
{

    static function removeAdjacentEmptyTextNodesAndBRs(?DOMNode $blockElement)
    {
        self::removePreviousEmptyTextNodesAndBRs($blockElement);
        self::removeFollowingEmptyTextNodesAndBRs($blockElement);
    }

    static function removePreviousEmptyTextNodesAndBRs(?DOMNode $blockElement)
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

    static function removeFollowingEmptyTextNodesAndBRs(?DOMNode $blockElement)
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

    static function coalesceAdjacentChildDIVs(DOMElement $blockElement)
    {

        $child = $blockElement->firstChild;

        while ($child) {

            if (
              DOM::isTag($child, 'div') &&
              $child->nextSibling &&
              DOM::isTag($child->nextSibling, 'div') &&
              $child->getAttribute('class') === $child->nextSibling->getAttribute('class') &&
              $child->nextSibling->childNodes->length === 1
            ) {

                if ($child->childNodes->length === 1 &&
                  DOM::isTag($child->firstChild, 'p') &&
                  DOM::isTag($child->nextSibling->firstChild, 'p')
                ) {

                    $child->firstChild->appendChild($blockElement->ownerDocument->createElement('br'));
                    while ($child->nextSibling->firstChild->firstChild) {
                        $child->firstChild->appendChild($child->nextSibling->firstChild->firstChild);
                    }
                    $blockElement->removeChild($child->nextSibling);
                    continue;
                } else {
                    $child->appendChild($child->nextSibling->firstChild);
                    $blockElement->removeChild($child->nextSibling);
                    continue;
                }

            }

            $child = $child->nextSibling;

        }

    }

    static function coalesceAdjacentChildren(DOMXPath $xpath)
    {

        $tagsToMerge = ['ul'];

        $ulParents = $xpath->query('//section | //dd | //li | //td');

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