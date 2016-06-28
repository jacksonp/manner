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
        $this->registers = [];
        $this->strings   = [];
        $this->addString('.T', 'ps');
        $this->addRegister('.g', '1');
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

    public function applyStringReplacement(string $line)
    {
        return Replace::pregCallback(
          '~(?J)(?<!\\\\)(?<bspairs>(?:\\\\\\\\)*)\\\\n(?:\[(?<reg>[^\]]+)\]|\((?<reg>..)|(?<reg>.))~u',
          function ($matches) {
              if (isset($this->registers[$matches['reg']])) {
                  return $matches['bspairs'] . $this->registers[$matches['reg']];
              } else {
                  //throw new Exception($matches['reg'] . ' - unavailable register: ' . $matches[0]);
                  return $matches[0];
              }
          },
          $line);
    }

    public function applyAllReplacements(string $line)
    {

        // Want to match any of: \*. \(.. \*(.. \[....] \*[....]
        $line = Replace::pregCallback(
          '~(?J)(?<!\\\\)(?<bspairs>(?:\\\\\\\\)*)\\\\(?:\*?\[(?<str>[^\]]+)\]|\*?\((?<str>..)|\*(?<str>.))~u',
          function ($matches) {
              if (isset($this->strings[$matches['str']])) {
                  return $matches['bspairs'] . $this->strings[$matches['str']];
              } else {
                  //throw new Exception($matches['str'] . ' - unavailable string: ' . $matches[0]);
                  return $matches[0];
              }
          },
          $line);

        $line = $this->applyStringReplacement($line);

        foreach ($this->aliases as $new => $old) {
            $line = Replace::preg('~^\.' . preg_quote($new, '~') . '(\s|$)~u', '.' . $old . '$1', $line);
        }

        return $line;
    }


}
