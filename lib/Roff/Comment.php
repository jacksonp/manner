<?php


class Roff_Comment
{

    static function checkLine(array &$lines): bool
    {

        // Skip full-line comments
        // See mscore.1 for full-line comments starting with '."
        // See cal3d_converter.1 for full-line comments starting with '''
        // See e.g. flow-import.1 for comment starting with .\\"
        // See e.g. card.1 for comment starting with ."
        // See e.g. node.1 for comment starting with .\
        if (preg_match('~^([\'\.]?\\\\?\\\\"|\'\."\'|\'\'\'|\."|\.\\\\\s+)~u', $lines[0], $matches)) {
            array_shift($lines);
            return true;
        }

        // \" is start of a comment. Everything up to the end of the line is ignored.
        // Some man pages get this wrong and expect \" to be printed (see fox-calculator.1),
        // but this behaviour is consistent with what the man command renders:
        $lines[0] = Replace::preg('~(^|.*?[^\\\\])\\\\".*$~u', '$1', $lines[0], -1, $replacements);
        if ($replacements > 0) {
            $lines[0] = rtrim($lines[0], "\t");
            // Look at this same line again:
            return true;
        }

        if (preg_match('~^[\'\.]\s*ig(?:\s+(?<delimiter>.*)|$)~u', $lines[0], $matches)) {
            array_shift($lines);
            $delimiter = empty($matches['delimiter']) ? '..' : ('.' . $matches['delimiter']);
            while (count($lines)) {
                $line = array_shift($lines);
                if ($line === $delimiter) {
                    return true;
                }
            }
            throw new Exception($matches[0] . ' with no corresponding "' . $delimiter . '"');
        }

        return false;

    }

}
