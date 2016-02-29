<?php


class TextContent
{

    /**
     * Interpret a line inside a block - could be a macro or text.
     *
     * @param HybridNode $parentNode
     * @param string $line
     * @throws Exception
     */
    static function interpretAndAppendCommand(HybridNode $parentNode, string $line)
    {

        $dom = $parentNode->ownerDocument;

        if (strlen($line) === 0) {
            return; // Discard for now, maybe useful as a hint later on?
        }

        if (preg_match('~^\.br~u', $line)) {
            $parentNode->appendChild($dom->createElement('br', $line));

            return;
        }

        if (preg_match('~^\.([RBI][RBI]?) ?(.*)$~u', $line, $matches)) {

            $command = $matches[1];
            if (empty($matches[2])) {
                throw new Exception($line . ' - UNHANDLED: if no text next input line should be bold/italic. See https://www.mankier.com/7/groff_man#Macros_to_Set_Fonts');
            }

            if (strlen($command) > 1) {
                $bits = str_getcsv($matches[2], ' ');
            } else {
                $bits = [trim($matches[2], '"')];
            }
            foreach ($bits as $bi => $bit) {
                $commandCharIndex = $bi % 2;
                if (!isset($command[$commandCharIndex])) {
                    throw new Exception($line . ' command ' . $command . ' has nothing at index ' . $commandCharIndex);
                }
                switch ($command[$commandCharIndex]) {
                    case 'R':
                        TextContent::interpretAndAppendText($parentNode, $bit, $bi === 0);
                        break;
                    case 'B':
                        $strongNode = $parentNode->appendChild($dom->createElement('strong'));
                        TextContent::interpretAndAppendText($strongNode, $bit, $bi === 0);
                        break;
                    case 'I':
                        $emNode = $parentNode->appendChild($dom->createElement('em'));
                        TextContent::interpretAndAppendText($emNode, $bit, $bi === 0);
                        break;
                    default:
                        throw new Exception($line . ' command ' . $command . ' unexpected character at index ' . $commandCharIndex);
                }
            }


            return;
        }


        // FAIL on unknown command
        if (in_array($line[0], ['.', "'"])) {
            echo 'Blocks status:', PHP_EOL;
            Debug::echoTidy($dom->saveHTML($parentNode));
            echo PHP_EOL, PHP_EOL;
            var_dump($parentNode->manLines);
            echo PHP_EOL, PHP_EOL;
            echo $line, ' - unknown command.', PHP_EOL;
            exit(1);
        }

        TextContent::interpretAndAppendText($parentNode, $line, true);


    }

    static function interpretAndAppendText(HybridNode $parentNode, string $line, $addSpacing = false)
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

    }

}
