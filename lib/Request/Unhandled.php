<?php


class Request_Unhandled implements Block_Template
{

    // Unhandled:
    const requests = [
        'ab',
        'ad',
        'aln',
        'am',
        'am1',
        'ami',
        'ami1',
        'as',
        'as1',
        'asciify',
        'backtrace',
        'blm',
        'box',
        'boxa',
        'brp',
        'break',
        'c2',
        'cc',
        'cf',
        'cflags',
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
        'di',
        'do',
        'dt',
        'ec',
        'ecr',
        'ecs',
        'fc',
        'ftr',
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
        'ti',
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
        'while',
        'write',
        'writec',
        'writem',
    ];

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        ?array $request = null,
        $needOneLineOnly = false
    ): bool {
        throw new exception('Unhandled request ' . $request['raw_line']);
    }

}
