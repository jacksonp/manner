<?php


class HybridNode extends DOMElement
{

    public $manLines = [];

    function __construct($name, $value = null)
    {
        parent::__construct($name, $value);
    }

    function addManLine ($line) {
        $this->manLines[] = $line;
    }


}
