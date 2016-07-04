<?php


class Blocks
{

    const BLOCK_END_REGEX = '~^\.([LP]?P$|HP|TP|IP|ti|RS|EX|ce|nf|TS|SS|SH|Vb|SY)~u';

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

    static function lineEndsBlock(array $lines, int $i)
    {
        $line = $lines[$i];

        return
          $line === '' or
          preg_match(Blocks::BLOCK_END_REGEX, $line) or
          Block_TabTable::isStart($lines, $i);
    }

    static function canSkip(string $line)
    {
        // Ignore:
        // stray .RE macros,
        // .ad macros that haven't been trimmed as in middle of $lines...
        return
          preg_match('~^\.(RE|fi|ad|Sh)~u', $line) or
          in_array($line, [
            '.',    // empty request
            '\'',   // empty request
            '.ns',  // TODO: Hack: see groff_mom.7 - this should be already skipped, but maybe not as in .TQ macro
            '.EE',  // strays
            '.BR',  // empty
            '.R',   // man page trying to set font to Regular? (not an actual macro, not needed)
              // .man page bugs:
            '.sp,',
            '.sp2',
            '.br.',
            '.pp', // spurious, in *_selinux.8 pages
            '.RH',
            '.Sp',
            '.TH',
            '.TC',
            '.TR',
          ]);
    }

    static function handle(DOMElement $parentNode, array $lines)
    {

        // Trim $lines
        $trimVals = ['', '.ad', '.ad n', '.ad b', '.', '\'', '.br', '.sp'];
        ArrayHelper::ltrim($lines, $trimVals);
        ArrayHelper::rtrim($lines, array_merge($trimVals, ['.nf']));

//        var_dump($parentNode->tagName);
//        var_dump($lines);

        $numLines = count($lines);
        for ($i = 0; $i < $numLines; ++$i) {

            $line = $lines[$i];

            $blockClasses = [
              'SH',
              'SS',
              'SY',
              'P',
              'IP',
              'TP',
              'ti',
              'RS',
              'EX',
              'Vb',
              'ce',
              'nf',
              'TS',
              'TabTable',
              'Text',
            ];

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

            $inlineClasses = ['Link', 'FontOneInputLine', 'AlternatingFont', 'ft', 'VerticalSpace'];

            foreach ($inlineClasses as $inlineClass) {
                $className = 'Inline_' . $inlineClass;
                $newI      = $className::checkAppend($parentNode, $lines, $i);
                if ($newI !== false) {
                    $i = $newI;
                    continue 2;
                }
            }

//            var_dump(array_slice($lines, $i - 5, 10));
//            var_dump($lines);
            throw new Exception('"' . $line . '" Blocks::handle() could not handle it.');

        }

    }

    static function getTextParent(DOMElement $parentNode)
    {
        if (in_array($parentNode->tagName, Blocks::TEXT_CONTAINERS)) {
            return [$parentNode, false];
        } else {
            $parentNodeLastBlock = $parentNode->getLastBlock();
            if (is_null($parentNodeLastBlock) or
              in_array($parentNodeLastBlock->tagName, ['div', 'pre', 'code', 'table', 'h2', 'h3', 'dl'])
            ) {
                return [$parentNode->ownerDocument->createElement('p'), true];
            } else {
                return [$parentNodeLastBlock, false];
            }
        }
    }

}
