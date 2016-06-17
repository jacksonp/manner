<?php


class Block_IP
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        // TODO:  --group-directories-first in ls.1 - separate para rather than br?
        // TODO $matches[1] will contain the indentation level, try to use this to handle nested dls?
        if (!preg_match('~^\.IP ?(.*)$~u', $lines[$i], $matches)) {
            return false;
        }

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;

        $ipArgs = Macro::parseArgString($matches[1]);

        // 2nd bit: If there's a "designator" - otherwise preg_match hit empty double quotes.
        if (!is_null($ipArgs) && trim($ipArgs[0]) !== '') {
            // Copied from .TP:
            if (!$parentNode->hasChildNodes() or $parentNode->lastChild->tagName !== 'dl') {
                $dl = $dom->createElement('dl');
                $parentNode->appendChild($dl);
                if (count($ipArgs) > 1) {
                    $dl->setAttribute('class', 'indent-' . $ipArgs[1]);
                }
            } else {
                $dl = $parentNode->lastChild;
            }
            $dt = $dom->createElement('dt');
            TextContent::interpretAndAppendCommand($dt, $ipArgs[0]);
            $dl->appendChild($dt);

            list ($i, $blockLines) = Blocks::getDDBlock($i, $lines);

            $dd = $dom->createElement('dd');
            Blocks::handle($dd, $blockLines);
            $dl->appendBlockIfHasContent($dd);

            return $i;
        }

        // If already in previous .IP:
        if ($parentNode->hasChildNodes() and $parentNode->lastChild->tagName === 'blockquote') {
            $parentNode->lastChild->appendChild($dom->createElement('br'));

            return $i;
        }

        $blockLines = [];
        while ($i < $numLines) {
            if ($i === $numLines - 1) {
                break;
            }
            $nextLine = $lines[$i + 1];
            if ($nextLine === '' or preg_match(Blocks::BLOCK_END_REGEX, $nextLine)) {
                break;
            }
            $blockLines[] = $nextLine;
            ++$i;
        }

        if (!$parentNode->hasChildNodes() or $parentNode->lastChild->tagName === 'pre') {
            $block = $parentNode->appendChild($dom->createElement('p'));
        } elseif (in_array($parentNode->lastChild->tagName, ['p', 'h2', 'h3'])) {
            $block = $parentNode->appendChild($dom->createElement('blockquote'));
        } else {
            throw new Exception($lines[$i] . ' - unexpected .IP in ' . $parentNode->lastChild->tagName . ' at line ' . $i . '. Last line was "' . $lines[$i - 1] . '"');
        }

        Blocks::handle($block, $blockLines);
        $parentNode->appendBlockIfHasContent($block);

        return $i;
    }


}
