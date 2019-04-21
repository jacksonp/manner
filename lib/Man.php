<?php
declare(strict_types=1);

/**
 * Class Man - Singleton
 */
class Man
{

    private $data;
    private $postOutputCallbacks;
    private $fontStack;
    private $aliases;
    private $macros;
    private $entities;
    private $registers;
    private $strings;
    private $characterTranslations; // .tr

    private $roffClasses;
    private $blockClasses;
    private $inlineClasses;

    /**
     * @var Man The reference to *Singleton* instance of this class
     */
    private static $instance;

    /**
     * Returns the *Singleton* instance of this class.
     *
     * @return Man The *Singleton* instance.
     */
    public static function instance()
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

    public function reset()
    {
        $this->data                = [
          'indentation'       => Indentation::DEFAULT,
          'left_margin_level' => 1, // The first level (i.e., no call to .RS yet) has number 1.
          'escape_char'       => '\\',
          'control_char'      => '.',
          'control_char_2'    => '\'',
          'eq_delim_left'     => null,
          'eq_delim_right'    => null,
          'title'             => null,
          'section'           => null,
          'extra1'            => null,
          'extra2'            => null,
          'extra3'            => null,
        ];
        $this->postOutputCallbacks = [];
        $this->resetFonts();
        $this->aliases  = [];
        $this->macros   = [];
        $this->entities = [];
        // See https://www.mankier.com/7/groff#Registers
        $this->registers             = [
          '.g'   => '1',
            //The current font family (string-valued).
          '.fam' => 'R',
            // Used by openpbs to specify -ms formatting (could remove and use 0 as fallback for undefined registers maybe):
          'Pb'   => '0',
          'BD'   => '0',
            // F register != 0 used to signal we should generate index entries. See e.g. frogatto.6
          'F'    => '0',
            // Current indentation.
          '.i'   => '0',
            // current line length
          '.l'   => '70',
          '.v'   => '1',
          '.H'   => '1500',
          '.V'   => '1500',
          'x'    => '0',
            // initial value, may get set once we have all actions in one loop, see e.g. nslcd.8
          'year' => date('Y'),
          'yr'   => date('Y') - 1900,
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
          'am'     => 'Roff_am',
          'cc'     => 'Roff_cc',
          'ec'     => 'Roff_ec',
          'eo'     => 'Roff_eo',
          'do'     => 'Roff_do',
          'nop'    => 'Roff_nop',
          'char'   => 'Roff_Char',
          'if'     => 'Roff_Condition',
          'ie'     => 'Roff_Condition',
          'while'  => 'Roff_Loop',
          'de'     => 'Roff_de',
          'de1'    => 'Roff_de',
          'di'     => 'Roff_di',
          'rr'     => 'Roff_Register',
          'nr'     => 'Roff_Register',
          'ds'     => 'Roff_String',
          'ds1'    => 'Roff_String',
          'as'     => 'Roff_as',
          'as1'    => 'Roff_as',
          'als'    => 'Roff_Alias',
          'tr'     => 'Roff_Translation',
          'rn'     => 'Roff_Rename',
          'return' => 'Roff_return',
        ];

        $this->blockClasses = [
          'SH' => 'Block_Section',
          'SS' => 'Block_Section',
          'P'  => 'Block_P',
          'LP' => 'Block_P',
          'PP' => 'Block_P',
          'HP' => 'Block_P',
          'IP' => 'Block_IP',
          'TP' => 'Block_TP',
          'TQ' => 'Block_TP',
          'ti' => 'Block_ti',
          'RS' => 'Block_RS',
          'RE' => 'Block_RE',
          'fc' => 'Block_fc',
          'ce' => 'Block_ce',
          'EX' => 'Block_Preformatted',
          'EE' => 'Block_EndPreformatted',
          'Vb' => 'Block_Preformatted',
          'Ve' => 'Block_EndPreformatted',
          'nf' => 'Block_Preformatted',
          'fi' => 'Block_EndPreformatted',
          'SY' => 'Block_SY',
          'YS' => 'Block_EndPreformatted',
          'ad' => 'Block_ad', // like Block_EndPreformatted
          'TS' => 'Block_TS',
          'TH' => 'Block_TH',
        ];

        $this->inlineClasses = [
          'URL' => 'Inline_URL',
          'MTO' => 'Inline_URL',
          'UR'  => 'Inline_Link',
          'UE'  => 'Inline_LinkEnd',
          'MT'  => 'Inline_Link',
          'ME'  => 'Inline_LinkEnd',
          'R'   => 'Inline_FontOneInputLine',
          'I'   => 'Inline_FontOneInputLine',
          'B'   => 'Inline_FontOneInputLine',
          'SB'  => 'Inline_FontOneInputLine',
          'SM'  => 'Inline_FontOneInputLine',
          'BI'  => 'Inline_AlternatingFont',
          'BR'  => 'Inline_AlternatingFont',
          'IB'  => 'Inline_AlternatingFont',
          'IR'  => 'Inline_AlternatingFont',
          'RB'  => 'Inline_AlternatingFont',
          'RI'  => 'Inline_AlternatingFont',
          'ft'  => 'Inline_ft',
          'br'  => 'Inline_VerticalSpace',
          'sp'  => 'Inline_VerticalSpace',
          'ne'  => 'Inline_VerticalSpace',
          'EQ'  => 'Inline_EQ',
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

    public function addPostOutputCallback(Closure $string)
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

    public function currentFont(): ?string
    {
        return count($this->fontStack) ? end($this->fontStack) : null;
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

    public function addAlias(string $original, string $alias)
    {
        $this->aliases[$original] = $alias;
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }

    public function addMacro(string $name, array $lines)
    {
        $this->macros[$name] = $lines;
    }

    public function getMacros(): array
    {
        return $this->macros;
    }

    public function setRegister(string $name, string $value): void
    {
        $this->registers[$name] = $value;
    }

    public function unsetRegister(string $name)
    {
        unset($this->registers[$name]);
    }

    public function getRegisters(): array
    {
        return $this->registers;
    }

    public function issetRegister(string $name): bool
    {
        return array_key_exists($name, $this->registers);
    }

    public function addString(string $string, string $value)
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

    public function setEntity(string $from, string $to)
    {
        $this->entities[$from] = $to;
    }

    public function setCharTranslation(string $from, string $to)
    {
        $this->characterTranslations[$from] = $to;
    }

    public function getCharTranslations()
    {
        return $this->characterTranslations;
    }

    public function applyCharTranslations($line)
    {
        if (count($this->characterTranslations) > 0) {
            $line = Replace::preg(array_map(function ($c) {
                return '~(?<!\\\\)' . preg_quote($c, '~') . '~u';
            }, array_keys($this->characterTranslations)), $this->characterTranslations, $line);
        }

        return $line;
    }

    public function applyAllReplacements(string $line): string
    {

        $line = Roff_Register::substitute($line, $this->registers);

        if (count($this->entities)) {
            $line = strtr($line, $this->entities);
        }

        // \w’string’: The width of the glyph sequence string.
        // We approximate with 2.4 char on average per em. See: http://maxdesign.com.au/articles/ideal-line-length-in-ems
        $line = Replace::pregCallback('~(?<!\\\\)(?:\\\\\\\\)*\\\\w\'(.*?)\'~u', function ($matches) {
            $string    = Roff_Glyph::substitute($matches[1]);
            $approxEms = mb_strlen(TextContent::interpretString($string)) / 2.4;

            return Roff_Unit::normalize((string)$approxEms, 'm', 'u');
        }, $line);

        // Don't worry about this:
        // \v, \h: "Local vertical/horizontal motion"
        // \l: Horizontal line drawing function (optionally using character c).
        // \L: Vertical line drawing function (optionally using character c).
        $line = Replace::preg('~(?<!\\\\)((?:\\\\\\\\)*)\\\\[vhLl]\'.*?\'~u', ' ', $line);

        // $line = Replace::preg('~(?<!\\\\)((?:\\\\\\\\)*)\\\\[vhLl]\'.*?\'~u', '$1 ', $line);

        return $line;
    }

    public function requestStartsBlock(string $requestName)
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

    public function resetIndentationToDefault()
    {
        $this->data['indentation'] = Indentation::DEFAULT;
    }

}
