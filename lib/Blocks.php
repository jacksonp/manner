<?php


class Blocks
{

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

    static function trim(array &$lines)
    {
        $trimVals = ['', ' ', '.ad', '.ad n', '.ad b', '.', '\'', '.br', '.sp'];
        ArrayHelper::ltrim($lines, $trimVals);
        ArrayHelper::rtrim($lines, array_merge($trimVals, ['.nf']));
    }

    static function lineEndsBlock(array $lines, int $i)
    {
        $request = Request::get($lines[$i]);
        if ($request['request'] && Man::instance()->requestStartsBlock($request['request'])) {
            return true;
        }

        return Block_TabTable::isStart($lines, $i);
    }

    /*
    static function handle(DOMElement $parentNode, array $lines)
    {
//        var_dump($parentNode->tagName);
//        var_dump($lines);

        $numLines = count($lines);
        for ($i = 0; $i < $numLines; ++$i) {

            $request = Request::getClass($lines, $i);

            $newI = $request['class']::checkAppend($parentNode, $lines, $i, $request['arguments'], $request['request']);
            if ($newI === false) {
//            var_dump(array_slice($lines, $i - 5, 10));
//            var_dump($lines);
                throw new Exception('"' . $lines[$i] . '" Roff::parse() could not handle it.');
            }

            $i = $newI;

        }

    }
    */

    static function _maybeLastEmptyChildWaitingForText(DOMElement $parentNode)
    {
        if (
          $parentNode->lastChild &&
          $parentNode->lastChild->nodeType === XML_ELEMENT_NODE &&
          in_array($parentNode->lastChild->tagName, ['em', 'strong', 'small']) &&
          $parentNode->lastChild->textContent === ''
        ) {
            if ($parentNode->lastChild->lastChild &&
              $parentNode->lastChild->lastChild->nodeType === XML_ELEMENT_NODE &&
              in_array($parentNode->lastChild->lastChild->tagName, ['em', 'strong', 'small'])
            ) {
                // bash.1:
                // .SM
                // .B
                // ARITHMETIC EVALUATION
                return [$parentNode->lastChild->lastChild, false, true];
            } else {
                return [$parentNode->lastChild, false, true];
            }
        } else {
            return [$parentNode, false, $parentNode->tagName === 'dt'];
        }
    }

    static function getTextParent(DOMElement $parentNode)
    {
        if (in_array($parentNode->tagName, Blocks::TEXT_CONTAINERS)) {
            return self::_maybeLastEmptyChildWaitingForText($parentNode);
        } else {
            $parentNodeLastBlock = $parentNode->getLastBlock();
            if (is_null($parentNodeLastBlock) ||
              in_array($parentNodeLastBlock->tagName, ['div', 'pre', 'code', 'table', 'h2', 'h3', 'dl', 'blockquote'])
            ) {
                return [$parentNode->ownerDocument->createElement('p'), true, false];
            } else {
                return self::_maybeLastEmptyChildWaitingForText($parentNodeLastBlock);
            }
        }
    }

}
