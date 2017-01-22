<?php


class Inline_ft implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        ?array $arguments = null,
        ?string $request = null,
        $needOneLineOnly = false
    ) {

        array_shift($lines);

        if (!count($lines)) {
            return 0; // trailing .ft: skip
        }

        if (count($arguments) === 0) {
            return 0; // Just skip empty requests
        }

        $fontAbbreviation = $arguments[0];

        // Skip stray regular font settings:
        if (in_array($fontAbbreviation, ['0', '1', 'R', 'P', 'CR', 'AR'])) {
            return 0;
        }

        $dom = $parentNode->ownerDocument;
        list ($textParent, $shouldAppend) = Blocks::getTextParent($parentNode);

        $blockLines = [];
        while (count($lines)) {

            // Force processing the line even if we don't use result. E.g. when a macro is defined inside a paragraph:
            $nextRequest = Request::getLine($lines, 0);

            if (!count($lines)) {
                break;
            }

            $line = $lines[0];
            if (
                preg_match('~^\.\s*((ft|I|B|SB|SM)(\s|$)|(BI|BR|IB|IR|RB|RI)\s)~u', $line) ||
                Blocks::lineEndsBlock($nextRequest, $lines)
            ) {
                break;
            }
            $blockLines[] = array_shift($lines);

            if (preg_match('~\\\\f1$~u', $line)) { // Include, but then stop
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
                Blocks::trim($blockLines);
                Roff::parse($node, $blockLines);
            }
            $textParent->appendBlockIfHasContent($node);
        }

        if ($shouldAppend) {
            $parentNode->appendBlockIfHasContent($textParent);
        }


        return 0;

    }

}
