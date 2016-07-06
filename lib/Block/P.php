<?php


class Block_P
{

    static function check(string $string)
    {
        // empty lines cause a new para also, see sar.1
        // See https://www.gnu.org/software/groff/manual/html_node/Implicit-Line-Breaks.html
        return $string === '' or preg_match('~^\.([LP]?P(?:\s|$)|HP)~u', $string);
    }

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        if (!self::check($lines[$i])) {
            return false;
        }

        $numLines = count($lines);
        $dom = $parentNode->ownerDocument;

        $blockLines = [];
        for (; $i < $numLines - 1; ++$i) {
            if (Blocks::lineEndsBlock($lines, $i + 1)) {
                break;
            }
            $blockLines[] = $lines[$i + 1];
        }

        if (count($blockLines) > 0) {
            if ($parentNode->tagName === 'p' and !$parentNode->hasContent()) {
                Blocks::handle($parentNode, $blockLines);
            } else {
                $p = $dom->createElement('p');
                Blocks::handle($p, $blockLines);
                $parentNode->appendBlockIfHasContent($p);
            }
        }

        return $i;

    }


}
