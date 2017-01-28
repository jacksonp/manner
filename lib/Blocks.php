<?php


class Blocks
{

    const TEXT_CONTAINERS = [
        'p',
        'blockquote',
        'dt',
        'strong',
        'em',
        'small',
        'code',
        'td',
        'th',
        'pre',
        'a',
        'h2',
        'h3',
    ];

    static function lineEndsBlock(array $request, array &$lines)
    {
        if ($request['request'] && Man::instance()->requestStartsBlock($request['request'])) {
            return true;
        }
        return Block_TabTable::isStart($lines);
    }

    static function _maybeLastEmptyChildWaitingForText(DOMElement $parentNode)
    {
        if (
            $parentNode->lastChild &&
            $parentNode->lastChild->nodeType === XML_ELEMENT_NODE &&
            in_array($parentNode->lastChild->tagName, ['em', 'strong', 'small']) &&
            $parentNode->lastChild->textContent === ''
        ) {
            if ($parentNode->lastChild->lastChild &&
                $parentNode->lastChild->lastChild->nodeType === XML_ELEMENT_NODE &&
                in_array($parentNode->lastChild->lastChild->tagName, ['em', 'strong', 'small'])
            ) {
                // bash.1:
                // .SM
                // .B
                // ARITHMETIC EVALUATION
                return [$parentNode->lastChild->lastChild, false, true];
            } else {
                return [$parentNode->lastChild, false, true];
            }
        } else {
            return [$parentNode, false, $parentNode->tagName === 'dt'];
        }
    }

    static function getTextParent(DOMElement $parentNode)
    {
        if (in_array($parentNode->tagName, Blocks::TEXT_CONTAINERS)) {
            return self::_maybeLastEmptyChildWaitingForText($parentNode);
        } else {
            $parentNodeLastBlock = $parentNode->getLastBlock();
            if (is_null($parentNodeLastBlock) ||
                in_array($parentNodeLastBlock->tagName, ['div', 'pre', 'code', 'table', 'h2', 'h3', 'dl', 'blockquote'])
            ) {
                return [$parentNode->ownerDocument->createElement('p'), true, false];
            } else {
                return self::_maybeLastEmptyChildWaitingForText($parentNodeLastBlock);
            }
        }
    }

}
