<?php


class Block_Preformatted
{

    private static $addIndent = 0;
    private static $nextIndent = 0;

    public static function reset()
    {
        self::$addIndent  = 0;
        self::$nextIndent = 0;
    }

    public static function handle(HybridNode $parentNode, array &$lines, array $request): bool
    {

        if ($parentNode->tagName !== 'pre') {
            return false;
        }

        $dom = $parentNode->ownerDocument;

        if (self::$nextIndent !== 0) {
            self::$addIndent  = self::$nextIndent;
            self::$nextIndent = 0;
        }

        $line = $request['raw_line'];

        if (!$parentNode->hasChildNodes() && count($lines) > 1 && Block_TabTable::lineContainsTab($line) && Block_TabTable::lineContainsTab($lines[1])) {

            // TODO: add "preformatted" table class instead of code tags in cells?
            $table = $parentNode->appendChild($dom->createElement('table'));
            while (Block_TabTable::lineContainsTab($lines[0])) {
                $line = array_shift($lines);
                if (in_array($line, ['.br', ''])) {
                    continue;
                }
                $nextRequest = '';
                // TODO: use Request?
                if (mb_substr($line, 0, 1) === '.') {
                    preg_match('~^(\.\w+ )"?(.*?)"?$~u', $line, $matches);
                    $nextRequest = $matches[1];
                    $line        = $matches[2];
                }
                $tds = preg_split('~\t+~u', $line);
                $tr  = $table->appendChild($dom->createElement('tr'));
                foreach ($tds as $tdLine) {
                    $cell     = $dom->createElement('td');
                    $codeNode = $cell->appendChild($dom->createElement('code'));
                    if (empty($nextRequest)) {
                        TextContent::interpretAndAppendText($codeNode, $tdLine);
                    } else {
                        $blockLines = [$nextRequest . $tdLine];
                        Roff::parse($codeNode, $blockLines);
                    }
                    $tr->appendChild($cell);
                }
            }

            $parentNode->parentNode->insertBefore($table, $parentNode);
            return true;
        }

        if (in_array($request['class'], ['Block_P', 'Inline_VerticalSpace', 'Empty_Request'])) {
            array_shift($lines);
            if ($parentNode->hasChildNodes()) {
                $parentNode->appendChild(new DOMText("\n"));
                self::$addIndent = 0;
            }
            return true;
        } elseif (in_array(
            $request['class'],
            ['Inline_FontOneInputLine', 'Inline_AlternatingFont', 'Inline_ft', 'Request_Skippable']
        )) {
            $request['class']::checkAppend($parentNode, $lines, $request);
            if ($request['class'] !== 'Request_Skippable') {
                self::endInputLine($parentNode);
            }
            return true;
        } elseif ($request['request'] === 'IP') {
            self::$nextIndent = 4;
            if (count($request['arguments']) === 0 || trim($request['arguments'][0]) === '') {
                array_shift($lines);
                return true;
            } else {
                $line               = $request['arguments'][0];
                $request['request'] = null;
            }
        } elseif ($request['request'] === 'TP') {
            self::$addIndent  = 0;
            self::$nextIndent = 4;
            array_shift($lines);
            return true;
        } elseif ($request['request'] === 'ti') {
            self::$nextIndent = 4;
            array_shift($lines);
            return true;
        } elseif (
            in_array($request['request'], ['nf', 'RS', 'RE', 'ce']) ||
            in_array($line, ['\\&', '\\)'])
        ) {
            array_shift($lines);
            return true;
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
            return true;
        }


        if (self::$addIndent > 0) {
            $parentNode->appendChild(new DOMText(str_repeat(' ', self::$addIndent)));
        }

        if (!is_null($request['request'])) {
            return false;
        }

        TextContent::interpretAndAppendText($parentNode, $line);

        self::endInputLine($parentNode);

        array_shift($lines);
        return true;

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
