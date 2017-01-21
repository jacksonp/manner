<?php


class Inline_Link implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        ?array $arguments = null,
        ?string $request = null,
        $needOneLineOnly = false
    ) {

        array_shift($lines);

        list ($textParent, $shouldAppend) = Blocks::getTextParent($parentNode);

        if ($request === 'URL') {
            $dom = $parentNode->ownerDocument;
            if (count($arguments) === 0) {
                throw new Exception('Not enough arguments to .URL: ' . $request['raw_line']);
            }
            $anchor = $dom->createElement('a');
            $anchor->setAttribute('href', $arguments[0]);

            if (count($arguments) > 1) {
                TextContent::interpretAndAppendText($anchor, $arguments[1]);
            } elseif (count($lines)) {
                $blockLines = array_shift($lines);
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

            return 0;
        }

        $dom         = $parentNode->ownerDocument;
        $punctuation = '';
        $blockLines  = [];

        while (count($lines)) {
            $request = Request::getLine($lines, 0);
            if (in_array($request['request'], ['UR', 'MT'])) {
                break;
            } elseif (in_array($request['request'], ['UE', 'ME'])) {
                $punctuation = trim($request['arg_string']);
                array_shift($lines);
                break;
            }
            $blockLines[] = array_shift($lines);
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

            return 0;
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

        return 0;

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
