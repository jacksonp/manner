<?php


class Inline_I
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        if (!preg_match('~^\.I(\s.*)?$~u', $lines[$i], $matches)) {
            return false;
        }

        $numLines  = count($lines);
        $dom       = $parentNode->ownerDocument;
        $em        = $dom->createElement('em');
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
            TextContent::interpretAndAppendCommand($em, $nextLine);
        } else {
            TextContent::interpretAndAppendText($em, implode(' ', $arguments), $parentNode->hasContent());
        }

        $parentNode->appendBlockIfHasContent($em);

        return $i;

    }

}
