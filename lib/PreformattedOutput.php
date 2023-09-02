<?php

declare(strict_types=1);

namespace Manner;

use DOMElement;
use DOMText;
use Exception;
use Manner\Block\TabTable;
use Manner\Roff\Unit;

class PreformattedOutput
{

    private static int  $addIndent           = 0;
    private static int  $nextIndent          = 0;
    private static bool $resetFontsAfterNext = false;

    public static function reset(): void
    {
        self::$addIndent           = 0;
        self::$nextIndent          = 0;
        self::$resetFontsAfterNext = false;
    }

    /**
     * @param DOMElement $parentNode
     * @param array $lines
     * @param array $request
     * @return bool | DomElement
     * @throws Exception
     */
    public static function handle(DOMElement $parentNode, array &$lines, array $request): bool|DOMElement
    {
        $pre = Node::ancestor($parentNode, 'pre');

        if (is_null($pre)) {
            return false;
        }

        $man = Man::instance();

        $dom = $parentNode->ownerDocument;

        if (self::$nextIndent !== 0) {
            self::$addIndent  = self::$nextIndent;
            self::$nextIndent = 0;
        }

        $line = $request['raw_line'];

        if (
          $pre->textContent === '' && count($lines) > 1 &&
          TabTable::lineContainsTab($line) &&
          TabTable::lineContainsTab($lines[1])
        ) {
            // TODO: add "preformatted" table class instead of code tags in cells? (see also CSS for removing margin on <pre> in cells)
            // or don't use fixed width font at all? see https://www.mankier.com/3/SoIndexedShape.3iv#Description
            $table = $parentNode->appendChild($dom->createElement('table'));
            while (count($lines) && mb_strpos($lines[0], "\t") !== false) {
                $request = Request::getLine($lines);
                array_shift($lines);
//                if (in_array($line, ['.br', ''])) {
//                    continue;
//                }

                if (is_null($request['request'])) {
                    $line = $request['raw_line'];
                } else {
                    $line = $request['arguments'][0];
                }
                $tds = preg_split('~\t+~u', $line);
                $tr  = $table->appendChild($dom->createElement('tr'));
                foreach ($tds as $tdLine) {
                    $cell = $dom->createElement('td');
                    /* @var DomElement $codeNode */
                    $codeNode = $cell->appendChild($dom->createElement('code'));
                    if (is_null($request['request'])) {
                        TextContent::interpretAndAppendText($codeNode, $tdLine);
                    } else {
                        $blockLines = [$man->control_char . $request['request'] . ' ' . $tdLine];
                        Roff::parse($codeNode, $blockLines);
                    }
                    $tr->appendChild($cell);
                }
            }

            $pre->parentNode->insertBefore($table, $pre);

            return true;
        }

        if ($request['class'] === 'Empty_Request' || $request['request'] === 'br') {
            array_shift($lines);
            self::$addIndent = 0;

            return true;
        } elseif ($request['raw_line'] === '' || in_array($request['class'], ['\Manner\Block\P', '\Manner\Inline\VerticalSpace'])) {
            array_shift($lines);
            if ($parentNode->hasChildNodes()) {
                $parentNode->appendChild(new DOMText("\n"));
                self::$addIndent = 0;
            }
            if (in_array($request['class'], ['\Manner\Block\P'])) {
                $man->resetFonts();
                if (Indentation::isSet($pre)) {
                    // Return new parent element without indentation for following requests
                    /* @var DomElement $newPre */
                    $newPre = $pre->parentNode->appendChild($dom->createElement('pre'));

                    return $newPre;
                }
            }

            return true;
        } elseif (in_array($request['class'], ['\Manner\Inline\AlternatingFont', '\Manner\Inline\ft', '\Manner\Request\Skippable'])) {
            $request['class']::checkAppend($parentNode, $lines, $request);
            if ($request['class'] === '\Manner\Inline\AlternatingFont') {
                self::endInputLine($parentNode);
            }

            return true;
        } elseif ($request['request'] === 'IP') {
            self::$nextIndent = 4;
            if (count($request['arguments']) === 0 || trim($request['arguments'][0]) === '') {
                array_shift($lines);
                $man->resetFonts();

                return true;
            } else {
                self::$resetFontsAfterNext = true;
                $line                      = $request['arguments'][0];
                $request['request']        = null;
            }
        } elseif ($request['request'] === 'TP') {
            self::$addIndent           = 0;
            self::$nextIndent          = 4;
            self::$resetFontsAfterNext = true;
            array_shift($lines);

            return true;
        } elseif ($request['request'] === 'ti') {
            self::$nextIndent = 4;
            array_shift($lines);

            return true;
        } elseif (in_array($request['request'], ['ce', 'nf'])) {
            array_shift($lines);

            return true;
        } elseif ($request['request'] === 'RS') {
            array_shift($lines);
            if (count($request['arguments'])) {
                self::$addIndent = (int)round((float)Unit::normalize($request['arguments'][0], 'n', 'm'));
            } else {
                self::$addIndent = 4;
            }

            return true;
        } elseif ($request['request'] === 'RE') {
            if (self::$addIndent === 0) {
                return false;
            }
            array_shift($lines);
            self::reset();

            return true;
        }


        if (self::$addIndent > 0) {
            $parentNode->appendChild(new DOMText(str_repeat(' ', self::$addIndent)));
        }

        if (!is_null($request['request'])) {
            return false;
        }

        TextContent::interpretAndAppendText($parentNode, $line);

        self::endInputLine($parentNode);

        array_shift($lines);

        if (self::$resetFontsAfterNext) {
            $man->resetFonts();
            self::$resetFontsAfterNext = false;
        }

        return true;
    }

    public static function endInputLine(DOMElement $parentNode): void
    {
        if (TextContent::$interruptTextProcessing || $parentNode->getAttribute('class') === 'synopsis') {
            $parentNode->appendChild(new DOMText(' '));
        } else {
            $parentNode->appendChild(new DOMText("\n"));
        }
    }

}
