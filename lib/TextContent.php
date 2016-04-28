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

        if (mb_strlen($line) === 0 || preg_match('~^\.(ad|fi)~u', $line)) {
            return;
        }

        self::$canAddWhitespace = !self::$continuation;
        // See e.g. imgtool.1
        $line               = preg_replace('~\\\\c$~', '', $line, -1, $replacements);
        self::$continuation = $replacements > 0;


        if (preg_match('~^\.(?:([RBI][RBI]?)|ft ([RBI]))\s(?<text>.*)$~u', $line, $matches)) {

            // See why (?J) setting with named <command> didn't work sometime instead of this:
            $command = $matches[1] ?: $matches[2];

            $bits = Macro::parseArgString($matches['text']);
            if (is_null($bits)) {
                throw new Exception($line . ' - UNHANDLED: if no text next input line should be bold/italic. See https://www.mankier.com/7/groff_man#Macros_to_Set_Fonts');
            }

            // Detect references to other man pages:
            // TODO: maybe punt this to mankier? also get \fB \fR ones.
            if ($command === 'BR'
              && preg_match('~^(?<name>[-+0-9a-zA-Z_:\.]+) \((?<num>[\dn]p?)\)(?<punc>\S*)(?<rol>.*)~u',
                trim($matches['text']), $matches)
            ) {
                $parentNode->appendChild(new DOMText(' '));
                $anchor = $dom->createElement('a');
                $anchor->appendChild(new DOMText($matches['name'] . '(' . $matches['num'] . ')'));
                $anchor->setAttribute('href', '/' . $matches['num'] . '/' . $matches['name']);
                $anchor->setAttribute('class', 'link-man');
                $parentNode->appendChild($anchor);
                if (mb_strlen($matches['punc']) !== 0) {
                    self::interpretAndAppendText($parentNode, $matches['punc']);
                }
                if (mb_strlen($matches['rol']) !== 0) {
                    // get the 2nd bit of e.g. ".BR getcap (8), setcap (8)"
                    self::interpretAndAppendCommand($parentNode, '.BR' . $matches['rol']);
                }

                return;
            }

            if (mb_strlen($command) === 1) {
                $bits = [implode(' ', $bits)];
            }

            foreach ($bits as $bi => $bit) {
                $commandCharIndex = $bi % 2;
                if (!isset($command[$commandCharIndex])) {
                    throw new Exception($line . ' command ' . $command . ' has nothing at index ' . $commandCharIndex);
                }
                if (trim($bit) === '') {
                    TextContent::interpretAndAppendText($parentNode, $bit, $bi === 0);
                    continue;
                }
                switch ($command[$commandCharIndex]) {
                    case 'R':
                        TextContent::interpretAndAppendText($parentNode, $bit, $bi === 0);
                        break;
                    case 'B':
                        $strongNode = $dom->createElement('strong');
                        TextContent::interpretAndAppendText($strongNode, $bit, $bi === 0);
                        if ($strongNode->hasContent()) {
                            $parentNode->appendChild($strongNode);
                        }
                        break;
                    case 'I':
                        $emNode = $dom->createElement('em');
                        TextContent::interpretAndAppendText($emNode, $bit, $bi === 0);
                        if ($emNode->hasContent()) {
                            $parentNode->appendChild($emNode);
                        }
                        break;
                    default:
                        throw new Exception($line . ' command ' . $command . ' unexpected character at index ' . $commandCharIndex);
                }
            }


            return;
        }

        // FAIL on unknown command
        if (mb_strlen($line) > 0 && in_array($line[0], ['.', "'"])) {
            throw new Exception($line . ' unexpected command in interpretAndAppendCommand().');
        }

        TextContent::interpretAndAppendText($parentNode, $line, true);


    }

    static function interpretAndAppendText(HybridNode $parentNode, string $line, $addSpacing = false)
    {

        $dom = $parentNode->ownerDocument;

        $textSegments = preg_split('~(\\\\f(?:[1-4BRIPCV]|\(CW[IB]?|\[[ICB]?\])|\\\\[ud])~u', $line, null,
          PREG_SPLIT_DELIM_CAPTURE);

        $numTextSegments = count($textSegments);

        for ($i = 0; $i < $numTextSegments; ++$i) {
            if ($addSpacing) {
                $addSpacing = $i === 0;
            }
            switch ($textSegments[$i]) {
                case '\u':
                    if ($i < $numTextSegments - 1) {
                        $sup = $dom->createElement('sup');
                        $sup->appendChild(new DOMText(self::interpretString($textSegments[++$i], $addSpacing)));
                        $parentNode->appendChild($sup);
                    }
                    break;
                case '\d':
                    break;
                case '\fB':
                case '\f[B]':
                case '\f3':
                    if ($i < $numTextSegments - 1) {
                        if ($parentNode->isOrInTag('strong') || trim($textSegments[$i + 1]) === '') {
                            $parentNode->appendChild(new DOMText(self::interpretString($textSegments[++$i],
                              $addSpacing)));
                        } else {
                            $strong = $dom->createElement('strong');
                            $strong->appendChild(new DOMText(self::interpretString($textSegments[++$i], $addSpacing)));
                            $parentNode->appendChild($strong);
                        }
                    }
                    break;
                case '\fI':
                case '\f[I]':
                case '\f2':
                    if ($i < $numTextSegments - 1) {
                        if ($parentNode->isOrInTag('em') || trim($textSegments[$i + 1]) === '') {
                            $parentNode->appendChild(new DOMText(self::interpretString($textSegments[++$i],
                              $addSpacing)));
                        } else {
                            $em = $dom->createElement('em');
                            $em->appendChild(new DOMText(self::interpretString($textSegments[++$i], $addSpacing)));
                            $parentNode->appendChild($em);
                        }
                    }
                    break;
                case '\f4':
                    if ($i < $numTextSegments - 1) {
                        $strong = $dom->createElement('strong');
                        $em     = $dom->createElement('em');
                        $em->appendChild(new DOMText(self::interpretString($textSegments[++$i], $addSpacing)));
                        $strong->appendChild($em);
                        $parentNode->appendChild($strong);
                    }
                    break;
                case '\fP':
                    // \fP: "Switch back to previous font." - groff(7)
                    // Assume back to normal text for now, so do nothing so next line passes thru to default.
                case '\fR':
                case '\f[]':
                case '\f1':
                    break;
                case '\fC':
                case '\fV':
                case '\f(CW':
                case '\f[C]':
                    if ($i < $numTextSegments - 1) {
                        if ($parentNode->tagName === 'code' || trim($textSegments[$i + 1]) === '') {
                            $parentNode->appendChild(new DOMText(self::interpretString($textSegments[++$i],
                              $addSpacing)));
                        } else {
                            $code = $dom->createElement('code');
                            $code->appendChild(new DOMText(self::interpretString($textSegments[++$i], $addSpacing,
                              false)));
                            $parentNode->appendChild($code);
                        }
                    }
                    break;
                case '\f(CWI':
                    if ($i < $numTextSegments - 1) {
                        $code = $dom->createElement('code');
                        $em   = $dom->createElement('em');
                        $code->appendChild($em);
                        $em->appendChild(new DOMText(self::interpretString($textSegments[++$i], $addSpacing, false)));
                        $parentNode->appendChild($code);
                    }
                    break;
                case '\f(CWB':
                    if ($i < $numTextSegments - 1) {
                        $code   = $dom->createElement('code');
                        $strong = $dom->createElement('strong');
                        $code->appendChild($strong);
                        $strong->appendChild(new DOMText(self::interpretString($textSegments[++$i], $addSpacing,
                          false)));
                        $parentNode->appendChild($code);
                    }
                    break;
                default:
                    $parentNode->appendChild(new DOMText(self::interpretString($textSegments[$i], $addSpacing,
                      !$parentNode->isOrInTag(['pre', 'code']))));
            }

        }

    }

    static function interpretString(string $string, bool $addSpacing = false, bool $replaceDoubleQuotes = true):string
    {

        // Get rid of this as no longer needed: "To begin a line with a control character without it being interpreted, precede it with \&. This represents a zero width space, which means it does not affect the output." (also remove tho if not at start of line)
        $string = preg_replace('~\\\\&~u', '', $string);

        if (self::$canAddWhitespace && $addSpacing) {
            // Do this after regex above
            $string = ' ' . $string;
        }

        $replacements = [
            // "\e represents the current escape character." - let's hope it's always a backslash
          '\\e'   => '\\',
          '\(rs'  => '\\',
          '\*(rs' => '\\',
          '\[rs]' => '\\',
            // If we do this earlier and it's on a line on its own, it would then erroneously be detected as a command:
          '\\.'   => '.',
            // Do double quotes here: if we do them earlier it messes up cases like in aide.1: .IP "--before=\(dq\fBconfigparameters\fR\(dq , -B \(dq\fBconfigparameters\fR\(dq"
          '\(dq'  => '"',
          '\*(dq' => '"',
          '\[dq]' => '"',
            // Do single quotes here: otherwise we hit problems earlier on if they are the first character on the line.
          '\(aq'  => '\'',
          '\*(aq' => '\'',
          '\[aq]' => '\'',
        ];
        $string       = strtr($string, $replacements);

        // Prettier double quotes:
        $string = preg_replace('~``(.*?)\'\'~', '“$1”', $string);
        if ($replaceDoubleQuotes) {
            $string = preg_replace('~"(.*?)"~', '“$1”', $string);
        }

        // Get rid of <> around URLs - these get translater to &lt; and &gt; and then cause problems with finding out what we can make into links.
        $string = preg_replace(
          '~<(?:URL:)?(?<url>(?:ftp|https?)://[^\s()<>]+(?:\([\w\d]+\)|(?:[^[:punct:]\s]|/)))>~u',
          '$1',
          $string);

        return $string;

    }

}
