<?php
declare(strict_types = 1);

interface Block_Template
{

    /**
     *
     * @param DOMElement $parentNode
     * @param array $lines
     * @param array|null $request
     * @param bool $needOneLineOnly
     * @return DOMElement|null The new parent node to use for following elements, or null we don't want to change that.
     */
    static function checkAppend(
        DOMElement $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement;

}
