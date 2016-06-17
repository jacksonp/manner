<?php


class Blocks
{

    const BLOCK_END_REGEX = '~^\.([LP]?P$|HP|TP|IP|ti|RS|EX|ce|nf|TS|SS)~u';

    /**
     * Utility function to avoid duplicating code.
     *
     * @param int $i
     * @param array $lines
     * @return array
     */
    static function getDDBlock(int $i, array $lines): array
    {

        $numLines   = count($lines);
        $blockLines = [];
        $rsLevel    = 0;

        for ($i = $i + 1; $i < $numLines; ++$i) {
            $line = $lines[$i];

            if (preg_match('~^\.RS~u', $line)) {
                ++$rsLevel;
            } elseif (preg_match('~^\.RE~u', $line)) {
                --$rsLevel;
            }

            $hitIP      = false;
            $hitBlankIP = false;
            if (preg_match('~^\.IP ?(.*)$~u', $line, $nextIPMatches)) {
                $hitIP      = true;
                $nextIPArgs = Macro::parseArgString($nextIPMatches[1]);
                $hitBlankIP = is_null($nextIPArgs) || trim($nextIPArgs[0]) === '';
            }

            // <= 0 for stray .REs
            if ($rsLevel <= 0) {
                if (preg_match('~^\.([HTLP]?P|SS)~u', $line) || ($hitIP && !$hitBlankIP)) {
                    --$i;
                    break;
                }
            }

            if ($hitBlankIP) {
                $blockLines[] = ''; // Empty creates new paragraph in block, see dir.1
            } else {
                if ($i < $numLines - 1 or $line !== '') {
                    $blockLines[] = $line;
                }
            }
        }

        return [$i, $blockLines];

    }


    static function handle(DOMElement $parentNode, array $lines)
    {

        // Trim $lines
        $trimVals = ['', '.br', '.sp', '.ad', '.ad n', '.ad b'];
        ArrayHelper::ltrim($lines, $trimVals);
        ArrayHelper::rtrim($lines, $trimVals);

        $dom = $parentNode->ownerDocument;

        $numLines = count($lines);
        for ($i = 0; $i < $numLines; ++$i) {

            $line = $lines[$i];

            $canAppendNextText = true;

            $blockClasses = ['SS', 'P', 'IP', 'TP', 'ti', 'RS', 'EX', 'ce', 'nf', 'TS', 'TabTable'];

            foreach ($blockClasses as $blockClass) {
                $className = 'Block_' . $blockClass;
                $newI      = $className::checkAppend($parentNode, $lines, $i);
                if ($newI !== false) {
                    $i = $newI;
                    continue 2;
                }
            }

            // Ignore .RE macros without corresponding .RS, and .ad macros that haven't been trimmed as in middle of $lines
            if (preg_match('~^\.RE~u', $line) or in_array($line, ['.ad', '.ad n', '.ad b'])) {
                continue;
            }

            if ($line === '.EE') {
                // Strays
                if ($parentNode->hasChildNodes()) {
                    $parentNode->appendChild($dom->createElement('br'));
                }
                continue;
            }


            $parentNodeLastBlock = $parentNode->getLastBlock();

            if (is_null($parentNodeLastBlock)) {
                if (in_array($parentNode->tagName, ['p', 'blockquote'])) {
                    $parentForLine = $parentNode;
                } else {
                    $parentForLine = $parentNode->appendChild($dom->createElement('p'));
                }
            } else {
                if (in_array($parentNodeLastBlock->tagName, ['div', 'pre', 'code', 'table', 'h2'])) {
                    // Start a new paragraph after certain blocks
                    $parentForLine = $parentNode->appendChild($dom->createElement('p'));
                } else {
                    $parentForLine = $parentNodeLastBlock;
                }
            }

            if (preg_match('~^\.UR <?(.*?)>?$~u', $line, $matches)) {
                $anchor = $dom->createElement('a');
                $url    = TextContent::interpretString(Macro::parseArgString($matches[1])[0]);
                if (filter_var($url, FILTER_VALIDATE_EMAIL)) {
                    $url = 'mailto:' . $url;
                }
                $anchor->setAttribute('href', $url);
                $parentForLine->appendChild($anchor);
                for ($i = $i + 1; $i < $numLines; ++$i) {
                    $line = $lines[$i];
                    if (preg_match('~^\.UE~u', $line)) {
                        continue 2;
                    }
                    TextContent::interpretAndAppendCommand($anchor, $line);
                }
                throw new Exception('.UR with no corresponding .UE');
            }

            if (preg_match('~^\.([RBI][RBI]?|ft (?:[123RBIP]|C[WR]))$~u', $line)) {
                if ($i === $numLines - 1
                  || $line === '.ft R'
                  || $lines[$i + 1] === '.IP http://www.gnutls.org/manual/'
                  || mb_strpos($lines[$i + 1], '.B') === 0
                  || mb_strpos($lines[$i + 1], '.I') === 0
                ) {
                    continue;
                }
                $nextLine = $lines[++$i];
                if ($nextLine === '') {
                    continue;
                } else {
                    if ($nextLine[0] === '.') {
                        if (in_array($line, ['.ft 1', '.ft P', '.ft CR']) || $nextLine === '.nf') {
                            --$i;
                            continue;
                        }
                        throw new Exception($nextLine . ' - ' . $line . ' followed by non-text');
                    } else {
                        if ($line === '.B' || $line === '.ft B' || $line === '.ft 3') {
                            $parentForLine = $parentForLine->appendChild($dom->createElement('strong'));
                            $line          = $nextLine;
                        } elseif ($line === '.I' || $line === '.ft I' || $line === '.ft 2') {
                            $parentForLine = $parentForLine->appendChild($dom->createElement('em'));
                            $line          = $nextLine;
                        } elseif ($line === '.ft CW') {
                            $parentForLine = $parentForLine->appendChild($dom->createElement('code'));
                            $line          = $nextLine;
                        } else {
                            $line .= ' ' . $nextLine;
                        }
                        $canAppendNextText = false;
                    }
                }
            }

            if ($line === '.SM') {
                if ($i === $numLines - 1) {
                    continue;
                }
                $nextLine = $lines[++$i];
                if ($nextLine === '') {
                    continue;
                }
                $parentForLine     = $parentForLine->appendChild($dom->createElement('small'));
                $line              = $nextLine;
                $canAppendNextText = false;
            }

            if ($line === '.SB') {
                if ($i === $numLines - 1) {
                    continue;
                }
                $nextLine = $lines[++$i];
                if ($nextLine === '') {
                    continue;
                }
                $small             = $parentForLine->appendChild($dom->createElement('small'));
                $parentForLine     = $small->appendChild($dom->createElement('strong'));
                $line              = $nextLine;
                $canAppendNextText = false;
            }

            if ($canAppendNextText
              && !in_array(mb_substr($line, 0, 1), ['.', ' '])
              && (mb_strlen($line) < 2 || mb_substr($line, 0, 2) !== '\\.')
              && !preg_match('~\\\\c$~', $line)
            ) {
                while ($i < $numLines - 1) {
                    $nextLine = $lines[$i + 1];
                    if (mb_strlen($nextLine) === 0 || in_array(mb_substr($nextLine, 0, 1), ['.', ' '])
                      || (mb_strlen($nextLine) > 1 && mb_substr($nextLine, 0, 2) === '\\.')
                    ) {
                        break;
                    }
                    $line .= ' ' . $nextLine;
                    ++$i;
                }
            }

            if (preg_match('~^\\\\?\.br~u', $line)) {
                if ($parentForLine->hasChildNodes() && $i !== $numLines - 1) {
                    // Only bother if this isn't the first node.
                    $parentForLine->appendChild($dom->createElement('br'));
                }
            } elseif (preg_match('~^\.(sp|ne)~u', $line)) {
                if ($parentForLine->hasChildNodes() && $i !== $numLines - 1) {
                    // Only bother if this isn't the first node.
                    $parentForLine->appendChild($dom->createElement('br'));
                    $parentForLine->appendChild($dom->createElement('br'));
                }
            } else {

                // Implicit line break: "A line that begins with a space causes a break and the space is output at the beginning of the next line. Note that this space isn't adjusted, even in fill mode."
                if (mb_substr($line, 0, 1) === ' '
                  && $parentForLine->hasChildNodes()
                  && ($parentForLine->lastChild->nodeType !== XML_ELEMENT_NODE || $parentForLine->lastChild->tagName !== 'br')
                ) {
                    $parentForLine->appendChild($dom->createElement('br'));
                }

                TextContent::interpretAndAppendCommand($parentForLine, $line);
            }

        }

    }

}
