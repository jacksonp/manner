<?php


class Blocks
{

    const BLOCK_END_REGEX = '~^\.([LP]?P$|HP|TP|IP|ti|RS|EX|ce|nf|TS|SS|SH)~u';

    const TEXT_CONTAINERS = [
      'p',
      'blockquote',
      'dt',
      'strong',
      'em',
      'small',
      'code',
      'td',
      'th',
      'pre',
      'a',
      'h2',
      'h3',
    ];

    static function canSkip(string $line)
    {
        // Ignore:
        // stray .RE and .EE macros,
        // .ad macros that haven't been trimmed as in middle of $lines
        // empty .BR macros
        // .R: man page trying to set font to Regular? (not an actual macro, not needed)
        // .RH, .sp,, .sp2, .br., .Sh, .Sp, .TH: man page bugs
        // .pp: spurious, in *_selinux.8 pages
        return
          preg_match('~^\.(RE|fi|ad|Sh)~u', $line) or
          in_array($line, ['.EE', '.BR', '.R', '.sp,', '.sp2', '.br.', '.pp', '.RH', '.Sp', '.TH', '.TC', '.TR']);
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

            $blockClasses = ['SH', 'SS', 'SY', 'P', 'IP', 'TP', 'ti', 'RS', 'EX', 'Vb', 'ce', 'nf', 'TS', 'TabTable', 'Text'];

            foreach ($blockClasses as $blockClass) {
                $className = 'Block_' . $blockClass;
                $newI      = $className::checkAppend($parentNode, $lines, $i);
                if ($newI !== false) {
                    $i = $newI;
                    continue 2;
                }
            }

            if (self::canSkip($line)) {
                continue;
            }

            $parentNodeLastBlock = $parentNode->getLastBlock();

            if (is_null($parentNodeLastBlock)) {
                if (in_array($parentNode->tagName, self::TEXT_CONTAINERS)) {
                    $parentForLine = $parentNode;
                } else {
                    $parentForLine = $parentNode->appendChild($dom->createElement('p'));
                }
            } else {
                if (in_array($parentNodeLastBlock->tagName, ['div', 'pre', 'code', 'table', 'h2', 'h3', 'dl'])) {
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

            if (in_array($line, ['', '.', '\''])) {
                continue;
            }

            if (in_array($line, ['.ns'])) {
                // TODO: Hack: see groff_mom.7 - this should be already skipped, but maybe not as in .TQ macro
                continue;
            }

            // FAIL on unknown command
            if (mb_strlen($line) > 0 and in_array(mb_substr($line, 0, 1), ['.', '\''])) {
                throw new Exception($line . ' unexpected command in Blocks::handle().');
            }

//            TextContent::interpretAndAppendText($parentForLine, $line,
//              !in_array($parentForLine->tagName, ['h2', 'h3']));

            throw new Exception('"' . $line . '" Blocks::handle() could not handle it.');

        }

    }

}
