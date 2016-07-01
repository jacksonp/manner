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

        $dl          = $dom->createElement('dl');
        $firstIndent = null;

        for (; $i < $numLines; ++$i) {
            $line = $lines[$i];
            if (preg_match('~^\.TP ?(.*)$~u', $line, $matches)) {
                if ($i === $numLines - 1 or preg_match('~^\.TP ?(.*)$~u', $lines[$i + 1])) {
                    // a bug in the man page, just skip:
                    continue;
                }

                $requestArgs = Macro::parseArgString($matches[1]);
                if (is_null($firstIndent) and count($requestArgs) > 0) {
                    $firstIndent = 'indent-' . $requestArgs[0];
                    $dl->setAttribute('class', $firstIndent);
                }

                $dtLine = $lines[++$i];
                while (in_array($dtLine, ['.fi', '.B', '.'])) { // cutter.1, blackbox.1
                    if ($i < $numLines - 1) {
                        $dtLine = $lines[++$i];
                    } else {
                        break 2;
                    }
                }

                // e.g. albumart-qt.1, ipmitool.1:
                if (in_array($dtLine, ['.br', '.sp']) or Blocks::lineEndsBlock($lines, $i)) {
                    --$i;
                    break; // i.e. skip the .TP line
                }

                if (preg_match('~^\.UR(\s|$)~u', $dtLine)) {
                    $dtLines = [$dtLine];
                    while ($i < $numLines) {
                        $dtLine = $lines[++$i];
                        if ($dtLine === '.UE') {
                            break;
                        }
                        $dtLines[] = $dtLine;
                    }
                } else {
                    $dtLines = [$dtLine];
                }

                $dt = $dom->createElement('dt');
                Blocks::handle($dt, $dtLines);
                $dl->appendBlockIfHasContent($dt);

                for ($i = $i + 1; $i < $numLines; ++$i) {
                    $line = $lines[$i];
                    if (preg_match('~^\.TQ$~u', $line)) {
                        $dtLine = $lines[++$i];
                        $dt     = $dom->createElement('dt');
                        Blocks::handle($dt, [$dtLine]);
                        $dl->appendChild($dt);
                    } else {
                        --$i;
                        break;
                    }
                }

                $dd = $dom->createElement('dd');
                $i  = Block_DataDefinition::checkAppend($dd, $lines, $i + 1);
                $dl->appendBlockIfHasContent($dd);

            } else {
                --$i;
                break;
            }
        }

        $parentNode->appendBlockIfHasContent($dl);

        return $i;

    }

}
