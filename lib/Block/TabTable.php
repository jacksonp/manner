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
        int $i,
        ?array $arguments = null,
        ?string $request = null,
        $needOneLineOnly = false
    ) {

        $numLines = count($lines);
        $line     = $lines[$i];

        $isStart = self::isStart($lines, $i);
        if (!$isStart) {
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

            if (in_array(trim($line), ['.br', ''])) {
                ++$i;
                if ($i === $numLines - 1) {
                    return $i;
                }
                $line = $lines[$i + 1];
            }

            if (mb_strpos($line, "\t") === false && $line !== '\\&...') { // \&... see pmlogextract.1
                return $i;
            }

        }

        throw new Exception('Should not get to the end of this function.');

    }


}
