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
            $line = $lines[$i];
            if (Request::is($line, ['ta', 'nf'])) {
                continue; // Swallow
            } elseif (Request::is($line, 'fi')) {
                break; // Finished
            } elseif (mb_strpos($line, $delim) === 0) {
                $cells = preg_split('~' . preg_quote($delim, '~') . '~u', $line);
                array_shift($cells);
                $cells = array_map(function ($contents) use ($pad) {
                    return trim($contents, $pad);
                }, $cells);
                self::addRow($dom, $table, $cells);
            } elseif (mb_strpos($line, "\t") !== 0) {
                $cells = preg_split("~\t~u", $line);
                self::addRow($dom, $table, $cells);
            } else {
                throw new Exception('Unexpected ' . $line);
            }
        }

        $parentNode->appendBlockIfHasContent($table);

        return $i;

    }

}
