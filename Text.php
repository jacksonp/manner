<?php


class Text
{

    static function massage($str)
    {
        return str_replace('\\-', '-', $str);
    }

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
          '\\en'  => '\n',
          '\\e'  => '\\', // "\e represents the current escape character." - let's hope it's always a backslash
          '\(co' => '©',
            // Quotes
          '\(Bq' => '„',
          '\(bq' => '‚',
          '\(lq' => '“',
          '\(rq' => '”',
          '\(oq' => '‘',
          '\(cq' => '’',
          '\(aq' => '\'',
          '\(dq' => '"',
          '\(Fo' => '«',
          '\(Fc' => '»',
          '\(fo' => '‹',
          '\(fc' => '›',
            // Done quotes
            // Punctuation
          '\(r!' => '¡',
          '\(r?' => '¿',
          '\(em' => '—',
          '\(en' => '–',
          '\(hy' => '‐',
            // Done punctuation
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $line);
    }

}
