<?php

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
      $needOneLineOnly = false
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

    public static function appendMath(DOMElement $parentNode, array $lines)
    {
        $eqnString = '.EQ' . PHP_EOL;
        foreach ($lines as $line) {
            // Hack for mm2gv:
            $line      = str_replace(['\\fI', '\\fP'], '', $line);
            $eqnString .= $line . PHP_EOL;
        }
        $eqnString .= '.EN' . PHP_EOL;
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

        return;
    }

}
