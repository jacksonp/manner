<?php

class Inline_EQ implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
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
            list ($textParent, $shouldAppend) = Blocks::getTextParent($parentNode);
            if ($textParent->hasContent()) {
                $textParent->appendChild(new DOMText(' '));
            }
            self::appendMath($textParent, $eqLines);
            if ($shouldAppend) {
                $parentNode->appendBlockIfHasContent($textParent);
            }
        }

        return null;

    }

    static function appendMath(DOMElement $parentNode, array $lines)
    {

        $eqnString = '.EQ' . PHP_EOL;
        foreach ($lines as $line) {
            // Hack for mm2gv:
            $line = str_replace(['\\fI', '\\fP'], '', $line);
            $eqnString .= $line . PHP_EOL;
        }
        $eqnString .= '.EN' . PHP_EOL;
        file_put_contents('/tmp/eqn', $eqnString);
        exec('eqn -T MathML /tmp/eqn', $output);
        // Now get rid of .EQ:
        array_shift($output);
        // ... and .EN:
        array_pop($output);

        $mathString = implode('', $output);

        # Hacks:
        $mathString = str_replace(['&ThinSpace;', '&equals;', '&plus;'], [' ', '=', '+'], $mathString);

        $mathDoc = new DOMDocument;
        @$mathDoc->loadHTML($mathString);
        $mathNode = $mathDoc->getElementsByTagName('math')->item(0);
//        $mathNode->setAttribute('xmlns', 'http://www.w3.org/1998/Math/MathML');
//        $mathNode->setAttribute('display', 'inline');

        $mathNode = $parentNode->ownerDocument->importNode($mathNode, true);
        $parentNode->appendChild($mathNode);

        return;
    }

}
