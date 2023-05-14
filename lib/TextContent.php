<?php

declare(strict_types=1);

namespace Manner;

use DOMElement;
use DOMText;
use Manner\Inline\EQ;
use Manner\Roff\Glyph;

class TextContent
{

    public static bool $interruptTextProcessing = false;

    public static function interpretAndAppendText(DOMElement $parentNode, string $line)
    {
        $dom = $parentNode->ownerDocument;
        $man = Man::instance();

        $line       = $man->applyAllReplacements($line);
        $lineLength = mb_strlen($line);

        if (!is_null($man->eq_delim_left) && !is_null($man->eq_delim_right)) {
            if (preg_match(
              '~^(.*?)' .
              preg_quote($man->eq_delim_left, '~') .
              '(.+?)' .
              preg_quote($man->eq_delim_right, '~') .
              '(.*)$~',
              $line,
              $matches
            )
            ) {
                if (mb_strlen($matches[1]) > 0) {
                    self::interpretAndAppendText($parentNode, $matches[1]);
                }
                EQ::appendMath($parentNode, [$matches[2]]);
                if (mb_strlen($matches[3]) > 0) {
                    self::interpretAndAppendText($parentNode, $matches[3]);
                }

                return;
            }
        }

        if (preg_match_all('~(?<!\\\\)(?:\\\\\\\\)*\\\\([du])~u', $line, $matches, PREG_OFFSET_CAPTURE)) {
            // If count is 1: just a stray... catch it later.
            if (count($matches[1]) !== 1) {
                $lastLetterPosition = 0;
                $nextLetterPosition = 0;
                for ($i = 0; $i < count($matches[1]); $i += 2) {
                    $letter = $matches[1][$i][0];
                    // http://stackoverflow.com/a/1725329 for the next line:
                    $letterPosition = mb_strlen(substr($line, 0, (int)$matches[1][$i][1]));

                    $nextLetter         = $matches[1][$i + 1][0];
                    $nextLetterPosition = mb_strlen(substr($line, 0, (int)$matches[1][$i + 1][1]));

                    if ($letter === $nextLetter) {
                        // Stick first letter into substring, recurse to carry on processing next letter
                        self::interpretAndAppendText($parentNode, mb_substr($line, 0, $letterPosition));
                        self::interpretAndAppendText($parentNode, mb_substr($line, $letterPosition));

                        return;
                    }

                    if ($letterPosition > $lastLetterPosition + 1) {
                        self::interpretAndAppendText(
                          $parentNode,
                          mb_substr($line, $lastLetterPosition, $letterPosition - $lastLetterPosition - 1)
                        );
                    }

                    /* @var DomElement $newChildNode */
                    if ($letter === 'u' && $nextLetter === 'd') {
                        $newChildNode = $parentNode->appendChild($dom->createElement('sup'));
                    } elseif ($letter === 'd' && $nextLetter === 'u') {
                        $newChildNode = $parentNode->appendChild($dom->createElement('sub'));
                    }
                    self::interpretAndAppendText(
                      $newChildNode,
                      mb_substr($line, $letterPosition + 1, $nextLetterPosition - $letterPosition - 2)
                    );

                    $lastLetterPosition = $nextLetterPosition + 1;
                }

                if ($nextLetterPosition < $lineLength - 1) {
                    self::interpretAndAppendText($parentNode, mb_substr($line, $nextLetterPosition + 1));
                }

                return;
            }
        }

        if (!Node::isOrInTag($parentNode, 'pre')) {
            // Preserve spaces used for indentation, e.g. autogsdoc.1 (2nd char in replacement is nbsp):
            $line = Replace::preg('~ {2}~', " \xC2\xA0", $line);
        }

        // See e.g. imgtool.1
        $line                          = Replace::preg('~\\\\c\s*$~', '', $line, -1, $replacements);
        self::$interruptTextProcessing = $replacements > 0;

        $textSegmentsS = preg_split(
          '~(?<!\\\\)((?:\\\\\\\\)*)(\\\\[fF](?:[^(\[]|\(..|\[.*?])?|\\\\[ud]|\\\\k(?:[^(\[]|\(..|\[.*?]))~u',
          $line,
          -1,
          PREG_SPLIT_DELIM_CAPTURE
        );

        $textSegments = [];
        for ($i = 0; $i < count($textSegmentsS); ++$i) {
            if ($textSegmentsS[$i] === '\\\\' &&
              $i > 0 &&
              count($textSegments) > 0 &&
              mb_substr($textSegments[count($textSegments) - 1], 0, 1) !== '\\'
            ) {
                $textSegments[count($textSegments) - 1] .= '\\\\';
            } elseif ($textSegmentsS[$i] !== '') {
                $textSegments[] = $textSegmentsS[$i];
            }
        }

        $numTextSegments    = count($textSegments);
        $horizontalPosition = 0; //TODO: could be worth setting this to characters in output so far from $line?

        for ($i = 0; $i < $numTextSegments; ++$i) {
            if (mb_substr($textSegments[$i], 0, 2) === '\\k') {
                $registerName = mb_substr($textSegments[$i], 2);
                if (mb_strlen($registerName) !== 1) {
                    if (mb_substr($registerName, 0, 1) === '(') {
                        $registerName = mb_substr($registerName, 1);
                    } else {
                        $registerName = trim($registerName, '[]');
                    }
                }
                $man->setRegister($registerName, (string)$horizontalPosition);
                continue;
            }

            if ($textSegments[$i] === '\u' || $textSegments[$i] === '\d') {
                // Do nothing - just drop the stray \u or \d - case where they're sensibly combined is handled earlier.
                continue;
            }


            if (
            preg_match(
              '~(?J)^\\\\(?:f\[(?<font>[^]\s]*)]|f\((?<font>\S{2})|f(?<font>\S))$~ui',
              $textSegments[$i],
              $matches
            )
            ) {
                $font = strtoupper($matches['font']);
                switch ($font) {
                    case '':
                    case 'P':
                        // \fP, \f[], \F[] : Switch back to previous font (or font family).
                        $man->popFont();
                        break;
                    default:
                        $man->pushFont($font);
                }

                continue;
            }

            if (mb_substr($textSegments[$i], 0, 2) !== '\\f') {
                self::appendTextChild($parentNode, $textSegments[$i]);
            }
        }
    }

    private static function getTagsForFont(?string $font): array
    {
        $tags = [];
        switch ($font) {
            case 'AR':
            case 'R':
            case '0':
            case '1':
                // Do nothing
                break;
            case 'I':
            case 'AI':
            case '2':
            case '7':
                $tags = ['em'];
                break;
            case 'B':
            case '3':
            case '8':
                $tags = ['strong'];
                break;
            case 'BI':
            case '4':
                $tags = ['strong', 'em'];
                break;
            case 'C':
            case 'CR':
            case 'CW':
            case 'CO':
            case 'CS':
            case 'V':
            case 'tt':
            case '5':
                $tags = ['code'];
                break;
            case 'CI':
            case 'CWI':
                $tags = ['code', 'em'];
                break;
            case 'CB':
            case 'CWB':
                $tags = ['code', 'strong'];
                break;
            case 'CBI':
                $tags = ['code', 'strong', 'em'];
                break;
            case 'SM':
                $tags = ['small'];
                break;
            case 'SB':
                $tags = ['strong', 'small'];
                break;
            default:
                // Do nothing
        }

        return $tags;
    }

    private static function appendTextChild(DOMElement $parentNode, string $textContent)
    {
        if (!in_array(trim($textContent), ['', '\\&'])) {
            $man   = Man::instance();
            $fonts = $man->getFonts();
            $tags  = self::getTagsForFont(array_pop($fonts));
            if (count($tags) === 1 && current($tags) === 'small') {
                $tags = array_merge($tags, self::getTagsForFont(array_pop($fonts)));
            } elseif ($man->isFontSmall()) {
                array_unshift($tags, 'small');
            }

            foreach ($tags as $tag) {
                if (is_null(Node::ancestor($parentNode, $tag))) {
                    $parentNode = $parentNode->appendChild($parentNode->ownerDocument->createElement($tag));
                }
            }
        }
        $parentNode->appendChild(new DOMText(self::interpretString($textContent)));
    }

    public static function interpretString(?string $string, bool $applyCharTranslations = true): string
    {
        if (is_null($string)) {
            return '';
        }

        $man = Man::instance();

        if (!is_null($man->escape_char)) {
            $string = Replace::pregCallback(
              '~(?<!\\\\)(?<bspairs>(?:\\\\\\\\)*)\\\\N\'(?<charnum>\d+)\'~u',
              function ($matches) {
                  return $matches['bspairs'] . chr((int)$matches['charnum']);
              },
              $string
            );

            $roffStrings = $man->getStrings();

            $string = Replace::pregCallback(
              '~\\\\\[u([\dA-F]{4})]~u',
              function ($matches) {
                  return html_entity_decode('&#x' . $matches[1] . ';', ENT_COMPAT, 'UTF-8');
              },
              $string
            );

            $string = Replace::pregCallback(
              '~\\\\\[char(\d+)]~u',
              function ($matches) {
                  return mb_convert_encoding('&#' . intval($matches[1]) . ';', 'UTF-8', 'HTML-ENTITIES');
              },
              $string
            );

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
                // This also is used in practice after a .TP to have indented text without a visible term in front.
              '&'  => Text::ZERO_WIDTH_SPACE_UTF8,
                // variation on \&
              ')'  => '',
              '\\' => '\\',

                // stray block ends (e.g. pmieconf.1):
              '}'  => '',
              '{'  => '',

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
              '~(?J)(?<!\\\\)(?<bspairs>(?:\\\\\\\\)*)\\\\(\[(?<glyph>[^]\s]+)]|\((?<glyph>\S{2})|C\'(?<glyph>[^\']+)\'|\*\[(?<string>[^]\s]+)]|\*\((?<string>\S{2})|\*(?<string>\S)|(?<char>.))~u',
              function ($matches) use (&$singleCharacterEscapes, &$roffStrings) {
                  // \\ "reduces to a single backslash" - Do this first as strtr() doesn't search replaced text for further replacements.
                  $prefix = str_repeat('\\', mb_strlen($matches['bspairs']) / 2);
                  if ($matches['glyph'] !== '') {
                      if (array_key_exists($matches['glyph'], Glyph::ALL_GLYPHS)) {
                          return $prefix . Glyph::ALL_GLYPHS[$matches['glyph']];
                      } else {
                          return $prefix; // Follow what groff does, if string isn't set use empty string.
                      }
                  } elseif ($matches['string'] !== '') {
                      if (isset($roffStrings[$matches['string']])) {
                          return $prefix . $roffStrings[$matches['string']];
                      } else {
                          return $prefix; // Follow what groff does, if string isn't set use empty string.
                      }
                  } elseif (isset($singleCharacterEscapes[$matches['char']])) {
                      return $prefix . $singleCharacterEscapes[$matches['char']];
                  } else {
                      // If a backslash is followed by a character that does not constitute a defined escape sequence,
                      // the backslash is silently ignored and the character maps to itself.
                      return $prefix . $matches['char'];
                  }
              },
              $string
            );
        }

        if ($applyCharTranslations) {
            $string = $man->applyCharTranslations($string);
        }

        return $string;
    }

}
