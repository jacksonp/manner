<?php

/**
 * Make tables out of tab-separated lines
 */
class Block_TabTable implements Block_Template
{

    const skippableLines = ['.br', ''];

    // \&... see pmlogextract.1
    const specialAcceptableLines = ['\\&...'];

    private static function isTabTableLine($line)
    {
        $line = trim($line);
        return
            mb_strpos($line, "\t") !== false ||
            in_array($line, self::skippableLines) ||
            in_array($line, self::specialAcceptableLines);
    }

    private static function lineContainsTab(string $line): bool
    {
        // char before tab avoid indented stuff + exclude escaped tabs
        return mb_strpos($line, "\t") > 0 && preg_match('~[^\\\\\s]\t~u', $line);
    }

    static function isStart(array &$lines): bool
    {
        return
            count($lines) > 2 &&
            !in_array(mb_substr($lines[0], 0, 1), ['.', '\'']) &&
            self::lineContainsTab($lines[0]) &&
            (
                self::lineContainsTab($lines[1]) ||
                in_array(trim($lines[1]), self::skippableLines + self::specialAcceptableLines)
            ) &&
            self::lineContainsTab($lines[2]);
    }

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        ?array $arguments = null,
        ?string $request = null,
        $needOneLineOnly = false
    ) {

        if (!self::isStart($lines)) {
            return false;
        }

        $dom = $parentNode->ownerDocument;

        $table = $dom->createElement('table');
        $parentNode->appendChild($table);

        while ($nextRequest = Request::getLine($lines)) {

            if (!self::isTabTableLine($nextRequest['raw_line'])) {
                break;
            }

            array_shift($lines);

            if (in_array(trim($nextRequest['raw_line']), self::skippableLines)) {
                continue;
            }

            $tds = preg_split('~\t+~u', $nextRequest['raw_line']);
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
