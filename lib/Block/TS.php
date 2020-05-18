<?php

declare(strict_types=1);

class Block_TS implements Block_Template
{

    private static array $rowFormatKeyCharacters = [
      'A',
      'C',
      'L',
      'N',
      'R',
      'S',
      '^',
      '_',
      '-',
      '=',
      '|',
    ];

    private static function getFormatChar(string $line, int $i): string
    {
        $char      = mb_substr($line, $i, 1);
        $upperChar = mb_strtoupper($char);

        return in_array($upperChar, self::$rowFormatKeyCharacters) ? $upperChar : $char;
    }

    private static function parseRowFormat(string $line): array
    {
        $line       = ltrim($line);
        $formats    = [];
        $format     = self::getFormatChar($line, 0);
        $lineLength = mb_strlen($line);
        for ($i = 1; $i < $lineLength; ++$i) {
            $char = self::getFormatChar($line, $i);
            if (in_array($char, self::$rowFormatKeyCharacters)) {
                $formats[] = trim($format);
                $format    = '';
            }

            if (in_array($char, ['w', 'W'])) {
                // We currently skip - but what we should do:
                // Minimum column width value. Must be followed either by a troff(1) width expression in parentheses or
                // a unitless integer. If no unit is given, en units are used. Also used as the default line length for
                // included text blocks. If used multiple times to specify the width for a particular column, the last
                // entry takes effect.
                $charAfterW = self::getFormatChar($line, ++$i);
                if ($charAfterW === '(') {
                    $opening = 1;
                    while ($i < $lineLength) {
                        $nextChar = self::getFormatChar($line, ++$i);
                        if ($nextChar === '(') {
                            ++$opening;
                        } elseif ($nextChar === ')') {
                            --$opening;
                            if ($opening === 0) {
                                continue 2;
                            }
                        }
                    }
                }
                while (preg_match('~\d~', self::getFormatChar($line, $i + 1))) {
                    ++$i;
                }
                continue;
            }

            $format .= $char;
            if (in_array($char, ['f', 'F'])) {
                // Either of these specifiers may be followed by a font name (either one or two characters long), font
                // number (a single digit), or long name in parentheses (the last form is a GNU tbl extension). A
                // one-letter font name must be separated by one or more blanks from whatever follows.
                $charAfterF = self::getFormatChar($line, ++$i);
                if (preg_match('~\d~', $charAfterF)) {
                    $format .= $charAfterF;
                } elseif (preg_match('~[A-Z]~', $charAfterF)) {
                    $charAfterAfterF = self::getFormatChar($line, ++$i);
                    $format          .= $charAfterF . $charAfterAfterF;
                } elseif ($charAfterF === '(') {
                    $format .= $charAfterF;
                    while ($i < $lineLength) {
                        $nextChar = self::getFormatChar($line, ++$i);
                        $format   .= $nextChar;
                        if ($nextChar === ')') {
                            break;
                        }
                    }
                }
            }
        }
        $formats[] = trim($format);

        return $formats;
    }

    /**
     * @param array $lines
     * @return array|null
     * @throws Exception
     */
    private static function parseRowFormats(array &$lines): ?array
    {
        $rowFormats  = [];
        $formatsDone = false;
        $man         = Man::instance();
        while (count($lines)) {
            $request = Request::getLine($lines);
            array_shift($lines);
            if ($request['request'] === 'TE') {
                return null; // Format is garbage, skip content.
            }
            if (strpos($request['raw_line'], ',') !== false) {
                $newLines = explode(',', $request['raw_line']);
                for ($i = count($newLines) - 1; $i >= 0; --$i) {
                    array_unshift($lines, trim($newLines[$i]));
                }
                continue;
            }
            $line = $man->applyAllReplacements($request['raw_line']);
            if (mb_substr(trim($line), -1, 1) === '.') {
                $line        = rtrim($line, '.');
                $formatsDone = true;
            }
            $rowFormats[] = self::parseRowFormat($line);
            if ($formatsDone) {
                break;
            }
        }

        return $rowFormats;
    }

    /**
     * @param DOMElement $table
     * @param array $lines
     * @return array|null
     * @throws Exception
     */
    private static function addRowsFromFormats(DOMElement $table, array &$lines)
    {
        $rowFormats = self::parseRowFormats($lines);
        if (is_null($rowFormats)) {
            return null; // We hit a .TE
        }
        $dom           = $table->ownerDocument;
        $skippableRows = [];
        foreach ($rowFormats as $i => $rowFormat) {
            if (preg_match('~^[-|_\s]+$~u', implode('', $rowFormat))) {
                if ($table->lastChild) {
                    Node::addClass($table->lastChild, 'border-bottom');
                } else {
                    $nextTRClass = 'border-top';
                }
                $skippableRows[] = $i;
                continue;
            }

            /* @var DomElement $tr */
            $tr = $dom->createElement('tr');
            $tr = $table->appendChild($tr);

            if (isset($nextTRClass)) {
                Node::addClass($tr, $nextTRClass);
                unset($nextTRClass);
            }

            for ($i = 0; $i < count($rowFormat); ++$i) {
                $tdFormat = $rowFormat[$i];

                if (preg_match('~^\|~', $tdFormat)) {
                    if ($tr->lastChild) {
                        Node::addClass($tr->lastChild, 'border-right');
                    } else {
                        $nextTDClass = 'border-left';
                    }
                    continue;
                }

                if (preg_match('~^S~', $tdFormat)) {
                    if ($tr->lastChild) {
                        $prevColspan = $tr->lastChild->getAttribute('colspan');
                        if ($prevColspan !== '') {
                            $colspan = (int)$prevColspan + 1;
                        } else {
                            $colspan = 2;
                        }
                        $tr->lastChild->setAttribute('colspan', (string)$colspan);
                    }
                    continue;
                }


                /* @var DomElement $td */
                $td = $dom->createElement('td');
                $td = $tr->appendChild($td);

                // Ignore for now:
                // * equal-width columns,
                // * setting the font to Regular
                $tdFormat = str_replace(['e', 'E', 'f(R)'], '', $tdFormat);

                $tdFormat = Replace::pregCallback(
                  '~^([LCRN])~',
                  function ($matches) use ($td) {
                      switch ($matches[1]) {
                          case 'C':
                              Node::addClass($td, 'center');
                              break;
                          case 'R':
                          case 'N':
                              Node::addClass($td, 'right-align');
                              break;
                          default:
                              break;
                      }

                      return '';
                  },
                  $tdFormat
                );

                // A number suffix on a key character is interpreted as a column separation in en units (multiplied in
                // proportion if the expand option is on – in case of overfull tables this might be zero). Default
                // separation is 3n.
                $tdFormat = Replace::preg('~^\d+(.*)$~', '$1', $tdFormat);

                $tdFormat = Replace::preg('~([fF]\()?[bB]\)?~u', '', $tdFormat, -1, $numReplaced);
                if ($numReplaced > 0) {
                    Node::addClass($td, 'bold');
                }

                $tdFormat = Replace::preg('~([fF]\()?[cC][wW]?\)?~u', '', $tdFormat, -1, $numReplaced);
                if ($numReplaced > 0) {
                    Node::addClass($td, 'code');
                }

                $tdFormat = Replace::preg('~([fF]\()?[iI]\)?~u', '', $tdFormat, -1, $numReplaced);
                if ($numReplaced > 0) {
                    Node::addClass($td, 'italic');
                }

                if (isset($nextTDClass)) {
                    Node::addClass($td, $nextTDClass);
                    unset($nextTDClass);
                }

                if (in_array($tdFormat, ['-', '_'])) {
                    Node::addClass($td, 'border-bottom');
                }
            }
        }

        return $skippableRows;
    }

    /**
     * @param DOMElement $parentNode
     * @param array $lines
     * @param array $request
     * @param bool $needOneLineOnly
     * @return DOMElement|null
     * @throws Exception
     */
    static function checkAppend(
      DOMElement $parentNode,
      array &$lines,
      array $request,
      $needOneLineOnly = false
    ): ?DOMElement {
        array_shift($lines);

        $parentNode = Blocks::getBlockContainerParent($parentNode);

        $dom = $parentNode->ownerDocument;

        $columnSeparator = "\t";

        $tableClasses = [];

        if (mb_substr(trim($lines[0]), -1, 1) === ';') {
            $globalOptions = rtrim($lines[0], ';');
            array_shift($lines);
            // Treat this separately as a space after "tab" is accepted:
            Replace::pregCallback(
              '~tab\s?\((.)\)~u',
              function ($matches) use (&$columnSeparator) {
                  $columnSeparator = $matches[1];

                  return '';
              },
              $globalOptions
            );
            $globalOptions = preg_split('~[\\t ,]~', $globalOptions);
            foreach ($globalOptions as $globalOption) {
                if (in_array($globalOption, ['box', 'allbox', 'doublebox'])) { // See about 'expand'?
                    $tableClasses[] = $globalOption;
                } elseif ($globalOption === 'center') {
                    $tableClasses[] = 'center-table';
                }
            }
        }

        /* @var DomElement $table */
        $table = $dom->createElement('table');
        Node::addClass($table, $tableClasses);

        $skippableRows = self::addRowsFromFormats($table, $lines);
        if (is_null($skippableRows)) {
            return null; // We hit a .TE
        }
        $trailingTRPrototype = $table->lastChild->cloneNode(true);


        $tableRowNum  = 0;
        $formatRowNum = 0;
        $tr           = false;
        $nextRowBold  = false;

        $pre = Node::ancestor($parentNode, 'pre');
        if (!is_null($pre)) {
            Node::addClass($table, 'pre');
            if ($pre->textContent === '') {
                $pre->parentNode->insertBefore($table, $pre);
            } else {
                $parentNode = $pre->parentNode;
                $table      = $parentNode->appendChild($table);
            }
        } else {
            $table = $parentNode->appendChild($table);
        }

        while ($request = Request::getLine($lines)) {
            array_shift($lines);

            if ($request['raw_line'] === $columnSeparator && in_array($tableRowNum, $skippableRows)) {
                // See e.g. 3rd table in md.4
                continue;
            } elseif ($request['raw_line'] === '') {
                continue;
            } elseif (in_array($request['request'], ['TE', 'SH', 'SS'])) {
                break;
            } elseif ($request['raw_line'] === '.T&') {
                $skippableRows = self::addRowsFromFormats($table, $lines);
                if (is_null($skippableRows)) {
                    return null; // We hit a .TE
                }
                $trailingTRPrototype = $table->lastChild->cloneNode(true);
                $formatRowNum        = 0;
                continue;
            } elseif ($request['raw_line'] === '_') {
                if ($tr) {
                    Node::addClass($tr, 'border-bottom');
                } else {
                    Node::addClass($table, 'border-top');
                }
            } elseif ($request['raw_line'] === '=') {
                if ($tr) {
                    $tr->setAttribute('class', 'border-bottom-double');
                }
            } elseif (in_array($request['raw_line'], ['.ft CW', '.ft R', '.P', '.PP'])) {
                // Do nothing for now - see sox.1
            } elseif (in_array($request['raw_line'], ['.B'])) {
                $nextRowBold = true;
            } elseif (!is_null($request['request']) && $request['request'] !== '') {
                continue;
            } else {
                $tr = $table->childNodes->item($tableRowNum);
                if (!$tr) {
                    $tr = $trailingTRPrototype->cloneNode(true);
                    $tr = $table->appendChild($tr);
                }

                if ($nextRowBold) {
                    Node::addClass($tr, 'bold');
                }

                $cols = explode($columnSeparator, $request['raw_line']);

                for ($j = 0; $j < count($cols); ++$j) { // NB: $cols can grow more elements with T{...

                    $cell = $tr->childNodes->item($j);
                    if (!$cell) {
                        $cell = $tr->lastChild;
                    }

                    $tdContents = $cols[$j];

                    if ($tdContents === '_') {
                        $cell->appendChild($dom->createElement('hr'));
                    } elseif (in_array($tdContents, ['.TH', '.TC'])) {
                        continue; //  Material up to the ".TH" is placed at the top of each page of table; the remaining lines in the table are placed on several pages as required. Note that this is not a feature of tbl, but of the ms layout macros.
                    } elseif (mb_strpos($tdContents, 'T{') === 0) {
                        $tBlockLines = [];
                        if ($tdContents !== 'T{') {
                            $tBlockLines[] = mb_substr($tdContents, 2);
                        }
                        while (count($lines)) {
                            if (mb_strpos($lines[0], '.TE') === 0) { // bug in latex2man.1
                                break;
                            }
                            $tBlockLine = array_shift($lines);
                            if (mb_strpos($tBlockLine, 'T}') === 0) {
                                if ($tBlockLine !== 'T}') {
                                    $restOfLine = mb_substr($tBlockLine, 3); // also take out separator
                                    $cols       = array_merge($cols, explode($columnSeparator, $restOfLine));
                                }
                                Roff::parse($cell, $tBlockLines);
                                break;
                            } else {
                                $tBlockLines[] = $tBlockLine;
                            }
                        }
                    } else {
                        // This fails e.g. in ed.1p on ";!. ; $" where ! is $columnSeparator
                        //Roff::parse($cell, [$tdContents]);
                        if (Inline_VerticalSpace::check($tdContents) === false) {
                            TextContent::interpretAndAppendText($cell, $tdContents);
                        }
                    }
                }

                ++$tableRowNum;
                ++$formatRowNum;
                $nextRowBold = false;
            }
        }

        return $parentNode;
    }


}
