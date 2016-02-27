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

$dom = new DOMDocument();
$dom->registerNodeClass('DOMElement', 'HybridNode');
$xpath = new DOMXpath($dom);

$manPageContainer = $dom->createElement('div');
$manPageContainer = $dom->appendChild($manPageContainer);

$lines = [];

//<editor-fold desc="Strip comments, handle title, stick rest in $lines">
for ($i = 0; $i < $numRawLines; ++$i) {
    $line = $rawLines[$i];

    // Skip comments
    if (preg_match('~^[\'\.]\\\\"(\s|$)~', $line)) {
        continue;
    }

    // Skip empty requests
    if ($line === '.') {
        continue;
    }

    // Handle the title details
    if (preg_match('~^\.TH (.*)$~', $line, $matches)) {
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

    $lines[] = $line;

}
//</editor-fold>

/** @var HybridNode[] $sectionNodes */
$sectionNodes     = [];
$foundNameSection = false;
$sectionNum       = 0;

$numLines = count($lines);

for ($i = 0; $i < $numLines; ++$i) {
    $line = $lines[$i];

    // Start a section
    if (preg_match('~^\.SH (.*)$~', $line, $matches)) {
        $sectionHeading = Text::massage($matches[1]);
        if (empty($sectionHeading)) {
            exit($line . ' - empty section heading.');
        }

        if (empty($sectionNodes)) {
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

        ++$sectionNum;
        $sectionNodes[$sectionNum] = $dom->createElement('div');
        $sectionNodes[$sectionNum]->setAttribute('class', 'section');
        $sectionNodes[$sectionNum]->appendChild($dom->createElement('h2', $sectionHeading));
        $sectionNodes[$sectionNum] = $manPageContainer->appendChild($sectionNodes[$sectionNum]);

        continue;
    }

    // FAIL Got something and we're not in a section
    if (empty($sectionNodes)) {
        exit($line . ' - not in a section.');
    }

    $sectionNodes[$sectionNum]->addManLine($line);

}

//foreach ($manPageContainer->childNodes as $node)
//{
//    if ($node->nodeType !== XML_TEXT_NODE) {
//        Debug::echoTidy($dom->saveHTML($node));
//        echo PHP_EOL;
//        var_dump($node->manLines);
//    }
//}


foreach ($sectionNodes as $section) {
//    var_dump($section->manLines);
    Section::handle($section, 3);
//    Debug::echoTidy($dom->saveHTML($section));
//    var_dump($section->manLines);
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

Debug::echoTidy($html);

