<?php

class Block_fc implements Block_Template
{

    private static function addRow(DOMDocument $dom, DOMElement $table, array $cells)
    {
        $tr = $dom->createElement('tr');
        foreach ($cells as $contents) {
            $td = $dom->createElement('td');
            TextContent::interpretAndAppendText($td, $contents);
            $tr->appendChild($td);
        }
        $table->appendChild($tr);
    }

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        ?array $arguments = null,
        ?string $request = null,
        $needOneLineOnly = false
    ) {

        array_shift($lines);

        $delim = $arguments[0];
        $pad   = $arguments[1];

        $dom = $parentNode->ownerDocument;

        $table = $dom->createElement('table');
        while (count($lines)) {
            $request = Request::getLine($lines, 0);
            array_shift($lines);
            if (in_array($request['request'], ['ta', 'nf'])) {
                continue; // Swallow
            } elseif ($request['request'] === 'fi') {
                break; // Finished
            } elseif (mb_strpos($request['raw_line'], $delim) === 0) {
                $cells = preg_split('~' . preg_quote($delim, '~') . '~u', $request['raw_line']);
                array_shift($cells);
                $cells = array_map(function ($contents) use ($pad) {
                    return trim($contents, $pad);
                }, $cells);
                self::addRow($dom, $table, $cells);
            } elseif (mb_strpos($request['raw_line'], "\t") !== 0) {
                $cells = preg_split("~\t~u", $request['raw_line']);
                self::addRow($dom, $table, $cells);
            } else {
                throw new Exception('Unexpected ' . $request['raw_line']);
            }
        }

        $parentNode->appendBlockIfHasContent($table);

        return 0;

    }

}
