<?php


class Text
{

    static function isText($line)
    {
        return !empty($line) && $line[0] !== '.';
    }

    static function mergeTextLines($lines)
    {

        $numLines = count($lines);

        $newLines = [];

        for ($i = 0; $i < $numLines; ++$i) {
            $line = $lines[$i];

            $newLines[$i] = $line;

            // If this line is text, merge in any following lines of text.
            if (self::isText($line)) {
                for ($j = $i; $j < $numLines; ++$j) {
                    if (!self::isText($lines[$j + 1])) {
                        $i = $j;
                        break;
                    }
                    $newLines[$i] .= ' ' . $lines[$j + 1];
                }
            }

        }

        return $newLines;

    }

    static function toCommonMark($lines)
    {

        $numLines = count($lines);

        $newLines = [];

        for ($i = 0; $i < $numLines; ++$i) {
            $line = $lines[$i];

            if (preg_match('~^\.I (.*)$~u', $line, $matches)) {
                $newLines[$i] = '*' . $matches[1] . '*';
                continue;
            } elseif (preg_match('~^\.B (.*)$~u', $line, $matches)) {
                $newLines[$i] = '**' . $matches[1] . '**';
                continue;
            }

            $newLines[$i] = $line;


        }

        return $newLines;

    }

    public static function preprocess($line)
    {
        // See http://man7.org/linux/man-pages/man7/groff_char.7.html

        $replacements = [
            // \/ Increases the width of the preceding glyph so that the spacing between that glyph and the following glyph is correct if the following glyph is a roman glyph. groff(7)
          '\\/'  => '',
            // \, Modifies the spacing of the following glyph so that the spacing between that glyph and the preceding glyph is correct if the preceding glyph is a roman glyph. groff(7)
          '\\,'  => '',
          '\\\'' => '´',
          '\\-'  => '-',
          '\\.'  => '.',
          '\\en' => '\n',
            // "\e represents the current escape character." - let's hope it's always a backslash
          '\\e'  => '\\',
            // 1/6 em narrow space glyph, e.g. enigma.6 synopsis. Just remove for now.
          '\\|'  => '',
            // Unpaddable space size space glyph (no line break). See enigma.6:
          '\\ '  => mb_convert_encoding(chr(160), 'UTF-8', 'HTML-ENTITIES'),
        ];

        $namedGlyphs = [
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
            // Done quotes
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
                $replacements['\(' . $name] = $val;
            }
            $replacements['\[' . $name . ']'] = $val;
        }

        $line = str_replace(array_keys($replacements), array_values($replacements), $line);

        // \\ "reduces to a single backslash" - Do this last so the new single backslashes don't get matched by any other pattern.
        return str_replace('\\\\', '\\', $line);
    }

}
