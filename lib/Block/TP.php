<?php


class Block_TP
{

    static function check(string $string)
    {
        if (preg_match('~^\.\s*T[PQ] ?(.*)$~u', $string, $matches)) {
            return $matches;
        }

        return false;
    }

    static function checkAppend(HybridNode $parentNode, array &$lines, int $i)
    {

        // TODO $matches[1] will contain the indentation level, try to use this to handle nested dls?
        $matches = self::check($lines[$i]);
        if ($matches === false) {
            return false;
        }

        $numLines = count($lines);

        if ($i < $numLines - 1 and $lines[$i + 1] === '.nf') {
            // Switch .TP and .nf around, and try again. See e.g. elasticdump.1
            $lines[$i + 1] = $lines[$i];
            $lines[$i]     = '.nf';

            return $i - 1;
        }

        $dom = $parentNode->ownerDocument;

        $dl          = $dom->createElement('dl');
        $firstIndent = null;

        for (; $i < $numLines; ++$i) {
            $line = $lines[$i];
            if (preg_match('~^\.\s*T[PQ] ?(.*)$~u', $line, $matches)) {
                if ($i === $numLines - 1 or preg_match('~^\.\s*[IT]P ?(.*)$~u', $lines[$i + 1])) {
                    // a bug in the man page, just skip:
                    continue;
                }

                $requestArgs = Macro::parseArgString($matches[1]);
                if (is_null($firstIndent) and count($requestArgs) > 0) {
                    $firstIndent = 'indent';
                    if ($indentVal = Roff_Unit::normalize($requestArgs[0])) { // note: filters out 0s
                        $firstIndent = 'indent-' . $indentVal;
                        $dl->setAttribute('class', $firstIndent);
                    }
                }

                $result = Block_Text::getNextInputLine($lines, $i + 1);
                $i      = $result['i'];
                $dt     = $dom->createElement('dt');
                Blocks::handle($dt, $result['lines']);
                $dl->appendBlockIfHasContent($dt);

                for ($i = $i + 1; $i < $numLines; ++$i) {
                    $line = $lines[$i];
                    if (preg_match('~^\.\s*TQ$~u', $line)) {
                        $result = Block_Text::getNextInputLine($lines, $i + 1);
                        $i      = $result['i'];
                        $dt     = $dom->createElement('dt');
                        Blocks::handle($dt, $result['lines']);
                        $dl->appendBlockIfHasContent($dt);
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

        Block_DefinitionList::appendDL($parentNode, $dl);

        return $i;

    }

}
