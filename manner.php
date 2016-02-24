#!/usr/bin/env php
<?php

if (empty($argv[1])) {
    exit('no file.');
}

$filePath = $argv[1];

if (!is_file($filePath)) {
    exit($filePath . ' is not a file.');
}

$lines = file($filePath, FILE_IGNORE_NEW_LINES);

$sections = [];
$sectionHeading = null;

foreach ($lines as $i => $line) {

    // Skip comments
    if (preg_match('~^\.\\\\" ~', $line)) {
        continue;
    }

    // Handle the title details
    if (preg_match('~^\.TH (.*)$~', $line, $matches)) {
        $titleDetails = str_getcsv($matches[1], ' ');
//        var_dump($titleDetails);
        continue;
    }

    // Start a section
    if (preg_match('~^\.SH (.*)$~', $line, $matches)) {
        $sectionHeading = $matches[1];
        if (empty($sectionHeading)) {
            exit($line . ' - empty section heading.');
        }
        $sections[$sectionHeading] = [];
        continue;
    }

    // FAIL Got something and we're not in a section
    if (is_null($sectionHeading)) {
        exit($line . ' - not in a section.');
    }

    $sections[$sectionHeading][] = $line;

    // FAIL on unknown command
//    if (preg_match('~^\.~', $line, $matches)) {
//        exit($line . ' (' . $i . ')' . "\n");
//    }

//    echo $i, ' - ', $line, "\n";
}

var_dump($sections);
