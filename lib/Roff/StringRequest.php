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

use Manner\Man;
use Manner\Replace;

class StringRequest implements Template
{

    public static function evaluate(array $request, array &$lines, ?array $macroArguments): void
    {
        array_shift($lines);

        $man = Man::instance();

        $known = [];

        $known[<<<'ROFF'
C+ C\v'-.1v'\h'-1p'+\h'-1p'+\v'.1v'\h'-1p'
ROFF] = 'C++';

        /*
        $known['ð'] = <<<'ROFF'
d- \h'0'\(pd\h'-\w'~'u'\v'-.25m'\f2\(hy\fP\v'.25m'\h'-0'
ROFF;
        $known['Ð'] = <<<'ROFF'
D- D\\k:\h'-\w'D'u'\v'-.11m'\z\(hy\v'.11m'\h'|\\n:u'
ROFF;
        $known['Þ'] = <<<'ROFF'
th \f1\v'.3m'I\v'-.3m'\h'-(\w'I'u*2/3)'o\fP
ROFF;
        $known['þ'] = <<<'ROFF'
Th \f1I\h'-\w'I'u*3/5'\v'-.3m'o\v'.3m'\fP
ROFF;
        */
        $known[<<<'ROFF'
d- d\h'-1'\(ga
ROFF]                              = 'ð';
        $known[<<<'ROFF'
D- D\h'-1'\(hy
ROFF] = 'Ð';
        $known[<<<'ROFF'
th \o'bp'
ROFF]      = 'Þ';
        $known[<<<'ROFF'
Th \o'LP'
ROFF]      = 'þ';
        // TODO: could render this the same way wikipedia does: https://en.wikipedia.org/wiki/TeX
        $known[<<<'ROFF'
TX \fRT  E  X\fP
ROFF]                              = 'TeX';
        $known[<<<'ROFF'
OX \fIT E X\fP
ROFF] = 'TeX';
        // eplain.1
        $known[<<<'ROFF'
OX \fIT E X\fP for troff
ROFF] = 'TeX';
        // dt2dv.1
        $known['Te T  E  X'] = 'TeX';
        // grodvi.1
        $known['tx T  E  X'] = 'TeX';
        // makeindex.1
        $known['TX T  E  X']           = 'TeX';
        $known[<<<'ROFF'
BX \fRBIB\fPTeX
ROFF]    = 'BibTeX';
        $known[<<<'ROFF'
LX \fRL  A  \fPTeX
ROFF] = 'LaTeX';
        // bibtex.1
        $known[<<<'ROFF'
LX \fRL  \s-2A\s0  \fPTeX
ROFF] = 'LaTeX';
        // pic.1
        $known[<<<'ROFF'
lx L\h'-0.36m'\v'-0.22v'A\h'-0.15m'\v'0.22v'TeX
ROFF] = 'LaTeX';
        // makeindex.1
        $known[<<<'ROFF'
LX L  \s-2A\s+2  T  E  X
ROFF] = 'LaTeX';
        // ttf2tfm.1
        $known['LX L  A  TeX']                      = 'LaTeX';
        $known[<<<'ROFF'
AX \fRA  M  S\fPTeX
ROFF]             = 'AmSTeX';
        $known[<<<'ROFF'
AY \fRA  M  S\fP\fRL  A  \fPTeX
ROFF] = 'AmSLaTeX';
        // bg5conv.1
        $known[<<<'ROFF'
LE LaTeX 2 \(*e 
ROFF] = 'LaTeX2e';

        //dv2dt.1
        $known['Xe X  E  T'] = 'XeT';
        $known['o   . .  o'] = 'ö';
        $known['u   . .  u'] = 'ü';

        if (array_key_exists($request['raw_arg_string'], $known)) {
            $man->addString($request['arguments'][0], $known[$request['raw_arg_string']]);

            return;
        }

        // Skippable ones: see e.g. a2ping.1
        $skippable = [];
        // TODO: for the following accents, we could conceivably look at the preceding character and replace it with the
        //       accented version
        // perl5120delta.1 this would add and acute accent on preceding char
        $skippable[] = <<<'ROFF'
' \k: \'\h"|0u"
ROFF;
        $skippable[] = <<<'ROFF'
` \\k:\h'-(\\n(.wu*8/10-((1u-(0u%2u))*.13m))'\`\h'|\\n:u'
ROFF;
        $skippable[] = <<<'ROFF'
^ \\k:\h'-(\\n(.wu*10/11-((1u-(0u%2u))*.13m))'^\h'|\\n:u'
ROFF;
        $skippable[] = <<<'ROFF'
, \\k:\h'-(\\n(.wu*8/10)',\h'|\\n:u'
ROFF;
        // perl5100delta.1 this would add the tilde on an existing n to create an ñ
        $skippable[] = <<<'ROFF'
~ \\k:\h'-(\\n(.wu-((1u-(0u%2u))*.13m)-.1m)'~\h'|\\n:u'
ROFF;
        $skippable[] = <<<'ROFF'
/ \\k:\h'-(\\n(.wu*8/10-((1u-(0u%2u))*.13m))'\(sl\h'|\\n:u'
ROFF;

        if (in_array($request['raw_arg_string'], $skippable)) {
            return;
        }

        if (!preg_match('~^(.+?)\s+(.+)$~u', $request['arg_string'], $matches)) {
            // May have just one argument, e.g. gnugo.6 - skip for now.
            return;
        }

        $newRequest = $matches[1];
        $requestVal = $matches[2];
        if (mb_substr($requestVal, 0, 1) === '"') {
            $requestVal = mb_substr($requestVal, 1);
        }

//        var_dump($newRequest);
//        var_dump($matches[2]);
//        var_dump($requestVal);
//        echo '----------------------', PHP_EOL;

        // Q and U are special cases for when replacement is in a macro argument, which are separated by double
        // quotes and otherwise get messed up.
        if (in_array($newRequest, ['C\'', 'C`'])) {
            $requestVal = '"';
        } elseif ($newRequest === 'Q' && $requestVal === '\&"') {
            $requestVal = '“';
        } elseif ($newRequest === 'U' && $requestVal === '\&"') {
            $requestVal = '”';
        }

        // Special case for ellipsis with \fS font:
        if ($newRequest === 'EL' && $requestVal === '\fS\N\'188\'\fP') {
            $requestVal = '…';
        }

        // See e.g. rcsfreeze.1 for a replacement including another previously defined replacement.
        $requestVal = $man->applyAllReplacements($requestVal);
        $requestVal = Macro::applyReplacements($requestVal, $macroArguments);

        $man->addString($newRequest, $requestVal);
    }

    public static function substitute(string $string): string
    {
        $replacements = Man::instance()->getStrings();

        // Want to match any of: \*. \*(.. \*[....]
        return Replace::pregCallback(
          '~(?J)(?<!\\\\)(?<bspairs>(?:\\\\\\\\)*)\\\\(?:\*\[(?<str>[^]\s]+)]|\*\((?<str>\S{2})|\*(?<str>\S))~u',
          function ($matches) use (&$replacements) {
              if (isset($replacements[$matches['str']])) {
                  return $matches['bspairs'] . $replacements[$matches['str']];
              } else {
                  return $matches['bspairs']; // Follow what groff does, if string isn't set use empty string.
              }
          },
          $string
        );
    }

}
