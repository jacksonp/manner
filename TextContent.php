<?php


class TextContent
{

    static function interpretAndAppend(HybridNode $parentNode, string $line, $addSpacing = true)
    {

        $dom = $parentNode->ownerDocument;

        // Get rid of this as no longer needed: "To begin a line with a control character without it being interpreted, precede it with \&. This represents a zero width space, which means it does not affect the output."
        $line = preg_replace('~^\\\\&~u', '', $line);

        if ($addSpacing) {
            // Do this after regex above
            $line = ' ' . $line;
        }

        $textSegments = preg_split('~(\\\\f[BRI])~u', $line, null, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        $numTextSegments = count($textSegments);

        for ($i = 0; $i < count($textSegments); ++$i) {
            switch ($textSegments[$i]) {
                case '\fB':
                    if ($i < $numTextSegments - 1) {
                        $parentNode->appendChild($dom->createElement('strong', $textSegments[++$i]));
                    }
                    break;
                case '\fI':
                    if ($i < $numTextSegments - 1) {
                        $parentNode->appendChild($dom->createElement('em', $textSegments[++$i]));
                    }
                    break;
                case '\fR':
                    break;
                default:
                    $parentNode->appendChild(new DOMText($textSegments[$i]));
            }

        }


//        $fontHandler = function (HybridNode $nodeToAddTo, string $text) {


//            $fontCommandPos = strpos($text, '\\f');
//            if ($fontCommandPos === false) {
//                $nodeToAddTo->appendChild(new DOMText(' ' . $text));
//            }

//        };

//        $fontHandler($parentNode, $line);


    }

}
