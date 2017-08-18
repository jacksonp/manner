<?php
declare(strict_types = 1);

class Inline_Link implements Block_Template
{

    static function checkAppend(
        DOMElement $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);
        $dom        = $parentNode->ownerDocument;
        $parentNode = Blocks::getParentForText($parentNode);

        $anchor = $dom->createElement('a');

        if (count($request['arguments'])) {
            $url = $request['arguments'][0];
            $href = self::getValidHREF($url);
            if ($href) {
                $anchor->setAttribute('href', $href);
            }
        }

        Block_Text::addSpace($parentNode);
        $parentNode->appendChild($anchor);

        return $anchor;

    }

    static function getValidHREF(string $url)
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
