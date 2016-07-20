<?php


class Blocks
{

    const BLOCK_END_REGEX = '~^\.([LP]?P|HP|TP|IP|ti|RS|EX|ce|nf|TS|SS|SH|Vb|SY)~u';

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

    static function handle(DOMElement $parentNode, array $lines)
    {

        // Trim $lines
        $trimVals = ['', ' ', '.ad', '.ad n', '.ad b', '.', '\'', '.br', '.sp'];
        ArrayHelper::ltrim($lines, $trimVals);
        ArrayHelper::rtrim($lines, array_merge($trimVals, ['.nf']));

//        var_dump($parentNode->tagName);
//        var_dump($lines);

        $numLines = count($lines);
        for ($i = 0; $i < $numLines; ++$i) {

            $lines[$i] = preg_replace('~^\\\\\.~u', '.', $lines[$i]);

            $line = $lines[$i];

            if (Request::canSkip($line)) {
                continue;
            }

            $classes = [
              'Block_SH',
              'Block_SS',
              'Block_SY',
              'Block_P',
              'Block_IP',
              'Block_TP',
              'Block_ti',
              'Block_RS',
              'Block_EX',
              'Block_Vb',
              'Block_ce',
              'Block_nf',
              'Block_TS',
              'Block_TH',
              'Block_TabTable',
              'Block_Text',
              'Inline_Link',
              'Inline_FontOneInputLine',
              'Inline_AlternatingFont',
              'Inline_ft',
              'Inline_VerticalSpace',
            ];

            foreach ($classes as $className) {
                $newI = $className::checkAppend($parentNode, $lines, $i);
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
