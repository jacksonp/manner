<?php


class Text
{

    private static function stripComments(array $lines): array
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

            // .do: "Interpret .name with compatibility mode disabled."  (e.g. .do if ... )
            $line = Replace::preg('~^\.do ~u', '.', $line);

            // NB: Workaround for lots of broken tcl man pages (section n, Tk_*, Tcl_*, others...):
            $line = Replace::preg('~^\.\s*el\s?\\\\}~u', '.el \\{', $line);

            $linesNoComments[] = $line;

        }

        return $linesNoComments;

    }

    static function applyRoffClasses(array $lines): array
    {

        $man = Man::instance();

        $numNoCommentLines = count($lines);
        $linesNoCond       = [];

        for ($i = 0; $i < $numNoCommentLines; ++$i) {

            // TODO: fix this hack, see groff_mom.7
            $lines[$i] = preg_replace('~^\.FONT ~u', '.', $lines[$i]);

            $lines[$i] = $man->applyAllReplacements($lines[$i]);

            if (mb_substr($lines[$i], 0, 1) === '.') {
                $bits = Macro::parseArgString($lines[$i]);
                if (count($bits) > 0) {
                    $macro  = array_shift($bits);
                    $macros = $man->getMacros();
                    if (isset($macros[$macro])) {
                        $man->setRegister('.$', count($bits));
                        $evaluatedMacroLines = [];
                        foreach ($macros[$macro] as $macroLine) {

                            // \$x - Macro or string argument with one-digit number x in the range 1 to 9.
                            for ($n = 1; $n < 10; ++$n) {
                                $macroLine = str_replace('\\$' . $n, @$bits[$n - 1] ?: '', $macroLine);
                            }

                            // \$* : In a macro or string, the concatenation of all the arguments separated by spaces.
                            $macroLine = str_replace('\\$*', implode(' ', $bits), $macroLine);

                            // Other \$ things are also arguments...
                            if (mb_strpos($macroLine, '\\$') !== false) {
                                throw new Exception($macroLine . ' - can not handle macro that specifies arguments.');
                            }

                            $evaluatedMacroLines[] = Text::translateCharacters($macroLine);
                        }
                        $linesNoCond = array_merge($linesNoCond, Text::applyRoffClasses($evaluatedMacroLines));

                        continue;
                    }
                }
            }

            $roffClasses = ['Comment', 'Condition', 'Macro', 'Register', 'String', 'Alias'];

            foreach ($roffClasses as $roffClass) {
                $className = 'Roff_' . $roffClass;
                $result    = $className::checkEvaluate($lines, $i);
                if ($result !== false) {
                    if (isset($result['lines'])) {
                        $linesNoCond = array_merge($linesNoCond, $result['lines']);
                    }
                    $i = $result['i'];
                    continue 2;
                }
            }


            $lines[$i] = Text::translateCharacters($lines[$i]);

            $linesNoCond[] = $lines[$i];

        }

        return $linesNoCond;

    }

    static function preprocessLines(array $lines): array
    {
        $linesNoComments = self::stripComments($lines);
        $linesNoCond     = self::applyRoffClasses($linesNoComments);

        $numNoCondLines = count($linesNoCond);
        $foundTitle     = false;
        $charSwaps      = [];
        $lines          = [];

        $man = Man::instance();

        for ($i = 0; $i < $numNoCondLines; ++$i) {

            $line = $man->applyAllReplacements($linesNoCond[$i]);

            $skipLines = [
                // Empty requests:
              '...',
              '\\.',
              '.rr rF',
            ];

            if (in_array($line, $skipLines)) {
                continue;
            }

            // Skip stuff we don't care about:
            // . : empty request followed by space followed by comment
            // .iX, .IX: index information: "Inserts index information (for a search system or printed index list). Index information is not normally displayed in the page itself."
            // .nh: No hyphenation
            // .na: "No output-line adjusting."
            // .hy: "Switch to hyphenation mode N."
            // .UN: " .UN u Creates a named hypertext location named u; do not include a corresponding UE command. When generating HTML this should translate into the HTML command <ANAME=\"u\"id=\"u\">&nbsp;</A> (the &nbsp; is optional if support for Mosaic is unneeded).
            // .UC: "Alter the footer for use with BSD man pages. This command exists only for compatibility; don't use it. See the groff info manual for more."
            // .DT: "Set tabs every 0.5 inches. Since this macro is always called during a TH macro, it makes sense to call it only if the tab positions have been changed. Use of this presentation-level macro is deprecated. It translates poorly to HTML, under which exact whitespace control and tabbing are not readily available. Thus, information or distinctions that you use .DT to express are likely to be lost. If you feel tempted to use it, you should probably be composing a table using tbl(1) markup instead."
            // .lf: "Set input line number to N."
            // .TA: something like Title Adjust?
            // .IN "sets the indent relative to subheads."
            // .LL "sets the line length, which includes the value of IN."
            // .PU: ?
            // .LO 1: ?
            // .pl: "Set the page length"
            // .pc: "Change the page number character"
            // .PD: "Adjust the empty space before a new paragraph or section."
            // .RP: "Specifies the report format for your document. The report format creates a separate cover page."
            // .po, .in, .ll: "dimensions which gtroff uses for placing a line of output onto the page." see http://apollo.ubishops.ca/~ajaja/TROFF/groff.html
            // .fam: sets font family, generally used in conjunction with .nf blocks which already get a monospace font.
            // .rs: "Restore spacing; turn no-space mode off."
            // .rm: "Remove request, macro, or string name."
            // .ta: "Set tabs after every position that is a multiple of N (default scaling indicator m)."
            // .cp: Enable or disable compatibility mode.
            // .it: "Set an input-line count trap for the next N lines."
            // .ps: affects point size
            // .bp: "Eject current page and begin new page."
            // .ul: "Underline (italicize in troff) N input lines." - Could revisit this and implement.
            // .so: ignore for now so we can build builtins.1
            // .bd: "Embolden font by N-1 units."
            // .BB: looks like a color setting, e.g. skipfish.1
            // .BY: looks like it sets the authors, e.g. as86.1
            // .mk: Mark current vertical position in register.
            // .rt: Return (upward only) to marked vertical place (default scaling indicator v).
            // .ss: Set space glyph size to N/12 of the space width in the current font.
            // .cs font N M: Set constant character width mode for font to N/36 ems with em M.
            // .vs: Change to previous vertical base line spacing.
            // .ev: Switch to previous environment and pop it off the stack.
            // .evc: Copy the contents of environment env to the current environment. No pushing or popping.
            // .ns: Turn on no-space mode.
            // .mso
            // .tm, .tm1, .tmc: printing to stdout
            // .defcolor: Define color, see https://www.gnu.org/software/groff/manual/html_node/Colors.html
            // .pdfdest: hack for error in foo2lava.1
            // .em macro: The macro is run after the end of input.
            // .Iq: ?
            // .XX: looks like some kind of indexing
            // .fp: mount font at position
            // .l: ?
            if (preg_match(
              '~^[\.\'](\s|iX|IX|nh|na|hy|hys|hym|UN|UC|DT|lf|TA|IN|LL|PU|LO 1|pl|pc|PD|RP|po|in|ll|fam|rs|rm|ta|cp|it|ps|bp|ul|so|bd|BB|BY|mk|rt|ss|cs|vs|ev|evc|hw|ns|mso|tm|tm1|tmc|defcolor|pdfdest|em|Iq|XX|fp|l)(\s|$)~u',
              $line)
            ) {
                continue;
            }

            // Do this after translating characters:
            if (preg_match('~^\.tr (.+)$~u', $line, $matches)) {
                $chrArray = preg_split('~~u', $matches[1], -1, PREG_SPLIT_NO_EMPTY);
                for ($j = 0; $j < count($chrArray); $j += 2) {
                    //  "If there is an odd number of arguments, the last one is translated to an unstretchable space (‘\ ’)."
                    $charSwaps[$chrArray[$j]] = $j === count($chrArray) - 1 ? ' ' : $chrArray[$j + 1];
                }
                continue;
            }

            if (count($charSwaps) > 0) {
                $line = Replace::preg(array_map(function ($c) {
                    return '~(?<!\\\\)' . preg_quote($c, '~') . '~u';
                }, array_keys($charSwaps)), $charSwaps, $line);
            }

            //<editor-fold desc="Handle man title macro">
            if (!$foundTitle and preg_match('~^\.TH\s(.*)$~u', $line, $matches)) {
                $foundTitle   = true;
                $titleDetails = Macro::parseArgString($matches[1]);
                if (is_null($titleDetails) or count($titleDetails) < 1) {
                    throw new Exception($line . ' - missing title info');
                }
                // Fix vnu's "Saw U+0000 in stream" e.g. in lvmsadc.8:
                $titleDetails = array_map('trim', $titleDetails);
                // See amor.6 for \FB \FR nonsense.
                $man->title = TextContent::interpretString(
                  Replace::preg('~\\\\F[BR]~', '', $titleDetails[0])
                );
                if (count($titleDetails) > 1) {
                    $man->section      = TextContent::interpretString($titleDetails[1]);
                    $man->date         = TextContent::interpretString(@$titleDetails[2] ?: '');
                    $man->package      = TextContent::interpretString(@$titleDetails[3] ?: '');
                    $man->section_name = TextContent::interpretString(@$titleDetails[4] ?: '');
                }
                continue;
            }
            //</editor-fold>

            if (count($lines) > 0 ||
              (mb_strlen($line) > 0 and $line !== '.PP')
            ) { // Exclude leading blank lines, and .PP
                $lines[] = $line;
            }

        }

        return $lines;

    }

    static function translateCharacters($line)
    {

        // See http://man7.org/linux/man-pages/man7/groff_char.7.html

        $replacements = [
            // \\ "reduces to a single backslash" - Do this first as strtr() doesn't search replaced text for further replacements.
          '\\\\' => '\\e',
            // \/ Increases the width of the preceding glyph so that the spacing between that glyph and the following glyph is correct if the following glyph is a roman glyph. groff(7)
          '\\/'  => '',
            // \, Modifies the spacing of the following glyph so that the spacing between that glyph and the preceding glyph is correct if the preceding glyph is a roman glyph. groff(7)
          '\\,'  => '',
            // The same as a dot (‘.’).  Necessary in nested macro definitions so that ‘\\..’ expands to ‘..’.
          '\\.'  => '.',
          '\\\'' => '´',
            // The acute accent ´; same as \(aa.
          '\\´'  => '´',
            // The grave accent `; same as \(ga.
          '\\`'  => '`',
          '\\-'  => '-',
            // The same as \(ul, the underline character.
          '\\_'  => '_',
          '\\t'  => "\t",
            // Unpaddable space size space glyph (no line break). See enigma.6:
          '\\ '  => mb_convert_encoding(chr(160), 'UTF-8', 'HTML-ENTITIES'),
            // Unbreakable space that stretches like a normal inter-word space when a line is adjusted
          '\\~'  => mb_convert_encoding(chr(160), 'UTF-8', 'HTML-ENTITIES'),
          '\\*R' => '®',
        ];

        // If a backslash is followed by a character that does not constitute a defined escape sequence, the backslash is silently ignored and the character maps to itself.
        // Just the cases we come across:
        $replacements['\\W']       = 'W';
        $replacements['\\=']       = '=';
        $replacements['\\+']       = '+';
        $replacements['\\<']       = '<';
        $replacements['\\>']       = '>';
        $replacements['\\]']       = ']';
        $replacements['\\' . "\t"] = "\t"; // See glite-lb-mon.1 where we want tabs inside <pre>

        $line = strtr($line, $replacements);

        $line = Replace::pregCallback('~\\\\\[u([\dA-F]{4})\]~u', function ($matches) {
            return html_entity_decode('&#x' . $matches[1] . ';', ENT_COMPAT, 'UTF-8');
        }, $line);

        $line = Replace::pregCallback('~\\\\\[char(\d+)\]~u', function ($matches) {
            return mb_convert_encoding('&#' . intval($matches[1]) . ';', 'UTF-8', 'HTML-ENTITIES');
        }, $line);

        // Don't worry about changes in point size for now:
        $line = Replace::preg('~\\\\s[-+]?\d~u', '', $line);

        // Don't worry about this:
        // \v, \h: "Local vertical/horizontal motion"
        // \l: Horizontal line drawing function (optionally using character c).
        // \L: Vertical line drawing function (optionally using character c).
        $line = Replace::preg('~\\\\[vhLl]\'.*?\'~u', ' ', $line);

        // \w’string’: The width of the glyph sequence string.
        $line = Replace::pregCallback('~\\\\w\'(.*?)\'~u', function ($matches) {
            return mb_strlen($matches[0]);
        }, $line);

        // Don't worry colour changes:
        $line = Replace::preg('~\\\\m(\(..|\[.*?\])~u', '', $line);

        return rtrim($line);

    }

}
