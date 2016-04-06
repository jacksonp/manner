<?php


class TextContent
{

    private static $continuation = false;

    private static $canAddWhitespace = true;

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

        if (mb_strlen($line) === 0) {
            return;
        }

        if (preg_match('~^\.IP ?(.*)$~u', $line, $matches)) {
            throw new Exception($line . ' - Unexpected .IP in interpretAndAppendCommand()');
        }

        self::$canAddWhitespace = !self::$continuation;
        // See e.g. imgtool.1
        $line               = preg_replace('~\\\\c$~', '', $line, -1, $replacements);
        self::$continuation = $replacements > 0;

        if (preg_match('~^\.br~u', $line)) {
            if ($parentNode->hasChildNodes()) {
                // Only bother if this isn't the first node.
                $parentNode->appendChild($dom->createElement('br'));
            }

            return;
        }

        if (preg_match('~^\.sp~u', $line)) {
            if ($parentNode->hasChildNodes()) {
                // Only bother if this isn't the first node.
                $parentNode->appendChild($dom->createElement('br'));
                $parentNode->appendChild($dom->createElement('br'));
            }

            return;
        }

        if (preg_match('~^\.([RBI][RBI]?)(.*)$~u', $line, $matches)) {

            $command        = $matches[1];
            $stringToFormat = trim($matches[2]);
            if (mb_strlen($stringToFormat) === 0) {
                throw new Exception($line . ' - UNHANDLED: if no text next input line should be bold/italic. See https://www.mankier.com/7/groff_man#Macros_to_Set_Fonts');
            }

            // Detect references to other man pages:
            // TODO: maybe punt this to mankier? also get \fB \fR ones.
            if ($command === 'BR'
              && preg_match('~^(?<name>[-+0-9a-zA-Z_:\.]+) \((?<num>[\dn]p?)\)(?<punc>\S*)(?<rol>.*)~u',
                $stringToFormat, $matches)
            ) {
                $parentNode->appendChild(new DOMText(' '));
                $anchor = $dom->createElement('a');
                $anchor->appendChild(new DOMText($matches['name'] . '(' . $matches['num'] . ')'));
                $anchor->setAttribute('href', '/' . $matches['num'] . '/' . $matches['name']);
                $anchor->setAttribute('class', 'link-man');
                $parentNode->appendChild($anchor);
                if (mb_strlen($matches['punc']) !== 0) {
                    $parentNode->appendChild(new DOMText($matches['punc']));
                }
                if (mb_strlen($matches['rol']) !== 0) {
                    // get the 2nd bit of e.g. ".BR getcap (8), setcap (8)"
                    self::interpretAndAppendCommand($parentNode, '.BR' . $matches['rol']);
                }

                return;
            }

            $bits = str_getcsv($stringToFormat, ' ');
            if (mb_strlen($command) === 1) {
                $bits = [implode(' ', $bits)];
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
        if (mb_strlen($line) > 0 && in_array($line[0], ['.', "'"])) {
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

        if (self::$canAddWhitespace && $addSpacing) {
            // Do this after regex above
            $line = ' ' . $line;
        }

        $textSegments = preg_split('~(\\\\f(?:[1-4BRIPC]|\(CW[IB]?|\[[ICB]?\])|\\\\[ud])~u', $line, null,
          PREG_SPLIT_DELIM_CAPTURE);

        $numTextSegments = count($textSegments);

        for ($i = 0; $i < count($textSegments); ++$i) {
            switch ($textSegments[$i]) {
                case '\u':
                    if ($i < $numTextSegments - 1) {
                        $sup = $dom->createElement('sup');
                        self::interpretAndAppendString($sup, $textSegments[++$i]);
                        $parentNode->appendChild($sup);
                    }
                    break;
                case '\d':
                    break;
                case '\fB':
                case '\f[B]':
                case '\f3':
                    if ($i < $numTextSegments - 1) {
                        $strong = $dom->createElement('strong');
                        self::interpretAndAppendString($strong, $textSegments[++$i]);
                        $parentNode->appendChild($strong);
                    }
                    break;
                case '\fI':
                case '\f[I]':
                case '\f2':
                    if ($i < $numTextSegments - 1) {
                        $em = $dom->createElement('em');
                        self::interpretAndAppendString($em, $textSegments[++$i]);
                        $parentNode->appendChild($em);
                    }
                    break;
                case '\f4':
                    if ($i < $numTextSegments - 1) {
                        $strong = $dom->createElement('strong');
                        $em = $dom->createElement('em');
                        self::interpretAndAppendString($em, $textSegments[++$i]);
                        $strong->appendChild($em);
                        $parentNode->appendChild($strong);
                    }
                    break;
                case '\fP':
                    // "Switch back to previous font." - groff(7)
                    // Assume back to normal text for now, so do nothing so next line passes thru to default.
                    break;
                case '\fR':
                case '\f[]':
                case '\f1':
                    break;
                case '\fC':
                case '\f(CW':
                    if ($i < $numTextSegments - 1) {
                        $code = $dom->createElement('code');
                        self::interpretAndAppendString($code, $textSegments[++$i]);
                        $parentNode->appendChild($code);
                    }
                    break;
                case '\f(CWI':
                case '\f[C]':
                    if ($i < $numTextSegments - 1) {
                        $code = $dom->createElement('code');
                        $em   = $dom->createElement('em');
                        $code->appendChild($em);
                        self::interpretAndAppendString($em, $textSegments[++$i]);
                        $parentNode->appendChild($code);
                    }
                    break;
                case '\f(CWB':
                    if ($i < $numTextSegments - 1) {
                        $code   = $dom->createElement('code');
                        $strong = $dom->createElement('strong');
                        $code->appendChild($strong);
                        self::interpretAndAppendString($strong, $textSegments[++$i]);
                        $parentNode->appendChild($code);
                    }
                    break;
                default:
                    self::interpretAndAppendString($parentNode, $textSegments[$i]);
            }

        }

    }

    static function interpretAndAppendString(HybridNode $parentNode, string $string)
    {

        $dom = $parentNode->ownerDocument;

        $replacements = [
            // "\e represents the current escape character." - let's hope it's always a backslash
          '\\e'   => '\\',
            // Do double quotes here: if we do them earlier it messes up cases like in aide.1: .IP "--before=\(dq\fBconfigparameters\fR\(dq , -B \(dq\fBconfigparameters\fR\(dq"
          '\(dq'  => '"',
          '\*(dq' => '"',
          '\[dq]' => '"',
        ];
        $string = strtr($string, $replacements);

        // Prettier double quotes:
        $string = preg_replace('~``(.*?)\'\'~', '“$1”', $string);

        if (preg_match(
          '~^(?<start>.*?)<?(?<url>(ftp|https?)://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))>?(?<end>.*)$~',
          $string, $matches)) {

            if (!empty($matches['start'])) {
                self::interpretAndAppendString($parentNode, $matches['start']);
            }

            $anchor = $dom->createElement('a');
            $anchor->appendChild(new DOMText($matches['url']));
            $anchor->setAttribute('href', $matches['url']);
            $parentNode->appendChild($anchor);

            if (!empty($matches['end'])) {
                self::interpretAndAppendString($parentNode, $matches['end']);
            }

            return;
        }

        $parentNode->appendChild(new DOMText($string));

    }

}
