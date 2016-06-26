<?php


class Text
{

    /**
     * Strip comments, handle title, stick rest in $lines
     */
    static function preprocessLines($rawLines)
    {

        $numRawLines = count($rawLines);
        $linesNoCond = [];
        $linePrefix  = '';

        for ($i = 0; $i < $numRawLines; ++$i) {

            $line       = $linePrefix . $rawLines[$i];
            $linePrefix = '';

            // Continuations
            // Do these before comments (see e.g. ppm.5 where first line is just "\" and next one is a comment.
            while ($i < $numRawLines - 1 && mb_substr($line, -1, 1) === '\\'
              && (mb_strlen($line) === 1 || mb_substr($line, -2, 1) !== '\\')) {
                $line = mb_substr($line, 0, -1) . $rawLines[++$i];
            }

            // Everything up to and including the next newline is ignored. This is interpreted in copy mode.  This is like \" except that the terminating newline is ignored as well.
            if (preg_match('~(^|.*?[^\\\\])\\\\#~u', $line, $matches)) {
                $linePrefix = $matches[1];
                continue;
            }

            // Skip full-line comments
            // See mscore.1 for full-line comments starting with '."
            if (preg_match('~^([\'\.]?\\\\"|\'\."\')~u', $line, $matches)) {
                continue;
            }

            // \" is start of a comment. Everything up to the end of the line is ignored.
            // Some man pages get this wrong and expect \" to be printed (see fox-calculator.1),
            // but this behaviour is consistent with what the man command renders:
            $line = Replace::preg('~(^|.*?[^\\\\])\\\\".*$~u', '$1', $line);

            if (preg_match('~^\.ig(\s|$)~u', $line)) {
                for ($i = $i + 1; $i < $numRawLines; ++$i) {
                    if ($rawLines[$i] === '..') {
                        continue 2;
                    }
                }
                throw new Exception('.ig with no corresponding ..');
            }

            // .do: "Interpret .name with compatibility mode disabled."  (e.g. .do if ... )
            $line = Replace::preg('~^\.do ~u', '.', $line);

            //            .ie n \{ USE
            //            THIS
            //            .\}
            //            .el \{ DISCARD
            //            THIS
            //            .\}
            if (preg_match('~^\.ie n \\\\{(.*)$~', $line, $matches)) {
                $line = $matches[1];
                while ($i < $numRawLines) {

                    if (preg_match('~^(.*)\\\\}$~', $line, $matches)) {
                        if (!empty($matches[1]) && $matches[1] !== '\'br') {
                            $linesNoCond[] = Macro::massageLine($matches[1]);
                        }
                        if (!preg_match('~^\.el\s*\\\\{~', $rawLines[++$i])) {
                            throw new Exception('.ie n \\{ - not followed by expected pattern on line ' . $i . ' (got "' . $rawLines[$i] . '").');
                        }
                        for ($i = $i + 1; $i < $numRawLines; ++$i) {
                            $line = $rawLines[$i];
                            if (preg_match('~\\\\}$~', $line)) {
                                continue 3;
                            }
                        }
                        throw new Exception('.el \\{ - not followed by expected pattern on line ' . $i . '.');
                    } elseif (!empty($line)) {
                        $linesNoCond[] = Macro::massageLine($line);
                    }

                    $line = $rawLines[++$i];

                }
            }

            if (mb_strpos($line, '~^\.ie t $~') === 0) {
                if (!preg_match('~^\.el (.*)$~', $rawLines[++$i], $matches)) {
                    throw new Exception('.ie t - not followed by expected pattern on line ' . $i . ' (got "' . $rawLines[$i] . '").');
                }
                $line = $matches[1];
            }

            if (preg_match('~^\.ie n (.*)$~', $line, $matches)) {
                $line = $matches[1];
                if (!preg_match('~^\.el ~', $rawLines[++$i])) {
                    throw new Exception('.ie n - not followed by expected pattern on line ' . $i . ' (got "' . $rawLines[$i] . '").');
                }
            }

            if (preg_match('~^\.if n \\\\{(.*)$~', $line, $matches)) {
                $line = $matches[1];
                while ($i < $numRawLines) {
                    if (preg_match('~^(.*)\\\\}$~', $line, $matches)) {
                        if (!empty($matches[1]) && $matches[1] !== '\'br') {
                            $linesNoCond[] = Macro::massageLine($matches[1]);
                        }
                        continue 2;
                    } elseif (!empty($line)) {
                        $linesNoCond[] = Macro::massageLine($line);
                    }

                    $line = $rawLines[++$i];
                }
                throw new Exception('.if n \\{ - not followed by expected pattern on line ' . $i . '.');
            }

            if (preg_match('~^\.if [tv] \\\\{~', $line)
              || preg_match('~^\.if \(\\\\n\(rF:\(\\\\n\(\.g==0\)\) \\\\{~', $line)
              || preg_match('~^\.if \\\\nF>0 \\\\{~', $line)
              || preg_match('~^\.if \\\\n\(\.H>23 \.if \\\\n\(\.V>19 ~', $line)
              || mb_strpos($line, '.if require_index') === 0
              || mb_strpos($line, '.if \\nF \\{') === 0
            ) {
                $openBraces = 0;
                while ($i < $numRawLines) {
                    $openBraces += substr_count($line, '\\{');
                    $openBraces -= substr_count($line, '\\}');
                    if ($openBraces < 1) {
                        continue 2;
                    }
                    $line = $rawLines[++$i];
                }
                throw new Exception('.if - not followed by expected pattern on line ' . $i . '.');
            }

            if (mb_strpos($line, '.ie \\nF \\{') === 0) {
                $openBraces = 0;
                while ($i < $numRawLines) {
                    $openBraces += substr_count($line, '\\{');
                    $openBraces -= substr_count($line, '\\}');
                    $line = $rawLines[++$i];
                    if ($openBraces < 1) {
                        break;
                    }
                }
                if (mb_strpos($line, '.el \{') !== 0) {
                    throw new Exception('.ie - not followed by expected .el on line ' . $i . '.');
                }
                $openBraces = 0;
                while ($i < $numRawLines) {
                    $openBraces += substr_count($line, '\\{');
                    $openBraces -= substr_count($line, '\\}');
                    if ($openBraces < 1) {
                        continue 2;
                    }
                    $line = $rawLines[++$i];
                }

                throw new Exception('.ie - not followed by expected pattern on line ' . $i . '.');
            }

            if (preg_match('~\.if [tv] ~', $line)) {
                continue;
            }

            if (preg_match('~\.if n (.*)~', $line, $matches)) {
                $line = $matches[1];
            }

            // construct for "hiding text from po4a", we don't need:
            $line = Replace::preg('~^\.if !\'po4a\'hide\' ~u', '', $line);

            // part of indexing we don't care about
            $line = Replace::preg('~^\.if !\\\\nF \.nr F 0~u', '', $line);

            $linesNoCond[] = $line;

        }

        $macroReplacements  = [];
        $numNoCondLines     = count($linesNoCond);
        $firstPassLines     = [];
        $aliases            = [];
        $registers          = [];
        $stringReplacements = [];
        $foundTitle         = false;

        $man = Man::instance();

        for ($i = 0; $i < $numNoCondLines; ++$i) {

            $line = $linesNoCond[$i];

            $bits = Macro::parseArgString($line);
            if (count($bits) > 0) {
                $macro = array_shift($bits);
                if (isset($macroReplacements[$macro])) {
                    foreach ($macroReplacements[$macro] as $macroLine) {

                        $macroLine = str_replace('\\$1', @$bits[0] ?: '', $macroLine);

                        // \$* : In a macro or string, the concatenation of all the arguments separated by spaces.
                        // Other \$ things are also arguments...
                        if (mb_strpos($macroLine, '\\$') !== false) {
                            throw new Exception($macroLine . ' - can not handle macro that specifies arguments.');
                        }

                        $firstPassLines[] = $macroLine;
                    }
                    continue;
                }
            }

            if (preg_match('~\.de1? ([^\s"]+)\s*$~u', $line, $matches)) {
                $newMacro   = '.' . $matches[1];
                $macroLines = [];
                for ($i = $i + 1; $i < $numNoCondLines; ++$i) {
                    $macroLine = $linesNoCond[$i];
                    if ($macroLine === '..') {
                        if (in_array($newMacro, ['.SS', '.EX', '.EE'])) {
                            // Don't override these macros.
                            // djvm e.g. does something dodgy when overriding .SS, just use normal .SS handling for it.
                            continue 2;
                        } elseif ($newMacro === '.INDENT') {
                            $aliases['INDENT'] = 'RS';
                            continue 2;
                        } elseif ($newMacro === '.UNINDENT') {
                            $aliases['UNINDENT'] = 'RE';
                            continue 2;
                        }
                        $macroReplacements[$newMacro] = $macroLines;
                        continue 2;
                    } else {
                        $macroLine = Macro::massageLine($macroLine);
                        if (isset($macroReplacements[$macroLine])) {
                            foreach ($macroReplacements[$macroLine] as $subMacroLine) {
                                $macroLines[] = $subMacroLine;
                            }
                        } else {
                            $macroLines[] = $macroLine;
                        }
                    }
                }
                throw new Exception($matches[0] . ' - not followed by expected pattern on line ' . $i . '.');
            }

            // Do registers after .de -see e.g. yum-copr.8
            if (preg_match('~^\.nr (?<name>[-\w]+) (?<val>\d+)$~u', $line, $matches)) {
                $registerName = $matches['name'];
                $registerVal  = $matches['val'];
                if (mb_strlen($registerName) === 1) {
                    $registers['\\n' . $registerName] = $registerVal;
                }
                if (mb_strlen($registerName) === 2) {
                    $registers['\\n(' . $registerName] = $registerVal;
                }
                $registers['\\n[' . $registerName . ']'] = $registerVal;
                continue;
            }

            $line = strtr($line, $registers);

            $skipLines = [
                // We don't care about this if there's nothing after it, otherwise it's handled in interpretAndAppendText():
              '\\&',
              '.so man.macros',
                // Empty requests:
              '...',
              '.',
              '\\.',
                // hmm
              '.if (\n(.H=4u)&(1m=24u) .ds -- \(*W\h\'-12u\'\(*W\h\'-12u\'-\" diablo 10 pitch', // e.g. lmmin.3
              '.if (\n(.H=4u)&(1m=20u) .ds -- \(*W\h\'-12u\'\(*W\h\'-8u\'-\"  diablo 12 pitch', // e.g. lmmin.3
              '.if \n(.g .if rF .nr rF 1',
              '.rr rF',
            ];

            if (in_array($line, $skipLines)) {
                continue;
            }

            // Skip stuff we don't care about:
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
            if (preg_match(
              '~^[\.\'](iX|IX|nh|na|hy|hys|hym|UN|UC|DT|lf|TA|IN|LL|PU|LO 1|pl|pc|PD|RP|po|in|ll|fam|rs|rm|ta|cp|it|ps|bp|ul|so|bd|BB|BY|mk|rt)(\s|$)~u',
              $line)
            ) {
                continue;
            }

            if (preg_match('~^\.als (?<new>\w+) (?<old>\w+)$~u', $line, $matches)) {
                $aliases[$matches['new']] = $matches['old'];
                continue;
            }

            if (preg_match('~^\.ds (.*?) (.*)$~u', $line, $matches)) {
                if (empty($matches[2])) {
                    continue;
                }
                $newRequest = $matches[1];
                $requestVal = Macro::simplifyRequest($matches[2]);

                // Q and U are special cases for when replacement is in a macro argument, which are separated by double
                // quotes and otherwise get messed up.
                if (in_array($newRequest, ['C\'', 'C`'])) {
                    $requestVal = '"';
                } elseif (in_array($newRequest, ['L"', 'R"'])) {
                    continue;
                } elseif ($newRequest === 'Q' and $requestVal === '\&"') {
                    $requestVal = '“';
                } elseif ($newRequest === 'U' and $requestVal === '\&"') {
                    $requestVal = '”';
                }

                // See e.g. rcsfreeze.1 for a replacement including another previously defined replacement.
                if (count($stringReplacements) > 0) {
                    $requestVal = strtr($requestVal, $stringReplacements);
                }

                Macro::addStringDefToReplacementArray($newRequest, $requestVal, $stringReplacements);

                continue;
            }

            if (preg_match('~^\.ds~u', $line, $matches)) {
                continue; // ignore any that didn't match above.
            }

            $firstPassLines[] = $line;

        }

        $numFirstPassLines = count($firstPassLines);
        $lines             = [];
        $macroReplacements = []; // Resetting this
        $charSwaps         = [];

        for ($i = 0; $i < $numFirstPassLines; ++$i) {

            $line = $firstPassLines[$i];

            // Don't care about .UR without an argument or with an invalid URL
            if (preg_match('~^\.UR\s*<?(?<url>.*?)>?$~', $line, $matchesUR)) {
                if ($i === $numFirstPassLines - 1) {
                    continue;
                }
                $lineAfterUR = $firstPassLines[$i + 1];
                if (preg_match('~^\.UE ?(.*)$~', $lineAfterUR, $matchesUE)) {
                    $line = $matchesUR['url'] . $matchesUE[1];
                    ++$i;
                } else {
                    if (empty($matchesUR['url']) || mb_substr($matchesUR['url'], 0, 1) === '#') {
                        $line = $lineAfterUR;
                        if (preg_match('~^\.UE ?(.*)$~', $firstPassLines[$i + 2], $matches)) {
                            $line .= $matches[1];
                            $i += 2;
                        } else {
                            throw new Exception('.UR (empty) with no corresponding .UE');
                        }
                    }
                }
            }

            if (count($aliases) > 0) {
                foreach ($aliases as $new => $old) {
                    $line = Replace::preg('~^\.' . preg_quote($new, '~') . '(\s|$)~u', '.' . $old . '$1', $line);
                }
            }

            if (count($stringReplacements) > 0) {
                $line = strtr($line, $stringReplacements);
            }

            // Handle stuff like:
            // .ie \n(.g .ds Aq \(aq
            // .el       .ds Aq '
            if (preg_match('~^\.ie \\\\n\(\.g \.ds (.+?) (.+)$~u', $line, $matches)) {
                if (!preg_match('~^\.el~u', $firstPassLines[++$i])) {
                    throw new Exception('.ie not followed by .el');
                }
                Macro::addStringDefToReplacementArray($matches[1], $matches[2], $macroReplacements);
                continue;
            }

            if (count($macroReplacements) > 0) {
                $line = strtr($line, $macroReplacements);
            }

            $line = Text::translateCharacters($line);

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
            if (!$foundTitle && preg_match('~^\.TH\s(.*)$~u', $line, $matches)) {
                $foundTitle   = true;
                $titleDetails = Macro::parseArgString($matches[1]);
                if (is_null($titleDetails) || count($titleDetails) < 2) {
                    throw new Exception($line . ' - missing title info');
                }
                // See amor.6 for \FB \FR nonsense.
                $man->title        = TextContent::interpretString(Replace::preg('~\\\\F[BR]~', '',
                  $titleDetails[0]));
                $man->section      = TextContent::interpretString($titleDetails[1]);
                $man->date         = TextContent::interpretString(@$titleDetails[2] ?: '');
                $man->package      = TextContent::interpretString(@$titleDetails[3] ?: '');
                $man->section_name = TextContent::interpretString(@$titleDetails[4] ?: '');
                continue;
            }
            //</editor-fold>

            if (count($lines) > 0 ||
              (mb_strlen($line) > 0 && $line !== '.PP')
            ) { // Exclude leading blank lines, and .PP
                $lines[] = $line;
            }

        }

        return $lines;

    }

    private static function translateCharacters($line)
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
          '\\en' => '\n',
          '\\t'  => "\t",
            // Unpaddable space size space glyph (no line break). See enigma.6:
          '\\ '  => mb_convert_encoding(chr(160), 'UTF-8', 'HTML-ENTITIES'),
            // Unbreakable space that stretches like a normal inter-word space when a line is adjusted
          '\\~'  => mb_convert_encoding(chr(160), 'UTF-8', 'HTML-ENTITIES'),
          '\\*R' => '®',
        ];

        $namedGlyphs = [
            // Hack for .tr (see e.g. myproxy-replicate.8):
          'Tr'             => '☃',
            // Nordic
          '-D'             => 'Ð',
          'Sd'             => 'ð',
          'TP'             => 'Þ',
          'Tp'             => 'þ',
          'ss'             => 'ß',
            // Ligatures and Other Latin Glyph
          'ff'             => 'ff',
          'fi'             => 'fi',
          'fl'             => 'fl',
          'Fi'             => 'ffi',
          'Fl'             => 'ffl',
          '/L'             => 'Ł',
          '/l'             => 'ł',
          '/O'             => 'Ø',
          '/o'             => 'ø',
          'AE'             => 'Æ',
          'ae'             => 'æ',
          'OE'             => 'Œ',
          'oe'             => 'œ',
          'IJ'             => 'Ĳ',
          'ij'             => 'ĳ',
          '.i'             => 'ı',
          '.j'             => 'ȷ',
            // Accented Characters
          '\'A'            => 'Á',
          '\'C'            => 'Ć',
          '\'E'            => 'É',
          '\'I'            => 'Í',
          '\'O'            => 'Ó',
          '\'U'            => 'Ú',
          '\'Y'            => 'Ý',
          '\'a'            => 'á',
          '\'c'            => 'ć',
          '\'e'            => 'é',
          '\'i'            => 'í',
          '\'o'            => 'ó',
          '\'u'            => 'ú',
          '\'y'            => 'ý',
          ':A'             => 'Ä',
          ':E'             => 'Ë',
          ':I'             => 'Ï',
          ':O'             => 'Ö',
          ':U'             => 'Ü',
          ':Y'             => 'Ÿ',
          ':a'             => 'ä',
          ':e'             => 'ë',
          ':i'             => 'ï',
          ':o'             => 'ö',
          ':u'             => 'ü',
          ':y'             => 'ÿ',
          '^A'             => 'Â',
          '^E'             => 'Ê',
          '^I'             => 'Î',
          '^O'             => 'Ô',
          '^U'             => 'Û',
          '^a'             => 'â',
          '^e'             => 'ê',
          '^i'             => 'î',
          '^o'             => 'ô',
          '^u'             => 'û',
          '`A'             => 'À',
          '`E'             => 'È',
          '`I'             => 'Ì',
          '`O'             => 'Ò',
          '`U'             => 'Ù',
          '`a'             => 'à',
          '`e'             => 'è',
          '`i'             => 'ì',
          '`o'             => 'ò',
          '`u'             => 'ù',
          '~A'             => 'Ã',
          '~N'             => 'Ñ',
          '~O'             => 'Õ',
          '~a'             => 'ã',
          '~n'             => 'ñ',
          '~o'             => 'õ',
          'vS'             => 'Š',
          'vs'             => 'š',
          'vZ'             => 'Ž',
          'vz'             => 'ž',
          ',C'             => 'Ç',
          ',c'             => 'ç',
          'oA'             => 'Å',
          'oa'             => 'å',
            // Accents
          'a"'             => '˝',
          'a-'             => '¯',
          'a.'             => '˙',
          'a^'             => '^',
          'aa'             => '´',
          'ga'             => '`',
          'ab'             => '˘',
          'ac'             => '¸',
          'ad'             => '¨',
          'ah'             => 'ˇ',
          'ao'             => '˚',
          'a~'             => '~',
          'ho'             => '˛',
          'ha'             => '^',
          'ti'             => '~',
            // Quotes
          'Bq'             => '„',
          'bq'             => '‚',
          'lq'             => '“',
          'rq'             => '”',
          'oq'             => '‘',
          'cq'             => '’',
            // NB: we do 'aq' and 'dq' in  interpretString()
          'Fo'             => '«',
          'Fc'             => '»',
          'fo'             => '‹',
          'fc'             => '›',
            // Non-standard quotes
          'L"'             => '“',
          'R"'             => '”',
            // Punctuation
          'r!'             => '¡',
          'r?'             => '¿',
          'em'             => '—',
          'en'             => '–',
          'hy'             => '‐',
            // Brackets
          'lB'             => '[',
          'rB'             => ']',
          'lC'             => '{',
          'rC'             => '}',
          'la'             => '⟨',
          'ra'             => '⟩',
          'bv'             => '⎪',
          'braceex'        => '⎪',
          'bracketlefttp'  => '⎡',
          'bracketleftbt'  => '⎣',
          'bracketleftex'  => '⎢',
          'bracketrighttp' => '⎤',
          'bracketrightbt' => '⎦',
          'bracketrightex' => '⎥',
          'lt'             => '╭',
          'bracelefttp'    => '⎧',
          'lk'             => '┥',
          'braceleftmid'   => '⎨',
          'lb'             => '╰',
          'braceleftbt'    => '⎩',
          'braceleftex'    => '⎪',
          'rt'             => '╮',
          'bracerighttp'   => '⎫',
          'rk'             => '┝',
          'bracerightmid'  => '⎬',
          'rb'             => '╯',
          'bracerightbt'   => '⎭',
          'bracerightex'   => '⎪',
          'parenlefttp'    => '⎛',
          'parenleftbt'    => '⎝',
          'parenleftex'    => '⎜',
          'parenrighttp'   => '⎞',
          'parenrightbt'   => '⎠',
          'parenrightex'   => '⎟',
            // Arrows
          '<-'             => '←',
          '->'             => '→',
          '<>'             => '↔',
          'da'             => '↓',
          'ua'             => '↑',
          'va'             => '↕',
          'lA'             => '⇐',
          'rA'             => '⇒',
          'hA'             => '⇔',
          'dA'             => '⇓',
          'uA'             => '⇑',
          'vA'             => '⇕',
          'an'             => '⎯',
            // Lines
          'ba'             => '|',
          'br'             => '│',
          'ul'             => '_',
          'rn'             => '‾',
          'ru'             => '_',
          'bb'             => '¦',
          'sl'             => '/',
            // Note we don't do "rs" line until interpretString() to avoid problems with adding backslashes
            // Text Markers
          'ci'             => '○',
          'bu'             => '·',
          'dd'             => '‡',
          'dg'             => '†',
          'lz'             => '◊',
          'sq'             => '□',
          'ps'             => '¶',
          'sc'             => '§',
          'lh'             => '☜',
          'rh'             => '☞',
          'at'             => '@',
          'sh'             => '#',
          'CR'             => '↵',
          'OK'             => '✓',
            // Legal Symbols
          'co'             => '©',
          'rg'             => '™',
          'tm'             => '™',
          'bs'             => '☎',
            // Currency symbols
          'Do'             => '$',
          'ct'             => '¢',
          'eu'             => '€',
          'Eu'             => '€',
          'Ye'             => '¥',
          'Po'             => '£',
          'Cs'             => '¤',
          'Fn'             => 'ƒ',
            // Units
          'de'             => '°',
          '%0'             => '‰',
          'fm'             => '′',
          'sd'             => '″',
          'mc'             => 'µ',
          'Of'             => 'ª',
          'Om'             => 'º',
            // Logical Symbols
          'AN'             => '∧',
          'OR'             => '∨',
          'no'             => '¬',
          'tn'             => '¬',
          'te'             => '∃',
          'fa'             => '∀',
          'st'             => '∋',
          '3d'             => '∴',
          'tf'             => '∴',
          'or'             => '|',
            // Mathematical Symbols
          '12'             => '½',
          '14'             => '¼',
          '34'             => '¾',
          '18'             => '⅛',
          '38'             => '⅜',
          '58'             => '⅝',
          '78'             => '⅞',
          'S1'             => '¹',
          'S2'             => '²',
          'S3'             => '³',
          'pl'             => '+',
          'mi'             => '−',
          '-+'             => '∓',
          '+-'             => '±',
          'pc'             => '·',
          'md'             => '⋅',
          'mu'             => '×',
          'tmu'            => '×',
          'c*'             => '⊗',
          'c+'             => '⊕',
          'di'             => '÷',
          'tdi'            => '÷',
          'f/'             => '⁄',
          '**'             => '∗',
          '<='             => '≤',
          '>='             => '≥',
          '<<'             => '≪',
          '>>'             => '≫',
          'eq'             => '=',
          '!='             => '≠',
          '=='             => '≡',
          'ne'             => '≢',
          '=~'             => '≅',
          '|='             => '≃',
          'ap'             => '∼',
          '~~'             => '≈',
          '~='             => '≈',
          'pt'             => '∝',
          'es'             => '∅',
          'mo'             => '∈',
          'nm'             => '∉',
          'sb'             => '⊂',
          'nb'             => '⊄',
          'sp'             => '⊃',
          'nc'             => '⊅',
          'ib'             => '⊆',
          'ip'             => '⊇',
          'ca'             => '∩',
          'cu'             => '∪',
          '/_'             => '∠',
          'pp'             => '⊥',
          'is'             => '∫',
          'integral'       => '∫',
          'sum'            => '∑',
          'product'        => '∏',
          'coproduct'      => '∐',
          'gr'             => '∇',
          'sr'             => '√',
          'sqrt'           => '√',
          'lc'             => '⌈',
          'rc'             => '⌉',
          'lf'             => '⌊',
          'rf'             => '⌋',
          'if'             => '∞',
          'Ah'             => 'ℵ',
          'Im'             => 'ℑ',
          'Re'             => 'ℜ',
          'wp'             => '℘',
          'pd'             => '∂',
          '-h'             => 'ℏ',
          'hbar'           => 'ℏ',
            // Greek glyphs
          '*A'             => 'Α',
          '*B'             => 'Β',
          '*G'             => 'Γ',
          '*D'             => 'Δ',
          '*E'             => 'Ε',
          '*Z'             => 'Ζ',
          '*Y'             => 'Η',
          '*H'             => 'Θ',
          '*I'             => 'Ι',
          '*K'             => 'Κ',
          '*L'             => 'Λ',
          '*M'             => 'Μ',
          '*N'             => 'Ν',
          '*C'             => 'Ξ',
          '*O'             => 'Ο',
          '*P'             => 'Π',
          '*R'             => 'Ρ',
          '*S'             => 'Σ',
          '*T'             => 'Τ',
          '*U'             => 'Υ',
          '*F'             => 'Φ',
          '*X'             => 'Χ',
          '*Q'             => 'Ψ',
          '*W'             => 'Ω',
          '*a'             => 'α',
          '*b'             => 'β',
          '*g'             => 'γ',
          '*d'             => 'δ',
          '*e'             => 'ε',
          '*z'             => 'ζ',
          '*y'             => 'η',
          '*h'             => 'θ',
          '*i'             => 'ι',
          '*k'             => 'κ',
          '*l'             => 'λ',
          '*m'             => 'μ',
          '*n'             => 'ν',
          '*c'             => 'ξ',
          '*o'             => 'ο',
          '*p'             => 'π',
          '*r'             => 'ρ',
          'ts'             => 'ς',
          '*s'             => 'σ',
          '*t'             => 'τ',
          '*u'             => 'υ',
          '*f'             => 'ϕ',
          '*x'             => 'χ',
          '*q'             => 'ψ',
          '*w'             => 'ω',
          '+h'             => 'ϑ',
          '+f'             => 'φ',
          '+p'             => 'ϖ',
          '+e'             => 'ϵ',
            // Card symbols
          'CL'             => '♣',
          'SP'             => '♠',
          'HE'             => '♥',
          'u2661'          => '♡',
          'DI'             => '♦',
          'u2662'          => '♢',
        ];

        foreach ($namedGlyphs as $name => $val) {
            Macro::addStringDefToReplacementArray($name, $val, $replacements);
        }

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

        // Don't worry about this: "Local vertical/horizontal motion"
        $line = Replace::preg('~\\\\[vh]\'.*?\'~u', ' ', $line);

        // Don't worry colour changes:
        $line = Replace::preg('~\\\\m(\(..|\[.*?\])~u', '', $line);

        return rtrim($line);

    }

}
