<?php


class Block_RS
{
    
    // TODO: see munch.6 Copyright section and .RS 0

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        if (!preg_match('~^\.RS ?(.*)$~u', $lines[$i], $matches)) {
            return false;
        }

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;

        $rsLevel = 1;
        $rsLines = [];
        for ($i = $i + 1; $i < $numLines; ++$i) {
            $line = $lines[$i];
            if (preg_match('~^\.RS~u', $line)) {
                ++$rsLevel;
            } elseif (preg_match('~^\.RE~u', $line)) {
                --$rsLevel;
                if ($rsLevel === 0) {
                    break;
                }
            }
            $rsLines[] = $line;
        }

        $rsBlock   = $dom->createElement('div');
        $className = 'indent';
        if (!empty($matches[1])) {
            $className .= '-' . trim($matches[1]);
        }
        $rsBlock->setAttribute('class', $className);

        Blocks::handle($rsBlock, $rsLines);
        $parentNode->appendBlockIfHasContent($rsBlock);

        return $i;

    }


}
