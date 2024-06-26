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

use DOMDocument;
use DOMElement;
use DOMXPath;
use Exception;
use Manner\Block\Template;
use Manner\Block\Text;
use Manner\Man;
use Manner\Node;
use Manner\Request;

class EQ implements Template
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

        if (count($eqLines) === 1) {
            if (preg_match('~^delim (.)(.)$~ui', $eqLines[0], $matches)) {
                $man->eq_delim_left  = $matches[1];
                $man->eq_delim_right = $matches[2];

                return null;
            }
        }

        foreach ($eqLines as $k => $eqLine) {
            if ($eqLine === 'delim off') {
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
    public static function appendMath(DOMElement $parentNode, array $lines): void
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
        // Now get rid of .EQ:
        array_shift($output);
        // ... and .EN:
        array_pop($output);

        $mathString = implode('', $output);

        # Hacks:
        $mathString = str_replace(['&ThinSpace;', '&equals;', '&plus;'], [' ', '=', '+'], $mathString);

        $mathDoc = new DOMDocument();
        @$mathDoc->loadHTML($mathString);
        $mathNode = $mathDoc->getElementsByTagName('math')->item(0);
//        $mathNode->setAttribute('xmlns', 'http://www.w3.org/1998/Math/MathML');
//        $mathNode->setAttribute('display', 'inline');
        $xpath   = new DOMXpath($mathDoc);
        $mErrors = $xpath->query('//merror');
        foreach ($mErrors as $mError) {
            Node::remove($mError, false);
        }

        $mathNode = $parentNode->ownerDocument->importNode($mathNode, true);
        $parentNode->appendChild($mathNode);
    }

}
