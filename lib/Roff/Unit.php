<?php
declare(strict_types=1);

class Roff_Unit
{

    private const basicUnitsPerInch = 240;

    private const unitMultipliers = [
        // device dependent measurement, quite small, ranging from 1/75th to 1/72000th of an inch
        'u' => 1,
        // inch
        'i' => self::basicUnitsPerInch,
        // One inch is equal to 2.54cm.
        'c' => self::basicUnitsPerInch / 2.54,
        // Points. This is a typesetter’s measurement used for measure type size. It is 72 points to an inch.
        'p' => self::basicUnitsPerInch / 72,
        // Pica. Another typesetting measurement. 6 Picas to an inch (and 12 points to a pica).
        'P' => self::basicUnitsPerInch / 6,
        // https://www.gnu.org/software/groff/manual/html_node/Fractional-Type-Sizes.html#Fractional-Type-Sizes
        's' => 1,
        // https://www.gnu.org/software/groff/manual/html_node/Fractional-Type-Sizes.html#Fractional-Type-Sizes
        'z' => 1,
        // https://www.gnu.org/software/groff/manual/html_node/Colors.html#Colors
        'f' => 65536,
        // em = 11/64 inch
        'm' => self::basicUnitsPerInch * 11 / 64,
        // en = 1/2 em:
        'n' => (self::basicUnitsPerInch * 11 / 64) / 2,
        // By default, gtroff uses 10 point type on 12 point spacing. https://www.gnu.org/software/groff/manual/html_node/Sizes.html#Sizes
        'v' => self::basicUnitsPerInch * 10 / 72,
        // 100ths of an em.
        'M' => (self::basicUnitsPerInch * 11 / 64) / 100,
    ];

    /**
     * Get the intended indentation in (implicit) unit n (ens).
     *
     * @param string $string
     * @param string $defaultUnit
     * @return string
     */
    static function normalize(string $string, string $defaultUnit = 'n'): string
    {

        $string = Replace::pregCallback(
            '~(\d+(?:\.\d+)?)([uicpPszfmnvM])?~u',
            function ($matches) use ($defaultUnit) {
                $unit       = @$matches[2] ?: $defaultUnit;
                $basicUnits = self::unitMultipliers[$unit] * $matches[1];
                return (string)round($basicUnits / self::unitMultipliers['n']);
            }, $string);

        if (preg_match('~^((\.\d)|[-\+\*/\d\(\)])+$~u', $string)) {
            try {
                $evalString = eval('return ' . $string . ';');
            } catch (ParseError $e) {
                return $string;
            }
            $string = (string)round($evalString);
        }

        return $string;

    }

}
