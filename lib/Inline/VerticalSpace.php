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

        // Only bother if this isn't the first node.
        if (!in_array($parentNode->tagName, ['p', 'blockquote']) or
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
