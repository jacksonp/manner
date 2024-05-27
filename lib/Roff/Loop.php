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

class Loop implements Template
{

    /**
     * @param array $request
     * @param array $lines
     * @param array|null $macroArguments
     * @throws Exception
     */
    public static function evaluate(array $request, array &$lines, ?array $macroArguments): void
    {
        array_shift($lines);

        if (mb_strlen($request['raw_arg_string']) === 0) {
            return; // Just skip
        }

        if (preg_match(
          '~^' . Condition::CONDITION_REGEX . ' \\\\{\s*(.*)$~u',
          $request['raw_arg_string'],
          $matches
        )
        ) {
            $unrollOne = Condition::test($matches[1], $macroArguments);
            $newLines  = Condition::ifBlock($lines, $matches[2], $unrollOne);

            if ($unrollOne) {
                $newLines = [...$newLines, '.while ' . $matches[1] . ' \\{', ...$newLines, '\\}'];
                array_splice($lines, 0, 0, $newLines);
            }
        }
    }

}
