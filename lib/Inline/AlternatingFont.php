<?php


class Inline_AlternatingFont
{

    static function check(string $string)
    {
        if (preg_match('~^\.(BI|BR|IB|IR|RB|RI)(\s.*)?$~u', $string, $matches)) {
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

        list ($textParent, $shouldAppend) = Blocks::getTextParent($parentNode);

        $arguments = Macro::parseArgString(@$matches[2]);

        if (is_null($arguments)) {
            return $i; // Just skip empty requests
        }

        $command = $matches[1];

        $dom = $parentNode->ownerDocument;

        Block_Text::addSpace($parentNode, $textParent, $shouldAppend);

        foreach ($arguments as $bi => $bit) {
            $commandCharIndex = $bi % 2;
            if (!isset($command[$commandCharIndex])) {
                throw new Exception($lines[$i] . ' command ' . $command . ' has nothing at index ' . $commandCharIndex);
            }
            if (trim($bit) === '') {
                TextContent::interpretAndAppendText($textParent, $bit);
                continue;
            }
            switch ($command[$commandCharIndex]) {
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
                    throw new Exception($lines[$i] . ' command ' . $command . ' unexpected character at index ' . $commandCharIndex);
            }
        }

        if ($shouldAppend) {
            $parentNode->appendBlockIfHasContent($textParent);
        }

        return $i;

    }

}
