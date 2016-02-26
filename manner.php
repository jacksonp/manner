#!/usr/bin/env php
<?php

require_once 'Section.php';
require_once 'Text.php';

if (empty($argv[1])) {
    exit('no file.');
}

$filePath = $argv[1];

if (!is_file($filePath)) {
    exit($filePath . ' is not a file.');
}

$lines = file($filePath, FILE_IGNORE_NEW_LINES);

$sections         = [];
$sectionHeading   = null;
$foundNameSection = false;

$numLines = count($lines);

$dom = new DOMDocument();

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
        $sectionHeading = $matches[1];
        if (empty($sectionHeading)) {
            exit($line . ' - empty section heading.');
        }

        if (empty($sections)) {
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

        $sections[$sectionHeading] = [];
        continue;
    }

    // FAIL Got something and we're not in a section
    if (is_null($sectionHeading)) {
        exit($line . ' - not in a section.');
    }

    $sections[$sectionHeading][] = $line;

}


foreach ($sections as $heading => $sectionLines) {
    //$sections[$heading] = Text::toCommonMark($sectionLines);
}

foreach ($sections as $heading => $sectionLines) {
//    Section::handle($manPageContainer, 2, $heading, $sectionLines);
    $sections[$heading] = Text::mergeTextLines($sectionLines);
}

var_dump($sections); exit;

$html = $dom->saveHTML();


$tidy = tidy_parse_string($html, [
  'hide-comments'       => true,
  'tidy-mark'           => false,
  'indent'              => true,
  'indent-spaces'       => 2,
  'hide-endtags'        => true,
  'new-blocklevel-tags' => 'article,header,footer,section,nav',
  'new-inline-tags'     => 'video,audio,canvas,ruby,rt,rp',
  'new-empty-tags'      => 'source',
  'doctype'             => '<!DOCTYPE HTML>',
  'sort-attributes'     => 'alpha',
  'vertical-space'      => false,
  'output-xhtml'        => false,
  'output-html'         => true,
  'wrap'                => 160,
  'wrap-attributes'     => false,
  'break-before-br'     => false,
  'quote-nbsp'          => false,
  'anchor-as-name'      => false,
  'show-body-only'      => true,
], 'UTF8');

$tidy->cleanRepair();
echo $tidy;
