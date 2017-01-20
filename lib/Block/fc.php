<?php


class Block_fc
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

    static function checkAppend(HybridNode $parentNode, array $lines, int $i, array $arguments)
    {

        $delim = $arguments[0];
        $pad   = $arguments[1];

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;

        $table = $dom->createElement('table');
        for ($i = $i + 1; $i < $numLines; ++$i) {
            $request = Request::getLine($lines, $i);
            if (in_array($request['request'], ['ta', 'nf'])) {
                continue; // Swallow
            } elseif ($request['request'] === 'fi') {
                break; // Finished
            } elseif (mb_strpos($lines[$i], $delim) === 0) {
                $cells = preg_split('~' . preg_quote($delim, '~') . '~u', $line);
                array_shift($cells);
                $cells = array_map(function ($contents) use ($pad) {
                    return trim($contents, $pad);
                }, $cells);
                self::addRow($dom, $table, $cells);
            } elseif (mb_strpos($lines[$i], "\t") !== 0) {
                $cells = preg_split("~\t~u", $lines[$i]);
                self::addRow($dom, $table, $cells);
            } else {
                throw new Exception('Unexpected ' . $lines[$i]);
            }
        }

        $parentNode->appendBlockIfHasContent($table);

        return $i;

    }

}
