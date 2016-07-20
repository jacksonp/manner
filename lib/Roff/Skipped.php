<?php


class Roff_Skipped
{

    static function checkEvaluate(array $lines, int $i)
    {

        $line = rtrim($lines[$i]);

        $skipLines = [
            // Empty requests:
          '...',
          '\\.',
          '.rr rF',
        ];

        if (in_array($line, $skipLines)) {
            return ['i' => $i];
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
        // .mc: margin glyph
        if (preg_match(
          '~^[\.\'](?:\s|(iX|IX|nh|na|hy|hys|hym|UN|UC|DT|lf|TA|IN|LL|PU|LO 1|pl|pc|PD|RP|po|in|ll|fam|rs|rm|ta|cp|it|ps|bp|ul|so|bd|BB|BY|mk|rt|ss|cs|vs|ev|evc|hw|ns|mso|tm|tm1|tmc|defcolor|pdfdest|em|Iq|XX|fp|l|mc)(\s|$))~u',
          $line)
        ) {
            return ['i' => $i];
        }


        if (count($lines) === 0 and
          ($line === '' or $line === '.PP')
        ) { // Exclude leading blank lines, and .PP
            return ['i' => $i];
        }

        return false;

    }

}
