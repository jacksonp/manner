<?php


class Inline_ft implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement {

        array_shift($lines);

        if (!count($lines)) {
            return null; // trailing .ft: skip
        }

        if (count($request['arguments']) === 0) {
            return null; // Just skip empty requests
        }

        $fontAbbreviation = $request['arguments'][0];

        // Skip stray regular font settings:
        if (in_array($fontAbbreviation, ['0', '1', 'R', 'P', 'CR', 'AR'])) {
            return null;
        }

        $dom = $parentNode->ownerDocument;
        list ($textParent, $shouldAppend) = Blocks::getTextParent($parentNode);

        $blockLines = [];
        // Force processing the line even if we don't use result. E.g. when a macro is defined inside a paragraph:
        while ($nextRequest = Request::getLine($lines)) {
            if (
                preg_match('~^\.\s*((ft|I|B|SB|SM)(\s|$)|(BI|BR|IB|IR|RB|RI)\s)~u', $nextRequest['raw_line']) ||
                Blocks::lineEndsBlock($nextRequest, $lines)
            ) {
                break;
            }
            $blockLines[] = array_shift($lines);

            if (preg_match('~\\\\f1$~u', $nextRequest['raw_line'])) { // Include, but then stop
                break;
            }

        }

        if (count($blockLines) > 0) {

            Block_Text::addSpace($parentNode, $textParent, $shouldAppend);

            switch ($fontAbbreviation) {
                case 'I':
                case '2':
                case 'AI':
                    $node = $dom->createElement('em');
                    break;
                case 'B':
                case '3':
                    $node = $dom->createElement('strong');
                    break;
                case 'C':
                case 'CW':
                case '4':
                case '5':
                case 'tt':
                case 'CB':
                case 'CS': // e.g. pmwebd.1
                    $node = $dom->createElement('code');
                    break;
                default:
                    $node = $dom->createElement('span');
                    $node->setAttribute('class', 'font-' . $fontAbbreviation);
            }
            if ($textParent->isOrInTag('pre')) {
                BlockPreformatted::handle($node, $blockLines);
            } else {
                Roff::parse($node, $blockLines);
            }
            $textParent->appendBlockIfHasContent($node);
        }

        if ($shouldAppend) {
            $parentNode->appendBlockIfHasContent($textParent);
        }


        return $parentNode;

    }

}
