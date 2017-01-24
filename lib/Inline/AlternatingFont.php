<?php


class Inline_AlternatingFont implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        ?array $request = null,
        $needOneLineOnly = false
    ): ?DOMElement {

        array_shift($lines);

        if (count($request['arguments']) === 0) {
            return null; // Just skip empty requests
        }

        list ($textParent, $shouldAppend) = Blocks::getTextParent($parentNode);

        $dom = $parentNode->ownerDocument;

        Block_Text::addSpace($parentNode, $textParent, $shouldAppend);

        foreach ($request['arguments'] as $bi => $bit) {
            $requestCharIndex = $bi % 2;
            if (!isset($request['request'][$requestCharIndex])) {
                throw new Exception($lines[0] . ' command ' . $request['request'] . ' has nothing at index ' . $requestCharIndex);
            }
            if (trim($bit) === '') {
                TextContent::interpretAndAppendText($textParent, $bit);
                continue;
            }
            switch ($request['request'][$requestCharIndex]) {
                case 'R':
                    TextContent::interpretAndAppendText($textParent, $bit);
                    break;
                case 'B':
                    $strongNode = $dom->createElement('strong');
                    TextContent::interpretAndAppendText($strongNode, $bit);
                    if ($strongNode->hasContent()) {
                        $textParent->appendChild($strongNode);
                    }
                    break;
                case 'I':
                    $emNode = $dom->createElement('em');
                    TextContent::interpretAndAppendText($emNode, $bit);
                    if ($emNode->hasContent()) {
                        $textParent->appendChild($emNode);
                    }
                    break;
                default:
                    throw new Exception($lines[0] . ' command ' . $request['request'] . ' unexpected character at index ' . $requestCharIndex);
            }
        }

        if ($shouldAppend) {
            $parentNode->appendBlockIfHasContent($textParent);
        }

        return null;

    }

}
