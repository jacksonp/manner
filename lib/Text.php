<?php


class Text
{

    /**
     * Strip comments, handle title, stick rest in $lines
     */
    static function preprocessLines($rawLines)
    {

        $numRawLines = count($rawLines);
        $lines       = [];

        $man = Man::instance();

        for ($i = 0; $i < $numRawLines; ++$i) {

            $line = $rawLines[$i];

            // Continuations
            while ($i < $numRawLines - 1 && mb_substr($line, -1, 1) === '\\'
              && (mb_strlen($line) === 1 || mb_substr($line, -2, 1) !== '\\')) {
                $line = mb_substr($line, 0, -1) . $rawLines[++$i];
            }

            // Skip comments
            if (preg_match('~^[\'\.]?\\\\"~u', $line, $matches)) {
                continue;
            }

            // \" is start of a comment. Everything up to the end of the line is ignored.
            // Some man pages get this wrong and expect \" to be printed (see fox-calculator.1),
            // but this behaviour is consistent with what the man command renders:
            $line = preg_replace('~^(.*)\s+\\\\".*$~', '$1', $line);

            // Skip stuff we don't care about:
            // .IX: index information: "Inserts index information (for a search system or printed index list). Index information is not normally displayed in the page itself."
            // .nh: No hyphenation
            // .ad: "line adjustment"
            if (preg_match('~^\.(IX|nh|ad)~u', $line)) {
                continue;
            }

            $line = Text::preprocess($line);

            if (preg_match('~^\.(if|ie|el)~u', $line, $matches)) {
                throw new Exception($line . ' - no support for ' . $matches[1]);
            }

            // Skip empty requests
            if ($line === '.') {
                continue;
            }

            //<editor-fold desc="Handle man title macro">
            if (preg_match('~^\.TH (.*)$~u', $line, $matches)) {
                $titleDetails = str_getcsv($matches[1], ' ');
                if (count($titleDetails) < 2) {
                    throw new Exception($line . ' - missing title info');
                }
                $man->title        = $titleDetails[0];
                $man->section      = $titleDetails[1];
                $man->date         = @$titleDetails[2] ?: '';
                $man->package      = @$titleDetails[3] ?: '';
                $man->section_name = @$titleDetails[4] ?: '';
                continue;
            }
            //</editor-fold>

            $lines[] = $line;

        }

        return $lines;

    }

    private static function preprocess($line)
    {
        // See http://man7.org/linux/man-pages/man7/groff_char.7.html

        $replacements = [
            // \\ "reduces to a single backslash" - Do this first as strtr() doesn't search replaced text for further replacements.
          '\\\\' => '\\',
            // \/ Increases the width of the preceding glyph so that the spacing between that glyph and the following glyph is correct if the following glyph is a roman glyph. groff(7)
          '\\/'  => '',
            // \, Modifies the spacing of the following glyph so that the spacing between that glyph and the preceding glyph is correct if the preceding glyph is a roman glyph. groff(7)
          '\\,'  => '',
          '\\\'' => '´',
            // The acute accent ´; same as \(aa.
          '\\´'  => '´',
            // The grave accent `; same as \(ga.
          '\\`'  => '`',
          '\\-'  => '-',
            // The same as \(ul, the underline character.
          '\\_'  => '_',
          '\\.'  => '.',
          '\\en' => '\n',
          '\\t'  => "\t",
            // Default optional hyphenation character. Just remove for now.
          '\\%'  => '',
            // Inserts a zero-width break point (similar to \% but without a soft hyphen character). Just remove for now.
          '\\:'  => '',
            // 1/6 em narrow space glyph, e.g. enigma.6 synopsis. Just remove for now.
          '\\|'  => '',
            // 1/12 em half-narrow space glyph; zero width in nroff. Just remove for now.
          '\\^'  => '',
            // Unpaddable space size space glyph (no line break). See enigma.6:
          '\\ '  => mb_convert_encoding(chr(160), 'UTF-8', 'HTML-ENTITIES'),
            // Unbreakable space that stretches like a normal inter-word space when a line is adjusted
          '\\~'  => mb_convert_encoding(chr(160), 'UTF-8', 'HTML-ENTITIES'),
        ];

        $namedGlyphs = [
          '-D'        => 'Ð',
          'Sd'        => 'ð',
          'TP'        => 'Þ',
          'Tp'        => 'þ',
          'ss'        => 'ß',
            // Ligatures and Other Latin Glyph
          'ff'        => 'ff',
          'fi'        => 'fi',
          'fl'        => 'fl',
          'Fi'        => 'ffi',
          'Fl'        => 'ffl',
          '/L'        => 'Ł',
          '/l'        => 'ł',
          '/O'        => 'Ø',
          '/o'        => 'ø',
          'AE'        => 'Æ',
          'ae'        => 'æ',
          'OE'        => 'Œ',
          'oe'        => 'œ',
          'IJ'        => 'Ĳ',
          'ij'        => 'ĳ',
          '.i'        => 'ı',
          '.j'        => 'ȷ',
            // Accented Characters
          '\'A'       => 'Á',
          '\'C'       => 'Ć',
          '\'E'       => 'É',
          '\'I'       => 'Í',
          '\'O'       => 'Ó',
          '\'U'       => 'Ú',
          '\'Y'       => 'Ý',
          '\'a'       => 'á',
          '\'c'       => 'ć',
          '\'e'       => 'é',
          '\'i'       => 'í',
          '\'o'       => 'ó',
          '\'u'       => 'ú',
          '\'y'       => 'ý',
          ':A'        => 'Ä',
          ':E'        => 'Ë',
          ':I'        => 'Ï',
          ':O'        => 'Ö',
          ':U'        => 'Ü',
          ':Y'        => 'Ÿ',
          ':a'        => 'ä',
          ':e'        => 'ë',
          ':i'        => 'ï',
          ':o'        => 'ö',
          ':u'        => 'ü',
          ':y'        => 'ÿ',
          '^A'        => 'Â',
          '^E'        => 'Ê',
          '^I'        => 'Î',
          '^O'        => 'Ô',
          '^U'        => 'Û',
          '^a'        => 'â',
          '^e'        => 'ê',
          '^i'        => 'î',
          '^o'        => 'ô',
          '^u'        => 'û',
          '`A'        => 'À',
          '`E'        => 'È',
          '`I'        => 'Ì',
          '`O'        => 'Ò',
          '`U'        => 'Ù',
          '`a'        => 'à',
          '`e'        => 'è',
          '`i'        => 'ì',
          '`o'        => 'ò',
          '`u'        => 'ù',
          '~A'        => 'Ã',
          '~N'        => 'Ñ',
          '~O'        => 'Õ',
          '~a'        => 'ã',
          '~n'        => 'ñ',
          '~o'        => 'õ',
          'vS'        => 'Š',
          'vs'        => 'š',
          'vZ'        => 'Ž',
          'vz'        => 'ž',
          ',C'        => 'Ç',
          ',c'        => 'ç',
          'oA'        => 'Å',
          'oa'        => 'å',
            // Lines
          'ba'        => '|',
          'br'        => '│',
          'ul'        => '_',
          'rn'        => '‾',
          'ru'        => '_',
          'bb'        => '¦',
          'sl'        => '/',
          'rs'        => '\\',
            // Text Markers
          'ci'        => '○',
          'bu'        => '·',
          'dd'        => '‡',
          'dg'        => '†',
          'lz'        => '◊',
          'sq'        => '□',
          'ps'        => '¶',
          'sc'        => '§',
          'lh'        => '☜',
          'rh'        => '☞',
          'at'        => '@',
          'sh'        => '#',
          'CR'        => '↵',
          'OK'        => '✓',
            // Legal Symbols
          'co'        => '©',
          'rg'        => '™',
          'tm'        => '™',
          'bs'        => '☎',
            // Currency symbols
          'Do'        => '$',
          'ct'        => '¢',
          'eu'        => '€',
          'Eu'        => '€',
          'Ye'        => '¥',
          'Po'        => '£',
          'Cs'        => '¤',
          'Fn'        => 'ƒ',
            // Units
          'de'        => '°',
          '%0'        => '‰',
          'fm'        => '′',
          'sd'        => '″',
          'mc'        => 'µ',
          'Of'        => 'ª',
          'Om'        => 'º',
            // Mathematical Symbols
          '12'        => '½',
          '14'        => '¼',
          '34'        => '¾',
          '18'        => '⅛',
          '38'        => '⅜',
          '58'        => '⅝',
          '78'        => '⅞',
          'S1'        => '¹',
          'S2'        => '²',
          'S3'        => '³',
          'pl'        => '+',
          'mi'        => '−',
          '-+'        => '∓',
          '+-'        => '±',
          'pc'        => '·',
          'md'        => '⋅',
          'mu'        => '×',
          'tmu'       => '×',
          'c*'        => '⊗',
          'c+'        => '⊕',
          'di'        => '÷',
          'tdi'       => '÷',
          'f/'        => '⁄',
          '**'        => '∗',
          '<='        => '≤',
          '>='        => '≥',
          '<<'        => '≪',
          '>>'        => '≫',
          'eq'        => '=',
          '!='        => '≠',
          '=='        => '≡',
          'ne'        => '≢',
          '=~'        => '≅',
          '|='        => '≃',
          'ap'        => '∼',
          '~~'        => '≈',
          '~='        => '≈',
          'pt'        => '∝',
          'es'        => '∅',
          'mo'        => '∈',
          'nm'        => '∉',
          'sb'        => '⊂',
          'nb'        => '⊄',
          'sp'        => '⊃',
          'nc'        => '⊅',
          'ib'        => '⊆',
          'ip'        => '⊇',
          'ca'        => '∩',
          'cu'        => '∪',
          '/_'        => '∠',
          'pp'        => '⊥',
          'is'        => '∫',
          'integral'  => '∫',
          'sum'       => '∑',
          'product'   => '∏',
          'coproduct' => '∐',
          'gr'        => '∇',
          'sr'        => '√',
          'sqrt'      => '√',
          'lc'        => '⌈',
          'rc'        => '⌉',
          'lf'        => '⌊',
          'rf'        => '⌋',
          'if'        => '∞',
          'Ah'        => 'ℵ',
          'Im'        => 'ℑ',
          'Re'        => 'ℜ',
          'wp'        => '℘',
          'pd'        => '∂',
          '-h'        => 'ℏ',
          'hbar'      => 'ℏ',
            // Accents
          'a"'        => '˝',
          'a-'        => '¯',
          'a.'        => '˙',
          'a^'        => '^',
          'aa'        => '´',
          'ga'        => '`',
          'ab'        => '˘',
          'ac'        => '¸',
          'ad'        => '¨',
          'ah'        => 'ˇ',
          'ao'        => '˚',
          'a~'        => '~',
          'ho'        => '˛',
          'ha'        => '^',
          'ti'        => '~',
            // Quotes
          'Bq'        => '„',
          'bq'        => '‚',
          'lq'        => '“',
          'rq'        => '”',
          'oq'        => '‘',
          'cq'        => '’',
          'aq'        => '\'',
          'dq'        => '"',
          'Fo'        => '«',
          'Fc'        => '»',
          'fo'        => '‹',
          'fc'        => '›',
            // Non-standard quotes
          'L"'        => '“',
          'R"'        => '”',
            // Punctuation
          'r!'        => '¡',
          'r?'        => '¿',
          'em'        => '—',
          'en'        => '–',
          'hy'        => '‐',
            // Done punctuation
        ];

        foreach ($namedGlyphs as $name => $val) {
            if (mb_strlen($name) === 2) {
                $replacements['\(' . $name]  = $val;
                $replacements['\*(' . $name] = $val;
            }
            $replacements['\[' . $name . ']'] = $val;
        }


        // If a backslash is followed by a character that does not constitute a defined escape sequence, the backslash is silently ignored and the character maps to itself.
        // Just the cases we come across:
        $replacements['\\=']       = '=';
        $replacements['\\+']       = '+';
        $replacements['\\]']       = ']';
        $replacements['\\' . "\t"] = ' ';

        $line = strtr($line, $replacements);

        $line = preg_replace_callback('~\\\\\[u([\dA-F]{4})\]~u', function ($matches) {
            return html_entity_decode('&#x' . $matches[1] . ';', ENT_COMPAT, 'UTF-8');
        }, $line);

        $line = preg_replace_callback('~\\\\\[char(\d+)\]~u', function ($matches) {
            return mb_convert_encoding('&#' . intval($matches[1]) . ';', 'UTF-8', 'HTML-ENTITIES');
        }, $line);

        // Don't worry about changes in point size for now:
        return preg_replace('~\\\\s[-+]?\d(.*?)\\\\s[-+]?\d~', '$1', $line);

    }

}
