<?php
declare(strict_types = 1);

class Block_SY implements Block_Template
{

    /**
     * @param DOMElement $parentNode
     * @param array $lines
     * @param array $request
     * @param bool $needOneLineOnly
     * @return DOMElement|null
     * @throws Exception
     */
    static function checkAppend(
        DOMElement $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        $parentNode = Blocks::getBlockContainerParent($parentNode, true);

        if ($parentNode->tagName === 'pre') {
            $parentNode = $parentNode->parentNode;
        }

        $commandName = '';

        if (count($request['arguments']) > 0) {
            $commandName = $request['arguments'][0];
        }

        /* @var DomElement $pre */
        $pre = $parentNode->ownerDocument->createElement('pre');
        if ($commandName !== '') {
            $commandName = trim(TextContent::interpretString($commandName));
            $pre->setAttribute('class', 'synopsis');
            $pre->appendChild(new DOMText($commandName . ' '));
        }

        $pre = $parentNode->appendChild($pre);

        return $pre;

    }

}
