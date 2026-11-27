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

namespace Manner\Inline;

use Dom\HTMLDocument;
use Dom\Element;
use Dom\XPath;
use Exception;
use Manner\Block\Template;
use Manner\Block\Text;
use Manner\Man;
use Manner\Node;
use Manner\Request;

class EQ implements Template
{

    /**
     * @param Element $parentNode
     * @param array $lines
     * @param array $request
     * @param bool $needOneLineOnly
     * @return Element|null
     * @throws Exception
     */
    public static function checkAppend(
      Element $parentNode,
      array &$lines,
      array $request,
      bool $needOneLineOnly = false
    ): ?Element {
        array_shift($lines);

        $foundEnd = false;

        $eqLines = [];
        while ($request = Request::getLine($lines)) {
            array_shift($lines);
            if ($request['request'] === 'EN') {
                $foundEnd = true;
                break;
            } else {
                $eqLines[] = $request['raw_line'];
            }
        }

        if (!$foundEnd) {
            throw new Exception('EQ without EN.');
        }

        $man = Man::instance();

        foreach ($eqLines as $k => $eqLine) {
            if (preg_match('~^\s*delim\s+(.)(.)\s*$~ui', $eqLine, $matches)) {
                $man->eq_delim_left  = $matches[1];
                $man->eq_delim_right = $matches[2];
                unset($eqLines[$k]);
            } elseif (preg_match('~^\s*delim\s+off\s*$~ui', $eqLine)) {
                $man->eq_delim_left  = null;
                $man->eq_delim_right = null;
                unset($eqLines[$k]);
            }
        }

        if (count($eqLines) > 0) {
            Text::addSpace($parentNode);
            self::appendMath($parentNode, $eqLines);
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public static function appendMath(Element $parentNode, array $lines): void
    {
        $eqnString = '.EQ' . PHP_EOL;
        foreach ($lines as $line) {
            // Hack for mm2gv:
            $line      = str_replace(['\\fI', '\\fP'], '', $line);
            $eqnString .= $line . PHP_EOL;
        }
        $eqnString   .= '.EN' . PHP_EOL;
        $tmpFileName = tempnam('/tmp', 'eqn');
        file_put_contents($tmpFileName, $eqnString);
        exec('eqn -T MathML ' . $tmpFileName, $output, $returnVar);
        if ($returnVar !== 0) {
            throw new Exception('Failed to render .EQ content');
        }
        $mathLines = [];
        foreach ($output as $outLine) {
            if (!str_starts_with(mb_trim($outLine), '.')) {
                $mathLines[] = $outLine;
            }
        }

        $mathString = implode('', $mathLines);
        if (mb_trim($mathString) === '') {
            return;
        }

        # Hacks:
        $mathString = str_replace(['&ThinSpace;', '&equals;', '&plus;'], [' ', '=', '+'], $mathString);

        $mathDoc = HTMLDocument::createFromString('<!DOCTYPE html><html><body>' . $mathString . '</body></html>');
        $mathNode = $mathDoc->getElementsByTagName('math')->item(0);
        if (is_null($mathNode)) {
            return;
        }
//        $mathNode->setAttribute('xmlns', 'http://www.w3.org/1998/Math/MathML');
//        $mathNode->setAttribute('display', 'inline');
        $xpath   = new XPath($mathDoc);
        $xpath->registerNamespace('h', 'http://www.w3.org/1999/xhtml');
        $mErrors = $xpath->query('//h:merror');
        foreach ($mErrors as $mError) {
            Node::remove($mError, false);
        }

        $mathNode = $parentNode->ownerDocument->importNode($mathNode, true);
        $parentNode->appendChild($mathNode);
    }

}
