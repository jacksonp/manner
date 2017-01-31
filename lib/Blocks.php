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

    const INLINE_ELEMENTS = [
        'em',
        'strong',
        'small',
        'code',
        'span'
    ];

    static function getParentForText(DOMElement $parentNode): DOMElement
    {
        if (in_array($parentNode->tagName, ['body', 'section', 'div', 'dd'])) {
            $parentNode = $parentNode->appendChild($parentNode->ownerDocument->createElement('p'));
        }
        return $parentNode;
    }

    static function getBlockContainerParent(DOMElement $parentNode, bool $superOnly = false): DOMElement
    {
        $blockTags = ['body', 'div', 'section', 'pre'];
        if (!$superOnly) {
            $blockTags[] = 'dd';
            $blockTags[] = 'blockquote';
            $blockTags[] = 'th';
            $blockTags[] = 'td';
        }

        while (!in_array($parentNode->tagName, $blockTags)) {
            $parentNode = $parentNode->parentNode;
            if (!$parentNode) {
                throw new Exception('No more parents.');
            }
        }
        return $parentNode;
    }

    static function getNonInlineParent(DOMElement $parentNode): DOMElement
    {
        while (in_array($parentNode->tagName, self::INLINE_ELEMENTS)) {
            $parentNode = $parentNode->parentNode;
        }
        return $parentNode;
    }

    static function lineEndsBlock(array $request, array &$lines)
    {
        if ($request['request'] && Man::instance()->requestStartsBlock($request['request'])) {
            return true;
        }
        return Block_TabTable::isStart($lines);
    }

}
