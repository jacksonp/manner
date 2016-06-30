<?php


class Inline_Link
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        list ($textParent, $shouldAppend) = Blocks::getTextParent($parentNode);

        if (preg_match('~^\.URL\s(.*)$~u', $lines[$i], $matches)) {
            $dom       = $parentNode->ownerDocument;
            $arguments = Macro::parseArgString($matches[1]);
            if (is_null($arguments)) {
                throw new Exception('Not enough arguments to .URL: ' . $lines[$i]);
            }
            $anchor = $dom->createElement('a');
            $anchor->setAttribute('href', $arguments[0]);

            if (count($arguments) > 1) {
                TextContent::interpretAndAppendText($anchor, $arguments[1], false, false);
            } else {
                Blocks::handle($anchor, [$lines[++$i]]);
            }

            if ($textParent->hasContent()) {
                $textParent->appendChild(new DOMText(' '));
            }
            $textParent->appendChild($anchor);
            if (count($arguments) === 3) {
                $textParent->appendChild(new DOMText($arguments[2]));
            }

            if ($shouldAppend) {
                $parentNode->appendBlockIfHasContent($textParent);
            }

            return $i;
        }

        if (!preg_match('~^\.(?:UR|MT)(?:$|\s<?(.*?)>?$)~u', $lines[$i], $matches)) {
            return false;
        }

        $dom         = $parentNode->ownerDocument;
        $numLines    = count($lines);
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
            if ($textParent->hasContent()) {
                $textParent->appendChild(new DOMText(' '));
            }
            $textParent->appendChild($anchor);
        }
        if ($punctuation !== '') {
            $textParent->appendChild(new DOMText($punctuation));
        }

        if ($shouldAppend) {
            $parentNode->appendBlockIfHasContent($textParent);
        }

        return $i;

    }

}
