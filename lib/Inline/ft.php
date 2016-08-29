<?php


class Inline_ft
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i, $arguments)
    {

        $numLines = count($lines);

        if ($i === $numLines - 1) {
            return $i; // trailing .ft: skip
        }

        if (is_null($arguments)) {
            return $i; // Just skip empty requests
        }

        $fontAbbreviation = $arguments[0];

        // Skip stray regular font settings:
        if (in_array($fontAbbreviation, ['0', '1', 'R', 'P', 'CR', 'AR'])) {
            return $i;
        }

        $dom = $parentNode->ownerDocument;
        list ($textParent, $shouldAppend) = Blocks::getTextParent($parentNode);

        $blockLines = [];
        for (; $i < $numLines - 1; ++$i) {
            $nextLine = $lines[$i + 1];
            if (
              preg_match('~^\.\s*((ft|I|B|SB|SM)(\s|$)|(BI|BR|IB|IR|RB|RI)\s)~u', $nextLine) or
              Blocks::lineEndsBlock($lines, $i + 1)
            ) {
                break;
            }
            $blockLines[] = $nextLine;

            if (preg_match('~\\\\f1$~u', $nextLine)) {
                ++$i; // We include $nextLine, swallow it.
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
                    throw new Exception($fontAbbreviation . ': Unhandled font abbreviation.');

            }
            if ($textParent->isOrInTag('pre')) {
                BlockPreformatted::handle($node, $blockLines);
            } else {
                Blocks::handle($node, $blockLines);
            }
            $textParent->appendBlockIfHasContent($node);
        }

        if ($shouldAppend) {
            $parentNode->appendBlockIfHasContent($textParent);
        }


        return $i;

    }

}
