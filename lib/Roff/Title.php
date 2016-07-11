<?php


class Roff_Title
{

    static function checkEvaluate(array $lines, int $i)
    {

        $man = Man::instance();
        if (!empty($man->title) or !preg_match('~^\.TH\s(.*)$~u', $lines[$i], $matches)) {
            return false;
        }

        $titleDetails = Macro::parseArgString($matches[1]);
        if (is_null($titleDetails) or count($titleDetails) < 1) {
            throw new Exception($lines[$i] . ' - missing title info');
        }

        foreach ($titleDetails as $k => $v) {
            $titleDetails[$k] = TextContent::interpretString($man->applyAllReplacements(Text::translateCharacters($v)));
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

        return ['i' => $i];

    }


}
