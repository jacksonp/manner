<?php


class Roff_Skipped
{

    const requests = [
      'bd', // "Embolden font by N-1 units."
      'bp', // "Eject current page and begin new page."
      'cp', // Enable or disable compatibility mode.
      'cs', // font N M: Set constant character width mode for font to N/36 ems with em M.
      'cu', // Continuous underline in nroff, like .ul in troff.
      'defcolor', // Define color, see https://www.gnu.org/software/groff/manual/html_node/Colors.html
      'em', // .em macro: The macro is run after the end of input.
      'eo', // Turn off escape character mechanism.
      'ev',  // Switch to previous environment and pop it off the stack.
      'evc', // Copy the contents of environment env to the current environment. No pushing or popping.
      'ex', // Exit from roff processing.
      'fam', // sets font family, generally used in conjunction with .nf blocks which already get a monospace font.
      'fchar', // Define fallback character (or glyph) c as string anything.
      'fcolor', // Set fill color
      'fp', // mount font at position
      'fspecial', // Reset list of special fonts for font to be empty.
      'fschar', // Define fallback character (or glyph) c for font f
      'hw', // List of words with exceptional hyphenation.
      'hy', // "Switch to hyphenation mode N."
      'hym', // Set the hyphenation margin to n (default scaling indicator m).
      'hys', // Set the hyphenation space to n.
      'in', // Change indentation
      'it', // "Set an input-line count trap for the next N lines."
      'itc', // "Same as .it but count lines interrupted with \c as one line."
      'lf', // "Set input line number to N."
      'll', // Set line length
      'lt', // Length of title
      'mc', // margin glyph
      'mk', // Mark current vertical position in register.
      'mso', // The same as .so except that file is searched in the tmac directories.
      'na', // "No output-line adjusting."
      'nh', // No hyphenation
      'ns', // Turn on no-space mode.
      'pc', // "Change the page number character"
      'pl', // "Set the page length"
      'po', // Page offset
      'ps', // Point size
      'rchar', // Remove the definitions of entities
      'rfschar', // Remove the definitions of entitie
      'rm', // Remove request, macro, or string name. // TODO: revisit?
      'rs', // "Restore spacing; turn no-space mode off."
      'rt', // Return (upward only) to marked vertical place (default scaling indicator v).
      'schar', // Define global fallback character (or glyph)
      'so', // ignore for now so we can build builtins.1
      'ss', // Set space glyph size to N/12 of the space width in the current font.
      'ta', // "Set tabs after every position that is a multiple of N (default scaling indicator m)."
      'tm', // Print anything on stdout.
      'tm1', // Print anything on stdout, allowing leading whitespace if anything starts with "
      'tmc', // Similar to .tm1 without emitting a final newline.
      'ul', // "Underline (italicize in troff) N input lines." TODO: Could revisit this and implement.
      'vs', // Change to previous vertical base line spacing.
      'warn', // Set warnings code to n.
    ];

    static function checkEvaluate(array $lines, int $i)
    {

        $skipLines = [
            // Empty requests:
          '...',
          '\\.',
          '.rr rF',
        ];

        if (in_array(rtrim($lines[$i]), $skipLines)) {
            return ['i' => $i];
        }

        // Skip stuff we don't care about:
        // .iX, .IX: index information: "Inserts index information (for a search system or printed index list). Index information is not normally displayed in the page itself."
        // .UN: " .UN u Creates a named hypertext location named u; do not include a corresponding UE command. When generating HTML this should translate into the HTML command <ANAME=\"u\"id=\"u\">&nbsp;</A> (the &nbsp; is optional if support for Mosaic is unneeded).
        // .UC: "Alter the footer for use with BSD man pages. This command exists only for compatibility; don't use it. See the groff info manual for more."
        // .DT: "Set tabs every 0.5 inches. Since this macro is always called during a TH macro, it makes sense to call it only if the tab positions have been changed. Use of this presentation-level macro is deprecated. It translates poorly to HTML, under which exact whitespace control and tabbing are not readily available. Thus, information or distinctions that you use .DT to express are likely to be lost. If you feel tempted to use it, you should probably be composing a table using tbl(1) markup instead."
        // .TA: something like Title Adjust?
        // .IN "sets the indent relative to subheads."
        // .LL "sets the line length, which includes the value of IN."
        // .PU: ?
        // .LO 1: ?
        // .PD: "Adjust the empty space before a new paragraph or section."
        // .RP: "Specifies the report format for your document. The report format creates a separate cover page."
        // .BB: looks like a color setting, e.g. skipfish.1
        // .BY: looks like it sets the authors, e.g. as86.1
        // .pdfdest: hack for error in foo2lava.1
        // .Iq: ?
        // .XX: looks like some kind of indexing
        // .l: ?
        if (preg_match(
          '~^[\.\'](iX|IX|UN|UC|DT|TA|IN|LL|PU|LO 1|PD|RP|BB|BY|pdfdest|Iq|XX|l|' .
          implode('|', self::requests)
          . ')(\s|$)~u',
          $lines[$i])
        ) {
            return ['i' => $i];
        }

        return false;

    }

}
