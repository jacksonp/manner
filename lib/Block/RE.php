<?php
declare(strict_types=1);

/**
 * .RE [nnn]
 * This macro moves the left margin back to level nnn, restoring the previous left margin. If no argument is given, it
 * moves one level back. The first level (i.e., no call to .RS yet) has number 1, and each call to .RS increases the
 * level by 1.
 *
 */
class Block_RE implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);


        if (count($request['arguments']) && $request['arguments'][0] === '1') {
            // 1 is back to base level, handle that specially as it is used e.g. in lsmcli.1
            return $parentNode->ancestor('section');
        }

        $lastIndentedBlock = $parentNode->ancestor('div');
        if (is_null($lastIndentedBlock)) {
            // Some pages use this get out of .IP, .TP:
            $lastIndentedBlock = $parentNode->ancestor('dl');
        }

        if (is_null($lastIndentedBlock)) {
            return null;
        } else {
            return $lastIndentedBlock->parentNode;
        }

    }

}
