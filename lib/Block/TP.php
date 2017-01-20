<?php


class Block_TP
{

    static function checkAppend(HybridNode $parentNode, array &$lines, int $i)
    {

        if ($i < count($lines) - 1 && $lines[$i + 1] === '.nf') {
            // Switch .TP and .nf around, and try again. See e.g. elasticdump.1
            $lines[$i + 1] = $lines[$i];
            $lines[$i]     = '.nf';

            return $i - 1;
        }

        $dom = $parentNode->ownerDocument;

        $dl          = $dom->createElement('dl');
        $firstIndent = null;

        for (; $i < count($lines); ++$i) {

            $request = Request::getClass($lines, $i);

            if ($request['class'] === 'Block_TP') {
                if ($i === count($lines) - 1 || Request::getClass($lines, $i + 1)['class'] === 'Block_TP') {
                    // a bug in the man page, just skip:
                    continue;
                }

                if (is_null($firstIndent) && count($request['arguments']) > 0) {
                    $firstIndent = 'indent';
                    if ($indentVal = Roff_Unit::normalize($request['arguments'][0])) { // note: filters out 0s
                        $firstIndent = 'indent-' . $indentVal;
                        $dl->setAttribute('class', $firstIndent);
                    }
                }

                $dt         = $dom->createElement('dt');
                $callerArgs = null;
                $i          = Roff::parse($dt, $lines, $callerArgs, $i + 1) - 1;

                $dl->appendBlockIfHasContent($dt);

                while ($i < count($lines)) {
                    $request = Request::getLine($lines, $i);
                    if ($request['request'] === 'TQ') {
                        $dt = $dom->createElement('dt');
                        $i  = Roff::parse($dt, $lines, $callerArgs, $i + 1) - 1;
                        $dl->appendBlockIfHasContent($dt);
                    } else {
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
