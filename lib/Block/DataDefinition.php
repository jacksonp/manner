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

            if (preg_match('~^\.\s*RS~u', $line)) {
                ++$rsLevel;
            } elseif (preg_match('~^\.\s*RE~u', $line)) {
                --$rsLevel;
            }

            $hitIP      = false;
            $hitBlankIP = false;
            if (preg_match('~^\.\s*IP ?(.*)$~u', $line, $nextIPMatches)) {
                $hitIP      = true;
                $nextIPArgs = Macro::parseArgString($nextIPMatches[1]);
                $hitBlankIP = is_null($nextIPArgs) || trim($nextIPArgs[0]) === '';
            }

            // <= 0 for stray .REs
            if ($rsLevel <= 0) {
                if (preg_match('~^\.\s*[LP]?P$~u', $line) and $i < $numLines - 1 and mb_substr($lines[$i + 1], 0, 3) === '.TP') {
                    // skip this line: last .PP used to visually separate .TP entries, keep as one dl
                    continue;
                } elseif (preg_match('~^\.\s*([HTLP]?P|SS|SH|TQ)~u', $line) or ($hitIP && !$hitBlankIP)) {
                    --$i;
                    break;
                }
            }

            if ($hitBlankIP) {
                $blockLines[] = ''; // Empty creates new paragraph in block, see dir.1
            } else {
                if ($i < $numLines - 1 or $line !== '') {
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
