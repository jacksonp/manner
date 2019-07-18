<?php
declare(strict_types=1);

class Inline_PS implements Block_Template
{

    /**
     * @param DOMElement $parentNode
     * @param array $lines
     * @param array $request
     * @param bool $needOneLineOnly
     * @return DOMElement|null
     * @throws Exception
     */
    static function checkAppend(
      DOMElement $parentNode,
      array &$lines,
      array $request,
      $needOneLineOnly = false
    ): ?DOMElement {

        array_shift($lines);

        $foundEnd = false;

        $picLines = [];
        while ($request = Request::getLine($lines)) {
            if ($request['request'] === 'TP') {
                // Pretend the .PS never happened, e.g. pear.1:
                for ($i = count($picLines) - 1; $i >= 0; --$i) {
                    array_unshift($lines, $picLines[$i]);
                }

                return $parentNode;
            }

            array_shift($lines);

            if ($request['request'] === 'PE') {
                $foundEnd = true;
                break;
            } else {
                $picLines[] = $request['raw_line'];
            }
        }

        if (!$foundEnd) {
            throw new Exception('PS without PE or TS.');
        }

        if ($parentNode->tagName === 'p') {
            $parentNode = $parentNode->parentNode;
        }

        if (count($picLines) > 0) {
            self::appendPic($parentNode, $picLines);
        }

        return $parentNode;

    }

    static function appendPic(DOMElement $parentNode, array $lines)
    {
        $picString = implode(PHP_EOL, $lines);

        file_put_contents('/tmp/pic', '.PS' . PHP_EOL . $picString . PHP_EOL . '.PE' . PHP_EOL);
        exec('dpic -z -v /tmp/pic 2>/dev/null', $output);

        $svgDocString = implode(PHP_EOL, $output);

        if (count($output) === 0) {
            $pre = $parentNode->ownerDocument->createElement('pre');
            $pre->appendChild($parentNode->ownerDocument->createTextNode($picString));
            $parentNode->appendChild($pre);

            return;
        }

        $svgDoc = new DOMDocument;
        @$svgDoc->loadXML($svgDocString);

        $svg = $svgDoc->getElementsByTagName('svg')->item(0);

        if (is_null($svg)) {
            // svg output could be invalid,
            // e.g. <text font-size="18.181818pt" stroke-width="0.266667" fill="black" x="668.266667" y="57.066667">\[>=]0x600</text>
            // in ovs-fields.7
            return;
        }

        // Hack for rcsfile.5
        if (preg_match('~\\\\h\'3\.812i\'~u', $svg->textContent)) {
            return;
        }

        $svgNode = $parentNode->ownerDocument->importNode($svg, true);

        $svgNode->setAttribute('font-size', '7.5pt');
        $svgNode->removeAttribute('xmlns');
        $svgNode->removeAttribute('xmlns:xlink');
        $svgNode->removeAttribute('xml:space');
        $textNodes = $svgNode->getElementsByTagName('text');
        foreach ($textNodes as $textNode) {
            $textNode->removeAttribute('font-size');
        }

        /* @var DomElement $div */
        $div = $parentNode->ownerDocument->createElement('div');
        Node::addClass($div, 'svg-container');
        $div = $parentNode->appendChild($div);

        $div->appendChild($svgNode);

        return;
    }

}
