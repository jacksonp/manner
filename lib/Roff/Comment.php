<?php


class Roff_Comment
{

    static function checkEvaluate(array &$lines, int $i)
    {

        // Skip full-line comments
        // See mscore.1 for full-line comments starting with '."
        // See cal3d_converter.1 for full-line comments starting with '''
        if (preg_match('~^([\'\.]?\\\\"|\'\."\'|\'\'\')~u', $lines[$i], $matches)) {
            return ['i' => $i];
        }

        // \" is start of a comment. Everything up to the end of the line is ignored.
        // Some man pages get this wrong and expect \" to be printed (see fox-calculator.1),
        // but this behaviour is consistent with what the man command renders:
        $lines[$i] = Replace::preg('~(^|.*?[^\\\\])\\\\".*$~u', '$1', $lines[$i], -1, $replacements);
        if ($replacements > 0) {
            // Look at this same line again:
            return ['i' => $i - 1];
        }

        if (preg_match('~^\.\s*ig(?:\s+(?<delimiter>.*)|$)~u', $lines[$i], $matches)) {
            $delimiter = empty($matches['delimiter']) ? '..' : ('.' . $matches['delimiter']);
            $numLines  = count($lines);
            for ($i = $i + 1; $i < $numLines; ++$i) {
                if ($lines[$i] === $delimiter) {
                    return ['i' => $i];
                }
            }
            throw new Exception($matches[0] . ' with no corresponding ' . $delimiter);
        }

        return false;

    }

}
