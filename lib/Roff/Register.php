<?php
declare(strict_types = 1);

class Roff_Register implements Roff_Template
{

    static function evaluate(array $request, array &$lines, ?array $macroArguments)
    {

        // Remove register
        if ($request['request'] === 'rr') {
            Man::instance()->unsetRegister($request['arg_string']);
            array_shift($lines);
            return [];
        }

        // .nr register ±N [M]
        // Define or modify register using ±N with auto-increment M
        if (
          $request['request'] !== 'nr' ||
          !preg_match('~^(?<name>\S+) (?<val>.+)$~u', $request['arg_string'], $matches)
        ) {
            return false;
        }

        array_shift($lines);
        $man           = Man::instance();
        $registerValue = $man->applyAllReplacements($matches['val']);
        // Normalize here: a unit value may be concatenated when the register is used.
        $registerValue = Roff_Unit::normalize($registerValue, 'u', 'u');
        $man->setRegister($matches['name'], $registerValue);

        return [];

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
