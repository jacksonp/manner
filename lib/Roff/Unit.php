<?php
declare(strict_types = 1);

class Roff_Unit
{

    static function normalize(string $string): string
    {

        $string = Replace::pregCallback('~(\d+(?:\.\d+)?)([uicpPszfmnvM])~u', function ($matches) {
            $unitMultipliers = [
                // device dependent measurement, quite small, ranging from 1/75th to 1/72000th of an inch
              'u' => 1,
                // inch
              'i' => 75,
                // One inch is equal to 2.54cm.
              'c' => 75 * 2.54,
                // Points. This is a typesetter’s measurement used for measure type size. It is 72 points to an inch.
              'p' => 75 / 72,
                // Pica. Another typesetting measurement. 6 Picas to an inch (and 12 points to a pica).
              'P' => 75 / 6,
                // https://www.gnu.org/software/groff/manual/html_node/Fractional-Type-Sizes.html#Fractional-Type-Sizes
              's' => 1,
                // https://www.gnu.org/software/groff/manual/html_node/Fractional-Type-Sizes.html#Fractional-Type-Sizes
              'z' => 1,
                // https://www.gnu.org/software/groff/manual/html_node/Colors.html#Colors
              'f' => 65536,
                // em = 11/64 inch
              'm' => 75 * 11 / 64,
                // en = 1/2 em:
              'n' => (75 * 11 / 64) / 2,
                // By default, gtroff uses 10 point type on 12 point spacing. https://www.gnu.org/software/groff/manual/html_node/Sizes.html#Sizes
              'v' => 75 * 10 / 72,
                // 100ths of an em.
              'M' => (75 * 11 / 64) / 100,
            ];

            return $unitMultipliers[$matches[2]] * $matches[1];
        }, $string);

        if (preg_match('~^((\.\d)|[-\+\*/\d\(\)])+$~u', $string)) {
            try {
                $evalString = eval('return ' . $string . ';');
            } catch (ParseError $e) {
                return $string;
            }
            $string = (string)round($evalString);
        }

        // Careful, we maybe not actually be touching input, in which case we return as-is.
        return $string;

    }

}
