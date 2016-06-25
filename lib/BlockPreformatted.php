<?php


class BlockPreformatted
{

    public static function handle(HybridNode $parentNode, array $lines)
    {

        $addIndent  = 0;
        $nextIndent = 0;
        $numLines   = count($lines);
        for ($i = 0; $i < $numLines; ++$i) {
            $line = $lines[$i];

            if ($nextIndent !== 0) {
                $addIndent  = $nextIndent;
                $nextIndent = 0;
            }

            $parentForLine = $parentNode;

            if (mb_strlen($line) === 0
              || preg_match('~^\.([LP]?P$|HP|br|sp|ne)~u', $line)
              || preg_match('~^\\\\?\.$~u', $line) // empty requests
            ) {
                if ($i > 0 && $i !== $numLines - 1) {
                    $parentNode->appendChild(new DOMText("\n"));
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
                $line       = $lines[++$i];
                $addIndent  = 0;
                $nextIndent = 4;
            } elseif (preg_match('~^\.ti ?(.*)$~u', $line, $matches)) {
                if ($i === $numLines - 1) {
                    continue;
                }
                $line      = $lines[++$i];
                $addIndent = 4;
            } elseif (preg_match('~^\.(nf|RS|RE|ft)~u', $line)) {
                continue;
            }

            $inlineClasses = ['FontOneInputLine'];

            foreach ($inlineClasses as $inlineClass) {
                $className = 'Inline_' . $inlineClass;
                $newI      = $className::checkAppend($parentNode, $lines, $i);
                if ($newI !== false) {
                    if ($i !== $numLines - 1) {
                        $parentNode->appendChild(new DOMText("\n"));
                    }
                    $i = $newI;
                    continue 2;
                }
            }

            if ($addIndent > 0) {
                $parentNode->appendChild(new DOMText(str_repeat(' ', $addIndent)));
            }

            TextContent::interpretAndAppendCommand($parentForLine, $line);
            if ($i !== $numLines - 1) {
                $parentNode->appendChild(new DOMText("\n"));
            }

        }

    }
}
