<?php


class Block_RS
{

    // TODO: see munch.6 Copyright section and .RS 0

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        if (!preg_match('~^\.RS ?(.*)$~u', $lines[$i], $matches)) {
            return false;
        }

        $thisIndent = trim($matches[1]);
        $numLines   = count($lines);
        $dom        = $parentNode->ownerDocument;
        $skippedRSs = 0;

        $rsLevel    = 1;
        $blockLines = [];
        for ($i = $i + 1; $i < $numLines; ++$i) {
            $line = $lines[$i];
            if (preg_match('~^\.RS ?(.*)$~u', $line, $matches)) {
                if (trim($matches[1]) === $thisIndent) {
                    if (count($blockLines) > 0 and !in_array($blockLines[count($blockLines) - 1], ['.sp', '.br'])) {
                        $blockLines[] = '.br';
                    }
                    ++$skippedRSs;
                    continue;
                } else {
                    ++$rsLevel;
                }
            } elseif (preg_match('~^\.RE~u', $line)) {
                if ($skippedRSs > 0) {
                    --$skippedRSs;
                    continue;
                } else {
                    --$rsLevel;
                    if ($rsLevel === 0) {
                        break;
                    }
                }
            }
            $blockLines[] = $line;
        }

        if (count($blockLines) > 0) {
            $rsBlock   = $dom->createElement('div');
            $className = 'indent';
            if ($thisIndent !== '') {
                $className .= '-' . $thisIndent;
            }
            $rsBlock->setAttribute('class', $className);

            Blocks::handle($rsBlock, $blockLines);
            $parentNode->appendBlockIfHasContent($rsBlock);
        }

        return $i;

    }


}
