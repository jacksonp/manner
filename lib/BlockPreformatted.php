<?php


class BlockPreformatted
{

    public static function handle(HybridNode $blockNode)
    {

        $dom = $blockNode->ownerDocument;

        $addIndent  = 0;
        $nextIndent = 0;
        $numLines   = count($blockNode->manLines);
        for ($i = 0; $i < $numLines; ++$i) {
            $line = $blockNode->manLines[$i];

            if ($nextIndent !== 0) {
                $addIndent  = $nextIndent;
                $nextIndent = 0;
            }

            $parentForLine = $blockNode;

            if (mb_strlen($line) === 0
              || preg_match('~^\.([LP]?P$|HP|br|sp|ne)~u', $line)
              || preg_match('~^\\\\?\.$~u', $line) // empty requests
            ) {
                if ($i > 0 && $i !== $numLines - 1) {
                    $blockNode->appendChild(new DOMText("\n"));
                    $addIndent = 0;
                }
                continue;
            } elseif (preg_match('~^\.IP ?(.*)$~u', $line, $matches)) {
                $ipArgs     = Macro::parseArgString($matches[1]);
                $nextIndent = 4;
                if (is_null($ipArgs) || trim($ipArgs[0]) === '') {
                    continue;
                } else {
                    $line = $ipArgs[0];
                }
            } elseif (preg_match('~^\.TP ?(.*)$~u', $line, $matches)) {
                if ($i === $numLines - 1) {
                    continue;
                }
                $line       = $blockNode->manLines[++$i];
                $addIndent  = 0;
                $nextIndent = 4;
            } elseif (preg_match('~^\.ti ?(.*)$~u', $line, $matches)) {
                if ($i === $numLines - 1) {
                    continue;
                }
                $line      = $blockNode->manLines[++$i];
                $addIndent = 4;
            } elseif (preg_match('~^\.(nf|RS|RE)~u', $line)) {
                continue;
            } elseif (preg_match('~^\.[RBI][RBI]?$~u', $line)) {
                if ($i === $numLines - 1) {
                    continue;
                }
                $nextLine = $blockNode->manLines[++$i];
                if (mb_strlen($nextLine) === 0) {
                    continue;
                } else {
                    if ($nextLine[0] === '.') {
                        throw new Exception($nextLine . ' - ' . $line . ' followed by non-text');
                    } else {
                        if ($line === '.B') {
                            $line          = $nextLine;
                            $parentForLine = $blockNode->appendChild($dom->createElement('strong'));
                        } elseif ($line === '.I') {
                            $line          = $nextLine;
                            $parentForLine = $blockNode->appendChild($dom->createElement('em'));
                        } else {
                            $line .= ' ' . $nextLine;
                        }
                    }
                }
            }

            if ($addIndent > 0) {
                $blockNode->appendChild(new DOMText(str_repeat(' ', $addIndent)));
            }

            TextContent::interpretAndAppendCommand($parentForLine, $line);
            if ($i !== $numLines - 1) {
                $blockNode->appendChild(new DOMText("\n"));
            }

        }

    }
}
