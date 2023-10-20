<?php

declare(strict_types=1);

namespace Manner;

use Closure;
use DOMElement;
use Exception;
use Manner\Roff\Glyph;
use Manner\Roff\Register;
use Manner\Roff\Unit;

/**
 * Class Man - Singleton
 */
class Man
{

    private array $data;
    private array $postOutputCallbacks;
    private array $fontStack;
    private array $aliases;
    private array $macros;
    private array $entities;
    private array $registers;
    private array $strings;
    private array $characterTranslations; // .tr

    private array $roffClasses;
    private array $blockClasses;
    private array $inlineClasses;

    /**
     * @var Man|null The reference to *Singleton* instance of this class
     */
    private static ?Man $instance = null;

    /**
     * Returns the *Singleton* instance of this class.
     *
     * @return Man The *Singleton* instance.
     */
    public static function instance(): Man
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    protected function __construct()
    {
    }

    public function reset(): void
    {
        $this->data                = [
          'indentation'    => Indentation::DEFAULT,
          'escape_char'    => '\\',
          'control_char'   => '.',
          'control_char_2' => '\'',
          'eq_delim_left'  => null,
          'eq_delim_right' => null,
          'title'          => null,
          'section'        => null,
          'extra1'         => null,
          'extra2'         => null,
          'extra3'         => null,
        ];
        $this->postOutputCallbacks = [];
        $this->resetFonts();
        $this->aliases  = [];
        $this->macros   = [];
        $this->entities = [];
        // See https://www.mankier.com/7/groff#Registers
        $this->registers             = [
          '.g'       => '1',
            //The current font family (string-valued).
          '.fam'     => 'R',
            // Used by openpbs to specify -ms formatting (could remove and use 0 as fallback for undefined registers maybe):
          'Pb'       => '0',
          'BD'       => '0',
            // F register != 0 used to signal we should generate index entries. See e.g. frogatto.6
          'F'        => '0',
            // Current indentation.
          '.i'       => '0',
            // current line length
          '.l'       => '70',
          '.v'       => '1',
          '.H'       => '1500',
          '.V'       => '1500',
          'x'        => '0',
            // initial value, may get set once we have all actions in one loop, see e.g. nslcd.8
          'year'     => date('Y'),
          'yr'       => date('Y') - 1900,
            // See how .RS and .RE use this in an-old.tmac:
          'an-level' => '1',
        ];
        $this->strings               = [
            // "The name of the current output device as specified by the -T command line option" (ps is default)
          '.T' => 'ps',
            // https://www.mankier.com/7/groff_man#Miscellaneous
            // The ‘registered’ sign.
          'R'  => '®',
            // Switch back to the default font size.
          'S'  => '',
            // Left and right quote. This is equal to ‘\(lq’ and ‘\(rq\[cq], respectively.
          'lq' => '“',
          'rq' => '”',
            // The typeface used to print headings and subheadings. The default is ‘B’.
          'HF' => 'B',
            // The ‘trademark’ sign.
          'Tm' => '™',
        ];
        $this->characterTranslations = [];

        $this->roffClasses = [
          'am'     => '\Manner\Roff\am',
          'cc'     => '\Manner\Roff\cc',
          'ec'     => '\Manner\Roff\ec',
          'eo'     => '\Manner\Roff\eo',
          'do'     => '\Manner\Roff\doRequest',
          'nop'    => '\Manner\Roff\nop',
          'char'   => '\Manner\Roff\Char',
          'if'     => '\Manner\Roff\Condition',
          'ie'     => '\Manner\Roff\Condition',
          'while'  => '\Manner\Roff\Loop',
          'de'     => '\Manner\Roff\de',
          'de1'    => '\Manner\Roff\de',
          'di'     => '\Manner\Roff\di',
          'rr'     => '\Manner\Roff\Register',
          'nr'     => '\Manner\Roff\Register',
          'ds'     => '\Manner\Roff\StringRequest',
          'ds1'    => '\Manner\Roff\StringRequest',
          'as'     => '\Manner\Roff\asRequest',
          'as1'    => '\Manner\Roff\asRequest',
          'als'    => '\Manner\Roff\Alias',
          'tr'     => '\Manner\Roff\Translation',
          'rn'     => '\Manner\Roff\Rename',
          'return' => '\Manner\Roff\returnRequest',
        ];

        $this->blockClasses = [
          'SH' => '\Manner\Block\Section',
          'SS' => '\Manner\Block\Section',
          'P'  => '\Manner\Block\P',
          'LP' => '\Manner\Block\P',
          'PP' => '\Manner\Block\P',
          'HP' => '\Manner\Block\P',
          'IP' => '\Manner\Block\IP',
          'TP' => '\Manner\Block\TP',
          'TQ' => '\Manner\Block\TP',
          'ti' => '\Manner\Block\ti',
          'RS' => '\Manner\Block\RS',
          'RE' => '\Manner\Block\RE',
          'fc' => '\Manner\Block\fc',
          'ce' => '\Manner\Block\ce',
          'EX' => '\Manner\Block\Preformatted',
          'EE' => '\Manner\Block\EndPreformatted',
          'Vb' => '\Manner\Block\Preformatted',
          'Ve' => '\Manner\Block\EndPreformatted',
          'nf' => '\Manner\Block\Preformatted',
          'fi' => '\Manner\Block\EndPreformatted',
          'SY' => '\Manner\Block\SY',
          'YS' => '\Manner\Block\EndPreformatted',
          'ad' => '\Manner\Block\ad', // like \Manner\Block\EndPreformatted
          'TS' => '\Manner\Block\TS',
          'TH' => '\Manner\Block\TH',
        ];

        $this->inlineClasses = [
          'URL' => '\Manner\Inline\URL',
          'MTO' => '\Manner\Inline\URL',
          'MR'  => '\Manner\Inline\MR',
          'UR'  => '\Manner\Inline\Link',
          'UE'  => '\Manner\Inline\LinkEnd',
          'MT'  => '\Manner\Inline\Link',
          'ME'  => '\Manner\Inline\LinkEnd',
          'R'   => '\Manner\Inline\FontOneInputLine',
          'I'   => '\Manner\Inline\FontOneInputLine',
          'B'   => '\Manner\Inline\FontOneInputLine',
          'SB'  => '\Manner\Inline\FontOneInputLine',
          'SM'  => '\Manner\Inline\FontOneInputLine',
          'BI'  => '\Manner\Inline\AlternatingFont',
          'BR'  => '\Manner\Inline\AlternatingFont',
          'IB'  => '\Manner\Inline\AlternatingFont',
          'IR'  => '\Manner\Inline\AlternatingFont',
          'RB'  => '\Manner\Inline\AlternatingFont',
          'RI'  => '\Manner\Inline\AlternatingFont',
          'ft'  => '\Manner\Inline\ft',
          'br'  => '\Manner\Inline\VerticalSpace',
          'sp'  => '\Manner\Inline\VerticalSpace',
          'ne'  => '\Manner\Inline\VerticalSpace',
          'EQ'  => '\Manner\Inline\EQ',
          'PS'  => '\Manner\Inline\PS',
          'OP'  => '\Manner\Inline\OP',
        ];
    }

    /**
     * @param $name
     * @param $value
     * @throws Exception
     */
    public function __set($name, $value)
    {
        if (!array_key_exists($name, $this->data)) {
            throw new Exception('Unexpected property in Man: ' . $name);
        }

        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        return null;
    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function addPostOutputCallback(Closure $string): void
    {
        $this->postOutputCallbacks[] = $string;
    }

    public function runPostOutputCallbacks(): ?DOMElement
    {
        $return = null;
        while ($cb = array_pop($this->postOutputCallbacks)) {
            $return = $cb();
        }

        return $return;
    }

    public function hasPostOutputCallbacks(): bool
    {
        return count($this->postOutputCallbacks) > 0;
    }

    public function pushFont(string $name): int
    {
        return array_push($this->fontStack, $name);
    }

    public function popFont(?int $newCount = null): ?string
    {
        if (is_null($newCount) || $newCount < 0) {
            return array_pop($this->fontStack);
        } else {
            $font = array_pop($this->fontStack);
            while (count($this->fontStack) > $newCount) {
                $font = array_pop($this->fontStack);
            }

            return $font;
        }
    }

    public function resetFonts(): void
    {
        $this->fontStack = [];
    }

    public function isFontSmall(): bool
    {
        foreach ($this->fontStack as $font) {
            if (in_array($font, ['SM', 'SB'])) {
                return true;
            }
        }

        return false;
    }

    public function getFonts(): array
    {
        return $this->fontStack;
    }

    public function addAlias(string $original, string $alias): void
    {
        $this->aliases[$original] = $alias;
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }

    public function addMacro(string $name, array $lines): void
    {
        $this->macros[$name] = $lines;
    }

    public function getMacros(): array
    {
        return $this->macros;
    }

    /**
     * @throws Exception
     */
    public function getRegister(string $name): string
    {
        if (!$this->issetRegister($name)) {
            throw new Exception('Requested invalid register: ' . $name);
        }
        return $this->registers[$name];
    }

    public function setRegister(string $name, string $value): void
    {
        $this->registers[$name] = $value;
    }

    public function unsetRegister(string $name): void
    {
        unset($this->registers[$name]);
    }

    public function issetRegister(string $name): bool
    {
        return array_key_exists($name, $this->registers);
    }

    public function addString(string $string, string $value): void
    {
        $this->strings[$string] = $value;
    }

    public function getString(string $name): string
    {
        return $this->strings[$name] ?: '';
    }

    public function getStrings(): array
    {
        return $this->strings;
    }

    public function setEntity(string $from, string $to): void
    {
        $this->entities[$from] = $to;
    }

    public function setCharTranslation(string $from, string $to): void
    {
        $this->characterTranslations[$from] = $to;
    }

    public function applyCharTranslations($line)
    {
        if (count($this->characterTranslations) > 0) {
            $line = Replace::preg(
              array_map(
                function ($c) {
                    return '~(?<!\\\\)' . preg_quote($c, '~') . '~u';
                },
                array_keys($this->characterTranslations)
              ),
              $this->characterTranslations,
              $line
            );
        }

        return $line;
    }

    public function applyAllReplacements(string $line): string
    {
        $line = Register::substitute($line, $this->registers);

        if (count($this->entities)) {
            $line = strtr($line, $this->entities);
        }

        // \w’string’: The width of the glyph sequence string.
        // We approximate with 2.4 char on average per em. See: http://maxdesign.com.au/articles/ideal-line-length-in-ems
        $line = Replace::pregCallback(
          '~(?<!\\\\)(?:\\\\\\\\)*\\\\w\'(.*?)\'~u',
          function ($matches) {
              $string    = Glyph::substitute($matches[1]);
              $approxEms = mb_strlen(TextContent::interpretString($string)) / 2.4;

              return Unit::normalize((string)$approxEms, 'm', 'u');
          },
          $line
        );

        // Don't worry about this:
        // \v, \h: "Local vertical/horizontal motion"
        // \l: Horizontal line drawing function (optionally using character c).
        // \L: Vertical line drawing function (optionally using character c).
        // \D: The \D escape provides a variety of drawing function
        // \Z: Print anything, then restore the horizontal and vertical position.
        $line = Replace::preg('~(?<!\\\\)((?:\\\\\\\\)*)\\\\Z@.*?@~u', ' ', $line);

        // $line = Replace::preg('~(?<!\\\\)((?:\\\\\\\\)*)\\\\[vhLl]\'.*?\'~u', '$1 ', $line);

        return Replace::preg('~(?<!\\\\)((?:\\\\\\\\)*)\\\\[vhLlD]\'.*?\'~u', ' ', $line);
    }

    public function requestStartsBlock(string $requestName): bool
    {
        return array_key_exists($requestName, $this->blockClasses);
    }

    public function getRequestClass(string $requestName): ?string
    {
        if ($this->requestStartsBlock($requestName)) {
            return $this->blockClasses[$requestName];
        } elseif (array_key_exists($requestName, $this->inlineClasses)) {
            return $this->inlineClasses[$requestName];
        } else {
            return null;
        }
    }

    public function getRoffRequestClass(string $requestName): ?string
    {
        if (array_key_exists($requestName, $this->roffClasses)) {
            return $this->roffClasses[$requestName];
        } else {
            return null;
        }
    }

    public function resetIndentationToDefault(): void
    {
        $this->data['indentation'] = Indentation::DEFAULT;
    }

}
