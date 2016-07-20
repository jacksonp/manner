<?php


class Block_SY
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        // These get swallowed:
        $blockEnds = ['.YS'];

        if (!preg_match('~^\.\s*SY\s?(.*)$~u', $lines[$i], $matches)) {
            return false;
        }

        $numLines    = count($lines);
        $dom         = $parentNode->ownerDocument;
        $commandName = '';
        $arguments   = Request::parseArguments(Request::massageLine($matches[1]));
        if (!is_null($arguments)) {
            $commandName = $arguments[0];
        }

        $pre = $dom->createElement('pre');
        if ($commandName !== '') {
            $commandName = trim(TextContent::interpretString($commandName));
            $pre->setAttribute('class', 'synopsis');
            $pre->appendChild(new DOMText($commandName . ' '));
        }

        $preLines = [];
        for ($i = $i + 1; $i < $numLines; ++$i) {
            if (in_array($lines[$i], $blockEnds)) {
                break;
            } elseif (preg_match('~^\.\s*SY~u', $lines[$i])) {
                --$i;
                break;
            }
            $preLines[] = $lines[$i];
        }

        if (count($preLines) === 0) {
            return $i;
        }

        BlockPreformatted::handle($pre, $preLines);
        $parentNode->appendChild($pre);

        return $i;
    }


}
