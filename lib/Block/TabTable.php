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

    static function isStart($lines, $i)
    {
        // char before tab avoid indented stuff + exclude escaped tabs
        return
            $i < count($lines) - 2 &&
            !in_array(mb_substr($lines[$i], 0, 1), ['.', '\'']) &&
            mb_strpos($lines[$i], "\t") > 0 &&
            preg_match('~[^\\\\\s]\t~u', $lines[$i]) &&
            (
                (preg_match('~[^\\\\\s]\t~u', $lines[$i + 1]) && mb_strpos($lines[$i + 1], "\t") > 0) ||
                in_array(trim($lines[$i + 1]), self::skippableLines + self::specialAcceptableLines) &&
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

            $nextRequest = Request::getLine($lines, 0);

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
