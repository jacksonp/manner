<?php


class Block_RS
{

    static function checkAppend(HybridNode $parentNode, array &$lines, int $i, array $arguments)
    {

        $thisIndent = '';
        $className  = 'indent';
        if (count($arguments) > 0) {
            $thisIndent = Roff_Unit::normalize($arguments[0]);
            if ($thisIndent) { // note this filters out 0s
                $className .= '-' . $thisIndent;
            }
        }
        $numLines   = count($lines);
        $dom        = $parentNode->ownerDocument;
        $skippedRSs = 0;

        $rsLevel    = 1;
        $blockLines = [];
        for ($i = $i + 1; $i < $numLines; ++$i) {
            $line    = $lines[$i];
            $request = Request::get($line);
            if ($request['request'] === 'RS') {
                $indent = Roff_Unit::normalize(trim($request['arg_string']));
                if ($indent === $thisIndent) {
                    if (count($blockLines) > 0 && !in_array($blockLines[count($blockLines) - 1], ['.sp', '.br'])) {
                        $blockLines[] = '.br';
                    }
                    ++$skippedRSs;
                    continue;
                } else {
                    ++$rsLevel;
                }
            } elseif ($request['request'] === 'RE') {
                if ($skippedRSs > 0) {
                    --$skippedRSs;
                    continue;
                } else {
                    --$rsLevel;
                    if ($rsLevel === 0) {
                        break;
                    }
                }
            } elseif ($request['request'] === 'TP') {
                // prevent skipping
                $thisIndent = 'GARBAGE';
            }
            $blockLines[] = $line;
        }

        // Hack for duplicity.1
        if (count($blockLines) > 0 && $blockLines[count($blockLines) - 1] === '.PP') {
            $lines[$i] = '.PP';
            --$i;
        }

        Blocks::trim($blockLines);

        if (count($blockLines) > 0) {
            $rsBlock = $dom->createElement('div');
            $rsBlock->setAttribute('class', $className);
            Roff::parse($rsBlock, $blockLines);
            if ($className === 'indent' &&
                $rsBlock->childNodes->length === 1 &&
                $rsBlock->firstChild instanceof DOMElement &&
                in_array($rsBlock->firstChild->tagName, ['dl', 'div'])
            ) {
                $parentNode->appendChild($rsBlock->firstChild);
            } elseif ($className === 'indent' &&
                $parentNode->tagName === 'dd'
            ) {
                $child = $rsBlock->firstChild;
                while ($child) {
                    $nextSibling = $child->nextSibling;
                    $parentNode->appendBlockIfHasContent($child);
                    $child = $nextSibling;
                }
            } else {
                $parentNode->appendBlockIfHasContent($rsBlock);

            }
        }

        return $i;

    }


}
