<?php


class Inline_VerticalSpace
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        if (!preg_match('~^\\\\?\.(br|sp|ne)(\s|$)~u', $lines[$i], $matches)) {
            return false;
        }

        $dom      = $parentNode->ownerDocument;
        $numLines = count($lines);

        if (!in_array($parentNode->tagName, [
            'p',
            'blockquote',
            'dt',
            'td',
            'th',
            'pre',
            'h2',
            'h3',
            'code',
          ]) or
          ($parentNode->hasChildNodes() and $i !== $numLines - 1)
        ) {
            $parentNode->appendChild($dom->createElement('br'));
            if (in_array($matches[1], ['sp', 'ne'])) {
                $parentNode->appendChild($dom->createElement('br'));
            }
        }

        return $i;

    }

}
