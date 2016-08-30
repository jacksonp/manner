<?php

class Block_TH
{

    static function checkAppend(DOMElement $parentNode, array $lines, int $i, array $arguments)
    {

        $man = Man::instance();

        if (empty($man->title)) {

            if (count($arguments) < 1) {
                throw new Exception($lines[$i] . ' - missing title info');
            }

            foreach ($arguments as $k => $v) {
                // See amor.6 for \FB \FR nonsense.
                $value = Replace::preg('~\\\\F[BR]~', '', $v);
                $value = $man->applyAllReplacements($value);
                $value = TextContent::interpretString($value);
                // Fix vnu's "Saw U+0000 in stream" e.g. in lvmsadc.8:
                $value         = trim($value);
                $arguments[$k] = $value;
            }

            $man->title = $arguments[0];
            if (count($arguments) > 1) {
                $man->section = $arguments[1];
                $man->extra1  = @$arguments[2] ?: '';
                $man->extra2  = @$arguments[3] ?: '';
                $man->extra3  = @$arguments[4] ?: '';
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
