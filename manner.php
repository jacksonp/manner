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

$lines = file($filePath, FILE_IGNORE_NEW_LINES);

/** @var HybridNode $lastSectionNode */
$lastSectionNode  = null;
$foundNameSection = false;

$numLines = count($lines);

$dom = new DOMDocument();
$dom->registerNodeClass('DOMElement', 'HybridNode');
$xpath = new DOMXpath($dom);

$manPageContainer = $dom->createElement('div');
$manPageContainer = $dom->appendChild($manPageContainer);

for ($i = 0; $i < $numLines; ++$i) {
    $line = $lines[$i];

    // Skip comments
    if (preg_match('~^\.\\\\"(\s|$)~', $line)) {
        continue;
    }

    // Handle the title details
    if (preg_match('~^\.TH (.*)$~', $line, $matches)) {
        $titleDetails = str_getcsv($matches[1], ' ');
        $manName      = $titleDetails[0];
        $manNum       = $titleDetails[1];
        $h1           = $dom->createElement('h1', $manName);
        $manPageContainer->appendChild($h1);

//        var_dump($titleDetails);
        continue;
    }

    // Start a section
    if (preg_match('~^\.SH (.*)$~', $line, $matches)) {
        $sectionHeading = Text::massage($matches[1]);
        if (empty($sectionHeading)) {
            exit($line . ' - empty section heading.');
        }

        if (is_null($lastSectionNode)) {
            if ($sectionHeading === 'NAME') {

                $nameText = Text::massage($lines[++$i]); // get next line

                $nameTextNode = $dom->createTextNode($nameText);
                $manPageContainer->appendChild($nameTextNode);

                // check line after that:
                if (!preg_match('~^\.SH (.*)$~', $lines[$i + 1])) {
                    exit($line . ' - expected section after one line of NAME section contents.');
                }
                $foundNameSection = true;
                continue;
            } elseif (!$foundNameSection) {
                exit($line . ' - expected NAME section.');
            }
        }

        var_dump($lastSectionNode->manLines);

        $lastSectionNode = $dom->createElement('div');
        $lastSectionNode->setAttribute('class', 'section');
        $lastSectionNode->appendChild($dom->createElement('h2', $sectionHeading));
        $lastSectionNode = $manPageContainer->appendChild($lastSectionNode);

        continue;
    }

    // FAIL Got something and we're not in a section
    if (is_null($lastSectionNode)) {
        exit($line . ' - not in a section.');
    }

    $lastSectionNode->addManLine($line);

}

foreach ($manPageContainer->childNodes as $node)
{
    if ($node->nodeType !== XML_TEXT_NODE) {
//        Debug::echoTidy($dom->saveHTML($node));
//        echo PHP_EOL;
//        var_dump($node->manLines);
    }
}


//$divs = $dom->getElementsByTagName('div');
//foreach ($divs as $div) {
//    var_dump($div->manLines);
//}

//$sections = $xpath->query('//div[@class="section"]');
//foreach ($sections as $section) {
//    var_dump($section->manLines);
//    Section::handle($xpath, $section, 3);
////    Debug::echoTidy($dom->saveHTML($section));
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

Debug::echoTidy($html);

