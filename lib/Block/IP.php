<?php


class Block_IP
{

    static function check(string $string)
    {
        if (preg_match('~^\.\s*IP ?(.*)$~u', $string, $matches)) {
            return $matches;
        }

        return false;
    }

    static function checkAppend(DOMElement $parentNode, array $lines, int $i)
    {

        // TODO $matches will contain the indentation level, try to use this to handle nested dls?
        $matches = self::check($lines[$i]);
        if ($matches === false) {
            return false;
        }

        $ipArgs = Macro::parseArgString($matches[1]);

        // 2nd bit: If there's a "designator" - otherwise preg_match hit empty double quotes.
        if (!is_null($ipArgs) && trim($ipArgs[0]) !== '') {
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
            $line = $lines[$i];
            if (preg_match('~^\.\s*IP ?(.*)$~u', $line, $matches)) {
                $ipArgs = Macro::parseArgString($matches[1]);
                // 2nd bit: If there's a "designator" - otherwise preg_match hit empty double quotes.
                if (!is_null($ipArgs) and trim($ipArgs[0]) !== '') {
                    if (
                      is_null($firstIndent) and
                      count($ipArgs) > 1 and
                      $indentVal = Roff_Unit::normalize($ipArgs[1]) // note this filters out 0s
                    ) {
                        $firstIndent = 'indent-' . $indentVal;
                        $dl->setAttribute('class', $firstIndent);
                    }
                    $dt = $dom->createElement('dt');
                    TextContent::interpretAndAppendText($dt, $ipArgs[0]);
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
            $line = $lines[$i];
            if (preg_match('~^\.\s*IP ?(.*)$~u', $line, $matches)) {
                $ipArgs = Macro::parseArgString($matches[1]);
                if (!is_null($ipArgs) and trim($ipArgs[0]) !== '') {
                    --$i;
                    break;
                }
            } else {
                --$i;
                break;
            }
            $i = Block_DataDefinition::checkAppend($block, $lines, $i + 1);
        }

        $parentNode->appendBlockIfHasContent($block);

        return $i;

    }


}
