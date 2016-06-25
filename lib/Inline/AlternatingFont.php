<?php


class Inline_AlternatingFont
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        if (!preg_match('~^\.(BI|BR|IB|IR|RB|RI)(\s.*)?$~u', $lines[$i], $matches)) {
            return false;
        }


        $arguments = Macro::parseArgString(@$matches[2]);

        if (is_null($arguments)) {
            return $i; // Just skip empty requests
        }

        $command = $matches[1];

        $dom      = $parentNode->ownerDocument;

        if ($parentNode->hasContent()) {
            $parentNode->appendChild(new DOMText(' '));
        }

        foreach ($arguments as $bi => $bit) {
            $commandCharIndex = $bi % 2;
            if (!isset($command[$commandCharIndex])) {
                throw new Exception($lines[$i] . ' command ' . $command . ' has nothing at index ' . $commandCharIndex);
            }
            if (trim($bit) === '') {
                TextContent::interpretAndAppendText($parentNode, $bit);
                continue;
            }
            switch ($command[$commandCharIndex]) {
                case 'R':
                    TextContent::interpretAndAppendText($parentNode, $bit);
                    break;
                case 'B':
                    $strongNode = $dom->createElement('strong');
                    TextContent::interpretAndAppendText($strongNode, $bit);
                    if ($strongNode->hasContent()) {
                        $parentNode->appendChild($strongNode);
                    }
                    break;
                case 'I':
                    $emNode = $dom->createElement('em');
                    TextContent::interpretAndAppendText($emNode, $bit);
                    if ($emNode->hasContent()) {
                        $parentNode->appendChild($emNode);
                    }
                    break;
                default:
                    throw new Exception($lines[$i] . ' command ' . $command . ' unexpected character at index ' . $commandCharIndex);
            }
        }

        return $i;

    }

}
