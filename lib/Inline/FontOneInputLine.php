<?php


class Inline_FontOneInputLine
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i, array $arguments, $request)
    {

        if (count($arguments) === 0 && $i < count($lines) - 1 && Request::getLine($lines, $i + 1)['request'] === 'IP') {
            return $i; // TODO: not sure how to handle this, just skip the font setting for now.
        }

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;

        list ($textParent, $shouldAppend) = Blocks::getTextParent($parentNode);

        switch ($request) {
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

        if (count($arguments) === 0) {
            if ($i === $numLines - 1) {
                return $i;
            }
            if ($appendToParentNode) {
                $textParent->appendChild($appendToParentNode);
            }
            if ($shouldAppend) {
                $parentNode->appendChild($textParent);
            }

            return $i;

            /*
            ++$i;
            if ($lines[$i] === '') {
                return $i;
            }
            $result = Block_Text::getNextInputLine($lines, $i);
            $i      = $result['i'];
            if (count($result['lines']) === 0) {
                return $i;
            }
            $blockLines = $result['lines'];
            Blocks::trim($blockLines);
            Roff::parse($innerNode, $blockLines);
            */
        } else {
            TextContent::interpretAndAppendText($innerNode, implode(' ', $arguments));
        }

        if ($appendToParentNode) {
            $textParent->appendChild($appendToParentNode);
        }

        if ($shouldAppend) {
            $parentNode->appendBlockIfHasContent($textParent);
        }

        return $i;

    }

}
