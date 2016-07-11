<?php


class Block_nf
{

    static function check(string $string)
    {
        if (preg_match('~^\.\s*nf\s?(.*)$~u', $string, $matches)) {
            // Don't actually expect anything in $matches[1]
            return $matches;
        }

        return false;
    }

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        $matches = self::check($lines[$i]);
        if ($matches === false) {
            return false;
        }

        // These get swallowed:
        $blockEnds = '~^\.(fi|ad|ad n|ad b)(?:\s|$)~u';
        $numLines  = count($lines);
        $dom       = $parentNode->ownerDocument;

        $preLines = [];
        while ($i < $numLines - 1) {
            $line = $lines[$i + 1];
            if (preg_match('~^\.\s*S[SH]~u', $line)) {
                break;
            } elseif (preg_match($blockEnds, $line)) {
                while ($i < $numLines - 1 and preg_match($blockEnds, $lines[$i + 1])) {
                    ++$i;
                }
                break;
            } elseif ($line !== '.ad l') {
                $preLines[] = $line;
            }
            ++$i;
        }

        if (count($preLines) === 0) {
            return $i;
        }

        if (
          $i < $numLines - 1 and
          preg_match('~\.RS~u', $preLines[0]) and
          $lines[$i + 1] === '.RE'
        ) {
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

        if (preg_match('~^\.\s*RS ?(.*)$~u', $preLines[0], $matches)) {
            if (!preg_match('~^\.\s*RE~u', array_pop($preLines))) {
                throw new Exception('.nf block contains initial .RS but not final .RE');
            }
            array_shift($preLines);
            ArrayHelper::trim($preLines, ['', '.br', '.sp']);
            $className = 'indent';
            if (
              !empty($matches[1]) and
              $indentVal = Roff_Unit::normalize(trim($matches[1])) // note this filters out 0s
            ) {
                $className .= '-' . $indentVal;
            }
            $pre->setAttribute('class', $className);
        }

        BlockPreformatted::handle($pre, $preLines);
        $parentNode->appendBlockIfHasContent($pre);

        return $i;
    }


}
