<?php


class Inline_FontOneInputLine
{

    static function check(string $string)
    {
        if (preg_match('~^\.(R|I|B|SB|SM)(\s.*)?$~u', $string, $matches)) {
            return $matches;
        }

        return false;
    }

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        $matches = self::check($lines[$i]);
        if ($matches === false) {
            return false;
        }

        $arguments = Macro::parseArgString(@$matches[2]);

        if (is_null($arguments) and $i < count($lines) - 1 and preg_match('~\.IP~u', $lines[$i + 1])) {
            return $i; // TODO: not sure how to handle this, just skip the font setting for now.
        }

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;

        list ($textParent, $shouldAppend) = Blocks::getTextParent($parentNode);

        switch ($matches[1]) {
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

        if ($parentNode->tagName !== 'pre' and !$shouldAppend and !TextContent::$continuation and $textParent->hasContent()) {
            $textParent->appendChild(new DOMText(' '));
        }

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
            Blocks::handle($innerNode, [$nextLine]);
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
