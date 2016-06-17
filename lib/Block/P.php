<?php


class Block_P
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        // empty lines cause a new para also, see sar.1
        // See https://www.gnu.org/software/groff/manual/html_node/Implicit-Line-Breaks.html
        if ($lines[$i] !== '' and !preg_match('~^\.([LP]?P$|HP)~u', $lines[$i])) {
            return false;
        }

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;

        if ($lines[$i] === '' and $i < $numLines - 3 and mb_strpos($lines[$i + 1], "\t") > 0 and
          (mb_strpos($lines[$i + 2], "\t") > 0 or $lines[$i + 2] === '') and
          mb_strpos($lines[$i + 3], "\t") > 0
        ) {
            // Looks like a table next, we detect that lower down, do nothing for now
            return false;
        }


        $blockLines = [];
        while ($i < $numLines) {
            if ($i === $numLines - 1) {
                break;
            }
            $nextLine = $lines[$i + 1];
            if ($nextLine === '' or preg_match(Blocks::BLOCK_END_REGEX, $nextLine)) {
                break;
            }
            $blockLines[] = $nextLine;
            ++$i;
        }
        $p = $dom->createElement('p');
        Blocks::handle($p, $blockLines);
        $parentNode->appendBlockIfHasContent($p);

        return $i;

    }


}
