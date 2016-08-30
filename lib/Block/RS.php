<?php


class Block_RS
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i, array $arguments)
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
                    if (count($blockLines) > 0 and !in_array($blockLines[count($blockLines) - 1], ['.sp', '.br'])) {
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

        if (count($blockLines) > 0) {
            $rsBlock = $dom->createElement('div');
            $rsBlock->setAttribute('class', $className);
            Blocks::handle($rsBlock, $blockLines);
            if ($className === 'indent' and
              $rsBlock->childNodes->length === 1 and
              $rsBlock->firstChild instanceof DOMElement and
              !in_array($rsBlock->firstChild->tagName, ['strong', 'em', 'small', 'a', 'code'])
            ) {
                $parentNode->appendChild($rsBlock->firstChild);
            } else {
                $parentNode->appendBlockIfHasContent($rsBlock);
            }
        }

        return $i;

    }


}
