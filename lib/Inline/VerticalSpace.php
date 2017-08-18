<?php
declare(strict_types = 1);

class Inline_VerticalSpace implements Block_Template
{

    static function addBR(DOMElement $parentNode)
    {
        $prevBRs   = 0;
        $nodeCheck = $parentNode->lastChild;
        while ($nodeCheck) {
            if ($nodeCheck instanceof DOMElement && $nodeCheck->tagName === 'br') {
                ++$prevBRs;
            } else {
                break;
            }
            $nodeCheck = $nodeCheck->previousSibling;
        }
        if ($prevBRs < 2) {
            $parentNode->appendChild($parentNode->ownerDocument->createElement('br'));
        }
    }

    static function check(string $string)
    {
        return in_array(Request::peepAt($string)['name'], ['br', 'sp', 'ne']);
    }

    static function checkAppend(
        DOMElement $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        /*if (count($request['arguments']) && $request['arguments'][0] === '-1') {
            if ($parentNode->lastChild instanceof DOMElement && $parentNode->lastChild->tagName === 'br') {
                $parentNode->removeChild($parentNode->lastChild);
            }
        } else*/
            if (
            !($parentNode->lastChild instanceof DOMElement) ||
            $parentNode->lastChild->tagName !== 'pre'
        ) {

            self::addBR($parentNode);
            if (in_array($request['request'], ['sp', 'ne'])) {
                self::addBR($parentNode);
            }

        }

        return null;

    }

}
