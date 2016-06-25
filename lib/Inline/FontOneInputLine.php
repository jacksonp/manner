<?php


class Inline_FontOneInputLine
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        if (!preg_match('~^\.(R|I|B|SB|SM)(\s.*)?$~u', $lines[$i], $matches)) {
            return false;
        }

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;

        switch ($matches[1]) {
            case 'R':
                $appendToParentNode = false;
                $innerNode          = $parentNode;
                break;
            case 'I':
                $appendToParentNode = $dom->createElement('em');
                $innerNode          = $appendToParentNode;
                break;
            case 'B':
                if ($parentNode->tagName === 'strong') {
                    $appendToParentNode = false;
                    $innerNode          = $parentNode;
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

        $arguments = Macro::parseArgString(@$matches[2]);

        if (is_null($arguments)) {
            if ($i === $numLines - 1) {
                return $i;
            }
            $nextLine = $lines[++$i];
            if ($nextLine === '') {
                return $i;
            }
            if (in_array($nextLine, ['.B', '.I', '.SM', '.SB'])) {
                // Workaround for man page bugs, see e.g. tcpdump.1
                return $i - 1;
            }
            if ($parentNode->hasContent()) {
                $parentNode->appendChild(new DOMText(' '));
            }
            Blocks::handle($innerNode, [$nextLine]);
        } else {
            TextContent::interpretAndAppendText($innerNode, implode(' ', $arguments), $parentNode->hasContent());
        }

        if ($appendToParentNode) {
            $parentNode->appendChild($appendToParentNode);
        }

        return $i;

    }

}
