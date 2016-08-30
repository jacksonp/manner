<?php


class Block_fc
{

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

                $cells = preg_split('~' . preg_quote($delim, '~') . '~', $line);
                array_shift($cells);

                $tr = $dom->createElement('tr');
                foreach ($cells as $contents) {
                    $contents = trim($contents, $pad);
                    $td       = $dom->createElement('td');
                    TextContent::interpretAndAppendText($td, $contents);
                    $tr->appendChild($td);
                }
                $table->appendChild($tr);


            } else {
                throw new Exception('Unexpected ' . $line);
            }
        }

        $parentNode->appendBlockIfHasContent($table);

        return $i;

    }

}
