<?php


class Manner
{

    static function roffToHTML(array $fileLines, string $outputFile = null)
    {

        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->registerNodeClass('DOMElement', 'HybridNode');

        /** @var HybridNode $manPageContainer */
        $manPageContainer = $dom->createElement('body');
        $manPageContainer = $dom->appendChild($manPageContainer);

        $man = Man::instance();
        $man->reset();

        $lines = Text::preprocessLines($fileLines);

        if (isset($man->title)) {
            $h1 = $dom->createElement('h1');
            $h1->appendChild(new DOMText($man->title));
            $manPageContainer->appendChild($h1);
        } else {
            throw new Exception('No $man->title.');
        }

        Blocks::handle($manPageContainer, $lines);

        $html = $dom->saveHTML();

        $hacks = ['</strong><strong>' => '', '</em><em>' => ''];

        $html = strtr($html, $hacks);

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
