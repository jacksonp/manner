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
          '\\-'  => '-',
          '\\.'  => '.',
          '\\en' => '\n',
            // "\e represents the current escape character." - let's hope it's always a backslash
          '\\e'  => '\\',
            // \\ "reduces to a single backslash"
          '\\\\' => '\\',
            // 1/6 em narrow space glyph, e.g. enigma.6 synopsis. Just remove for now.
          '\\|'  => '',
            // Unpaddable space size space glyph (no line break). Just use space for now:
          '\\ '  => ' ',
        ];

        $namedGlyphs = [
            // Accented Characters
          '\'A' => 'Á',
          '\'C' => 'Ć',
          '\'E' => 'É',
          '\'I' => 'Í',
          '\'O' => 'Ó',
          '\'U' => 'Ú',
          '\'Y' => 'Ý',
          '\'a' => 'á',
          '\'c' => 'ć',
          '\'e' => 'é',
          '\'i' => 'í',
          '\'o' => 'ó',
          '\'u' => 'ú',
          '\'y' => 'ý',
          ':A'  => 'Ä',
          ':E'  => 'Ë',
          ':I'  => 'Ï',
          ':O'  => 'Ö',
          ':U'  => 'Ü',
          ':Y'  => 'Ÿ',
          ':a'  => 'ä',
          ':e'  => 'ë',
          ':i'  => 'ï',
          ':o'  => 'ö',
          ':u'  => 'ü',
          ':y'  => 'ÿ',
          '^A'  => 'Â',
          '^E'  => 'Ê',
          '^I'  => 'Î',
          '^O'  => 'Ô',
          '^U'  => 'Û',
          '^a'  => 'â',
          '^e'  => 'ê',
          '^i'  => 'î',
          '^o'  => 'ô',
          '^u'  => 'û',
          '`A'  => 'À',
          '`E'  => 'È',
          '`I'  => 'Ì',
          '`O'  => 'Ò',
          '`U'  => 'Ù',
          '`a'  => 'à',
          '`e'  => 'è',
          '`i'  => 'ì',
          '`o'  => 'ò',
          '`u'  => 'ù',
          '~A'  => 'Ã',
          '~N'  => 'Ñ',
          '~O'  => 'Õ',
          '~a'  => 'ã',
          '~n'  => 'ñ',
          '~o'  => 'õ',
          'vS'  => 'Š',
          'vs'  => 'š',
          'vZ'  => 'Ž',
          'vz'  => 'ž',
          ',C'  => 'Ç',
          ',c'  => 'ç',
          'oA'  => 'Å',
          'oa'  => 'å',
            // Legal Symbols
          'co'  => '©',
          'rg'  => '™',
          'tm'  => '™',
          'bs'  => '☎',
            // Quotes
          'Bq'  => '„',
          'bq'  => '‚',
          'lq'  => '“',
          'rq'  => '”',
          'oq'  => '‘',
          'cq'  => '’',
          'aq'  => '\'',
          'dq'  => '"',
          'Fo'  => '«',
          'Fc'  => '»',
          'fo'  => '‹',
          'fc'  => '›',
            // Done quotes
            // Punctuation
          'r!'  => '¡',
          'r?'  => '¿',
          'em'  => '—',
          'en'  => '–',
          'hy'  => '‐',
            // Done punctuation
        ];

        foreach ($namedGlyphs as $name => $val) {
            $replacements['\(' . $name]       = $val;
            $replacements['\[' . $name . ']'] = $val;
        }

        return str_replace(array_keys($replacements), array_values($replacements), $line);
    }

}
