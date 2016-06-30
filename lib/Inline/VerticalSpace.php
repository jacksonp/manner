<?php


class Inline_VerticalSpace
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        if (!preg_match('~^\\\\?\.(br|sp|ne)(\s|$)~u', $lines[$i], $matches)) {
            return false;
        }

        list ($textParent, $shouldAppend) = Blocks::getTextParent($parentNode);
        $dom      = $parentNode->ownerDocument;
        $numLines = count($lines);

        if (!in_array($textParent->tagName, [
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
          (
            $textParent->hasChildNodes() and
            (
              !($textParent->lastChild instanceof DOMElement) or
              $textParent->lastChild->tagName !== 'pre'
            ) and
            $i !== $numLines - 1
          )
        ) {

            $textParent->appendChild($dom->createElement('br'));
            if (in_array($matches[1], ['sp', 'ne'])) {
                $textParent->appendChild($dom->createElement('br'));
            }

            if ($shouldAppend) {
                $parentNode->appendBlockIfHasContent($textParent);
            }
        }

        return $i;

    }

}
