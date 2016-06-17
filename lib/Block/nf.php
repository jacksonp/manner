<?php


class Block_nf
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        // These get swallowed:
        $blockEnds = ['.fi', '.ad', '.ad n', '.ad b'];

        if (!preg_match('~^\.nf~u', $lines[$i])) {
            return false;
        }

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;

        $preLines = [];
        while ($i < $numLines - 1) {
            $line = $lines[$i + 1];
            if (preg_match('~^\.S[SH]~u', $line)) {
                break;
            } elseif (in_array($line, $blockEnds)) {
                while ($i < $numLines - 1 and in_array($lines[$i + 1], $blockEnds)) {
                    ++$i;
                }
                break;
            }
            $preLines[] = $line;
            ++$i;
        }

        ArrayHelper::rtrim($preLines, array_merge($blockEnds, ['', '.br', '.sp']));

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
                            TextContent::interpretAndAppendCommand($codeNode, $request . $tdLine);
                        }
                        $tr->appendChild($cell);
                    }
                }

                return $i;
            }
        }

        $pre = $dom->createElement('pre');

        if (preg_match('~^\.RS ?(.*)$~u', $preLines[0], $matches)) {
            if (!preg_match('~^\.RE~u', array_pop($preLines))) {
                throw new Exception('.nf block contains initial .RS but not final .RE');
            }
            array_shift($preLines);
            $className = 'indent';
            if (!empty($matches[1])) {
                $className .= '-' . trim($matches[1]);
            }
            $pre->setAttribute('class', $className);
        }

        BlockPreformatted::handle($pre, $preLines);
        $parentNode->appendBlockIfHasContent($pre);

        return $i;
    }


}
