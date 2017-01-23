<?php


class Inline_FontOneInputLine implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        ?array $request = null,
        $needOneLineOnly = false
    ): bool {

        array_shift($lines);

        if (count($request['arguments']) === 0 && count($lines) && Request::getLine($lines, 0)['request'] === 'IP') {
            return true; // TODO: not sure how to handle this, just skip the font setting for now.
        }

        $dom = $parentNode->ownerDocument;

        list ($textParent, $shouldAppend) = Blocks::getTextParent($parentNode);

        switch ($request['request']) {
            case 'R':
                $appendToParentNode = false;
                $innerNode          = $textParent;
                break;
            case 'I':
                $appendToParentNode = $dom->createElement('em');
                $innerNode          = $appendToParentNode;
                break;
            case 'B':
                if ($textParent->tagName === 'strong') {
                    $appendToParentNode = false;
                    $innerNode          = $textParent;
                } else {
                    $appendToParentNode = $dom->createElement('strong');
                    $innerNode          = $appendToParentNode;
                }
                break;
            case 'SB':
                $appendToParentNode = $dom->createElement('small');
                $innerNode          = $appendToParentNode->appendChild($dom->createElement('strong'));
                break;
            case 'SM':
                $appendToParentNode = $dom->createElement('small');
                $innerNode          = $appendToParentNode;
                break;
            default:
                throw new Exception('switch is exhaustive.');
        }

        Block_Text::addSpace($parentNode, $textParent, $shouldAppend);

        if (count($request['arguments']) === 0) {
            if (count($lines) === 0) {
                return true;
            }

            $callerArgs = null;
            Roff::parse($innerNode, $lines, $callerArgs, true);

        } else {
            TextContent::interpretAndAppendText($innerNode, implode(' ', $request['arguments']));
        }

        if ($appendToParentNode) {
            $textParent->appendChild($appendToParentNode);
        }

        if ($shouldAppend) {
            $parentNode->appendBlockIfHasContent($textParent);
        }

        return true;

    }

}
