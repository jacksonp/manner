<?php
declare(strict_types = 1);

class Request_Unhandled implements Block_Template
{

    // Unhandled:
    const requests = [
        'ab',
        'aln',
        'am1',
        'ami',
        'ami1',
        'asciify',
        'backtrace',
        'blm',
        'box',
        'boxa',
        'brp',
        'break',
        'c2',
        'cf',
        'ch',
        'chop',
        'class',
        'color',
        'composite',
        'continue',
        'da',
        'dei',
        'dei1',
        'device',
        'devicem',
        'do',
        'dt',
        'ecr',
        'ecs',
        'fc',
        'fzoom',
        'gcolor',
        'hcode',
        'hla',
        'hlm',
        'hpf',
        'hpfa',
        'hpfcode',
        'kern',
        'lc', // leader repetition glyph.
        'length',
        'linetabs',
        'lg',
        'lsm',
        'ls',
        'ne',
        'nf',
        'nm',
        'nn',
        'nroff',
        'nx',
        'open',
        'opena',
        'os',
        'output',
        'pev',
        'pi',
        'pm',
        'pn',
        'pnr',
        'psbb',
        'pso',
        'ptr',
        'pvs',
        'rd',
        'return',
        'rn',
        'rnn',
        'rr',
        'shc',
        'sizes',
        'special',
        'spreadwarn',
        'sty',
        'substring',
        'sv',
        'sy',
        'tc',
        'tkf',
        'tl',
        'trf',
        'trin',
        'trnt',
        'troff',
        'uf',
        'unformat',
        'vpt',
        'warnscale',
        'wh',
        'write',
        'writec',
        'writem',
    ];

    /**
     * @param DOMElement $parentNode
     * @param array $lines
     * @param array $request
     * @param bool $needOneLineOnly
     * @return DOMElement|null
     * @throws exception
     */
    static function checkAppend(
        DOMElement $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement {
        throw new exception('Unhandled request ' . $request['raw_line']);
    }

}
