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
    private $characterTranslations; // .tr

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
        $this->data    = [];
        $this->aliases = [];
        $this->macros  = [];
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

    public function setRegister(string $name, string $value)
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

    public function setCharTranslation(string $from, string $to)
    {
        $this->characterTranslations[$from] = $to;
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

    public function applyAllReplacements(string $line):string
    {

        $line = Roff_Register::substitute($line, $this->registers);
        $line = Roff_String::substitute($line, $this->strings);
        foreach ($this->aliases as $new => $old) {
            $line = Replace::preg('~^\.' . preg_quote($new, '~') . '(\s|$)~u', '.' . $old . '$1', $line);
        }
        $line = Text::translateCharacters($line);


        return $line;
    }


}
