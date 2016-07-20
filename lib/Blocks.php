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


            $request = Request::getClass($lines, $i);

            $newI = $request['class']::checkAppend($parentNode, $lines, $i);
            if ($newI !== false) {
                $i = $newI;
                continue;
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
