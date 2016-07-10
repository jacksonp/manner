<?php


class Roff_Register
{

    static function checkEvaluate(array $lines, int $i)
    {

        if (!preg_match('~^\.\s*nr (?<name>\S+) (?<val>.+)$~u', $lines[$i], $matches)) {
            return false;
        }

        $man           = Man::instance();
        $registerValue = $man->applyAllReplacements($matches['val']);
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
                  //throw new Exception($matches['reg'] . ' - unavailable register: ' . $matches[0]);
                  return $matches[0];
              }
          },
          $string);

    }

}
