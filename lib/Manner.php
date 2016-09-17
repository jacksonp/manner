<?php


class Manner
{

    static function roffToDOM(array $fileLines, string $filePath):DOMDocument
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->registerNodeClass('DOMElement', 'HybridNode');

        /** @var HybridNode $manPageContainer */
        $manPageContainer = $dom->createElement('body');
        $manPageContainer = $dom->appendChild($manPageContainer);

        $man = Man::instance();
        $man->reset();

        $strippedLines = Preprocessor::strip($fileLines);
        Roff::parse($manPageContainer, $strippedLines);

        return $dom;
    }


    static function roffToHTML(array $fileLines, string $filePath, string $outputFile = null)
    {

        $dom  = self::roffToDOM($fileLines, $filePath);
        $html = $dom->saveHTML();

        $man = Man::instance();

        $manPageInfo = '<meta name="man-page-info" data-extra1="' . htmlspecialchars($man->extra1) . '" data-extra2="' . htmlspecialchars($man->extra2) . '" data-extra3="' . htmlspecialchars($man->extra3) . '">';

        if (is_null($outputFile)) {
            echo '<!DOCTYPE html>',
            '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">',
            $manPageInfo,
            '<title>', htmlspecialchars($man->title), '</title>',
            $html;
        } else {
            $fp = fopen($outputFile, 'w');
            fwrite($fp, '<!DOCTYPE html>');
            fwrite($fp, '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">');
            fwrite($fp, $manPageInfo);
            fwrite($fp, '<title>' . htmlspecialchars($man->title) . '</title>');
            fwrite($fp, $html);
            fclose($fp);
        }
    }

}
