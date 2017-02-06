<?php
declare(strict_types = 1);

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
        $table->appendBlockIfHasContent($tr);
    }

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        $delim = $request['arguments'][0];
        $pad   = $request['arguments'][1];

        $dom = $parentNode->ownerDocument;

        $table = $dom->createElement('table');

        // We don't want to handle the lines at this stage as a fresh call to .fc call a new Roff_fc, so don't iterate
        // with Request::getLine()
        while (count($lines)) {

            // Don't process next line yet, could be new .fc
            $requestDetails = Request::peepAt($lines[0]);

            if (
                $requestDetails['name'] === 'fi' ||
                ($requestDetails['name'] === 'fc' && $requestDetails['raw_arg_string'] === '')
            ) {
                array_shift($lines);
                break; // Finished
            }

            $nextRequest = Request::getLine($lines);
            array_shift($lines);

            if (in_array($nextRequest['request'], ['ta', 'nf', 'br'])) {
                continue; // Ignore
            } elseif (mb_strpos($nextRequest['raw_line'], $delim) === 0) {
                $cells = preg_split('~' . preg_quote($delim, '~') . '~u', $nextRequest['raw_line']);
                array_shift($cells);
                $cells = array_map(function ($contents) use ($pad) {
                    return trim($contents, $pad);
                }, $cells);
                self::addRow($dom, $table, $cells);
            } elseif (mb_strpos($nextRequest['raw_line'], "\t") !== 0) {
                $cells = preg_split("~\t~u", $nextRequest['raw_line']);
                self::addRow($dom, $table, $cells);
            } else {
                throw new Exception('Unexpected ' . $nextRequest['raw_line']);
            }
        }

        $parentNode->appendBlockIfHasContent($table);

        return null;

    }

}
