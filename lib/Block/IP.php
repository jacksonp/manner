<?php


class Block_IP
{

    static function checkAppend(DOMElement $parentNode, array $lines, int $i, array $arguments)
    {

        // TODO $arguments will contain the indentation level, try to use this to handle nested dls?

        // 2nd bit: If there's a "designator" - otherwise preg_match hit empty double quotes.
        if (count($arguments) > 0 and trim($arguments[0]) !== '') {
            return self::appendDl($parentNode, $lines, $i);
        } else {
            return self::appendBlockquote($parentNode, $lines, $i);
        }

    }

    static function appendDl(DOMElement $parentNode, array $lines, int $i)
    {

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;

        $dl          = $dom->createElement('dl');
        $firstIndent = null;

        for (; $i < $numLines; ++$i) {
            $line    = $lines[$i];
            $request = Request::get($line);
            if ($request['request'] === 'IP') {
                // 2nd bit: If there's a "designator" - otherwise preg_match hit empty double quotes.
                if (count($request['arguments']) and trim($request['arguments'][0]) !== '') {
                    if (
                      is_null($firstIndent) and
                      count($request['arguments']) > 1 and
                      $indentVal = Roff_Unit::normalize($request['arguments'][1]) // note this filters out 0s
                    ) {
                        $firstIndent = 'indent-' . $indentVal;
                        $dl->setAttribute('class', $firstIndent);
                    }
                    $dt = $dom->createElement('dt');
                    TextContent::interpretAndAppendText($dt, $request['arguments'][0]);
                    $dl->appendChild($dt);
                    $dd = $dom->createElement('dd');
                    $i  = Block_DataDefinition::checkAppend($dd, $lines, $i + 1);
                    $dl->appendBlockIfHasContent($dd);
                }
            } else {
                --$i;
                break;
            }
        }

        Block_DefinitionList::appendDL($parentNode, $dl);

        return $i;

    }

    static function appendBlockquote(DOMElement $parentNode, array $lines, int $i)
    {

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;

        $block = $dom->createElement('blockquote');

        for (; $i < $numLines; ++$i) {
            $line    = $lines[$i];
            $request = Request::get($line);
            if (
              $request['request'] !== 'IP' or
              (count($request['arguments']) > 0 and trim($request['arguments'][0]) !== '')
            ) {
                --$i;
                break;
            }
            $i = Block_DataDefinition::checkAppend($block, $lines, $i + 1);
        }

        $parentNode->appendBlockIfHasContent($block);

        return $i;

    }


}
