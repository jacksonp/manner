<?php

class Roff_EQ
{

    static function evaluate(array $request, array &$lines, int $i)
    {

        $numLines = count($lines);
        $foundEnd = false;

        $eqLines = [];
        for ($i = $i + 1; $i < $numLines; ++$i) {
            $line = $lines[$i];
            if (Request::is($line, 'EN')) {
                $foundEnd = true;
                break;
            } else {
                $eqLines[] = $line;
            }
        }

        if (!$foundEnd) {
            throw new Exception('EQ without EN.');
        }

        if (count($eqLines) !== 1) {
            throw new Exception('Unhandled EQ contents.');
        }

        if (preg_match('~^delim (.)(.)$~ui', $eqLines[0], $matches)) {
            $man                 = Man::instance();
            $man->eq_delim_left  = $matches[1];
            $man->eq_delim_right = $matches[2];
        } else {
            throw new Exception('Unhandled EQ line.');
        }

        return ['i' => $i];

    }

    static function appendMath(DOMElement $parentNode, string $line)
    {
        $eqnString = '.EQ' . PHP_EOL;
        $eqnString .= $line . PHP_EOL;
        $eqnString .= '.EN' . PHP_EOL;
        file_put_contents('/tmp/eqn', $eqnString);
        exec('eqn -T MathML /tmp/eqn', $output);
        // Now get rid of .EQ:q
        array_shift($output);
        // ,, and .EN:
        array_pop($output);

        $mathString = implode('', $output);

        // Hacks:
        $mathString = str_replace(['\\fI', '\\fP'], '', $mathString);

        $mathDoc = new DOMDocument;
        @$mathDoc->loadHTML($mathString);
        $mathNode = $mathDoc->getElementsByTagName('math')->item(0);
        $mathNode = $parentNode->ownerDocument->importNode($mathNode, true);
        $parentNode->appendChild($mathNode);

        return;
    }

}
