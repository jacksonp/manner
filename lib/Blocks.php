<?php
declare(strict_types=1);

class Blocks
{

    const TEXT_CONTAINERS = [
        'p',
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
        'a',
        'em',
        'strong',
        'small',
        'code',
        'span'
    ];

    static function getParentForText(DOMElement $parentNode): DOMElement
    {
        if (in_array($parentNode->tagName, ['body', 'section', 'div', 'dd'])) {
            $p = $parentNode->ownerDocument->createElement('p');
            $p->setAttribute('implicit', '1');
            $parentNode = $parentNode->appendChild($p);
        }
        return $parentNode;
    }

    static function getBlockContainerParent(
        DOMElement $parentNode,
        bool $superOnly = false,
        bool $ipOK = false
    ): DOMElement {
        $blockTags = ['body', 'div', 'section', 'pre'];
        if (!$superOnly) {
            $blockTags[] = 'dt';
            $blockTags[] = 'dd';
            $blockTags[] = 'th';
            $blockTags[] = 'td';
        }

        // We use <div>s to "remap" .IP temporarily so it can contain other <div>s, so treat remaps as non-blocks in
        // some cases.
        while (!in_array($parentNode->tagName, $blockTags) || (!$ipOK && $parentNode->hasAttribute('remap'))) {
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
