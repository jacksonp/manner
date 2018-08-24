<?php
declare(strict_types=1);

/**
 * Class Block_TP
 *
 * .de1 TP
 * .  sp \\n[PD]u
 * .  if \\n[.$] .nr an-prevailing-indent (n;\\$1)
 * .  it 1 an-trap
 * .  in 0
 * .  if !\\n[an-div?] \{\
 * .    ll -\\n[an-margin]u
 * .    di an-div
 * .  \}
 * .  nr an-div? 1
 * ..
 *
 * .\" Continuation line for .TP header.
 * .de TQ
 * .  br
 * .  ns
 * .  TP \\$1\" no doublequotes around argument!
 * ..
 *
 */
class Block_TP implements Block_Template
{

    /**
     * @param DOMElement $parentNode
     * @param array $lines
     * @param array $request
     * @param bool $needOneLineOnly
     * @return DOMElement|null
     * @throws Exception
     */
    static function checkAppend(
        DOMElement $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        if (count($lines) > 1 && $lines[1] === '.nf') {
            // Switch .TP and .nf around, and try again. See e.g. elasticdump.1
            $lines[1] = $lines[0];
            $lines[0] = '.nf';
            return null;
        }

        array_shift($lines);

        if (count($lines) && $lines[0] === '\\&') {
            if (count($request['arguments'])) {
                $lines[0] = '.IP "" ' . $request['arguments'][0];
            } else {
                $lines[0] = '.IP';
            }
            return null;
        }

        $dom = $parentNode->ownerDocument;
        $man = Man::instance();

        $blockContainerParentNode = Blocks::getBlockContainerParent($parentNode);

        if (count($request['arguments'])) {
            $indentVal        = Roff_Unit::normalize($request['arguments'][0], 'n', 'n');
            if (is_numeric($indentVal)) {
                $man->indentation = $indentVal;
            } else {
                $indentVal = $man->indentation;
            }
        } else {
            $indentVal = $man->indentation;
        }

        $dl = Block_DefinitionList::getParentDL($blockContainerParentNode);

        if (is_null($dl)) {
            $dl = $dom->createElement('dl');
            $dl = $blockContainerParentNode->appendChild($dl);
        }

        /* @var DomElement $dt */
        $dt         = $dom->createElement('dt');
        $dt         = $dl->appendChild($dt);
        $gotContent = Roff::parse($dt, $lines, true);
        if (!$gotContent) {
            $dl->removeChild($dt);
            return null;
        }

        while (count($lines)) {
            $request = Request::getLine($lines);
            if ($request['request'] === 'TQ') {
                array_shift($lines);
                if (count($request['arguments'])) {
                    $indentVal        = Roff_Unit::normalize($request['arguments'][0], 'n', 'n');
                    $man->indentation = $indentVal;
                }
                $dt = $dom->createElement('dt');
                $dl->appendChild($dt);
                $gotContent = Roff::parse($dt, $lines, true);
                if (!$gotContent) {
                    $dl->removeChild($dt);
                }
            } else {
                break;
            }
        }

        $man->resetFonts();

        /* @var DomElement $dd */
        $dd = $dom->createElement('dd');
        Indentation::set($dd, $indentVal);
        $dd = $dl->appendChild($dd);

        return $dd;

    }

}
