<?php


class BlockPreformatted
{

    public static function handle(HybridNode $parentNode, array $lines)
    {

        $dom = $parentNode->ownerDocument;

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

            if ($line === '' or
              Block_P::check($line) or
              Inline_VerticalSpace::check($line) or
              preg_match('~^\\\\?\.$~u', $line) // empty requests
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
                $nextIndent = 4;
                continue;
            } elseif (preg_match('~^\.(nf|RS|RE|fi)~u', $line) or in_array($line, ['\\&', '\\)'])) {
                continue;
            } elseif (preg_match('~^\.OP\s(.+)$~u', $line, $matches)) {
                $parentNode->appendChild(new DOMText('['));
                $arguments = Macro::parseArgString($matches[1]);
                $strong    = $parentNode->appendChild($dom->createElement('strong'));
                TextContent::interpretAndAppendText($strong, $arguments[0]);
                if (count($arguments) > 1) {
                    $parentNode->appendChild(new DOMText(' '));
                    $em = $parentNode->appendChild($dom->createElement('em'));
                    TextContent::interpretAndAppendText($em, $arguments[1]);
                }
                $parentNode->appendChild(new DOMText('] '));
                continue;
            }

            $inlineClasses = ['FontOneInputLine', 'AlternatingFont', 'ft'];

            foreach ($inlineClasses as $inlineClass) {
                $className = 'Inline_' . $inlineClass;
                $newI      = $className::checkAppend($parentNode, $lines, $i);
                if ($newI !== false) {
                    if ($i !== $numLines - 1) {
                        self::endInputLine($parentNode);
                    }
                    $i = $newI;
                    continue 2;
                }
            }

            if ($addIndent > 0) {
                $parentNode->appendChild(new DOMText(str_repeat(' ', $addIndent)));
            }

            if ($line === '.') {
                continue;
            }

            // FAIL on unknown command
            if (mb_strlen($line) > 0 and in_array($line[0], ['.', "'"])) {
                throw new Exception($line . ' unexpected command in BlockPreformatted::handle().');
            }

            TextContent::interpretAndAppendText($parentForLine, $line, false, false);
            if ($i !== $numLines - 1) {
                self::endInputLine($parentNode);
            }

        }

        while (
          $parentForLine->lastChild and
          $parentForLine->lastChild instanceof DOMText
          and trim($parentForLine->lastChild->textContent) === ''
        ) {
            $parentForLine->removeChild($parentForLine->lastChild);
        }

    }

    private static function endInputLine(DOMElement $parentNode)
    {
        if (TextContent::$continuation or $parentNode->getAttribute('class') === 'synopsis') {
            $parentNode->appendChild(new DOMText(' '));
        } else {
            $parentNode->appendChild(new DOMText("\n"));
        }
    }

}
