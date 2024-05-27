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
use Manner\Replace;

class Comment
{

    /**
     * @param array $lines
     * @return bool
     * @throws Exception
     */
    public static function checkLine(array &$lines): bool
    {
        // Skip full-line comments
        // See mscore.1 for full-line comments starting with '."
        // See cal3d_converter.1 for full-line comments starting with '''
        // See e.g. flow-import.1 for comment starting with .\\"
        // See e.g. card.1 for comment starting with ."
        // See e.g. node.1 for comment starting with .\
        // See e.g. units.1 for comment in a .de starting with .    \"
        if (preg_match('~^([\'.]?\\\\?\\\\"|\.?\s*\\\\"|\'\."\'|\'\'\'|\."|\.\\\\\s+)~u', $lines[0], $matches)) {
            array_shift($lines);

            return true;
        }

        // \" is start of a comment. Everything up to the end of the line is ignored.
        // Some man pages get this wrong and expect \" to be printed (see fox-calculator.1),
        // but this behaviour is consistent with what the man command renders:
        $lines[0] = Replace::preg('~(^|.*?[^\\\\])\\\\".*$~u', '$1', $lines[0], -1, $replacements);
        if ($replacements > 0) {
            $lines[0] = rtrim($lines[0], "\t");

            // Look at this same line again:
            return true;
        }

        if (preg_match('~^[\'.]\s*ig(?:\s+(?<delimiter>.*)|$)~u', $lines[0], $matches)) {
            array_shift($lines);
            $delimiter = empty($matches['delimiter']) ? '..' : ('.' . $matches['delimiter']);
            while (count($lines)) {
                $line = array_shift($lines);
                if ($line === $delimiter) {
                    return true;
                }
            }
            throw new Exception($matches[0] . ' with no corresponding "' . $delimiter . '"');
        }

        return false;
    }

}
