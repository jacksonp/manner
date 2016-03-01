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

$man = Man::instance();

//<editor-fold desc="Strip comments, handle title, stick rest in $lines">
for ($i = 0; $i < $numRawLines; ++$i) {
    $line = Text::preprocess($rawLines[$i]);

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
        $man->title   = $titleDetails[0];
        $man->section = $titleDetails[1];
        if (isset($titleDetails[2])) {
            $man->date = $titleDetails[2];
        }
        if (isset($titleDetails[3])) {
            $man->package = $titleDetails[3];
        }
        if (isset($titleDetails[4])) {
            $man->section_name = $titleDetails[4];
        }
        $h1 = $dom->createElement('h1', $man->title);
        $manPageContainer->appendChild($h1);
        continue;
    }

    //<editor-fold desc="mdoc title macros">
    if (preg_match('~^\.Dd (.*)$~u', $line, $matches)) {
        $manDate = $line;
        continue;
    }

    if (preg_match('~^\.Dt (.*)$~u', $line, $matches)) {
        $titleDetails = str_getcsv($matches[1], ' ');
        if (count($titleDetails) < 2) {
            exit($line . ' - missing title info');
        }
        $man->title   = $titleDetails[0];
        $man->section = $titleDetails[1];
        continue;
    }

    if (preg_match('~^\.Os$~u', $line)) {
        // Do nothing, I don't think we care about this
        continue;
    }
    //</editor-fold>

    $lines[] = $line;

}
//</editor-fold>

//<editor-fold desc="Handle NAME section, take it out of $lines">
$nameHeadingLine = array_shift($lines);
if (!preg_match('~\.S[Hh] "?NAME"?~', $nameHeadingLine)) {
    echo($nameHeadingLine . ' - expected NAME section.');
    exit(1);
}

$nameSectionText = array_shift($lines);
$nameTextNode    = $dom->createTextNode($nameSectionText);
$manPageContainer->appendChild($nameTextNode);
//</editor-fold>

$manPageContainer->manLines = $lines;

try {
    Section::handle($manPageContainer, 2);
} catch (Exception $e) {
    echo 'Doc status:', PHP_EOL;
    echo $dom->saveHTML($manPageContainer);
    echo PHP_EOL, PHP_EOL, $e->getMessage(), PHP_EOL;
    echo $e->getTraceAsString(), PHP_EOL;
    exit(1);
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
'<title>', htmlspecialchars($man->title), '</title>',
'<body>', // stop warning about implicit body in tidy
$html;

//Debug::echoTidy($html);

