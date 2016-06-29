<?php


class TextContent
{

    private static $continuation = false;

    private static $canAddWhitespace = true;

    static function interpretAndAppendText(
      DOMElement $parentNode,
      string $line,
      $addSpacing = false,
      bool $replaceQuotes = null
    ) {

        $dom = $parentNode->ownerDocument;

        self::$canAddWhitespace = !self::$continuation;
        // See e.g. imgtool.1
        $line               = Replace::preg('~\\\\c$~', '', $line, -1, $replacements);
        self::$continuation = $replacements > 0;

        if (is_null($replaceQuotes)) {
            $replaceQuotes = !$parentNode->isOrInTag(['pre', 'code']);
        }

        $textSegments = preg_split(
          '~(?<!\\\\)(\\\\f(?:[^\(\[]|\(..|\[.*?\])?|\\\\[ud])~u',
          $line,
          null,
          PREG_SPLIT_DELIM_CAPTURE
        );

        $numTextSegments = count($textSegments);

        for ($i = 0; $i < $numTextSegments; ++$i) {
            if ($addSpacing) {
                $addSpacing = $i === 0;
            }
            switch ($textSegments[$i]) {
                case '\u':
                    if ($i < $numTextSegments - 1) {
                        if ($i < $numTextSegments - 2 and $textSegments[$i + 2] === '\d') {
                            $sup = $parentNode->appendChild($dom->createElement('sup'));
                            $sup->appendChild(new DOMText(
                              self::interpretString($textSegments[++$i], false, $replaceQuotes)));
                            ++$i;
                        } else {
                            throw new Exception('\u followed by unexpected pattern: ' . $line);
                        }
                    }
                    break;
                case '\d':
                    if ($i < $numTextSegments - 1) {
                        if ($i < $numTextSegments - 2 and $textSegments[$i + 2] === '\u') {
                            $sub = $parentNode->appendChild($dom->createElement('sub'));
                            $sub->appendChild(new DOMText(
                              self::interpretString($textSegments[++$i], false, $replaceQuotes)));
                            ++$i;
                        } else {
                            throw new Exception('\d followed by unexpected pattern: ' . $line);
                        }
                    }
                    break;
                case '\fB':
                case '\fb':
                case '\f[B]':
                case '\f3':
                    if ($i < $numTextSegments - 1) {
                        $domText = new DOMText(self::interpretString($textSegments[++$i], $addSpacing, $replaceQuotes));
                        if ($parentNode->isOrInTag('strong') || trim($textSegments[$i]) === '') {
                            $parentNode->appendChild($domText);
                        } else {
                            $strong = $parentNode->appendChild($dom->createElement('strong'));
                            $strong->appendChild($domText);
                        }
                    }
                    break;
                case '\fI':
                case '\fi':
                case '\f[I]':
                case '\f2':
                    if ($i < $numTextSegments - 1) {
                        $domText = new DOMText(self::interpretString($textSegments[++$i], $addSpacing, $replaceQuotes));
                        if ($parentNode->isOrInTag('em') or trim($textSegments[$i]) === '') {
                            $parentNode->appendChild($domText);
                        } else {
                            $em = $parentNode->appendChild($dom->createElement('em'));
                            $em->appendChild($domText);
                        }
                    }
                    break;
                case '\f4':
                case '\f(BI':
                case '\f[BI]':
                    if ($i < $numTextSegments - 1) {
                        $strong = $parentNode->appendChild($dom->createElement('strong'));
                        $em     = $strong->appendChild($dom->createElement('em'));
                        $em->appendChild(new DOMText(
                          self::interpretString($textSegments[++$i], $addSpacing, $replaceQuotes)));
                    }
                    break;
                case '\f':
                case '\fP':
                case '\fp':
                    // \fP: "Switch back to previous font." - groff(7)
                    // Assume back to normal text for now, so do nothing so next line passes thru to default.
                case '\fR':
                case '\fr':
                case '\f[]':
                case '\f1':
                    break;
                case '\fC':
                case '\fc':
                case '\fV':
                case '\fv':
                case '\f5':
                case '\f(CR':
                case '\f(CW':
                case '\f(CO':
                case '\f[C]':
                case '\f[CR]':
                case '\f[CW]':
                case '\f[CO]':
                    if ($i < $numTextSegments - 1) {
                        $domText = new DOMText(self::interpretString($textSegments[++$i], $addSpacing, false));
                        if ($parentNode->tagName === 'code' || trim($textSegments[$i]) === '') {
                            $parentNode->appendChild($domText);
                        } else {
                            $code = $dom->createElement('code');
                            $code->appendChild($domText);
                            $parentNode->appendChild($code);
                        }
                    }
                    break;
                case '\f(CWI':
                case '\f[CI]':
                case '\f(CI':
                    if ($i < $numTextSegments - 1) {
                        $code = $parentNode->appendChild($dom->createElement('code'));
                        $em   = $code->appendChild($dom->createElement('em'));
                        $em->appendChild(new DOMText(self::interpretString($textSegments[++$i], $addSpacing, false)));
                    }
                    break;
                case '\f(CWB':
                case '\f[CB]':
                case '\f(CB':
                    if ($i < $numTextSegments - 1) {
                        $code   = $parentNode->appendChild($dom->createElement('code'));
                        $strong = $code->appendChild($dom->createElement('strong'));
                        $strong->appendChild(new DOMText(self::interpretString($textSegments[++$i], $addSpacing,
                          false)));
                    }
                    break;
                case '\f[CBI]':
                    if ($i < $numTextSegments - 1) {
                        $code   = $parentNode->appendChild($dom->createElement('code'));
                        $strong = $code->appendChild($dom->createElement('strong'));
                        $em     = $strong->appendChild($dom->createElement('em'));
                        $em->appendChild(new DOMText(self::interpretString($textSegments[++$i], $addSpacing, false)));
                    }
                    break;
                case '\f[SM]':
                case '\f(SM':
                    if ($i < $numTextSegments - 1) {
                        $small = $parentNode->appendChild($dom->createElement('small'));
                        $small->appendChild(new DOMText(
                          self::interpretString($textSegments[++$i], $addSpacing, $replaceQuotes)));
                    }
                    break;
                default:
                    if (mb_substr($textSegments[$i], 0, 2) !== '\\f') {
                        $parentNode->appendChild(new DOMText(self::interpretString(
                          $textSegments[$i],
                          $addSpacing,
                          $replaceQuotes
                        )));
                    }
            }

        }

    }

    static function interpretString(string $string, bool $addSpacing = false, bool $replaceDoubleQuotes = true):string
    {

        // Get rid of this as no longer needed: "To begin a line with a control character without it being interpreted, precede it with \&. This represents a zero width space, which means it does not affect the output." (also remove tho if not at start of line)
        $string = Replace::preg('~\\\\&~u', '', $string);

        if (self::$canAddWhitespace and $addSpacing) {
            // Do this after regex above
            $string = ' ' . $string;
        }

        $lastMinuteStringSubs = [
          'rs' => '\\',
          'dq' => '"',
          'aq' => '\'',
        ];

        $string = Roff_String::substitute($string, $lastMinuteStringSubs);

        $replacements = [
            // "\e represents the current escape character." - let's hope it's always a backslash
          '\\e' => '\\',
            // 1/6 em narrow space glyph, e.g. enigma.6 synopsis. Just remove for now (but don't do this earlier to not break case where it's followed by a dot, e.g. npm-cache.1).
          '\\|' => '',
            // 1/12 em half-narrow space glyph; zero width in nroff. Just remove for now.
          '\\^' => '',
            // Default optional hyphenation character. Just remove for now.
          '\\%' => '',
            // Inserts a zero-width break point (similar to \% but without a soft hyphen character). Just remove for now.
          '\\:' => '',
            // Digit-width space.
          '\\0' => ' ',
        ];
        $string       = strtr($string, $replacements);


        // Prettier double quotes:
        $string = Replace::preg('~``(.*?)\'\'~u', '“$1”', $string);
        if ($replaceDoubleQuotes) {
            $string = Replace::preg('~"(.*?)"~u', '“$1”', $string);
        }

        $string = Replace::pregCallback('~\\\\N\'(\d+)\'~u', function ($matches) {
            return chr($matches[1]);
        }, $string);

        // Get rid of <> around URLs - these get translated to &lt; and &gt; and then cause problems with finding out what we can make into links.
        $string = Replace::preg(
          '~[<⟨](?:URL:\s?)?(?<url>(?:ftp|https?)://[^\s()<>⟨⟩]+(?:\([\w\d]+\)|(?:[^[:punct:]\s]|/)))\s?[>⟩]~u',
          '$1',
          $string);

        return $string;

    }

}
