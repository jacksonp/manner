<?php

class Block_TH
{

    static function checkAppend(DOMElement $parentNode, array $lines, int $i)
    {
        // NB: empty .TH also used in tbl tables
        if (!preg_match('~^\.\s*TH\s(.*)$~u', $lines[$i], $matches)) {
            return false;
        }

        $man = Man::instance();

        if (empty($man->title)) {

            $titleDetails = Macro::parseArgString($matches[1]);
            if (is_null($titleDetails) or count($titleDetails) < 1) {
                throw new Exception($lines[$i] . ' - missing title info');
            }

            foreach ($titleDetails as $k => $v) {
                $titleDetails[$k] = TextContent::interpretString($man->applyAllReplacements($v));
            }

            // Fix vnu's "Saw U+0000 in stream" e.g. in lvmsadc.8:
            $titleDetails = array_map('trim', $titleDetails);
            // See amor.6 for \FB \FR nonsense.
            $man->title = Replace::preg('~\\\\F[BR]~', '', $titleDetails[0]);
            if (count($titleDetails) > 1) {
                $man->section      = $titleDetails[1];
                $man->date         = @$titleDetails[2] ?: '';
                $man->package      = @$titleDetails[3] ?: '';
                $man->section_name = @$titleDetails[4] ?: '';
            }


            $h1 = $parentNode->ownerDocument->createElement('h1');
            $h1->appendChild(new DOMText($man->title));
            $parentNode->appendChild($h1);

        } else {
            // Some pages  have multiple .THs for different commands in one page, just had a horizontal line when we hit
            // .THs with content after the first
            $hr = $parentNode->ownerDocument->createElement('hr');
            $parentNode->appendChild($hr);
        }

        return $i;

    }

}
