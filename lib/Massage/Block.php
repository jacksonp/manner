<?php
declare(strict_types=1);

class Massage_Block
{

    static function removeAdjacentEmptyTextNodesAndBRs(?DOMElement $blockElement)
    {
        self::removePreviousEmptyTextNodesAndBRs($blockElement);
        self::removeFollowingEmptyTextNodesAndBRs($blockElement);
    }

    static function removePreviousEmptyTextNodesAndBRs(?DOMElement $blockElement)
    {

        if (is_null($blockElement)) {
            return;
        }

        while (
            $previousSibling = $blockElement->previousSibling and
            (
                Node::isTextAndEmpty($previousSibling) ||
                ($blockElement->tagName !== 'div' && DOM::isTag($previousSibling, 'br'))
            )
        ) {
            $blockElement->parentNode->removeChild($previousSibling);
        }

    }

    static function removeFollowingEmptyTextNodesAndBRs(?DOMElement $blockElement)
    {

        if (is_null($blockElement)) {
            return;
        }

        while (
            $nextSibling = $blockElement->nextSibling and
            (
                Node::isTextAndEmpty($nextSibling) ||
                ($blockElement->tagName !== 'div' && DOM::isTag($nextSibling, 'br'))
            )
        ) {
            $blockElement->parentNode->removeChild($nextSibling);
        }

    }

}
