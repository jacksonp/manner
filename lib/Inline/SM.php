<?php


class Inline_SM
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        if (!preg_match('~^\.SM ?(.*?)$~u', $lines[$i], $matches)) {
            return false;
        }

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;
        $small    = $dom->createElement('small');

        if ($matches[1] === '') {
            if ($i === $numLines - 1) {
                return $i;
            }
            $nextLine = $lines[++$i];
            if ($nextLine === '') {
                return $i;
            }
            TextContent::interpretAndAppendCommand($small, $nextLine);
        } else {
            TextContent::interpretAndAppendText($small, $matches[1], $parentNode->hasContent());
        }
        $parentNode->appendBlockIfHasContent($small);

        return $i;

    }

}
