<?php


class Inline_ft implements Block_Template
{

    private static function getNonFontParent(DOMElement $parentNode): DOMElement
    {
        while (in_array($parentNode->tagName, Blocks::INLINE_ELEMENTS)) {
            $parentNode = $parentNode->parentNode;
        }
        return $parentNode;
    }

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        // Return to previous font. Same as \f[] or \fP.
        if (count($request['arguments']) === 0) {
            return self::getNonFontParent($parentNode);
        }

        $fontAbbreviation = $request['arguments'][0];

        // Skip stray regular font settings:
        if (in_array($fontAbbreviation, ['0', '1', 'R', 'P', 'CR', 'AR'])) {
            return self::getNonFontParent($parentNode);
        }

        $parentNode = Blocks::getParentForText($parentNode);

        $dom = $parentNode->ownerDocument;

        switch ($fontAbbreviation) {
            case 'I':
            case '2':
            case 'AI':
                if ($parentNode->isOrInTag('em')) {
                    return null;
                }
                $node = $dom->createElement('em');
                break;
            case 'B':
            case '3':
                if ($parentNode->isOrInTag('strong')) {
                    return null;
                }
                $node = $dom->createElement('strong');
                break;
            case 'C':
            case 'CW':
            case '4':
            case '5':
            case 'tt':
            case 'CB':
            case 'CS': // e.g. pmwebd.1
                if ($parentNode->isOrInTag('code')) {
                    return null;
                }
                $node = $dom->createElement('code');
                break;
            default:
                $node = $dom->createElement('span');
                $node->setAttribute('class', 'font-' . $fontAbbreviation);
        }

        $node = $parentNode->appendChild($node);

        return $node;
        /*

        $inPre = $parentNode->isOrInTag('pre');

        if ($inPre) {
            list($textParent) = Blocks::maybeLastEmptyChildWaitingForText($parentNode);
        } else {
            list ($textParent, $shouldAppend) = Blocks::getTextParent($parentNode);
            if ($shouldAppend) {
                $parentNode->appendChild($textParent);
            }
        }
        $node = $textParent->appendChild($node);

        return $node;


        $blockLines = [];
        // Force processing the line even if we don't use result. E.g. when a macro is defined inside a paragraph:
        while ($nextRequest = Request::getNextClass($lines)) {
            if (
                preg_match('~^\.\s*((ft|I|B|SB|SM)(\s|$)|(BI|BR|IB|IR|RB|RI)\s)~u', $nextRequest['raw_line']) ||
                Blocks::lineEndsBlock($nextRequest, $lines)
            ) {
                break;
            }

            if ($inPre) {
                Block_Preformatted::handle($node, $lines, $nextRequest);
            }

            $blockLines[] = array_shift($lines);

            if (preg_match('~\\\\f1$~u', $nextRequest['raw_line'])) { // Include, but then stop
                break;
            }

        }

        if ($inPre) {
            return null;
        }

        if (count($blockLines) > 0) {
            Roff::parse($node, $blockLines);
        }


        return null;
        */

    }

}
