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

namespace Manner\Block;

// TODO: handle this differently? Use a flag for preformat?
// See esp. submit.1:
/*
 * environment variable \fBSGE_TASK_ID\fP. The option arguments n, m and s will be available through the environment variables \fBSGE_TASK_FIRST\fP, \fBSGE_TASK_LAST\fP and  \fBSGE_TASK_STEPSIZE\fP.
.sp 1
.nf
.RS
Following restrictions apply to the values n and m:
.sp 1
.RS
1 <= n <= MIN(2^31-1, max_aj_tasks)
1 <= m <= MIN(2^31-1, max_aj_tasks)
n <= m
.RE
.fi
.sp 1
\fImax_aj_tasks\fP is defined in the cluster configuration (see
.M sge_conf 5)
.sp 1
The task id range
 *
 */

use DOMElement;
use Exception;
use Manner\Blocks;
use Manner\Indentation;
use Manner\Man;
use Manner\Node;
use Manner\Request;

class Preformatted implements Template
{

    /**
     * @param DOMElement $parentNode
     * @param array $lines
     * @param array $request
     * @param bool $needOneLineOnly
     * @return DOMElement|null
     * @throws Exception
     */
    public static function checkAppend(
      DOMElement $parentNode,
      array &$lines,
      array $request,
      bool $needOneLineOnly = false
    ): ?DOMElement {
        $man = Man::instance();

        array_shift($lines);

        if (Node::isOrInTag($parentNode, 'pre') || !count($lines)) {
            return null;
        }

        $firstInternalLine = Request::peepAt($lines[0]);

        if ($firstInternalLine['name'] === 'PP') {
            array_shift($lines);
            $parentNode = Blocks::getBlockContainerParent($parentNode, true);
        } else {
            $parentNode = Blocks::getBlockContainerParent($parentNode, false, true);
        }

        $pre = $parentNode->ownerDocument->createElement('pre');

        if ($firstInternalLine['name'] === 'IP' && $firstInternalLine['raw_arg_string'] === '') {
            array_shift($lines);
            Indentation::set($pre, $man->indentation);
        }

        /* @var DomElement $pre */
        $pre = $parentNode->appendChild($pre);

        return $pre;
    }

}
