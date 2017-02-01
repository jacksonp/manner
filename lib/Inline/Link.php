<?php


class Inline_Link implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        $parentNode = Blocks::getParentForText($parentNode);

        if ($request['request'] === 'URL') {
            $dom = $parentNode->ownerDocument;
            Block_Text::addSpace($parentNode);
            if (count($request['arguments']) === 0) {
                throw new Exception('Not enough arguments to .URL: ' . $request['raw_line']);
            }
            $anchor = $dom->createElement('a');
            $anchor->setAttribute('href', $request['arguments'][0]);
            $parentNode->appendChild($anchor);

            if (count($request['arguments']) > 1) {
                TextContent::interpretAndAppendText($anchor, $request['arguments'][1]);
            } elseif (count($lines)) {
                Roff::parse($anchor, $lines, true);
            }

            if ($anchor->textContent === '') {
                $anchor->appendChild(new DOMText($request['arguments'][0]));
            }

            if (count($request['arguments']) === 3) {
                $parentNode->appendChild(new DOMText($request['arguments'][2]));
            }

            return $parentNode;
        }

        $dom         = $parentNode->ownerDocument;
        $punctuation = '';
        $blockLines  = [];

        while ($nextRequest = Request::getLine($lines)) {
            if (in_array($nextRequest['request'], ['UR', 'MT', 'SH', 'SS'])) {
                break;
            } elseif (in_array($nextRequest['request'], ['UE', 'ME'])) {
                $punctuation = trim($nextRequest['arg_string']);
                array_shift($lines);
                break;
            }
            $blockLines[] = array_shift($lines);
        }

        $href = false;
        if (count($request['arguments']) > 0) {
            $url  = $request['arguments'][0];
            $href = self::getValidHREF($url);
        }
        if ($href === false && count($blockLines) === 1) {
            $url  = $blockLines[0];
            $href = self::getValidHREF($url);
        }
        if ($href === false) {
            // No valid URL, output any content as text and bail.
            Roff::parse($parentNode, $blockLines);
            return null;
        }

        $anchor = $dom->createElement('a');
        $anchor->setAttribute('href', $href);

        if (count($blockLines) === 0) {
            TextContent::interpretAndAppendText($anchor, $url);
        } else {
            Roff::parse($anchor, $blockLines);
        }
        if ($anchor->hasContent()) {
            Block_Text::addSpace($parentNode);
            $parentNode->appendChild($anchor);
        }
        if ($punctuation !== '') {
            $parentNode->appendChild(new DOMText($punctuation));
        }

        return $parentNode;

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
