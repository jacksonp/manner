<?php


class Block_nf
{

    private static function endBlock($request)
    {
        if (in_array($request['request'], ['fi'])) {
            return true;
        }
        if ($request['request'] === 'ad' and in_array($request['arg_string'], ['', 'n', 'b'])) {
            return true;
        }

        return false;
    }

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;

        $preLines = [];
        while ($i < $numLines - 1) {
            $line    = $lines[$i + 1];
            $request = Request::get($line);
            if (Block_SS::endSubsection($line) or $request['request'] === 'TS') {
                break;
            } elseif (self::endBlock($request)) {
                while ($i < $numLines - 1 and $request = Request::get($lines[$i + 1]) and self::endBlock($request)) {
                    ++$i; // swallow
                }
                break;
            } elseif (
              in_array($request['request'], ['EX', 'EE']) or
              ($request['request'] === 'ad' and $request['arg_string'] === 'l')
            ) {
                // Skip
            } else {
                $preLines[] = $line;
            }
            ++$i;
        }

        if (count($preLines) === 0) {
            return $i;
        }

        if ($i < $numLines - 1 and Request::is($preLines[0], 'RS') and Request::is($lines[$i + 1], 'RE')) {
            $preLines[] = $lines[++$i];
        }

        ArrayHelper::rtrim($preLines, ['.fi', '.ad', '.ad n', '.ad b', '', '.br', '.sp']);

        if (count($preLines) === 0) {
            return $i;
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
                    $request = '';
                    if (mb_substr($preLine, 0, 1) === '.') {
                        preg_match('~^(\.\w+ )"?(.*?)"?$~u', $preLine, $matches);
                        $request = $matches[1];
                        $preLine = $matches[2];
                    }
                    $tds = preg_split('~\t+~u', $preLine);
                    $tr  = $table->appendChild($dom->createElement('tr'));
                    foreach ($tds as $tdLine) {
                        $cell     = $dom->createElement('td');
                        $codeNode = $cell->appendChild($dom->createElement('code'));
                        if (empty($request)) {
                            TextContent::interpretAndAppendText($codeNode, $tdLine);
                        } else {
                            Blocks::handle($codeNode, [$request . $tdLine]);
                        }
                        $tr->appendChild($cell);
                    }
                }

                return $i;
            }
        }

        $pre = $dom->createElement('pre');

        $request = Request::get($preLines[0]);
        if ($request['request'] === 'RS') {
            $lastRequest = Request::get($preLines[count($preLines) - 1]);
            if ($lastRequest['request'] === 'RE') {
                array_pop($preLines);
                array_shift($preLines);
                ArrayHelper::trim($preLines, ['', '.br', '.sp']);
                $className = 'indent';
                if (
                  !empty($request['arg_string']) and
                  $indentVal = Roff_Unit::normalize(trim($request['arg_string'])) // note this filters out 0s
                ) {
                    $className .= '-' . $indentVal;
                }
                $pre->setAttribute('class', $className);
            }
        }

        BlockPreformatted::handle($pre, $preLines);
        $parentNode->appendBlockIfHasContent($pre);

        return $i;
    }


}
