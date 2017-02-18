<?php
declare(strict_types = 1);

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
