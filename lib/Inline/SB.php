<?php


class Inline_SB
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        if (!preg_match('~^\.SB ?(.*?)$~u', $lines[$i], $matches)) {
            return false;
        }

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;
        $small    = $dom->createElement('small');
        $strong   = $small->appendChild($dom->createElement('strong'));

        if ($matches[1] === '') {
            if ($i === $numLines - 1) {
                return $i;
            }
            $nextLine = $lines[++$i];
            if ($nextLine === '') {
                return $i;
            }
            TextContent::interpretAndAppendCommand($strong, $nextLine);
        } else {
            TextContent::interpretAndAppendText($strong, $matches[1], $parentNode->hasContent());
        }

        $parentNode->appendBlockIfHasContent($small);

        return $i;

    }

}
