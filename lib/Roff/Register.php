<?php

/**
 * manner: convert troff man pages to semantic HTML
 * Copyright (C) 2024  Jackson Pauls
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Manner\Roff;

use Exception;
use Manner\Man;
use Manner\Replace;

class Register implements Template
{

    /**
     * @param array $request
     * @param array $lines
     * @param array|null $macroArguments
     * @throws Exception
     */
    public static function evaluate(array $request, array &$lines, ?array $macroArguments): void
    {
        $man = Man::instance();
        array_shift($lines);

        // Remove register
        if ($request['request'] === 'rr') {
            if (count($request['arguments']) === 1) {
                $man->unsetRegister($request['arguments'][0]);
            }

            return;
        }

        // .nr register ±N [M]
        // Define or modify register using ±N with auto-increment M

        if (count($request['arguments']) < 2) {
            return;
        }

        // Step might be in $request['arguments'][2] - but we just assume step is 1 for now.

        // Normalize here: a unit value may be concatenated when the register is used.
        $registerValue = Unit::normalize($request['arguments'][1], 'u', 'u');
        $man->setRegister($request['arguments'][0], $registerValue);
    }

    public static function substitute(string $string, array &$replacements): string
    {
        return Replace::pregCallback(
          '~(?J)(?<!\\\\)(?<bspairs>(?:\\\\\\\\)*)\\\\n(?<op>\+)?(?:\[(?<reg>[^]]+)]|\((?<reg>..)|(?<reg>.))~u',
          function ($matches) use (&$replacements) {
              if (isset($replacements[$matches['reg']])) {
                  if ($matches['op'] === '+') {
                      $replacements[$matches['reg']] = (int)$replacements[$matches['reg']] + 1;
                  }

                  return $matches['bspairs'] . $replacements[$matches['reg']];
              } else {
                  // Match groff's behaviour: unset registers are 0
                  return $matches['bspairs'] . '0';
              }
          },
          $string
        );
    }

}
