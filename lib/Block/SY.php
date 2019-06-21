<?php
declare(strict_types=1);

class Block_SY implements Block_Template
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
    ): ?DOMElement {

        $dom        = $parentNode->ownerDocument;
        $parentNode = Blocks::getBlockContainerParent($parentNode, true);

        $foundEnd = false;

        $syRows          = [];
        $syLines         = [];
        $lastCommandName = '';
        $firstLine       = true;
        while ($request = Request::getLine($lines)) {
            if (in_array($request['request'], ['SH', 'SS'])) {
                $foundEnd = true;
                // no array_shift(): we need this line for later
                $syRows[] = ['cmd_name' => $lastCommandName, 'sy_lines' => $syLines];
                break;
            }
            array_shift($lines);
            if ($request['request'] === 'SY') {
                $lastCommandName = count($request['arguments']) ? $request['arguments'][0] : '';
                if (!$firstLine) { // Don't do this on first .SY
                    $syRows[] = ['cmd_name' => $lastCommandName, 'sy_lines' => $syLines];
                }
                $syLines = [];
            } elseif ($request['request'] === 'YS') {
                $syRows[] = ['cmd_name' => $lastCommandName, 'sy_lines' => $syLines];
                $foundEnd = true;
                break;
            } else {
                $syLines[] = $request['raw_line'];
            }
            $firstLine = false;
        }

        if (!$foundEnd) {
            throw new Exception('SY not followed by YS, SH, or SS.');
        }

        $table = $dom->createElement('table');
        $table->setAttribute('class', 'synopsis');

        foreach ($syRows as $syRow) {
            $tr            = $table->appendChild($dom->createElement('tr'));
            $tdCommandName = $tr->appendChild($dom->createElement('td'));

            if ($syRow['cmd_name'] !== '') {
                $syRow['cmd_name'] = trim(TextContent::interpretString($syRow['cmd_name']));
                $tdCommandName->appendChild(new DOMText($syRow['cmd_name']));
            }

            /* @var DomElement $tdOptions */
            $tdOptions = $tr->appendChild($dom->createElement('td'));

            Roff::parse($tdOptions, $syRow['sy_lines']);
        }

        $parentNode->appendChild($table);

        return $parentNode;

    }

}
