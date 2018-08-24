<?php
declare(strict_types=1);

class Block_TH implements Block_Template
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

        array_shift($lines);

        $man = Man::instance();

        $body = Node::ancestor($parentNode, 'body');

        if (empty($man->title)) {

            if (count($request['arguments']) < 1) {
                throw new Exception($request['raw_line'] . ' - missing title info');
            }

            foreach ($request['arguments'] as $k => $v) {
                // See amor.6 for \FB \FR nonsense.
                $value = Replace::preg('~\\\\F[BR]~', '', $v);
                $value = TextContent::interpretString($value);
                // Fix vnu's "Saw U+0000 in stream" e.g. in lvmsadc.8:
                $value                    = trim($value);
                $request['arguments'][$k] = $value;
            }

            $man->title = $request['arguments'][0];
            if (count($request['arguments']) > 1) {
                $man->section = $request['arguments'][1];
                $man->extra1  = @$request['arguments'][2] ?: '';
                $man->extra2  = @$request['arguments'][3] ?: '';
                $man->extra3  = @$request['arguments'][4] ?: '';
            }

            $h1 = $body->ownerDocument->createElement('h1');
            $h1->appendChild(new DOMText($man->title));
            $body->appendChild($h1);

        } elseif (count($request['arguments'])) {
            // Some pages  have multiple .THs for different commands in one page, just had a horizontal line when we hit
            // .THs with content after the first
            $hr = $body->ownerDocument->createElement('hr');
            $body->appendChild($hr);
        }

        return $body;

    }

}
