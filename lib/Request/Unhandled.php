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

namespace Manner\Request;

use DOMElement;
use Exception;
use Manner\Block\Template;

class Unhandled implements Template
{

    // Unhandled:
    public const requests = [
      'ab',
      'aln',
      'am1',
      'ami',
      'ami1',
      'asciify',
      'backtrace',
      'blm',
      'box',
      'boxa',
      'brp',
      'break',
      'c2',
      'cf',
      'ch',
      'chop',
      'class',
      'composite',
      'continue',
      'da',
      'dei',
      'dei1',
      'device',
      'devicem',
      'do',
      'dt',
      'ecr',
      'ecs',
      'fc',
      'fzoom',
      'gcolor',
      'hcode',
      'hla',
      'hlm',
      'hpf',
      'hpfa',
      'hpfcode',
      'kern',
      'lc', // leader repetition glyph.
      'length',
      'linetabs',
      'lg',
      'lsm',
      'ls',
      'ne',
      'nm',
      'nn',
      'nroff',
      'nx',
      'open',
      'opena',
      'os',
      'output',
      'pev',
      'pi',
      'pm',
      'pn',
      'pnr',
      'psbb',
      'pso',
      'ptr',
      'pvs',
      'rd',
      'return',
      'rn',
      'rnn',
      'rr',
      'shc',
      'sizes',
      'special',
      'spreadwarn',
      'sty',
      'substring',
      'sv',
      'sy',
      'tc',
      'tkf',
      'tl',
      'trf',
      'trin',
      'trnt',
      'troff',
      'uf',
      'unformat',
      'vpt',
      'warnscale',
      'write',
      'writec',
      'writem',
    ];

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
        throw new Exception('Unhandled request ' . $request['raw_line']);
    }

}
