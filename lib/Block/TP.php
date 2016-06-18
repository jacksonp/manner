<?php


class Block_TP
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        // TODO $matches[1] will contain the indentation level, try to use this to handle nested dls?
        if (!preg_match('~^\.TP ?(.*)$~u', $lines[$i], $matches)) {
            return false;
        }

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;

        // if this is the last line in a section, it's a bug in the man page, just ignore.
        if ($i === $numLines - 1 or $lines[$i + 1] === '.TP') {
            return $i;
        }

        $dtLine = $lines[++$i];
        while ($i < $numLines - 1 && in_array($dtLine, ['.fi', '.B'])) { // cutter.1
            $dtLine = $lines[++$i];
        }
        if (in_array($dtLine, ['.br', '.sp', '.B'])) { // e.g. albumart-qt.1, ipmitool.1, blackbox.1
            return $i - 1; // i.e. skip the .TP line
        } else {
            if (!$parentNode->hasChildNodes() or $parentNode->lastChild->tagName !== 'dl') {
                $dl = $dom->createElement('dl');
                $parentNode->appendChild($dl);
            } else {
                $dl = $parentNode->lastChild;
            }
            $dt = $dom->createElement('dt');
            TextContent::interpretAndAppendCommand($dt, $dtLine);
            $dl->appendChild($dt);

            for ($i = $i + 1; $i < $numLines; ++$i) {
                $line = $lines[$i];
                if (preg_match('~^\.TQ$~u', $line)) {
                    $dtLine = $lines[++$i];
                    $dt     = $dom->createElement('dt');
                    TextContent::interpretAndAppendCommand($dt, $dtLine);
                    $dl->appendChild($dt);
                } else {
                    --$i;
                    break;
                }
            }

            $dd = $dom->createElement('dd');
            $i  = Block_DataDefinition::checkAppend($dd, $lines, $i + 1);
            $dl->appendBlockIfHasContent($dd);

            return $i;
        }
    }

}
