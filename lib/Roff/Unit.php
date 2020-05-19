<?php
declare(strict_types=1);

namespace Manner\Roff;

use Manner\Replace;
use ParseError;

class Unit
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
     * Normalize all roff sizes in the given string to (implicit) unit n (ens).
     * There may be several different units in string, e.g. in a comparison "2m+.3i>3n".
     *
     * @param string $string
     * @param string $defaultUnit
     * @param string $targetUnit
     * @return string
     * @throws Exception
     */
    public static function normalize(string $string, string $defaultUnit, string $targetUnit): string
    {
        $string = Replace::pregCallback(
          '~((?:\d*\.)?\d+)([uicpPszfmnvM])?~u',
          function ($matches) use ($defaultUnit, $targetUnit) {
              $unit       = @$matches[2] ?: $defaultUnit;
              $basicUnits = self::unitMultipliers[$unit] * $matches[1];

              return (string)round($basicUnits / self::unitMultipliers[$targetUnit]);
          },
          $string
        );

        $string = preg_replace('~[^\d.()+*/\-><= ?]~', '', $string);

        return self::evaluate($string);
    }

    /**
     * Parentheses may be used as in any other language. However, in gtroff they are necessary to ensure order of
     * evaluation. gtroff has no operator precedence; expressions are evaluated left to right. This means that
     * gtroff evaluates ‘3+5*4’ as if it were parenthesized like ‘(3+5)*4’, not as ‘3+(5*4)’, as might be expected.
     * @param string $string
     * @return string
     * @throws Exception
     */
    private static function evaluate(string $string): string
    {
        $string = trim($string);

        // Remove parentheses around a lone number:
        $evaluatedString = Replace::preg('~\(((?:\d*\.)?\d+)\)~u', '$1', $string);

        if (preg_match('~^(?<left>.*)(?<op>[><]\?)(?<right>.*)$~u', $evaluatedString, $matches)) {
            $left  = self::evaluate($matches['left']);
            $right = self::evaluate($matches['right']);
            if ($matches['op'] === '>?') {
                return max($left, $right);
            } elseif ($matches['op'] === '<?') {
                return min($left, $right);
            }
        }

        // Evaluate first matched expression only, because gtroff has no operator precedence:
        $evaluatedString = Replace::pregCallback(
          '~[-\+]?(?:\d*\.)?\d+[-\+\*/](?:\d*\.)?\d+~u',
          function ($matches) {
              $expression = $matches[0];
              try {
                  return eval('return ' . $expression . ';');
              } catch (ParseError $e) {
                  throw new Exception('Could not evaluate ' . $expression);
              }
          },
          $evaluatedString,
          1
        );

        if ($evaluatedString === $string) {
            return $evaluatedString;
        } else {
            return self::evaluate($evaluatedString);
        }
    }

}
