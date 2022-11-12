<?php

declare(strict_types=1);

namespace Manner\Inline;

use DOMDocument;
use DOMElement;
use Exception;
use Manner\Block\Template;
use Manner\DOM;
use Manner\Man;
use Manner\Node;
use Manner\Request;
use Manner\Roff;
use Manner\Roff\Glyph;

class PS implements Template
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

    /**
     * @throws Exception
     */
    public static function appendPic(DOMElement $parentNode, array $lines)
    {
        // Aborted attempt to fix rcsfile.5 diagram
//        if (count($lines) > 0 && $lines[0] === '.nf') {
//            array_shift($lines);
//        }
//
//        $man = Man::instance();
//        for ($i = 0; $i < count($lines); ++$i) {
//            $lines[$i] = $man->applyAllReplacements($lines[$i]);
//        }

        $picString = implode(PHP_EOL, $lines);

        $tmpFileName    = tempnam('/tmp', 'pic-');
        $tmpSVGFileName = $tmpFileName . '.svg';

        file_put_contents($tmpFileName, '.PS' . PHP_EOL . $picString . PHP_EOL . '.PE' . PHP_EOL);

        exec(
          'pic2plot --font-size 0.0125 --bg-color none -T svg ' . $tmpFileName . ' > ' . $tmpSVGFileName,
          $output,
          $returnVar
        );

        if ($returnVar !== 0) {
            // See e.g. pt_astree.n
            $newLines = array_map(fn(string $s): string => str_replace('\\&', '', $s), $lines);

            Roff::parse($parentNode, $newLines);

            return;
        }

        exec(
          'inkscape --vacuum-defs --export-id=content --export-id-only --export-plain-svg --export-overwrite ' . $tmpSVGFileName,
          $output,
          $returnVar
        );
        if ($returnVar !== 0) {
            $pre = $parentNode->ownerDocument->createElement('pre');
            $pre->appendChild($parentNode->ownerDocument->createTextNode('[.PS MKERR2 - failed to render]'));
            $parentNode->appendChild($pre);

            return;
        }

        $svgDocString = file_get_contents($tmpSVGFileName);

        $svgDoc = new DOMDocument();
        @$svgDoc->loadXML($svgDocString);

        $svg = $svgDoc->getElementsByTagName('svg')->item(0);

        if (is_null($svg)) {
            $pre = $parentNode->ownerDocument->createElement('pre');
            $pre->appendChild($parentNode->ownerDocument->createTextNode('[.PS MKERR3 - failed to render]'));
            $parentNode->appendChild($pre);

            return;
        }

        /* @var DomElement $svgNode */
        $svgNode = $parentNode->ownerDocument->importNode($svg, true);

        while (
          $svgNode->firstChild &&
          (!DOM::isElementNode($svgNode->firstChild) || $svgNode->firstChild->getAttribute("id") !== "content")
        ) {
            $svgNode->removeChild($svgNode->firstChild);
        }

        Node::removeIds($svgNode);
        Node::replaceGlyphs($svgNode);

        $svgNode->removeAttribute('xmlns');
        $svgNode->removeAttribute('xmlns:svg');

        $textNodes = $svgNode->getElementsByTagName('text');
        foreach ($textNodes as $textNode) {
            $textNode->removeAttribute('font-family');
        }

        $div = $parentNode->ownerDocument->createElement('div');
        Node::addClass($div, 'svg-container');
        $div = $parentNode->appendChild($div);

        $div->appendChild($svgNode);
    }

}
