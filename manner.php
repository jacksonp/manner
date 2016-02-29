#!/usr/bin/env php
<?php

//<editor-fold desc="Setup">
spl_autoload_register(function ($class) {
    require_once str_replace('_', '/', $class) . '.php';
});

if (empty($argv[1])) {
    exit('no file.');
}

$filePath = $argv[1];

if (!is_file($filePath)) {
    exit($filePath . ' is not a file.');
}
//</editor-fold>

$rawLines = file($filePath, FILE_IGNORE_NEW_LINES);

$numRawLines = count($rawLines);

$dom = new DOMDocument('1.0', 'utf-8');
$dom->registerNodeClass('DOMElement', 'HybridNode');
$xpath = new DOMXpath($dom);

/** @var HybridNode $manPageContainer */
$manPageContainer = $dom->createElement('div');
$manPageContainer = $dom->appendChild($manPageContainer);

$lines = [];

//<editor-fold desc="Strip comments, handle title, stick rest in $lines">
for ($i = 0; $i < $numRawLines; ++$i) {
    $line = $rawLines[$i];

    // Skip comments
    if (preg_match('~^[\'\.]\\\\"(\s|$)~u', $line)) {
        continue;
    }

    // Skip empty requests
    if ($line === '.') {
        continue;
    }

    // Handle the title details
    if (preg_match('~^\.TH (.*)$~u', $line, $matches)) {
        $titleDetails = str_getcsv($matches[1], ' ');
        if (count($titleDetails) < 2) {
            exit($line . ' - missing title info');
        }
        $manName = $titleDetails[0];
        $manNum  = $titleDetails[1];
        $h1      = $dom->createElement('h1', $manName);
        $manPageContainer->appendChild($h1);

//        var_dump($titleDetails);
        continue;
    }

    $lines[] = Text::preprocess($line);

}
//</editor-fold>

//<editor-fold desc="Handle NAME section, take it out of $lines">
$nameHeadingLine = array_shift($lines);
if ($nameHeadingLine !== '.SH NAME') {
    exit($nameHeadingLine . ' - expected NAME section.');
}

$nameSectionText = Text::massage(array_shift($lines));
$nameTextNode    = $dom->createTextNode($nameSectionText);
$manPageContainer->appendChild($nameTextNode);
//</editor-fold>

$manPageContainer->manLines = $lines;

try {
    Section::handle($manPageContainer, 2);
} catch (Exception $e) {
    echo 'Doc status:', PHP_EOL;
    Debug::echoTidy($dom->saveHTML($manPageContainer));
    echo PHP_EOL, PHP_EOL, $e->getMessage();
    echo $e->getTraceAsString(), PHP_EOL;
    exit;
}

//$sections = $xpath->query('//div[@class="subsection"]');
//foreach ($sections as $section) {
//    var_dump($section->manLines);
//}

//$divs = $dom->getElementsByTagName('div');
//foreach ($divs as $div) {
//    var_dump($div->manLines);
//}


//exit;

//foreach ($sections as $heading => $sectionLines) {
//    //$sections[$heading] = Text::toCommonMark($sectionLines);
//}
//
//foreach ($sections as $heading => $sectionLines) {
//    Section::handle($manPageContainer, 2, $heading, $sectionLines);
////    $sections[$heading] = Text::mergeTextLines($sectionLines);
//}
//
//var_dump($sections);
//exit;

$html = $dom->saveHTML();

echo '<!DOCTYPE html>',
'<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">',
'<title>', htmlspecialchars($manName), '</title>',
$html;

//Debug::echoTidy($html);

