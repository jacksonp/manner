<?php


class TextContent
{

    static $continuation = false;

    static function interpretAndAppendText(DOMElement $parentNode, string $line)
    {

        $dom = $parentNode->ownerDocument;
        $man = Man::instance();

        // See e.g. imgtool.1
        $line               = Replace::preg('~\\\\c\s*$~', '', $line, -1, $replacements);
        self::$continuation = $replacements > 0;

        $textSegmentsS = preg_split(
          '~(?<!\\\\)((?:\\\\\\\\)*)(\\\\[fF](?:[^\(\[]|\(..|\[.*?\])?|\\\\[ud]|\\\\k(?:[^\(\[]|\(..|\[.*?\]))~u',
          $line,
          null,
          PREG_SPLIT_DELIM_CAPTURE
        );

        $textSegments = [];
        for ($i = 0; $i < count($textSegmentsS); ++$i) {
            if ($textSegmentsS[$i] === '\\\\' and
              $i > 0 and
              count($textSegments) > 0 and
              mb_substr($textSegments[count($textSegments) - 1], 0, 1) !== '\\'
            ) {
                $textSegments[count($textSegments) - 1] .= '\\\\';
            } elseif ($textSegmentsS[$i] !== '') {
                $textSegments[] = $textSegmentsS[$i];
            }
        }

//        $textSegments = array_filter($textSegments, function ($s) {
//            return $s !== '';
//        });
//        $textSegments = array_values($textSegments);
//        var_dump($line);
//        var_dump($textSegments);

        $numTextSegments    = count($textSegments);
        $horizontalPosition = 0; //TODO: could be worth setting this to characters in output so far from $line?

        for ($i = 0; $i < $numTextSegments; ++$i) {
            if (mb_substr($textSegments[$i], 0, 2) === '\\k') {
                $registerName = mb_substr($textSegments[$i], 2);
                if (mb_strlen($registerName) === 1) {
                } elseif (mb_substr($registerName, 0, 1) === '(') {
                    $registerName = mb_substr($registerName, 1);
                } else {
                    $registerName = trim($registerName, '[]');
                }
                $man->setRegister($registerName, $horizontalPosition);
                continue;
            }

            if ($i < $numTextSegments - 1 and in_array(mb_substr($textSegments[$i], 0, 2),
                ['\f', '\F']) and in_array(mb_substr($textSegments[$i + 1], 0, 2), ['\f', '\F', '\d', '\u'])
            ) {
                continue;
            }

            switch ($textSegments[$i]) {
                case '\u':
                    if ($i < $numTextSegments - 1) {
                        if ($i < $numTextSegments - 2 and $textSegments[$i + 2] === '\d') {
                            $sup = $parentNode->appendChild($dom->createElement('sup'));
                            $sup->appendChild(new DOMText(
                              self::interpretString($textSegments[++$i], false)));
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
                              self::interpretString($textSegments[++$i], false)));
                            ++$i;
                        } else {
                            throw new Exception('\d followed by unexpected pattern: ' . $line);
                        }
                    }
                    break;
                case '\fB':
                case '\FB':
                case '\fb':
                case '\f[B]':
                case '\f3':
                    if ($i < $numTextSegments - 1) {
                        $domText = new DOMText(self::interpretString($textSegments[++$i]));
                        if ($parentNode->isOrInTag('strong') || trim($textSegments[$i]) === '') {
                            $parentNode->appendChild($domText);
                        } else {
                            $strong = $parentNode->appendChild($dom->createElement('strong'));
                            $strong->appendChild($domText);
                        }
                    }
                    break;
                case '\fI':
                case '\FI':
                case '\fi':
                case '\f[I]':
                case '\f2':
                    if ($i < $numTextSegments - 1) {
                        $domText = new DOMText(self::interpretString($textSegments[++$i]));
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
                          self::interpretString($textSegments[++$i])));
                    }
                    break;
                case '\f':
                case '\fP':
                case '\FP':
                case '\fp':
                    // \fP: "Switch back to previous font." - groff(7)
                    // Assume back to normal text for now, so do nothing so next line passes thru to default.
                case '\fR':
                case '\fr':
                case '\f[]':
                case '\FR':
                case '\F[R]':
                case '\f1':
                    break;
                case '\fC':
                case '\FC':
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
                        $domText = new DOMText(self::interpretString($textSegments[++$i]));
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
                        $em->appendChild(new DOMText(self::interpretString($textSegments[++$i])));
                    }
                    break;
                case '\f(CWB':
                case '\f[CB]':
                case '\f(CB':
                    if ($i < $numTextSegments - 1) {
                        $code   = $parentNode->appendChild($dom->createElement('code'));
                        $strong = $code->appendChild($dom->createElement('strong'));
                        $strong->appendChild(new DOMText(self::interpretString($textSegments[++$i])));
                    }
                    break;
                case '\f[CBI]':
                    if ($i < $numTextSegments - 1) {
                        $code   = $parentNode->appendChild($dom->createElement('code'));
                        $strong = $code->appendChild($dom->createElement('strong'));
                        $em     = $strong->appendChild($dom->createElement('em'));
                        $em->appendChild(new DOMText(self::interpretString($textSegments[++$i])));
                    }
                    break;
                case '\f[SM]':
                case '\f(SM':
                    if ($i < $numTextSegments - 1) {
                        $small = $parentNode->appendChild($dom->createElement('small'));
                        $small->appendChild(new DOMText(
                          self::interpretString($textSegments[++$i])));
                    }
                    break;
                default:
                    if (mb_substr($textSegments[$i], 0, 2) !== '\\f') {
                        $parentNode->appendChild(new DOMText(self::interpretString($textSegments[$i])));
                    }
            }

        }

    }

    static function interpretString(string $string, bool $applyCharTranslations = true):string
    {

        $man = Man::instance();

        $string = Replace::pregCallback('~(?<!\\\\)(?<bspairs>(?:\\\\\\\\)*)\\\\N\'(?<charnum>\d+)\'~u',
          function ($matches) {
              return $matches['bspairs'] . chr($matches['charnum']);
          }, $string);

        $roffStrings = $man->getStrings();
//        $string      = Roff_String::substitute($string, $roffStrings);

//        $string = Roff_Glyph::substitute($string);


        $string = Replace::pregCallback('~\\\\\[u([\dA-F]{4})\]~u', function ($matches) {
            return html_entity_decode('&#x' . $matches[1] . ';', ENT_COMPAT, 'UTF-8');
        }, $string);

        $string = Replace::pregCallback('~\\\\\[char(\d+)\]~u', function ($matches) {
            return mb_convert_encoding('&#' . intval($matches[1]) . ';', 'UTF-8', 'HTML-ENTITIES');
        }, $string);

        // NB: these substitutions have to happen at the same time, with no backtracking to look again at replaced chars.
        $singleCharacterEscapes = [
            // "\e represents the current escape character." - let's hope it's always a backslash
          'e'  => '\\',
            // 1/6 em narrow space glyph, e.g. enigma.6 synopsis. Just remove for now (but don't do this earlier to not
            // break case where it's followed by a dot, e.g. npm-cache.1).
          '|'  => '',
            // 1/12 em half-narrow space glyph; zero width in nroff. Just remove for now.
          '^'  => '',
            // Default optional hyphenation character. Just remove for now.
          '%'  => '',
            // Inserts a zero-width break point (similar to \% but without a soft hyphen character). Just remove for now.
          ':'  => '',
            // Digit-width space.
          '0'  => ' ',
            // "To begin a line with a control character without it being interpreted, precede it with \&.
            // This represents a zero width space, which means it does not affect the output."
            // (also remove tho if not at start of line.)
          '&'  => '',
            // variation on \&
          ')'  => '',
          '\\' => '\\',


            // \/ Increases the width of the preceding glyph so that the spacing between that glyph and the following glyph is correct if the following glyph is a roman glyph. groff(7)
          '/'  => '',
            // \, Modifies the spacing of the following glyph so that the spacing between that glyph and the preceding glyph is correct if the preceding glyph is a roman glyph. groff(7)
          ','  => '',
            // The same as a dot (‘.’).  Necessary in nested macro definitions so that ‘\\..’ expands to ‘..’.
          '.'  => '.',
          '\'' => '´',
            // The acute accent ´; same as \(aa.
          '´'  => '´',
            // The grave accent `; same as \(ga.
          '`'  => '`',
          '-'  => '-',
            // The same as \(ul, the underline character.
          '_'  => '_',
          't'  => "\t",
            // Unpaddable space size space glyph (no line break). See enigma.6:
          ' '  => mb_convert_encoding(chr(160), 'UTF-8', 'HTML-ENTITIES'),
            // Unbreakable space that stretches like a normal inter-word space when a line is adjusted
          '~'  => mb_convert_encoding(chr(160), 'UTF-8', 'HTML-ENTITIES'),

        ];

        $string = Replace::pregCallback(
          '~(?J)(?<!\\\\)(?<bspairs>(?:\\\\\\\\)*)\\\\(\[(?<glyph>[^\]\s]+)\]|\((?<glyph>[^\s]{2})|\*\[(?<string>[^\]\s]+)\]|\*\((?<string>[^\s]{2})|\*(?<string>[^\s])|(?<char>.))~u',
          function ($matches) use (&$singleCharacterEscapes, &$roffStrings) {
              // \\ "reduces to a single backslash" - Do this first as strtr() doesn't search replaced text for further replacements.
              $prefix = str_repeat('\\', mb_strlen($matches['bspairs']) / 2);
              if ($matches['glyph'] !== '') {
                  if (isset(Roff_Glyph::ALL_GLYPHS[$matches['glyph']])) {
                      return $prefix . Roff_Glyph::ALL_GLYPHS[$matches['glyph']];
                  } else {
                      return $prefix; // Follow what groff does, if string isn't set use empty string.
                  }
              } elseif ($matches['string'] !== '') {
                  if (isset($roffStrings[$matches['string']])) {
                      return $prefix . $roffStrings[$matches['string']];
                  } else {
                      return $prefix; // Follow what groff does, if string isn't set use empty string.
                  }
              } else {
                  if (isset($singleCharacterEscapes[$matches['char']])) {
                      return $prefix . $singleCharacterEscapes[$matches['char']];
                  } else {
                      // If a backslash is followed by a character that does not constitute a defined escape sequence,
                      // the backslash is silently ignored and the character maps to itself.
                      return $prefix . $matches['char'];
                  }
              }
          },
          $string);

        if ($applyCharTranslations) {
            $string = $man->applyCharTranslations($string);
        }

        // Prettier double quotes:
        $string = Replace::preg('~``(.*?)\'\'~u', '“$1”', $string);

        return $string;

    }

}
