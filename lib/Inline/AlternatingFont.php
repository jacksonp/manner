<?php
declare(strict_types = 1);

class Inline_AlternatingFont implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        $parentNode = Blocks::getParentForText($parentNode);

        Block_Text::addSpace($parentNode);

        foreach ($request['arguments'] as $bi => $bit) {
            $requestCharIndex = $bi % 2;
            if (!isset($request['request'][$requestCharIndex])) {
                throw new Exception($lines[0] . ' command ' . $request['request'] . ' has nothing at index ' . $requestCharIndex);
            }
            if (trim($bit) === '') {
                TextContent::interpretAndAppendText($parentNode, $bit);
                continue;
            }
            switch ($request['request'][$requestCharIndex]) {
                case 'R':
                    TextContent::interpretAndAppendText($parentNode, $bit);
                    break;
                case 'B':
                    $strongNode = $parentNode->appendChild($parentNode->ownerDocument->createElement('strong'));
                    TextContent::interpretAndAppendText($strongNode, $bit);
                    break;
                case 'I':
                    $emNode = $parentNode->appendChild($parentNode->ownerDocument->createElement('em'));
                    TextContent::interpretAndAppendText($emNode, $bit);
                    break;
                default:
                    throw new Exception($lines[0] . ' command ' . $request['request'] . ' unexpected character at index ' . $requestCharIndex);
            }
        }

        return $parentNode;

    }

}
