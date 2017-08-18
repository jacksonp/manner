<?php
declare(strict_types=1);

class Manner
{

    static function roffToDOM(array $fileLines, string $filePath): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'utf-8');

        /** @var DOMElement $manPageContainer */
        $manPageContainer = $dom->createElement('body');
        $manPageContainer = $dom->appendChild($manPageContainer);

        $man = Man::instance();
        $man->reset();

        $strippedLines = Preprocessor::strip($fileLines);
        Roff::parse($manPageContainer, $strippedLines);
        $xpath = new DOMXpath($dom);
        Massage_P::removeEmpty($xpath);
        Massage_DL::mergeAdjacent($xpath);
        Massage_Remap::doAll($xpath);
        Massage_Indents::recalc($xpath);
        DOM::massage($manPageContainer);
        Massage_Tidy::doAll($xpath);
        Massage_DL::checkPrecedingNodes($xpath);
        Massage_Tidy::indentAttributeToClass($xpath);

        return $dom;
    }


    static function roffToHTML(array $fileLines, string $filePath, string $outputFile = null, $test = false)
    {

//        set_time_limit(3);

        $dom  = self::roffToDOM($fileLines, $filePath);
        $html = $dom->saveHTML();

        $man = Man::instance();

        // Remove \& chars aka zero width space.
        $html   = str_replace(Char::ZERO_WIDTH_SPACE_HTML, '', $html);
        $title  = str_replace(Char::ZERO_WIDTH_SPACE_UTF8, '', $man->title);
        $extra1 = str_replace(Char::ZERO_WIDTH_SPACE_UTF8, '', $man->extra1);
        $extra2 = str_replace(Char::ZERO_WIDTH_SPACE_UTF8, '', $man->extra2);
        $extra3 = str_replace(Char::ZERO_WIDTH_SPACE_UTF8, '', $man->extra3);

        if ($test) {
            echo $html;
            return;
        }

        $manPageInfo = '<meta name="man-page-info" data-extra1="' . htmlspecialchars($extra1) . '" data-extra2="' . htmlspecialchars($extra2) . '" data-extra3="' . htmlspecialchars($extra3) . '">';

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
