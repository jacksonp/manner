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

            if (Request::is($line, 'RS')) {
                ++$rsLevel;
            } elseif (Request::is($line, 'RE')) {
                --$rsLevel;
            }

            $hitIP      = false;
            $hitBlankIP = false;
            if (preg_match('~^\.\s*IP ?(.*)$~u', $line, $nextIPMatches)) {
                $hitIP      = true;
                $nextIPArgs = Request::parseArguments($nextIPMatches[1]);
                $hitBlankIP = count($nextIPArgs) === 0 || trim($nextIPArgs[0]) === '';
            }

            // <= 0 for stray .REs
            if ($rsLevel <= 0) {
                if (Request::is($line, ['LP', 'PP', 'P']) and $i < $numLines - 1 and Request::is($lines[$i + 1], 'TP')) {
                    // skip this line: last .PP used to visually separate .TP entries, keep as one dl
                    continue;
                } elseif (
                  Request::is($line, ['HP', 'TP', 'LP', 'PP', 'P', 'SS', 'SH', 'TQ', 'TH']) or
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
