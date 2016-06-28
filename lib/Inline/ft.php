<?php


class Inline_ft
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        if (!preg_match('~^\.ft(\s.*)?$~u', $lines[$i], $matches)) {
            return false;
        }

        $numLines = count($lines);

        if ($i === $numLines - 1) {
            return $i; // trailing .ft: skip
        }

        $arguments = Macro::parseArgString(@$matches[1]);

        if (is_null($arguments)) {
            return $i; // Just skip empty requests
        }

        $fontAbbreviation = $arguments[0];

        // Skip stray regular font settings:
        if (in_array($fontAbbreviation, ['1', 'R', 'P', 'CR'])) {
            return $i;
        }

        $dom = $parentNode->ownerDocument;

        $blockLines = [];
        for (; $i < $numLines - 1; ++$i) {
            $nextLine = $lines[$i + 1];
            if ($nextLine === '' or
              preg_match('~^\.((ft|I|B|SB|SM)(\s|$)|(BI|BR|IB|IR|RB|RI)\s)~u', $nextLine) or
              preg_match(Blocks::BLOCK_END_REGEX, $nextLine)
            ) {
                break;
            }
            $blockLines[] = $nextLine;
        }

        if (count($blockLines) > 0) {
            switch ($fontAbbreviation) {
                case '2':
                case 'I':
                    $node = $dom->createElement('em');
                    break;
                case 'B':
                case '3':
                    $node = $dom->createElement('strong');
                    break;
                case 'C':
                case 'CW':
                case '4':
                case 'tt':
                case 'CS': // e.g. pmwebd.1
                    $node = $dom->createElement('code');
                    break;
                default:
                    throw new Exception($fontAbbreviation . ': Unhandled font abbreviation.');

            }
            if ($parentNode->isOrInTag('pre')) {
                BlockPreformatted::handle($node, $blockLines);
            } else {
                Blocks::handle($node, $blockLines);
            }
            $parentNode->appendBlockIfHasContent($node);
        }


        return $i;

    }

}
