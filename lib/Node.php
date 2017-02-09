<?php
declare(strict_types = 1);

class Node
{

    static function addClass(DOMElement $node, string $className): void
    {
        $existingClassString = $node->getAttribute('class');
        if (!in_array($className, explode(' ', $existingClassString))) {
            $node->setAttribute('class', trim($existingClassString . ' ' . $className));
        }

    }

}
