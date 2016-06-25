<?php


class Inline_UR
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        if (!preg_match('~^\.UR <?(.*?)>?$~u', $lines[$i], $matches)) {
            return false;
        }

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;

        $anchor = $dom->createElement('a');
        $url    = TextContent::interpretString(Macro::parseArgString($matches[1])[0]);
        if (filter_var($url, FILTER_VALIDATE_EMAIL)) {
            $url = 'mailto:' . $url;
        }
        $anchor->setAttribute('href', $url);
        $parentNode->appendChild($anchor);
        for ($i = $i + 1; $i < $numLines; ++$i) {
            $line = $lines[$i];
            if (preg_match('~^\.UE~u', $line)) {
                return $i;
            }
            Blocks::handle($anchor, [$line]);
        }
        throw new Exception('.UR with no corresponding .UE');


    }

}
