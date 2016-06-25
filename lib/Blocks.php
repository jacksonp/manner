<?php


class Blocks
{

    const BLOCK_END_REGEX = '~^\.([LP]?P$|HP|TP|IP|ti|RS|EX|ce|nf|TS|SS|SH)~u';

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

            // Ignore:
            // stray .RE and .EE macros,
            // .ad macros that haven't been trimmed as in middle of $lines
            // empty .BR macros
            // .R: man page trying to set font to Regular? (not an actual macro, not needed)
            // .BB: ???
            if (preg_match('~^\.RE~u', $line) or
              in_array($line, ['.ad', '.ad n', '.ad b', '.EE', '.BR', '.R', '.BB'])
            ) {
                continue;
            }

            $parentNodeLastBlock = $parentNode->getLastBlock();

            if (is_null($parentNodeLastBlock)) {
                if (in_array($parentNode->tagName, ['p', 'blockquote', 'dt', 'strong', 'em', 'small', 'code'])) {
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

            $inlineClasses = ['MT', 'UR', 'FontOneInputLine', 'AlternatingFont', 'ft'];

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

            if (preg_match('~^\\\\?\.br~u', $line)) {
                if (!in_array($parentForLine->tagName,
                    ['p', 'blockquote']) or ($parentForLine->hasChildNodes() and $i !== $numLines - 1)
                ) {
                    // Only bother if this isn't the first node.
                    $parentForLine->appendChild($dom->createElement('br'));
                }
            } elseif (preg_match('~^\.(sp|ne)~u', $line)) {
                if (!in_array($parentForLine->tagName,
                    ['p', 'blockquote']) or ($parentForLine->hasChildNodes() and $i !== $numLines - 1)
                ) {
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
