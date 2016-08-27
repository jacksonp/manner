<?php


class Block_fc
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {
        if (!preg_match('~^\.\s*fc\s+(.)\s+(.)$~u', $lines[$i], $matches)) {
            return false;
        }

        $delim = $matches[1];
        $pad   = $matches[2];

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;

        $table = $dom->createElement('table');
        for ($i = $i + 1; $i < $numLines; ++$i) {
            $line = $lines[$i];
            if (preg_match('~^\.\s*(nf|ta)~u', $line)) {
                continue; // Swallow
            } elseif (preg_match('~^\.\s*fi~u', $line)) {
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
