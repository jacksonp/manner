<?php


class Inline_VerticalSpace implements Block_Template
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

    static function check(string $string)
    {
        $stringArray = [$string];
        $request     = Request::getLine($stringArray, 0);
        return in_array($request['request'], ['br', 'sp', 'ne']);
    }

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        ?array $request = null,
        $needOneLineOnly = false
    ): bool {

        array_shift($lines);

        if (!count($lines)) {
            return true;
        }

        list ($textParent, $shouldAppend) = Blocks::getTextParent($parentNode);

        if (!in_array($textParent->tagName, ['p', 'blockquote', 'dt', 'td', 'th', 'pre', 'h2', 'h3', 'code']) ||
            (
                $textParent->hasChildNodes() &&
                (
                    !($textParent->lastChild instanceof DOMElement) ||
                    $textParent->lastChild->tagName !== 'pre'
                )
            )
        ) {

            $nextRequest = Request::getLine($lines);

            if (count($lines) && !Blocks::lineEndsBlock($nextRequest, $lines)) {
                self::addBR($textParent);
                if (in_array($request['request'], ['sp', 'ne'])) {
                    self::addBR($textParent);
                }
            }

            if ($shouldAppend) {
                $parentNode->appendBlockIfHasContent($textParent);
            }
        }

        return true;

    }

}
