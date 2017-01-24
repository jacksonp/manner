<?php


class Block_TP implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement {

        if (count($lines) > 1 && $lines[1] === '.nf') {
            // Switch .TP and .nf around, and try again. See e.g. elasticdump.1
            $lines[1] = $lines[0];
            $lines[0] = '.nf';
            return null;
        }

        $dom = $parentNode->ownerDocument;

        $dl          = $dom->createElement('dl');
        $firstIndent = null;

        while ($request = Request::getLine($lines)) {

            if (in_array($request['request'], ['TP', 'TQ'])) {

                array_shift($lines);

                if (count($lines) === 0 || in_array(Request::getLine($lines)['request'], ['TP', 'TQ', 'IP', 'SH'])) {
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
                Roff::parse($dt, $lines, true);

                $dl->appendBlockIfHasContent($dt);

                while (count($lines)) {
                    $request = Request::getLine($lines);
                    if ($request['request'] === 'TQ') {
                        array_shift($lines);
                        $dt = $dom->createElement('dt');
                        Roff::parse($dt, $lines, true);
                        $dl->appendBlockIfHasContent($dt);
                    } else {
                        break;
                    }
                }

                $dd = $dom->createElement('dd');
                Block_DataDefinition::append($dd, $lines);
                $dl->appendBlockIfHasContent($dd);

            } else {
                break;
            }
        }

        Block_DefinitionList::appendDL($parentNode, $dl);

        return null;

    }

}
