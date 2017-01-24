<?php


class Block_nf implements Block_Template
{

    private static function endBlock($request)
    {
        if (in_array($request['request'], ['fi'])) {
            return true;
        }
        if ($request['request'] === 'ad' && in_array($request['arg_string'], ['', 'n', 'b'])) {
            return true;
        }

        return false;
    }

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        ?array $request = null,
        $needOneLineOnly = false
    ): ?DOMElement {

        array_shift($lines);

        $dom = $parentNode->ownerDocument;

        $preLines = [];
        while ($nextRequest = Request::getLine($lines)) {
            if (Block_SS::endSubsection($nextRequest['request']) || $nextRequest['request'] === 'TS') {
                break;
            } elseif (self::endBlock($nextRequest)) {
                array_shift($lines);
                while (count($lines) && self::endBlock(Request::getLine($lines))) {
                    array_shift($lines); // swallow
                }
                break;
            } elseif (
                in_array($nextRequest['request'], ['EX', 'EE']) ||
                ($nextRequest['request'] === 'ad' && $nextRequest['arg_string'] === 'l')
            ) {
                // Skip
                array_shift($lines);
            } else {
                $preLines[] = array_shift($lines);
            }
        }

        if (count($preLines) === 0) {
            return null;
        }

        if (
            count($lines)
            && Request::getLine($preLines)['request'] === 'RS'
            && Request::getLine($lines)['request'] === 'RE'
        ) {
            $preLines[] = array_shift($lines);
        }

        ArrayHelper::rtrim($preLines, ['.fi', '.ad', '.ad n', '.ad b', '', '.br', '.sp']);

        if (count($preLines) === 0) {
            return null;
        }

        if (count($preLines) > 1) {
            $isTable = true;
            foreach ($preLines as $preLine) {
                $firstTab = mb_strpos($preLine, "\t");
                if ($firstTab === false || $firstTab === 0) {
                    $isTable = false;
                    break;
                }
            }

            if ($isTable) {
                $table = $parentNode->appendChild($dom->createElement('table'));
                foreach ($preLines as $preLine) {
                    if (in_array($preLine, ['.br', ''])) {
                        continue;
                    }
                    $nextRequest = '';
                    // TODO: use Request?
                    if (mb_substr($preLine, 0, 1) === '.') {
                        preg_match('~^(\.\w+ )"?(.*?)"?$~u', $preLine, $matches);
                        $nextRequest = $matches[1];
                        $preLine     = $matches[2];
                    }
                    $tds = preg_split('~\t+~u', $preLine);
                    $tr  = $table->appendChild($dom->createElement('tr'));
                    foreach ($tds as $tdLine) {
                        $cell     = $dom->createElement('td');
                        $codeNode = $cell->appendChild($dom->createElement('code'));
                        if (empty($nextRequest)) {
                            TextContent::interpretAndAppendText($codeNode, $tdLine);
                        } else {
                            $blockLines = [$nextRequest . $tdLine];
                            Blocks::trim($blockLines);
                            Roff::parse($codeNode, $blockLines);
                        }
                        $tr->appendChild($cell);
                    }
                }

                return null;
            }
        }

        $pre = $dom->createElement('pre');

        $nextRequest = Request::getLine($preLines);
        if ($nextRequest['request'] === 'RS') {
            $lastRequest = Request::peepAt($preLines[count($preLines) - 1]);
            if ($lastRequest['name'] === 'RE') {
                array_pop($preLines);
                array_shift($preLines);
                ArrayHelper::trim($preLines, ['', '.br', '.sp']);
                $className = 'indent';
                if (
                    !empty($nextRequest['arg_string']) &&
                    $indentVal = Roff_Unit::normalize(trim($nextRequest['arg_string'])) // note this filters out 0s
                ) {
                    $className .= '-' . $indentVal;
                }
                $pre->setAttribute('class', $className);
            }
        }

        BlockPreformatted::handle($pre, $preLines);
        $parentNode->appendBlockIfHasContent($pre);

        return null;
    }


}
