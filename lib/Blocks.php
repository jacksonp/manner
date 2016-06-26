<?php


class Blocks
{

    const BLOCK_END_REGEX = '~^\.([LP]?P$|HP|TP|IP|ti|RS|EX|ce|nf|TS|SS|SH)~u';

    static function isSkippable(string $line)
    {
        // Ignore:
        // stray .RE and .EE macros,
        // .ad macros that haven't been trimmed as in middle of $lines
        // empty .BR macros
        // .R: man page trying to set font to Regular? (not an actual macro, not needed)
        // .RH, .sp,, .sp2, .br., .Sh: man page bugs
        // .pp: spurious, in *_selinux.8 pages
        return
          preg_match('~^\.(RE|fi|ad|Sh)~u', $line) or
          in_array($line, ['.EE', '.BR', '.R', '.sp,', '.sp2', '.br.', '.pp', '.RH']);
    }

    static function handle(DOMElement $parentNode, array $lines)
    {

        // Trim $lines
        $trimVals = ['', '.ad', '.ad n', '.ad b'];
        ArrayHelper::ltrim($lines, $trimVals);
        ArrayHelper::rtrim($lines, array_merge($trimVals, ['.nf']));

        $dom = $parentNode->ownerDocument;

        $numLines = count($lines);
        for ($i = 0; $i < $numLines; ++$i) {

            $line = $lines[$i];

            $blockClasses = ['SH', 'SS', 'P', 'IP', 'TP', 'ti', 'RS', 'EX', 'ce', 'nf', 'TS', 'TabTable'];

            foreach ($blockClasses as $blockClass) {
                $className = 'Block_' . $blockClass;
                $newI      = $className::checkAppend($parentNode, $lines, $i);
                if ($newI !== false) {
                    $i = $newI;
                    continue 2;
                }
            }

            if (self::isSkippable($line)) {
                continue;
            }

            $parentNodeLastBlock = $parentNode->getLastBlock();

            if (is_null($parentNodeLastBlock)) {
                if (in_array($parentNode->tagName,
                  ['p', 'blockquote', 'dt', 'strong', 'em', 'small', 'code', 'td', 'th', 'pre', 'a', 'h2', 'h3'])
                ) {
                    $parentForLine = $parentNode;
                } else {
                    $parentForLine = $parentNode->appendChild($dom->createElement('p'));
                }
            } else {
                if (in_array($parentNodeLastBlock->tagName, ['div', 'pre', 'code', 'table', 'h2', 'h3'])) {
                    // Start a new paragraph after certain blocks
                    $parentForLine = $parentNode->appendChild($dom->createElement('p'));
                } else {
                    $parentForLine = $parentNodeLastBlock;
                }
            }

            $inlineClasses = ['Link', 'FontOneInputLine', 'AlternatingFont', 'ft', 'VerticalSpace'];

            foreach ($inlineClasses as $inlineClass) {
                $className = 'Inline_' . $inlineClass;
                $newI      = $className::checkAppend($parentForLine, $lines, $i);
                if ($newI !== false) {
                    $i = $newI;
                    continue 2;
                }
            }

            if (!in_array(mb_substr($line, 0, 1), ['.', ' '])
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


            // Implicit line break: "A line that begins with a space causes a break and the space is output at the beginning of the next line. Note that this space isn't adjusted, even in fill mode."
            if (mb_substr($line, 0, 1) === ' '
              && $parentForLine->hasChildNodes()
              && ($parentForLine->lastChild->nodeType !== XML_ELEMENT_NODE || $parentForLine->lastChild->tagName !== 'br')
            ) {
                $parentForLine->appendChild($dom->createElement('br'));
            }

            if (in_array($line, ['', '.'])) {
                continue;
            }

            // FAIL on unknown command
            if (mb_strlen($line) > 0 && in_array($line[0], ['.', "'"])) {
                throw new Exception($line . ' unexpected command in Blocks::handle().');
            }

            TextContent::interpretAndAppendText($parentForLine, $line,
              !in_array($parentForLine->tagName, ['h2', 'h3']));

        }

    }

}
