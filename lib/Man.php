<?php

/**
 * Class Man - Singleton
 */
class Man
{

    private $data;
    private $aliases;
    private $macros;
    private $registers;
    private $strings;

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
        $this->data      = [];
        $this->aliases   = [];
        $this->macros    = [];
        $this->registers = [
          '.g' => '1',
          'Pb' => '0', // Used by openpbs to specify -ms formatting
        ];
        $this->strings   = [
            // "The name of the current output device as specified by the -T command line option" (ps is default)
          '.T'             => 'ps',
            // Hack for .tr (see e.g. myproxy-replicate.8):
          'Tr'             => '☃',
            // Nordic
          '-D'             => 'Ð',
          'Sd'             => 'ð',
          'TP'             => 'Þ',
          'Tp'             => 'þ',
          'ss'             => 'ß',
            // Ligatures and Other Latin Glyph
          'ff'             => 'ff',
          'fi'             => 'fi',
          'fl'             => 'fl',
          'Fi'             => 'ffi',
          'Fl'             => 'ffl',
          '/L'             => 'Ł',
          '/l'             => 'ł',
          '/O'             => 'Ø',
          '/o'             => 'ø',
          'AE'             => 'Æ',
          'ae'             => 'æ',
          'OE'             => 'Œ',
          'oe'             => 'œ',
          'IJ'             => 'Ĳ',
          'ij'             => 'ĳ',
          '.i'             => 'ı',
          '.j'             => 'ȷ',
            // Accented Characters
          '\'A'            => 'Á',
          '\'C'            => 'Ć',
          '\'E'            => 'É',
          '\'I'            => 'Í',
          '\'O'            => 'Ó',
          '\'U'            => 'Ú',
          '\'Y'            => 'Ý',
          '\'a'            => 'á',
          '\'c'            => 'ć',
          '\'e'            => 'é',
          '\'i'            => 'í',
          '\'o'            => 'ó',
          '\'u'            => 'ú',
          '\'y'            => 'ý',
          ':A'             => 'Ä',
          ':E'             => 'Ë',
          ':I'             => 'Ï',
          ':O'             => 'Ö',
          ':U'             => 'Ü',
          ':Y'             => 'Ÿ',
          ':a'             => 'ä',
          ':e'             => 'ë',
          ':i'             => 'ï',
          ':o'             => 'ö',
          ':u'             => 'ü',
          ':y'             => 'ÿ',
          '^A'             => 'Â',
          '^E'             => 'Ê',
          '^I'             => 'Î',
          '^O'             => 'Ô',
          '^U'             => 'Û',
          '^a'             => 'â',
          '^e'             => 'ê',
          '^i'             => 'î',
          '^o'             => 'ô',
          '^u'             => 'û',
          '`A'             => 'À',
          '`E'             => 'È',
          '`I'             => 'Ì',
          '`O'             => 'Ò',
          '`U'             => 'Ù',
          '`a'             => 'à',
          '`e'             => 'è',
          '`i'             => 'ì',
          '`o'             => 'ò',
          '`u'             => 'ù',
          '~A'             => 'Ã',
          '~N'             => 'Ñ',
          '~O'             => 'Õ',
          '~a'             => 'ã',
          '~n'             => 'ñ',
          '~o'             => 'õ',
          'vS'             => 'Š',
          'vs'             => 'š',
          'vZ'             => 'Ž',
          'vz'             => 'ž',
          ',C'             => 'Ç',
          ',c'             => 'ç',
          'oA'             => 'Å',
          'oa'             => 'å',
            // Accents
          'a"'             => '˝',
          'a-'             => '¯',
          'a.'             => '˙',
          'a^'             => '^',
          'aa'             => '´',
          'ga'             => '`',
          'ab'             => '˘',
          'ac'             => '¸',
          'ad'             => '¨',
          'ah'             => 'ˇ',
          'ao'             => '˚',
          'a~'             => '~',
          'ho'             => '˛',
          'ha'             => '^',
          'ti'             => '~',
            // Quotes
          'Bq'             => '„',
          'bq'             => '‚',
          'lq'             => '“',
          'rq'             => '”',
          'oq'             => '‘',
          'cq'             => '’',
            // NB: we do 'aq' and 'dq' in  interpretString()
          'Fo'             => '«',
          'Fc'             => '»',
          'fo'             => '‹',
          'fc'             => '›',
            // Non-standard quotes
          'L"'             => '“',
          'R"'             => '”',
            // Punctuation
          'r!'             => '¡',
          'r?'             => '¿',
          'em'             => '—',
          'en'             => '–',
          'hy'             => '‐',
            // Brackets
          'lB'             => '[',
          'rB'             => ']',
          'lC'             => '{',
          'rC'             => '}',
          'la'             => '⟨',
          'ra'             => '⟩',
          'bv'             => '⎪',
          'braceex'        => '⎪',
          'bracketlefttp'  => '⎡',
          'bracketleftbt'  => '⎣',
          'bracketleftex'  => '⎢',
          'bracketrighttp' => '⎤',
          'bracketrightbt' => '⎦',
          'bracketrightex' => '⎥',
          'lt'             => '╭',
          'bracelefttp'    => '⎧',
          'lk'             => '┥',
          'braceleftmid'   => '⎨',
          'lb'             => '╰',
          'braceleftbt'    => '⎩',
          'braceleftex'    => '⎪',
          'rt'             => '╮',
          'bracerighttp'   => '⎫',
          'rk'             => '┝',
          'bracerightmid'  => '⎬',
          'rb'             => '╯',
          'bracerightbt'   => '⎭',
          'bracerightex'   => '⎪',
          'parenlefttp'    => '⎛',
          'parenleftbt'    => '⎝',
          'parenleftex'    => '⎜',
          'parenrighttp'   => '⎞',
          'parenrightbt'   => '⎠',
          'parenrightex'   => '⎟',
            // Arrows
          '<-'             => '←',
          '->'             => '→',
          '<>'             => '↔',
          'da'             => '↓',
          'ua'             => '↑',
          'va'             => '↕',
          'lA'             => '⇐',
          'rA'             => '⇒',
          'hA'             => '⇔',
          'dA'             => '⇓',
          'uA'             => '⇑',
          'vA'             => '⇕',
          'an'             => '⎯',
            // Lines
          'ba'             => '|',
          'br'             => '│',
          'ul'             => '_',
          'rn'             => '‾',
          'ru'             => '_',
          'bb'             => '¦',
          'sl'             => '/',
            // Note we don't do "rs" line until interpretString() to avoid problems with adding backslashes
            // Text Markers
          'ci'             => '○',
          'bu'             => '·',
          'dd'             => '‡',
          'dg'             => '†',
          'lz'             => '◊',
          'sq'             => '□',
          'ps'             => '¶',
          'sc'             => '§',
          'lh'             => '☜',
          'rh'             => '☞',
          'at'             => '@',
          'sh'             => '#',
          'CR'             => '↵',
          'OK'             => '✓',
            // Legal Symbols
          'co'             => '©',
          'rg'             => '™',
          'tm'             => '™',
          'bs'             => '☎',
            // Currency symbols
          'Do'             => '$',
          'ct'             => '¢',
          'eu'             => '€',
          'Eu'             => '€',
          'Ye'             => '¥',
          'Po'             => '£',
          'Cs'             => '¤',
          'Fn'             => 'ƒ',
            // Units
          'de'             => '°',
          '%0'             => '‰',
          'fm'             => '′',
          'sd'             => '″',
          'mc'             => 'µ',
          'Of'             => 'ª',
          'Om'             => 'º',
            // Logical Symbols
          'AN'             => '∧',
          'OR'             => '∨',
          'no'             => '¬',
          'tn'             => '¬',
          'te'             => '∃',
          'fa'             => '∀',
          'st'             => '∋',
          '3d'             => '∴',
          'tf'             => '∴',
          'or'             => '|',
            // Mathematical Symbols
          '12'             => '½',
          '14'             => '¼',
          '34'             => '¾',
          '18'             => '⅛',
          '38'             => '⅜',
          '58'             => '⅝',
          '78'             => '⅞',
          'S1'             => '¹',
          'S2'             => '²',
          'S3'             => '³',
          'pl'             => '+',
          'mi'             => '−',
          '-+'             => '∓',
          '+-'             => '±',
          'pc'             => '·',
          'md'             => '⋅',
          'mu'             => '×',
          'tmu'            => '×',
          'c*'             => '⊗',
          'c+'             => '⊕',
          'di'             => '÷',
          'tdi'            => '÷',
          'f/'             => '⁄',
          '**'             => '∗',
          '<='             => '≤',
          '>='             => '≥',
          '<<'             => '≪',
          '>>'             => '≫',
          'eq'             => '=',
          '!='             => '≠',
          '=='             => '≡',
          'ne'             => '≢',
          '=~'             => '≅',
          '|='             => '≃',
          'ap'             => '∼',
          '~~'             => '≈',
          '~='             => '≈',
          'pt'             => '∝',
          'es'             => '∅',
          'mo'             => '∈',
          'nm'             => '∉',
          'sb'             => '⊂',
          'nb'             => '⊄',
          'sp'             => '⊃',
          'nc'             => '⊅',
          'ib'             => '⊆',
          'ip'             => '⊇',
          'ca'             => '∩',
          'cu'             => '∪',
          '/_'             => '∠',
          'pp'             => '⊥',
          'is'             => '∫',
          'integral'       => '∫',
          'sum'            => '∑',
          'product'        => '∏',
          'coproduct'      => '∐',
          'gr'             => '∇',
          'sr'             => '√',
          'sqrt'           => '√',
          'lc'             => '⌈',
          'rc'             => '⌉',
          'lf'             => '⌊',
          'rf'             => '⌋',
          'if'             => '∞',
          'Ah'             => 'ℵ',
          'Im'             => 'ℑ',
          'Re'             => 'ℜ',
          'wp'             => '℘',
          'pd'             => '∂',
          '-h'             => 'ℏ',
          'hbar'           => 'ℏ',
            // Greek glyphs
          '*A'             => 'Α',
          '*B'             => 'Β',
          '*G'             => 'Γ',
          '*D'             => 'Δ',
          '*E'             => 'Ε',
          '*Z'             => 'Ζ',
          '*Y'             => 'Η',
          '*H'             => 'Θ',
          '*I'             => 'Ι',
          '*K'             => 'Κ',
          '*L'             => 'Λ',
          '*M'             => 'Μ',
          '*N'             => 'Ν',
          '*C'             => 'Ξ',
          '*O'             => 'Ο',
          '*P'             => 'Π',
          '*R'             => 'Ρ',
          '*S'             => 'Σ',
          '*T'             => 'Τ',
          '*U'             => 'Υ',
          '*F'             => 'Φ',
          '*X'             => 'Χ',
          '*Q'             => 'Ψ',
          '*W'             => 'Ω',
          '*a'             => 'α',
          '*b'             => 'β',
          '*g'             => 'γ',
          '*d'             => 'δ',
          '*e'             => 'ε',
          '*z'             => 'ζ',
          '*y'             => 'η',
          '*h'             => 'θ',
          '*i'             => 'ι',
          '*k'             => 'κ',
          '*l'             => 'λ',
          '*m'             => 'μ',
          '*n'             => 'ν',
          '*c'             => 'ξ',
          '*o'             => 'ο',
          '*p'             => 'π',
          '*r'             => 'ρ',
          'ts'             => 'ς',
          '*s'             => 'σ',
          '*t'             => 'τ',
          '*u'             => 'υ',
          '*f'             => 'ϕ',
          '*x'             => 'χ',
          '*q'             => 'ψ',
          '*w'             => 'ω',
          '+h'             => 'ϑ',
          '+f'             => 'φ',
          '+p'             => 'ϖ',
          '+e'             => 'ϵ',
            // Card symbols
          'CL'             => '♣',
          'SP'             => '♠',
          'HE'             => '♥',
          'u2661'          => '♡',
          'DI'             => '♦',
          'u2662'          => '♢',
        ];
    }

    public function __set($name, $value)
    {
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

    public function addRegister(string $name, string $value)
    {
        $this->registers[$name] = $value;
    }

    public function getRegisters(): array
    {

        return $this->registers;
    }

    public function addString(string $string, string $value)
    {
        $this->strings[$string] = $value;
    }

    public function getStrings(): array
    {
        return $this->strings;
    }

    public function applyStringReplacement(string $line):string
    {
        return Roff_String::substitute($line, $this->strings);
    }

    public function applyAllReplacements(string $line):string
    {
        $line = Roff_Register::substitute($line, $this->registers);
        $line = $this->applyStringReplacement($line);
        foreach ($this->aliases as $new => $old) {
            $line = Replace::preg('~^\.' . preg_quote($new, '~') . '(\s|$)~u', '.' . $old . '$1', $line);
        }

        return $line;
    }


}
