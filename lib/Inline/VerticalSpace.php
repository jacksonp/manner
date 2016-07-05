<?php


class Inline_VerticalSpace
{

    private static function addBR(DOMElement $parentNode)
    {
        $prevBRs   = 0;
        $nodeCheck = $parentNode->lastChild;
        while ($nodeCheck) {
            if ($nodeCheck instanceof DOMElement and $nodeCheck->tagName === 'br') {
                ++$prevBRs;
            } else {
                break;
            }
            $nodeCheck = $nodeCheck->previousSibling;
        }
        if ($prevBRs < 2) {
            $parentNode->appendChild($parentNode->ownerDocument->createElement('br'));
        }
    }

    static function check($string)
    {
        if (preg_match('~^\\\\?\.(br|sp|ne)(\s|$)~u', $string, $matches)) {
            return $matches;
        }

        return false;
    }

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        $matches = self::check($lines[$i]);
        if ($matches === false) {
            return false;
        }

        list ($textParent, $shouldAppend) = Blocks::getTextParent($parentNode);
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

            self::addBR($textParent);
            if (in_array($matches[1], ['sp', 'ne'])) {
                self::addBR($textParent);
            }

            if ($shouldAppend) {
                $parentNode->appendBlockIfHasContent($textParent);
            }
        }

        return $i;

    }

}
