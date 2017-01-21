<?php


class BlockPreformatted
{

    public static function handle(HybridNode $parentNode, array $lines)
    {

        $dom = $parentNode->ownerDocument;

        $addIndent  = 0;
        $nextIndent = 0;
        while (count($lines)) {

            if ($nextIndent !== 0) {
                $addIndent  = $nextIndent;
                $nextIndent = 0;
            }

            $request = Request::getClass($lines, 0);
            $line    = $request['raw_line'];

            if (in_array($request['class'], ['Block_P', 'Inline_VerticalSpace', 'Empty_Request'])) {
                if ($parentNode->hasChildNodes() && count($lines)) {
                    $parentNode->appendChild(new DOMText("\n"));
                    $addIndent = 0;
                }
                array_shift($lines);
                continue;
            } elseif (in_array(
                $request['class'],
                ['Inline_FontOneInputLine', 'Inline_AlternatingFont', 'Inline_ft', 'Request_Skippable']
            )) {
                $newI = $request['class']::checkAppend($parentNode, $lines, $request['arguments'],
                    $request['request']);
                if ($newI !== false) {
                    if (count($lines) && $request['class'] !== 'Request_Skippable') {
                        self::endInputLine($parentNode);
                    }
                    continue;
                }
            } elseif ($request['request'] === 'IP') {
                $nextIndent = 4;
                if (count($request['arguments']) === 0 || trim($request['arguments'][0]) === '') {
                    array_shift($lines);
                    continue;
                } else {
                    $line = $request['arguments'][0];
                }
            } elseif ($request['request'] === 'TP') {
                $addIndent  = 0;
                $nextIndent = 4;
                array_shift($lines);
                continue;
            } elseif ($request['request'] === 'ti') {
                $nextIndent = 4;
                array_shift($lines);
                continue;
            } elseif (
                in_array($request['request'], ['nf', 'RS', 'RE', 'fi', 'ce']) ||
                in_array($line, ['\\&', '\\)'])
            ) {
                array_shift($lines);
                continue;
            } elseif ($request['request'] === 'OP') {
                $parentNode->appendChild(new DOMText('['));
                $strong = $parentNode->appendChild($dom->createElement('strong'));
                TextContent::interpretAndAppendText($strong, $request['arguments'][0]);
                if (count($request['arguments']) > 1) {
                    $parentNode->appendChild(new DOMText(' '));
                    $em = $parentNode->appendChild($dom->createElement('em'));
                    TextContent::interpretAndAppendText($em, $request['arguments'][1]);
                }
                $parentNode->appendChild(new DOMText('] '));
                array_shift($lines);
                continue;
            }


            if ($addIndent > 0) {
                $parentNode->appendChild(new DOMText(str_repeat(' ', $addIndent)));
            }

            // FAIL on unknown command
            if (mb_strlen($line) > 0 && mb_substr($line[0], 0, 1) === Man::instance()->control_char) {
                throw new Exception($line . ' unexpected command in BlockPreformatted::handle().');
            }

            TextContent::interpretAndAppendText($parentNode, $line);
            if (count($lines)) {
                self::endInputLine($parentNode);
            }
            array_shift($lines);

        }

        while (
            $parentNode->lastChild &&
            $parentNode->lastChild instanceof DOMText &&
            trim($parentNode->lastChild->textContent) === ''
        ) {
            $parentNode->removeChild($parentNode->lastChild);
        }

    }

    private static function endInputLine(DOMElement $parentNode)
    {
        if (TextContent::$continuation || $parentNode->getAttribute('class') === 'synopsis') {
            $parentNode->appendChild(new DOMText(' '));
        } else {
            $parentNode->appendChild(new DOMText("\n"));
        }
    }

}
