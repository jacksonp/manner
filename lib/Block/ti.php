<?php

/**
 * Class Block_ti
 * .ti ±N: Temporary indent next line (default scaling indicator m).
 */
class Block_ti implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        $className = 'indent-ti';
        $indentVal = null;
        if (
            count($request['arguments']) &&
            $normalizedVal = Roff_Unit::normalize($request['arguments'][0]) // note this filters out 0s
        ) {
            $indentVal = $normalizedVal;
            if ($indentVal) {
                $className .= '-' . $indentVal;
            }
        }

        if ($parentNode->tagName === 'p' && $parentNode->getAttribute('class') === $className) {
            if ($parentNode->lastChild->nodeType !== XML_ELEMENT_NODE || $parentNode->lastChild->tagName !== 'br') {
                Inline_VerticalSpace::addBR($parentNode);
            }
            return $parentNode;
        }

        $parentNode = Blocks::getBlockContainerParent($parentNode);
        $p          = $parentNode->ownerDocument->createElement('p');
        $p          = $parentNode->appendChild($p);
        $p->setAttribute('class', $className);
        return $p;

        /*
        if (!count($lines)) {
            return null;
        }

        $blockLines = [];
        while ($nextRequest = Request::getLine($lines)) {
            if ($nextRequest['request'] === 'ti') {
                // Could be a change in indentation, just add a break for now
                array_shift($lines);
                $blockLines[] = '.br';
                continue;
            } elseif (Blocks::lineEndsBlock($nextRequest, $lines) || $lines[0] === '') {
                // This check has to come after .ti check, as .ti is otherwise a block-ender.
                break;
            } else {
                $blockLines[] = array_shift($lines);
            }
        }

        if ($parentNode->tagName === 'p') {
            $parentNode = $parentNode->parentNode;
        }

        $block = $dom->createElement('blockquote');
        Roff::parse($block, $blockLines);
        $parentNode->appendBlockIfHasContent($block);

        return $parentNode;
        */

    }


}
