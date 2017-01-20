<?php


class Inline_Link
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i, array $arguments, $request)
    {

        list ($textParent, $shouldAppend) = Blocks::getTextParent($parentNode);

        if ($request === 'URL') {
            $dom = $parentNode->ownerDocument;
            if (count($arguments) === 0) {
                throw new Exception('Not enough arguments to .URL: ' . $lines[$i]);
            }
            $anchor = $dom->createElement('a');
            $anchor->setAttribute('href', $arguments[0]);

            if (count($arguments) > 1) {
                TextContent::interpretAndAppendText($anchor, $arguments[1]);
            } elseif ($i < count($lines) - 1) {
                $blockLines = [$lines[++$i]];
                Blocks::trim($blockLines);
                Roff::parse($anchor, $blockLines);
            } else {
                $anchor->appendChild(new DOMText($arguments[0]));
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

        $dom         = $parentNode->ownerDocument;
        $punctuation = '';
        $blockLines  = [];

        for ($i = $i + 1; $i < count($lines); ++$i) {
            $request = Request::getLine($lines, $i);
            if (in_array($request['request'], ['UR', 'MT'])) {
                --$i;
                break;
            } elseif (in_array($request['request'], ['UE', 'ME'])) {
                $punctuation = trim($request['arg_string']);
                break;
            }
            $blockLines[] = $lines[$i];
        }

        $href = false;
        if (count($arguments) > 0) {
            $url  = $arguments[0];
            $href = self::getValidHREF($url);
        }
        if ($href === false && count($blockLines) === 1) {
            $url  = $blockLines[0];
            $href = self::getValidHREF($url);
        }
        if ($href === false) {
            // No valid URL, output any content as text and bail.
            Blocks::trim($blockLines);
            Roff::parse($parentNode, $blockLines);

            return $i;
        }

        $anchor = $dom->createElement('a');
        $anchor->setAttribute('href', $href);

        Blocks::trim($blockLines);
        if (count($blockLines) === 0) {
            TextContent::interpretAndAppendText($anchor, $url);
        } else {
            Roff::parse($anchor, $blockLines);
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

    private static function getValidHREF(string $url)
    {
        $url  = Replace::preg('~^<(.*)>$~u', '$1', $url);
        $href = TextContent::interpretString($url);
        if (filter_var($href, FILTER_VALIDATE_URL)) {
            return $href;
        } elseif (filter_var($href, FILTER_VALIDATE_EMAIL)) {
            return 'mailto:' . $href;
        } else {
            return false;
        }
    }

}
