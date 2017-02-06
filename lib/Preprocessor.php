<?php
declare(strict_types = 1);

class Preprocessor
{

    static function strip(array &$lines): array
    {

        $linesNoComments = [];
        $linePrefix      = '';

        for ($i = 0; $i < count($lines); ++$i) {

            $line       = $linePrefix . $lines[$i];
            $linePrefix = '';

            // Continuations
            // Can't do these in applyRoffClasses() loop: a continuation could e.g. been in the middle of a conditional
            // picked up Roff_Condition, e.g. man.1
            // Do these before comments (see e.g. ppm.5 where first line is just "\" and next one is a comment.
            while (
                $i < count($lines) - 1 &&
                mb_substr($line, -1, 1) === '\\' &&
                (mb_strlen($line) === 1 || mb_substr($line, -2, 1) !== '\\')) {
                $line = mb_substr($line, 0, -1) . $lines[++$i];
            }

            // Everything up to and including the next newline is ignored. This is interpreted in copy mode.  This is like \" except that the terminating newline is ignored as well.
            if (preg_match('~(^|.*?[^\\\\])\\\\#~u', $line, $matches)) {
                $linePrefix = $matches[1];
                continue;
            }

            // NB: Workaround for lots of broken tcl man pages (section n, Tk_*, Tcl_*, others...):
            $line = Replace::preg('~^\.\s*el\s?\\\\}~u', '.el \\{', $line);

            // TODO: fix this hack, see groff_mom.7
            $line = Replace::preg('~^\.FONT ~u', '.', $line);

            // Don't worry about changes in point size for now (see rc.1 for digit instead of +- in \s10):
            $line = Replace::preg('~(?<!\\\\)((?:\\\\\\\\)*)\\\\s[-+\d]?\d~u', '$1', $line);

            // Don't worry colour changes:
            $line = Replace::preg('~(?<!\\\\)((?:\\\\\\\\)*)\\\\m(\(..|\[.*?\])~u', '$1', $line);

            // Copied from Roff_Comment for top.1
            if (preg_match('~^[\'\.]\s*ig(?:\s+(?<delimiter>.*)|$)~u', $line, $matches)) {
                $delimiter = empty($matches['delimiter']) ? '..' : ('.' . $matches['delimiter']);
                for ($i = $i + 1; $i < count($lines); ++$i) {
                    if ($lines[$i] === $delimiter) {
                        continue 2;
                    }
                }
                throw new Exception($matches[0] . ' with no corresponding "' . $delimiter . '"');
            }

            $linesNoComments[] = $line;

        }

        return $linesNoComments;

    }

}
