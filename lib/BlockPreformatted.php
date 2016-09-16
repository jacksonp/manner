<?php


class BlockPreformatted
{

    public static function handle(HybridNode $parentNode, array $lines)
    {

        $dom = $parentNode->ownerDocument;

        $addIndent  = 0;
        $nextIndent = 0;
        $numLines   = count($lines);
        for ($i = 0; $i < $numLines; ++$i) {
            $line = $lines[$i];

            if ($nextIndent !== 0) {
                $addIndent  = $nextIndent;
                $nextIndent = 0;
            }

            $request = Request::getClass($lines, $i);

            if (in_array($request['class'], ['Block_P', 'Inline_VerticalSpace', 'Empty_Request'])) {
                if ($i > 0 && $i !== $numLines - 1) {
                    $parentNode->appendChild(new DOMText("\n"));
                    $addIndent = 0;
                }
                continue;
            } elseif (in_array(
              $request['class'],
              ['Inline_FontOneInputLine', 'Inline_AlternatingFont', 'Inline_ft', 'Request_Skippable']
            )) {
                $newI = $request['class']::checkAppend($parentNode, $lines, $i, $request['arguments'],
                  $request['request']);
                if ($newI !== false) {
                    if ($i !== $numLines - 1 && $request['class'] !== 'Request_Skippable') {
                        self::endInputLine($parentNode);
                    }
                    $i = $newI;
                    continue;
                }
            } elseif ($request['request'] === 'IP') {
                $nextIndent = 4;
                if (count($request['arguments']) === 0 || trim($request['arguments'][0]) === '') {
                    continue;
                } else {
                    $line = $request['arguments'][0];
                }
            } elseif ($request['request'] === 'TP') {
                if ($i === $numLines - 1) {
                    continue;
                }
                $addIndent  = 0;
                $nextIndent = 4;
                continue;
            } elseif ($request['request'] === 'ti') {
                $nextIndent = 4;
                continue;
            } elseif (
              in_array($request['request'], ['nf', 'RS', 'RE', 'fi', 'ce']) ||
              in_array($line, ['\\&', '\\)'])
            ) {
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
            if ($i !== $numLines - 1) {
                self::endInputLine($parentNode);
            }

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
