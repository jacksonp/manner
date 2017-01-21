<?php


class Block_IP implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        ?array $arguments = null,
        ?string $request = null,
        $needOneLineOnly = false
    ) {

        // TODO $arguments will contain the indentation level, try to use this to handle nested dls?

        // 2nd bit: If there's a "designator" - otherwise preg_match hit empty double quotes.
        if (count($arguments) > 0 && trim($arguments[0]) !== '') {
            return self::appendDl($parentNode, $lines);
        } else {
            return self::appendBlockquote($parentNode, $lines);
        }

    }

    static function appendDl(DOMElement $parentNode, array &$lines)
    {

        $dom = $parentNode->ownerDocument;

        $dl          = $dom->createElement('dl');
        $firstIndent = null;

        while (count($lines)) {
            $request = Request::getLine($lines, 0);
            if ($request['request'] === 'IP') {
                array_shift($lines);
                // 2nd bit: If there's a "designator" - otherwise preg_match hit empty double quotes.
                if (count($request['arguments']) && trim($request['arguments'][0]) !== '') {
                    if (
                        is_null($firstIndent) &&
                        count($request['arguments']) > 1 &&
                        $indentVal = Roff_Unit::normalize($request['arguments'][1]) // note this filters out 0s
                    ) {
                        $firstIndent = 'indent-' . $indentVal;
                        $dl->setAttribute('class', $firstIndent);
                    }
                    $dt = $dom->createElement('dt');
                    TextContent::interpretAndAppendText($dt, $request['arguments'][0]);
                    $dl->appendChild($dt);
                    $dd = $dom->createElement('dd');
                    Block_DataDefinition::checkAppend($dd, $lines);
                    $dl->appendBlockIfHasContent($dd);
                }
            } else {
                break;
            }
        }

        Block_DefinitionList::appendDL($parentNode, $dl);

        return 0;

    }

    static function appendBlockquote(DOMElement $parentNode, array &$lines)
    {

        $dom = $parentNode->ownerDocument;

        $block = $dom->createElement('blockquote');

        while (count($lines)) {
            $request = Request::getLine($lines, 0);
            if (
                $request['request'] !== 'IP' ||
                (count($request['arguments']) > 0 && trim($request['arguments'][0]) !== '')
            ) {
                break;
            }
            Block_DataDefinition::checkAppend($block, $lines);
        }

        $parentNode->appendBlockIfHasContent($block);

        return 0;

    }


}
