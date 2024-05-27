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

namespace Manner\Massage;

use DOMXPath;
use Exception;
use Manner\DOM;

class Body
{

    /**
     * @param DOMXPath $xpath
     * @throws Exception
     */
    public static function trimNodesBeforeH1(DOMXPath $xpath): void
    {
        $bodies = $xpath->query('//body');
        if ($bodies->length !== 1) {
            throw new Exception('Found more than one body');
        }
        $body = $bodies->item(0);
        while ($body->firstChild && !DOM::isTag($body->firstChild, 'h1')) {
            $body->removeChild($body->firstChild);
        }
    }

}