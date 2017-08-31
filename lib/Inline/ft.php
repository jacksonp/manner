<?php
declare(strict_types=1);

class Inline_ft implements Block_Template
{

    private static function getNonFontParent(DOMElement $parentNode): DOMElement
    {
        while (in_array($parentNode->tagName, Blocks::INLINE_ELEMENTS)) {
            $parentNode = $parentNode->parentNode;
        }
        return $parentNode;
    }

    static function checkAppend(
        DOMElement $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);
        $man = Man::instance();

        // Return to previous font. Same as \f[] or \fP.
        if (count($request['arguments']) === 0 || $request['arguments'][0] === 'P') {
            $man->popFont();
        } else {
            $man->pushFont($request['arguments'][0]);
        }

        return null;
        /*

        $fontAbbreviation = $request['arguments'][0];

        // Skip stray regular font settings:
        if (in_array($fontAbbreviation, ['0', '1', 'R', 'P', 'CR', 'AR'])) {
            return self::getNonFontParent($parentNode);
        }

        $parentNode = Blocks::getParentForText($parentNode);

        $dom = $parentNode->ownerDocument;

        switch ($fontAbbreviation) {
            case 'I':
            case '2':
            case 'AI':
                if (Node::isOrInTag($parentNode, 'em')) {
                    return null;
                }
                $node = $dom->createElement('em');
                break;
            case 'B':
            case '3':
                if (Node::isOrInTag($parentNode, 'strong')) {
                    return null;
                }
                $node = $dom->createElement('strong');
                break;
            case 'C':
            case 'CW':
            case '4':
            case '5':
            case 'tt':
            case 'CB':
            case 'CS': // e.g. pmwebd.1
                if (Node::isOrInTag($parentNode, 'code')) {
                    return null;
                }
                $node = $dom->createElement('code');
                break;
            default:
                $node = $dom->createElement('span');
                $node->setAttribute('class', 'font-' . $fontAbbreviation);
        }

        $node = $parentNode->appendChild($node);

        return $node;

        */

    }

}
