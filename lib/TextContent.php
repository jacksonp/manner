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

        $man = Man::instance();
        $dom = $parentNode->ownerDocument;

        if (preg_match('~^\.br~u', $line)) {
            $parentNode->appendChild($dom->createElement('br', $line));

            return;
        }

        if (preg_match('~^\.Nm$~u', $line)) {
            if (!isset($man->macro_Nm)) {
                throw new Exception($line . ' - found .Nm but $man->macro_Nm is not set');
            }
            // TODO: should only add br in synopsis, see https://www.mankier.com/7/groff_mdoc#Manual_Domain-Names
            $parentNode->appendChild($dom->createElement('br', $line));
            $parentNode->appendChild(new DOMText($man->macro_Nm));
            return;
        }

        if (preg_match('~^\.([RBI][RBI]?)(.*)$~u', $line, $matches)) {

            $command        = $matches[1];
            $stringToFormat = trim($matches[2]);
            if (empty($stringToFormat)) {
                throw new Exception($line . ' - UNHANDLED: if no text next input line should be bold/italic. See https://www.mankier.com/7/groff_man#Macros_to_Set_Fonts');
            }

            if (strlen($command) > 1) {
                $bits = str_getcsv($stringToFormat, ' ');
            } else {
                $bits = [trim($stringToFormat, '"')];
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
            echo 'Doc status:', PHP_EOL;
            Debug::echoTidy($dom->saveHTML());
            echo PHP_EOL, PHP_EOL;
            var_dump($parentNode->manLines);
            echo PHP_EOL, PHP_EOL;
            echo $line, ' - unexpected command.', PHP_EOL;
            exit(1);
        }

        TextContent::interpretAndAppendText($parentNode, $line, true);


    }

    static function interpretAndAppendText(HybridNode $parentNode, string $line, $addSpacing = false)
    {

        $dom = $parentNode->ownerDocument;

        // Get rid of this as no longer needed: "To begin a line with a control character without it being interpreted, precede it with \&. This represents a zero width space, which means it does not affect the output." (also remove tho if not at start of line)
        $line = preg_replace('~\\\\&~u', '', $line);

        if ($addSpacing) {
            // Do this after regex above
            $line = ' ' . $line;
        }

        $textSegments = preg_split('~(\\\\f[BRIP])~u', $line, null, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

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
                case '\fP':
                    // "Switch back to previous font." - groff(7)
                    // Assume back to normal text for now, so do nothing so next line passes thru to default.
                    break;
                case '\fR':
                    break;
                default:
                    $parentNode->appendChild(new DOMText($textSegments[$i]));
            }

        }

    }

}
