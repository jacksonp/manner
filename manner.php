#!/usr/bin/env php
<?php

//<editor-fold desc="Setup">
spl_autoload_register(function ($class) {
    require_once 'lib/' . str_replace('_', '/', $class) . '.php';
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
$manPageContainer = $dom->createElement('body');
$manPageContainer = $dom->appendChild($manPageContainer);

$lines = [];

$man = Man::instance();

//<editor-fold desc="Strip comments, handle title, stick rest in $lines">
for ($i = 0; $i < $numRawLines; ++$i) {
    $line = Text::preprocess($rawLines[$i]);

    if (preg_match('~^\.(if|ie|el)~u', $line, $matches)) {
        echo $line . ' - no support for ' . $matches[1], PHP_EOL;
        exit(1);
    }

    // Skip comments
    if (preg_match('~^[\'\.]\\\\"(\s|$)~u', $line)) {
        continue;
    }

    // Skip empty requests
    if ($line === '.') {
        continue;
    }

    //<editor-fold desc="Handle man title macro">
    if (preg_match('~^\.TH (.*)$~u', $line, $matches)) {
        $titleDetails = str_getcsv($matches[1], ' ');
        if (count($titleDetails) < 2) {
            echo $line . ' - missing title info';
            exit(1);
        }
        $man->title        = $titleDetails[0];
        $man->section      = $titleDetails[1];
        $man->date         = @$titleDetails[2] ?: '';
        $man->package      = @$titleDetails[3] ?: '';
        $man->section_name = @$titleDetails[4] ?: '';
        continue;
    }
    //</editor-fold>

    $lines[] = $line;

}

if (isset($man->title)) {
    $h1 = $dom->createElement('h1');
    $h1->appendChild(new DOMText($man->title));
    $manPageContainer->appendChild($h1);
} else {
    echo 'No $man->title.';
    exit(1);
}

//</editor-fold>

//<editor-fold desc="Handle NAME section, take it out of $lines">
$nameHeadingLine = array_shift($lines);
if (!preg_match('~^\.S[Hh] "?NAME"?~', $nameHeadingLine)) {
    echo($nameHeadingLine . ' - expected NAME section.');
    exit(1);
}

$nameSectionText = array_shift($lines);

if (preg_match('~^\.Nm (.*)~', $nameSectionText, $matches)) {
    $man->macro_Nm = $matches[1];
    $ndText        = array_shift($lines);
    if (preg_match('~^\.Nd (.*)~', $ndText, $matches)) {
        $p = $dom->createElement('p');
        $p->appendChild($matches[1]);
        $manPageContainer->appendChild($p);
    } else {
        echo($ndText . ' - expected .Nd.');
        exit(1);
    }
} else {
    $p = $dom->createElement('p');
    $p->appendChild(new DOMText($nameSectionText));
    $manPageContainer->appendChild($p);
}
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
'<meta name="man-page-info" data-date="', htmlspecialchars($man->date), '" data-package="', htmlspecialchars($man->package), '" data-section-name="', htmlspecialchars($man->section_name), '">',
'<title>', htmlspecialchars($man->title), '</title>',
$html;

//Debug::echoTidy($html);

