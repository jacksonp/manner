<?php


class Inline_VerticalSpace
{

    private static function addBR(DOMElement $parentNode)
    {
        $prevBRs   = 0;
        $nodeCheck = $parentNode->lastChild;
        while ($nodeCheck) {
            if ($nodeCheck instanceof DOMElement && $nodeCheck->tagName === 'br') {
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
        return Request::is($string, ['br', 'sp', 'ne']);
    }

    static function checkAppend(HybridNode $parentNode, array $lines, int $i, $arguments, $request)
    {

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
          ]) ||
          (
            $textParent->hasChildNodes() &&
            (
              !($textParent->lastChild instanceof DOMElement) ||
              $textParent->lastChild->tagName !== 'pre'
            ) &&
            $i !== $numLines - 1
          )
        ) {

            self::addBR($textParent);
            if (in_array($request, ['sp', 'ne'])) {
                self::addBR($textParent);
            }

            if ($shouldAppend) {
                $parentNode->appendBlockIfHasContent($textParent);
            }
        }

        return $i;

    }

}
