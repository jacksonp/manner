<?php


class Inline_Link
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        if (!preg_match('~^\.(?:UR|MT)\s?<?(.*?)>?$~u', $lines[$i], $matches)) {
            return false;
        }

        $numLines    = count($lines);
        $dom         = $parentNode->ownerDocument;
        $arguments   = Macro::parseArgString($matches[1]);
        $punctuation = '';
        $blockLines  = [];

        for ($i = $i + 1; $i < $numLines; ++$i) {
            if (preg_match('~^\.(?:UE|ME)(.*)~u', $lines[$i], $matches)) {
                $punctuation = trim($matches[1]);
                break;
            }
            $blockLines[] = $lines[$i];
        }

        if (is_null($arguments)) {
            if (count($blockLines) === 1) {
                $url = $blockLines[0];
            } else {
                throw new Exception('Missing URL for Link.');
            }
        } else {
            $url = $arguments[0];
        }

        $href = TextContent::interpretString($url);
        if (filter_var($href, FILTER_VALIDATE_EMAIL)) {
            $href = 'mailto:' . $href;
        }

        $anchor = $dom->createElement('a');
        $anchor->setAttribute('href', $href);

        if (count($blockLines) === 0) {
            TextContent::interpretAndAppendText($anchor, $url, false, false);
        } else {
            Blocks::handle($anchor, $blockLines);
        }
        if ($anchor->hasContent()) {
            if ($parentNode->hasContent()) {
                $parentNode->appendChild(new DOMText(' '));
            }
            $parentNode->appendChild($anchor);
        }
        if ($punctuation !== '') {
            $parentNode->appendChild(new DOMText($punctuation));
        }

        return $i;

    }

}
