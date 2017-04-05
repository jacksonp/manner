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
                (DOM::isTag($previousSibling, 'br'))
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
                (DOM::isTag($nextSibling, 'br'))
            )
        ) {
            $blockElement->parentNode->removeChild($nextSibling);
        }

    }

}
