<?php


class Blocks
{

    /**
     * Utility function to avoid duplicating code.
     *
     * @param int $i
     * @param array $lines
     * @return array
     */
    static function getDDBlock(int $i, array $lines)
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
                if (preg_match('~^\.[HTLP]?P~u', $line) || ($hitIP && !$hitBlankIP)) {
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

        // Right trim $lines
        for ($i = count($lines) - 1; $i >= 0; --$i) {
            if (in_array($lines[$i], ['', '.br'])) {
                unset($lines[$i]);
            } else {
                break;
            }
        }

        $dom = $parentNode->ownerDocument;

        $numLines = count($lines);
        for ($i = 0; $i < $numLines; ++$i) {

            $line = $lines[$i];

            $canAppendNextText = true;

            // empty lines cause a new para also, see sar.1
            // See https://www.gnu.org/software/groff/manual/html_node/Implicit-Line-Breaks.html
            if ($line === '' or preg_match('~^\.([LP]?P$|HP)~u', $line)) {
                if ($line === '' and $i < $numLines - 3 and mb_strpos($lines[$i + 1], "\t") > 0 and
                  (mb_strpos($lines[$i + 2], "\t") > 0 or $lines[$i + 2] === '') and
                  mb_strpos($lines[$i + 3], "\t") > 0
                ) {
                    // Looks like a table next, we detect that lower down, do nothing for now
                    continue;
                } else {
                    $blockLines = [];
                    while ($i < $numLines) {
                        if ($i === $numLines - 1) {
                            break;
                        }
                        $nextLine = $lines[$i + 1];
                        if ($nextLine === '' or preg_match('~^\.([LP]?P$|HP|TP|IP|ti|RS|EX|ce|nf|TS)~u', $nextLine)) {
                            break;
                        }
                        $blockLines[] = $nextLine;
                        ++$i;
                    }
                    $p = $dom->createElement('p');
                    self::handle($p, $blockLines);
                    $parentNode->appendBlockIfHasContent($p);
                    continue;
                }
            }

            // TODO:  --group-directories-first in ls.1 - separate para rather than br?
            // TODO $matches[1] will contain the indentation level, try to use this to handle nested dls?
            if (preg_match('~^\.IP ?(.*)$~u', $line, $matches)) {

                $ipArgs = Macro::parseArgString($matches[1]);

                // 2nd bit: If there's a "designator" - otherwise preg_match hit empty double quotes.
                if (!is_null($ipArgs) && trim($ipArgs[0]) !== '') {
                    // Copied from .TP:
                    if (!$parentNode->hasChildNodes() or $parentNode->lastChild->tagName !== 'dl') {
                        $dl = $dom->createElement('dl');
                        $parentNode->appendChild($dl);
                        if (count($ipArgs) > 1) {
                            $dl->setAttribute('class', 'indent-' . $ipArgs[1]);
                        }
                    } else {
                        $dl = $parentNode->lastChild;
                    }
                    $dt = $dom->createElement('dt');
                    TextContent::interpretAndAppendCommand($dt, $ipArgs[0]);
                    $dl->appendChild($dt);

                    list ($i, $blockLines) = self::getDDBlock($i, $lines);

                    $dd = $dom->createElement('dd');
                    self::handle($dd, $blockLines);
                    $dl->appendBlockIfHasContent($dd);

                    continue;
                }

                if (!$parentNode->hasChildNodes() or $parentNode->lastChild->tagName === 'pre') {
                    $p = $dom->createElement('p');
                    $parentNode->appendChild($p);
                } elseif (in_array($parentNode->lastChild->tagName, ['p', 'h2'])) {
                    $parentNode->appendChild($dom->createElement('blockquote'));
                } elseif ($parentNode->lastChild->tagName === 'blockquote') {
                    // Already in previous .IP,
                    $parentNode->lastChild->appendChild($dom->createElement('br'));
                } else {
                    throw new Exception($line . ' - unexpected .IP in ' . $parentNode->lastChild->tagName . ' at line ' . $i . '. Last line was "' . $lines[$i - 1] . '"');
                }
                continue;
            }

            $blockClasses = ['TP', 'ti', 'RS', 'EX', 'ce', 'nf', 'TS', 'TabTable'];

            foreach ($blockClasses as $blockClass) {
                $className = 'Block_' . $blockClass;
                $res       = $className::checkAppend($parentNode, $lines, $i);
                if ($res !== false) {
                    $i = $res;
                    continue 2;
                }
            }

            if (preg_match('~^\.RE~u', $line)) {
                // Ignore .RE macros without corresponding .RS
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
                if ($parentNode->tagName === 'p') {
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
                if (mb_strlen($nextLine) === 0) {
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
