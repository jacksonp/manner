<?php

declare(strict_types=1);

namespace Manner;

use DOMDocument;
use DOMXPath;
use Exception;

class Manner
{

    /**
     * @param array $fileLines
     * @return DOMDocument
     * @throws Exception
     */
    public static function roffToDOM(array $fileLines): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'utf-8');

        $manPageContainer = $dom->createElement('body');
        $manPageContainer = $dom->appendChild($manPageContainer);

        $man = Man::instance();
        $man->reset();

        $strippedLines = Preprocessor::strip($fileLines);
        Roff::parse($manPageContainer, $strippedLines);
        $xpath = new DOMXpath($dom);
        Massage\Body::trimNodesBeforeH1($xpath);
        Massage\P::removeEmpty($xpath);
        Massage\DL::mergeAdjacentAndConvertLoneDD($xpath);
        Massage\Remap::doAll($xpath);
        Massage\Indents::recalc($xpath);
        DOM::massage($manPageContainer);
        Massage\Tidy::doAll($xpath);
        Massage\DL::checkPrecedingNodes($xpath);
        Massage\Tidy::indentAttributeToClass($xpath);
        Massage\Block::coalesceAdjacentChildren($xpath);
        Massage\DL::CreateOLs($xpath);

        return $dom;
    }


    /**
     * @param array $fileLines
     * @param string|null $outputFile
     * @param bool $test
     * @throws Exception
     */
    public static function roffToHTML(array $fileLines, string $outputFile = null, $test = false)
    {
        $dom  = self::roffToDOM($fileLines);
        $html = $dom->saveHTML();

        $man = Man::instance();

        // Remove \& chars aka zero width space.
        $html   = str_replace(Text::ZERO_WIDTH_SPACE_HTML, '', $html);
        $title  = Text::trimAndRemoveZWSUTF8($man->title);
        $extra1 = Text::trimAndRemoveZWSUTF8($man->extra1);
        $extra2 = Text::trimAndRemoveZWSUTF8($man->extra2);
        $extra3 = Text::trimAndRemoveZWSUTF8($man->extra3);

        if (!$title) {
            $title = 'UNTITLED';
        }

        if ($test) {
            echo $html;

            return;
        }

        $manPageInfo = '<meta name="man-page-info" data-extra1="' . htmlspecialchars(
            $extra1
          ) . '" data-extra2="' . htmlspecialchars($extra2) . '" data-extra3="' . htmlspecialchars($extra3) . '">';

        if (is_null($outputFile)) {
            echo '<!DOCTYPE html>',
            '<meta charset="utf-8">',
            $manPageInfo,
            '<title>', htmlspecialchars($title), '</title>',
            $html;
        } else {
            $fp = fopen($outputFile, 'w');
            fwrite($fp, '<!DOCTYPE html>');
            fwrite($fp, '<meta charset="utf-8">');
            fwrite($fp, $manPageInfo);
            fwrite($fp, '<title>' . htmlspecialchars($title) . '</title>');
            fwrite($fp, $html);
            fclose($fp);
        }
    }

}
