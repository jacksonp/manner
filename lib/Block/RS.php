<?php


class Block_RS implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement {

        array_shift($lines);

        $thisIndent = '';
        $className  = 'indent';
        if (count($request['arguments']) > 0) {
            $thisIndent = Roff_Unit::normalize($request['arguments'][0]);
            if ($thisIndent) { // note this filters out 0s
                $className .= '-' . $thisIndent;
            }
        }
        $dom        = $parentNode->ownerDocument;
        $skippedRSs = 0;

        $rsLevel    = 1;
        $blockLines = [];
        while ($request = Request::getLine($lines)) {
            if ($request['request'] === 'RS') {
                $indent = Roff_Unit::normalize(trim($request['arg_string']));
                if ($indent === $thisIndent) {
                    if (count($blockLines) > 0 && !in_array($blockLines[count($blockLines) - 1], ['.sp', '.br'])) {
                        $blockLines[] = '.br';
                    }
                    ++$skippedRSs;
                    array_shift($lines);
                    continue;
                } else {
                    ++$rsLevel;
                }
            } elseif ($request['request'] === 'RE') {
                if ($skippedRSs > 0) {
                    --$skippedRSs;
                    array_shift($lines);
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
            } elseif (in_array($request['request'],  ['SH', 'SS'])) {
                break;
            }
            $blockLines[] = array_shift($lines);
        }

        // Hack for duplicity.1
//        if (count($blockLines) > 0 && $blockLines[count($blockLines) - 1] === '.PP') {
//            $lines[$i] = '.PP';
//            --$i;
//        }

        if ($parentNode->tagName === 'p') {
            $parentNode = $parentNode->parentNode;
        }

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

        return $parentNode;

    }


}
