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

$errorLog = '/tmp/mannerrors.log';
if (!empty($arv[2])) {
    $errorLog = $arv[2];
}
//</editor-fold>

$rawLines = file($filePath, FILE_IGNORE_NEW_LINES);

$dom = new DOMDocument('1.0', 'utf-8');
$dom->registerNodeClass('DOMElement', 'HybridNode');
$xpath = new DOMXpath($dom);

/** @var HybridNode $manPageContainer */
$manPageContainer = $dom->createElement('body');
$manPageContainer = $dom->appendChild($manPageContainer);

$man = Man::instance();

try {

    $lines = Text::preprocessLines($rawLines);

    if (isset($man->title)) {
        $h1 = $dom->createElement('h1');
        $h1->appendChild(new DOMText($man->title));
        $manPageContainer->appendChild($h1);
    } else {
        throw new Exception('No $man->title.');
    }

    //<editor-fold desc="Handle NAME section, take it out of $lines">
    while (empty($nameHeadingLine) && count($lines) > 0) {
        $nameHeadingLine = array_shift($lines);
    }
    if (!preg_match('~^\.S[Hh] "?[Nn](AME|ame)"?~u', $nameHeadingLine)) {
        throw new Exception($nameHeadingLine . ' - expected NAME section.');
    }
    do {
        $nameSectionText = array_shift($lines);
    } while (mb_strlen($nameSectionText) === 0);

    $p = $dom->createElement('p');
    TextContent::interpretAndAppendCommand($p, $nameSectionText);
    $manPageContainer->appendChild($p);
    //</editor-fold>

    $manPageContainer->manLines = $lines;

    Section::handle($manPageContainer, 2);
} catch (Exception $e) {
    file_put_contents($errorLog, $e->getMessage() . ' (' . basename($filePath) . ')' . PHP_EOL, FILE_APPEND);
    echo 'Doc status:', PHP_EOL;
    echo $dom->saveHTML($manPageContainer);
    echo PHP_EOL, PHP_EOL, $e->getMessage(), PHP_EOL;
    echo $e->getTraceAsString(), PHP_EOL;
    exit(1);
}

$html = $dom->saveHTML();

echo '<!DOCTYPE html>',
'<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">',
'<meta name="man-page-info" data-date="', htmlspecialchars($man->date), '" data-package="', htmlspecialchars($man->package), '" data-section-name="', htmlspecialchars($man->section_name), '">',
'<title>', htmlspecialchars($man->title), '</title>',
$html;

//Debug::echoTidy($html);

