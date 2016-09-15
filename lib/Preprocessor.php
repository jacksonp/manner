<?php


class Preprocessor
{

    static function strip(array &$lines): array
    {

        $numLines        = count($lines);
        $linesNoComments = [];
        $linePrefix      = '';

        for ($i = 0; $i < $numLines; ++$i) {

            $line       = $linePrefix . $lines[$i];
            $linePrefix = '';

            // Continuations
            // Can't do these in applyRoffClasses() loop: a continuation could e.g. been in the middle of a conditional
            // picked up Roff_Condition, e.g. man.1
            // Do these before comments (see e.g. ppm.5 where first line is just "\" and next one is a comment.
            while (
              $i < $numLines - 1 and
              mb_substr($line, -1, 1) === '\\' and
              (mb_strlen($line) === 1 or mb_substr($line, -2, 1) !== '\\')) {
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

            $linesNoComments[] = $line;

        }

        return $linesNoComments;

    }

}
