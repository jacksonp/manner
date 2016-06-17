<?php


class Block_RS
{

    static function checkAppend(HybridNode $parentNode, $lines, $i)
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
            $rsLines[] = $line;
        }

        throw new Exception($lines[$i] . '.RS without corresponding .RE ending at line ' . $i . '. Prev line is "' . @$lines[$i - 2] . '"');

    }


}
