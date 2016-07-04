<?php


class Block_SY
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        // These get swallowed:
        $blockEnds = ['.YS'];

        if (!preg_match('~^\.SY\s?(.*)$~u', $lines[$i], $matches)) {
            return false;
        }

        $numLines    = count($lines);
        $dom         = $parentNode->ownerDocument;
        $commandName = trim($matches[1]);

        $pre = $dom->createElement('pre');
        if ($commandName !== '') {
            $pre->setAttribute('class', 'synopsis');
            $pre->appendChild(new DOMText($commandName));
        }

        $preLines = [];
        for ($i = $i + 1; $i < $numLines; ++$i) {
            if (in_array($lines[$i], $blockEnds)) {
                break;
            } elseif (preg_match('~^\.SY~u', $lines[$i])) {
                --$i;
                break;
            }
            $preLines[] = $lines[$i];
        }

        if (count($preLines) === 0) {
            return $i;
        }

        BlockPreformatted::handle($pre, $preLines);
        $parentNode->appendChild($pre);

        return $i;
    }


}
