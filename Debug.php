<?php


class Debug
{

    static function echoTidy($html) {
        $tidy = tidy_parse_string($html, [
          'hide-comments'       => true,
          'tidy-mark'           => false,
          'indent'              => true,
          'indent-spaces'       => 2,
          'hide-endtags'        => true,
          'new-blocklevel-tags' => 'article,header,footer,section,nav',
          'new-inline-tags'     => 'video,audio,canvas,ruby,rt,rp',
          'new-empty-tags'      => 'source',
          'doctype'             => '<!DOCTYPE HTML>',
          'sort-attributes'     => 'alpha',
          'vertical-space'      => false,
          'output-xhtml'        => false,
          'output-html'         => true,
          'wrap'                => 160,
          'wrap-attributes'     => false,
          'break-before-br'     => false,
          'quote-nbsp'          => false,
          'anchor-as-name'      => false,
          'show-body-only'      => true,
        ], 'UTF8');

        $tidy->cleanRepair();
        echo $tidy;
    }


}
