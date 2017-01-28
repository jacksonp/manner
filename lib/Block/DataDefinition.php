<?php


class Block_DataDefinition
{

    static function append(HybridNode $parentNode, array &$lines): void
    {

        $blockLines = [];
        $rsLevel    = 0;

        while ($nextRequest = Request::getLine($lines)) {

            if ($nextRequest['request'] === 'RS') {
                ++$rsLevel;
            } elseif ($nextRequest['request'] === 'RE') {
                --$rsLevel;
            }

            $hitIP      = false;
            $hitBlankIP = false;
            if ($nextRequest['request'] === 'IP') {
                $hitIP      = true;
                $hitBlankIP = count($nextRequest['arguments']) === 0 || trim($nextRequest['arguments'][0]) === '';
            }

            // <= 0 for stray .REs
            if ($rsLevel <= 0 || in_array($nextRequest['request'], ['SS', 'SH'])) {
                if (
                    in_array($nextRequest['request'], ['LP', 'PP', 'P']) &&
                    count($lines) > 1 && Request::peepAt($lines[1])['name'] === 'TP'
                ) {
                    // skip this line: last .PP used to visually separate .TP entries, keep as one dl
                    array_shift($lines);
                    continue;
                } elseif (
                    in_array($nextRequest['request'], ['HP', 'TP', 'LP', 'PP', 'P', 'SS', 'SH', 'TQ', 'TH']) ||
                    ($hitIP && !$hitBlankIP)
                ) {
                    break;
                }
            }

            $line = array_shift($lines);

            if ($hitBlankIP) {
                $blockLines[] = ''; // Empty creates new paragraph in block, see dir.1
            } else {
                if (count($lines) || trim($line) !== '') {
                    $blockLines[] = $line;
                }
            }
        }

        Roff::parse($parentNode, $blockLines);

    }

}
