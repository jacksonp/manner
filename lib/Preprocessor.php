<?php

declare(strict_types=1);

namespace Manner;

class Preprocessor
{

    public static function strip(array $lines): array
    {
        $linesNoComments = [];
        $linePrefix      = '';

        for ($i = 0; $i < count($lines); ++$i) {
            $line       = $linePrefix . $lines[$i];
            $linePrefix = '';

            // Everything up to and including the next newline is ignored. This is interpreted in copy mode.  This is like \" except that the terminating newline is ignored as well.
            if (preg_match('~(^|.*?[^\\\\])\\\\#~u', $line, $matches)) {
                $linePrefix = $matches[1];
                continue;
            }

            // Workaround for lots of broken tcl man pages (section n, Tk_*, Tcl_*, others...):
            $line = Replace::preg('~^\.\s*el\s?\\\\}~u', '.el \\{', $line);

            // Don't worry about changes in point size for now (see rc.1 for digit instead of +- in \s10):
            $line = Replace::preg('~(?<!\\\\)((?:\\\\\\\\)*)\\\\s[-+\d]?\d~u', '$1', $line);

            // Don't worry about colour changes:
            $line = Replace::preg('~(?<!\\\\)((?:\\\\\\\\)*)\\\\m(\(..|\[.*?])~u', '$1', $line);

            // Don't worry about:  \zc - Print c with zero width (without spacing).
            // TODO: see if we can use this for underlining when \z_, e.g. in groff.7
            $line = Replace::preg('~(?<!\\\\)((?:\\\\\\\\)*)\\\\z~u', '$1', $line);

            $linesNoComments[] = $line;
        }

        return $linesNoComments;
    }

}
