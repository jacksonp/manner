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
            // Legal Symbols
          'co' => '©',
          'rg' => '™',
          'tm' => '™',
          'bs' => '☎',
            // Quotes
          'Bq' => '„',
          'bq' => '‚',
          'lq' => '“',
          'rq' => '”',
          'oq' => '‘',
          'cq' => '’',
          'aq' => '\'',
          'dq' => '"',
          'Fo' => '«',
          'Fc' => '»',
          'fo' => '‹',
          'fc' => '›',
            // Done quotes
            // Punctuation
          'r!' => '¡',
          'r?' => '¿',
          'em' => '—',
          'en' => '–',
          'hy' => '‐',
            // Done punctuation
        ];

        foreach ($namedGlyphs as $name => $val) {
            $replacements['\(' . $name] = $val;
            $replacements['\[' . $name . ']'] = $val;
        }

        return str_replace(array_keys($replacements), array_values($replacements), $line);
    }

}