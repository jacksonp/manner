<?php


class Inline_B
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        if (!preg_match('~^\.B(\s.*)?$~u', $lines[$i], $matches)) {
            return false;
        }

        $numLines  = count($lines);
        $dom       = $parentNode->ownerDocument;
        $strong    = $dom->createElement('strong');
        $arguments = Macro::parseArgString(@$matches[1]);

        if (is_null($arguments)) {
            if ($i === $numLines - 1) {
                return $i;
            }
            $nextLine = $lines[++$i];
            if (mb_substr($nextLine, 0, 1) === '.') {
                return $i - 1;
            }
            if ($nextLine === '') {
                return $i;
            }
            TextContent::interpretAndAppendCommand($strong, $nextLine);
        } else {
            TextContent::interpretAndAppendText($strong, implode(' ', $arguments), $parentNode->hasContent());
        }

        $parentNode->appendBlockIfHasContent($strong);

        return $i;

    }

}
