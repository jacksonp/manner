<?php

/**
 * Make tables out of tab-separated lines
 */
class Block_TabTable implements Block_Template
{

    static function isStart($lines, $i)
    {
        // char before tab avoid indented stuff + exclude escaped tabs
        return
            $i < count($lines) - 2 &&
            !in_array(mb_substr($lines[$i], 0, 1), ['.', '\'']) &&
            mb_strpos($lines[$i], "\t") > 0 &&
            preg_match('~[^\\\\\s]\t~u', $lines[$i]) &&
            (
                (
                    preg_match('~[^\\\\\s]\t~u', $lines[$i + 1]) && mb_strpos($lines[$i + 1], "\t") > 0) ||
                (in_array(trim($lines[$i + 1]), ['.br', '', '\\&...'])
                ) &&
                preg_match('~[^\\\\\s]\t~u', $lines[$i + 2]) &&
                mb_strpos($lines[$i + 2], "\t") > 0
            );
    }

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        ?array $arguments = null,
        ?string $request = null,
        $needOneLineOnly = false
    ) {

        $isStart = self::isStart($lines, 0);
        if (!$isStart) {
            return false;
        }

        $dom = $parentNode->ownerDocument;

        $table = $dom->createElement('table');
        $parentNode->appendChild($table);

        while (count($lines)) {

            if (mb_strpos($lines[0], "\t") === false && $lines[0] !== '\\&...') { // \&... see pmlogextract.1
                break;
            }

            $line = array_shift($lines);

            if (in_array(trim($line), ['.br', ''])) {
                continue;
            }

            $tds = preg_split('~\t+~u', $line);
            $tr  = $table->appendChild($dom->createElement('tr'));
            foreach ($tds as $tdLine) {
                $cell = $dom->createElement('td');
                TextContent::interpretAndAppendText($cell, $tdLine);
                $tr->appendChild($cell);
            }

        }

        return 0;

    }


}
