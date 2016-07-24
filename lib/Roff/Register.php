<?php


class Roff_Register
{

    static function checkEvaluate(array $lines, int $i)
    {

        if (preg_match('~^\.\s*rr (?<name>\S+)$~u', $lines[$i], $matches)) {
            Man::instance()->unsetRegister($matches['name']);

            return ['i' => $i];
        }

        if (!preg_match('~^\.\s*nr (?<name>\S+) (?<val>.+)$~u', $lines[$i], $matches)) {
            return false;
        }

        $man           = Man::instance();
        $registerValue = $man->applyAllReplacements($matches['val']);
        // Normalize here: a unit value may be concatenated when the register is used.
        $registerValue = Roff_Unit::normalize($registerValue);
        $man->setRegister($matches['name'], $registerValue);

        return ['i' => $i];

    }

    static function substitute(string $string, array &$replacements) :string
    {

        return Replace::pregCallback(
          '~(?J)(?<!\\\\)(?<bspairs>(?:\\\\\\\\)*)\\\\n(?:\[(?<reg>[^\]]+)\]|\((?<reg>..)|(?<reg>.))~u',
          function ($matches) use (&$replacements) {
              if (isset($replacements[$matches['reg']])) {
                  return $matches['bspairs'] . $replacements[$matches['reg']];
              } else {
                  // Match groff's behaviour: unset registers are 0
                  return $matches['bspairs'] . '0';
              }
          },
          $string);

    }

}
