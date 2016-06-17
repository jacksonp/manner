<?php

/**
 * Make tables out of tab-separated lines
 */
class Block_TabTable
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        $numLines = count($lines);
        $line     = $lines[$i];

        // mb_strpos() > 0: avoid indented stuff
        if ($i === $numLines - 1 or $line === '' or $line[0] === '.' or !(mb_strpos($line, "\t") > 0)) {
            return false;
        }

        if (
          !(mb_strpos($lines[$i + 1], "\t") > 0) and
          (
            !in_array($lines[$i + 1], ['.br', '']) or
            $i === $numLines - 2 or
            !(mb_strpos($lines[$i + 2], "\t") > 0)
          )
        ) {
            return false;
        }

        $dom = $parentNode->ownerDocument;

        $table = $dom->createElement('table');
        $parentNode->appendChild($table);
        for (; ; ++$i) {

            $tds = preg_split('~\t+~u', $line);
            $tr  = $table->appendChild($dom->createElement('tr'));
            foreach ($tds as $tdLine) {
                $cell = $dom->createElement('td');
                TextContent::interpretAndAppendText($cell, $tdLine);
                $tr->appendChild($cell);
            }

            if ($i === $numLines - 1) {
                return $i;
            }

            $line = $lines[$i + 1];

            if (in_array($line, ['.br', ''])) {
                ++$i;
                if ($i === $numLines - 1) {
                    return $i;
                }
                $line = $lines[$i + 1];
            }

            if (mb_strpos($line, "\t") === false) {
                return $i;
            }

        }
    }


}
