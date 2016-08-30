<?php


class Block_DataDefinition
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        $numLines = count($lines);

        $blockLines = [];
        $rsLevel    = 0;

        for (; $i < $numLines; ++$i) {
            $line = $lines[$i];

            $request = Request::get($line);

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
                  in_array($request['request'], ['LP', 'PP', 'P']) and
                  $i < $numLines - 1 and Request::is($lines[$i + 1], 'TP')
                ) {
                    // skip this line: last .PP used to visually separate .TP entries, keep as one dl
                    continue;
                } elseif (
                  in_array($request['request'], ['HP', 'TP', 'LP', 'PP', 'P', 'SS', 'SH', 'TQ', 'TH']) or
                  ($hitIP and !$hitBlankIP)
                ) {
                    --$i;
                    break;
                }
            }

            if ($hitBlankIP) {
                $blockLines[] = ''; // Empty creates new paragraph in block, see dir.1
            } else {
                if ($i < $numLines - 1 or trim($line) !== '') {
                    $blockLines[] = $line;
                }
            }
        }

        if (count($blockLines) > 0) {
            Blocks::handle($parentNode, $blockLines);
        }

        return $i;

    }

}
