<?php


class Inline_MT
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        if (!preg_match('~^\.MT <?(.*?)>?$~u', $lines[$i], $matches)) {
            return false;
        }

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;

        $anchor       = $dom->createElement('a');
        $emailAddress = TextContent::interpretString(Macro::parseArgString($matches[1])[0]);
        if (filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            $emailAddress = 'mailto:' . $emailAddress;
        }
        $anchor->setAttribute('href', $emailAddress);
        $parentNode->appendChild($anchor);
        for ($i = $i + 1; $i < $numLines; ++$i) {
            $line = $lines[$i];
            if (preg_match('~^\.ME~u', $line)) {
                return $i;
            }
            TextContent::interpretAndAppendCommand($anchor, $line);
        }
        throw new Exception('.MT with no corresponding .ME');


    }

}
