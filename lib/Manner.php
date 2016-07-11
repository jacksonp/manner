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

        $linesNoComments = Text::stripComments($fileLines);
        $linesRoffed     = Text::applyRoffClasses($linesNoComments);
        Blocks::handle($manPageContainer, $linesRoffed);

        return $dom;
    }


    static function roffToHTML(array $fileLines, string $filePath, string $outputFile = null)
    {

        $dom  = self::roffToDOM($fileLines, $filePath);
        $html = $dom->saveHTML();

        $man = Man::instance();

        if (is_null($outputFile)) {
            echo '<!DOCTYPE html>',
            '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">',
            '<meta name="man-page-info" data-date="', htmlspecialchars($man->date), '" data-package="', htmlspecialchars($man->package), '" data-section-name="', htmlspecialchars($man->section_name), '">',
            '<title>', htmlspecialchars($man->title), '</title>',
            $html;
        } else {
            $fp = fopen($outputFile, 'w');
            fwrite($fp, '<!DOCTYPE html>');
            fwrite($fp, '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">');
            fwrite($fp,
              '<meta name="man-page-info" data-date="' . htmlspecialchars($man->date) . '" data-package="' . htmlspecialchars($man->package) . '" data-section-name="' . htmlspecialchars($man->section_name) . '">');
            fwrite($fp, '<title>' . htmlspecialchars($man->title) . '</title>');
            fwrite($fp, $html);
            fclose($fp);
        }
    }

}
