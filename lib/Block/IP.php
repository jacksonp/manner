<?php


class Block_IP
{

    static function checkAppend(DOMElement $parentNode, array $lines, int $i)
    {

        // TODO:  --group-directories-first in ls.1 - separate para rather than br?
        // TODO $matches[1] will contain the indentation level, try to use this to handle nested dls?
        if (!preg_match('~^\.IP ?(.*)$~u', $lines[$i], $matches)) {
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

    //    Change the functions below to handle .IPs in a sequence (as they make one dl block)

    static function appendDl(DOMElement $parentNode, array $lines, int $i)
    {

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;

        $dl          = $dom->createElement('dl');
        $firstIndent = null;

        for (; $i < $numLines; ++$i) {
            $line = $lines[$i];
            if (preg_match('~^\.IP ?(.*)$~u', $line, $matches)) {
                $ipArgs = Macro::parseArgString($matches[1]);
                // 2nd bit: If there's a "designator" - otherwise preg_match hit empty double quotes.
                if (!is_null($ipArgs) and trim($ipArgs[0]) !== '') {
                    if (is_null($firstIndent) and count($ipArgs) > 1) {
                        $firstIndent = 'indent-' . $ipArgs[1];
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

        $parentNode->appendBlockIfHasContent($dl);

        return $i;

    }

    static function appendBlockquote(DOMElement $parentNode, array $lines, int $i)
    {

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;

        $block = $dom->createElement('blockquote');

        for (; $i < $numLines; ++$i) {
            $line = $lines[$i];
            if (preg_match('~^\.IP ?(.*)$~u', $line, $matches)) {
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
