<?php


class Block_DataDefinition implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        int $i,
        ?array $arguments = null,
        ?string $request = null,
        $needOneLineOnly = false
    ) {

        $blockLines = [];
        $rsLevel    = 0;

        for (; $i < count($lines); ++$i) {

            $request = Request::getLine($lines, $i);

            if ($request['request'] === 'RS') {
                ++$rsLevel;
            } elseif ($request['request'] === 'RE') {
                --$rsLevel;
            }

            $hitIP      = false;
            $hitBlankIP = false;
            if ($request['request'] === 'IP') {
                $hitIP      = true;
                $hitBlankIP = count($request['arguments']) === 0 || trim($request['arguments'][0]) === '';
            }

            // <= 0 for stray .REs
            if ($rsLevel <= 0) {
                if (
                    in_array($request['request'], ['LP', 'PP', 'P']) &&
                    $i < count($lines) - 1 && Request::getLine($lines, $i + 1)['request'] === 'TP'
                ) {
                    // skip this line: last .PP used to visually separate .TP entries, keep as one dl
                    continue;
                } elseif (
                    in_array($request['request'], ['HP', 'TP', 'LP', 'PP', 'P', 'SS', 'SH', 'TQ', 'TH']) ||
                    ($hitIP && !$hitBlankIP)
                ) {
                    --$i;
                    break;
                }
            }

            if ($hitBlankIP) {
                $blockLines[] = ''; // Empty creates new paragraph in block, see dir.1
            } else {
                if ($i < count($lines) - 1 || ($i < count($lines) && trim($lines[$i]) !== '')) {
                    $blockLines[] = $lines[$i];
                }
            }
        }

        Blocks::trim($blockLines);
        Roff::parse($parentNode, $blockLines);

        return $i;

    }

}
