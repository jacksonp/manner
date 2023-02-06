<?php

declare(strict_types=1);

namespace Manner\Block;

use DOMElement;
use Exception;
use Manner\Blocks;
use Manner\Indentation;
use Manner\Man;
use Manner\Roff;
use Manner\TextContent;

/*
 * .de1 IP
 * .  ie !\\n[.$] \{\
 * .    ps \\n[PS]u
 * .    vs \\n[VS]u
 * .    ft R
 * .    sp \\n[PD]u
 * .    ne (1v + 1u)
 * .    in (\\n[an-margin]u + \\n[an-prevailing-indent]u)
 * .    ns
 * .  \}
 * .  el \{\
 * .    ie (\\n[.$] - 1) .TP "\\$2"
 * .    el               .TP
 * \&\\$1
 * .  \}
 * ..
 *
 */
class IP implements Template
{

    /**
     * @param DOMElement $parentNode
     * @param array $lines
     * @param array $request
     * @param bool $needOneLineOnly
     * @return DOMElement|null
     * @throws Exception
     */
    public static function checkAppend(
      DOMElement $parentNode,
      array &$lines,
      array $request,
      bool $needOneLineOnly = false
    ): ?DOMElement {
        $man = Man::instance();

        if ($needOneLineOnly && $parentNode->tagName === 'dt') { // See e.g. links2.1
            if (count($request['arguments'])) {
                $lines[0] = $request['arguments'][0];
            } else {
                array_shift($lines);
            }
            $man->resetFonts();

            return null;
        }

        array_shift($lines);

        $dom = $parentNode->ownerDocument;

        $parentNode = Blocks::getBlockContainerParent($parentNode);

        if (count($request['arguments']) > 1) {
            $indentVal = Roff\Unit::normalize($request['arguments'][1], 'n', 'n');
            if (is_numeric($indentVal)) {
                $man->indentation = $indentVal;
            } else {
                $indentVal = $man->indentation;
            }
        } else {
            $indentVal = $man->indentation;
        }

        if (count($request['arguments']) > 0) {
            $dt = $dom->createElement('dt');
            TextContent::interpretAndAppendText($dt, $request['arguments'][0]);
        }

        // Could have hit double quotes, null, \& (zero-width space), just font settings...
        if (isset($dt) && \Manner\Text::trimAndRemoveZWSUTF8($dt->textContent) !== '') {
            $dl = DefinitionList::getParentDL($parentNode);

            $dd = $dom->createElement('dd');

            // TODO: See about adding a check like $dl->lastChild->getAttribute('indent') <= $indentVal
            // And reducing indent if $indentVal is greater
            // And creating new $dl if $indentVal is less

            if (is_null($dl)) {
                $dl = $dom->createElement('dl');
                $dl = $parentNode->appendChild($dl);
            }

            $dl->appendChild($dt);

            $man->resetFonts();

            Indentation::set($dd, $indentVal);
            $dd = $dl->appendChild($dd);

            /* @var DomElement $dd */
            return $dd;
        } else {
            $man->resetFonts();


            $div = $dom->createElement('div');
            $div->setAttribute('remap', 'IP');
            if (!$indentVal) {
                // Resetting indentation, exit dd
                $parentNode = Blocks::getBlockContainerParent($parentNode, true);
            } elseif ($parentNode->tagName !== 'dd' || Indentation::get($parentNode) !== (float)$indentVal) {
                Indentation::set($div, $indentVal);
            }
            /* @var DomElement $div */
            $div = $parentNode->appendChild($div);

            return $div;
        }
    }

}
