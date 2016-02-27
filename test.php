<?php

class myDOMElement extends DOMElement
{

    public $myProp = 'Some default';
}

$doc = new DOMDocument();
$doc->registerNodeClass('DOMElement', 'myDOMElement');

$node         = $doc->createElement('a');
$node->myProp = 'A';
$doc->appendChild($node);

$node         = $doc->createElement('b');
$node->myProp = 'B';
$doc->appendChild($node);

$nodeC         = $doc->createElement('c');
$nodeC->myProp = 'C';
$doc->appendChild($nodeC);

foreach ($doc->childNodes as $n) {
    echo 'Tag ', $n->tagName, ' myProp: ';
    var_dump($n->myProp);
}


exit;

/*
class myDOMElement extends DOMElement
{

    public $anArray = [];

    function __construct($name, $value = null)
    {
        parent::__construct($name, $value);
    }

    function addToMyArray($str)
    {
        $this->anArray[] = $str;
    }

}

$doc = new DOMDocument();
$doc->registerNodeClass('DOMElement', 'myDOMElement');

$node = $doc->createElement('p');
$node->addToMyArray('A');
$node = $doc->appendChild($node);

var_dump($node->anArray);

$node = $doc->createElement('p');
$node->addToMyArray('B');
$node = $doc->appendChild($node);

var_dump($node->anArray);

foreach ($doc->childNodes as $node) {
    var_dump($node->anArray);
}


exit;

$nodeB->setAttribute('class', 'section');

var_dump($nodeB->anArray);
var_dump($nodeC->anArray);

$nodeB = $doc->appendChild($nodeB);

var_dump($nodeB->anArray);
var_dump($nodeC->anArray);

$nodeB->appendChild($doc->createElement('a', 'blah'));

var_dump($nodeB->anArray);
var_dump($nodeC->anArray);

$nodeB->addToMyArray('B2');
$nodeC->addToMyArray('C2');

var_dump($nodeB->anArray);
var_dump($nodeC->anArray);
*/
