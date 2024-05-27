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
use Manner\Request;

class de implements Template
{

    /**
     * @param array $request
     * @param array $lines
     * @param array|null $macroArguments
     * @throws Exception
     */
    public static function evaluate(array $request, array &$lines, ?array $macroArguments): void
    {
        // shift .de
        array_shift($lines);

        if (!preg_match('~^([^\s"]+)\s*$~u', $request['arg_string'], $matches)) {
            throw new Exception('Unexpected argument in \Roff\de: ' . $request['arg_string']);
        }

        $newMacro   = $matches[1];
        $macroLines = [];
        $foundEnd   = false;

        // We don't want to handle the lines at this stage (e.g. a conditional in the macro), so don't iterate with
        // Request::getLine()
        while (count($lines)) {
            $line    = array_shift($lines);
            $request = Request::peepAt($line);
            if (
              $request['name'] === '.' ||
              ($newMacro === 'P!' && $line === '.') // work around bug in Xm*.3 man pages
            ) {
                $foundEnd = true;
                break;
            }
            $macroLines[] = Request::massageLine($line);
        }

        if (!$foundEnd) {
            throw new Exception('Macro definition for "' . $matches[1] . '" does not follow expected pattern.');
        }

        // Don't override these macros.
        // djvm e.g. does something dodgy when overriding .SS, just use normal .SS handling for it.
        // .URL: we can do a better job with the semantic info.
        // .BB & .EB: see criu.8: does something tricky with .di across macros.
        $protectedMacros = ['SS', 'MTO', 'URL', 'SY', 'YS', 'SH', 'TP', 'RS', 'RE', 'BB', 'EB', 'MR'];

        if (!in_array($newMacro, $protectedMacros)) {
            Man::instance()->addMacro($newMacro, $macroLines);
        }
    }

}
