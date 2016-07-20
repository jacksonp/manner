<?php


class Inline_AlternatingFont
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i, $arguments, $request)
    {

        if (is_null($arguments) or count($arguments) === 0) {
            return $i; // Just skip empty requests
        }

        list ($textParent, $shouldAppend) = Blocks::getTextParent($parentNode);

        $dom = $parentNode->ownerDocument;

        Block_Text::addSpace($parentNode, $textParent, $shouldAppend);

        foreach ($arguments as $bi => $bit) {
            $requestCharIndex = $bi % 2;
            if (!isset($request[$requestCharIndex])) {
                throw new Exception($lines[$i] . ' command ' . $request . ' has nothing at index ' . $requestCharIndex);
            }
            if (trim($bit) === '') {
                TextContent::interpretAndAppendText($textParent, $bit);
                continue;
            }
            switch ($request[$requestCharIndex]) {
                case 'R':
                    TextContent::interpretAndAppendText($textParent, $bit);
                    break;
                case 'B':
                    $strongNode = $dom->createElement('strong');
                    TextContent::interpretAndAppendText($strongNode, $bit);
                    if ($strongNode->hasContent()) {
                        $textParent->appendChild($strongNode);
                    }
                    break;
                case 'I':
                    $emNode = $dom->createElement('em');
                    TextContent::interpretAndAppendText($emNode, $bit);
                    if ($emNode->hasContent()) {
                        $textParent->appendChild($emNode);
                    }
                    break;
                default:
                    throw new Exception($lines[$i] . ' command ' . $request . ' unexpected character at index ' . $requestCharIndex);
            }
        }

        if ($shouldAppend) {
            $parentNode->appendBlockIfHasContent($textParent);
        }

        return $i;

    }

}
