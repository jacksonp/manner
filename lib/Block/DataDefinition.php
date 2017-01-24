<?php


class Block_DataDefinition implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        ?array $request = null,
        $needOneLineOnly = false
    ): ?DOMElement {

        $blockLines = [];
        $rsLevel    = 0;

        while ($request = Request::getLine($lines)) {

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
            if ($rsLevel <= 0 || in_array($request['request'], ['SS', 'SH'])) {
                if (
                    in_array($request['request'], ['LP', 'PP', 'P']) &&
                    count($lines) > 1 && Request::getLine($lines, 1)['request'] === 'TP'
                ) {
                    // skip this line: last .PP used to visually separate .TP entries, keep as one dl
                    array_shift($lines);
                    continue;
                } elseif (
                    in_array($request['request'], ['HP', 'TP', 'LP', 'PP', 'P', 'SS', 'SH', 'TQ', 'TH']) ||
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

        Blocks::trim($blockLines);
        Roff::parse($parentNode, $blockLines);

        return null;

    }

}
