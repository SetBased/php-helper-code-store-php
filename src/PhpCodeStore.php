<?php
declare(strict_types=1);

namespace SetBased\Helper\CodeStore;

/**
 * A helper class for automatically generating PHP code with proper indentation.
 */
class PhpCodeStore extends CodeStore
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The levels of nested default clauses.
   *
   * @var int[]
   */
  private $defaultLevel = [];

  /**
   * The heredoc identifier.
   *
   * @var string|null
   */
  private $heredocIdentifier;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param int $indentation The number of spaces per indentation level.
   * @param int $width       The maximum width of the generated code (in chars).
   *
   * @since 1.0.0
   * @api
   */
  public function __construct(int $indentation = 2, int $width = 120)
  {
    parent::__construct($indentation, $width);

    $this->separator = '//'.str_repeat('-', $width - 2);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  protected function indentationMode(string $line): int
  {
    $line = trim($line);

    $mode = 0;

    $mode |= $this->indentationModeHeredoc($line);
    $mode |= $this->indentationModeSwitch($line);
    $mode |= $this->indentationModeBLock($line);

    return $mode;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Decrements indent level of the current switch statement (if any).
   */
  private function defaultLevelDecrement(): void
  {
    if (!empty($this->defaultLevel) && $this->defaultLevel[sizeof($this->defaultLevel) - 1]>0)
    {
      $this->defaultLevel[sizeof($this->defaultLevel) - 1]--;
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Increments indent level of the current switch statement (if any).
   */
  private function defaultLevelIncrement(): void
  {
    if (!empty($this->defaultLevel))
    {
      $this->defaultLevel[sizeof($this->defaultLevel) - 1]++;
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns true if the indent level of the current switch statement (if any) is zero. Otherwise, returns false.
   */
  private function defaultLevelIsZero(): bool
  {
    return (!empty($this->defaultLevel) && $this->defaultLevel[sizeof($this->defaultLevel) - 1]==0);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the indentation mode based blocks of code.
   *
   * @param string $line The line of code.
   *
   * @return int
   */
  private function indentationModeBlock(string $line): int
  {
    $mode = 0;

    if ($this->heredocIdentifier!==null) return $mode;

    if (substr($line, -1, 1)=='{')
    {
      $mode |= self::C_INDENT_INCREMENT_AFTER;

      $this->defaultLevelIncrement();
    }

    if (substr($line, 0, 1)=='}')
    {
      $this->defaultLevelDecrement();

      if ($this->defaultLevelIsZero())
      {
        $mode |= self::C_INDENT_DECREMENT_BEFORE_DOUBLE;

        array_pop($this->defaultLevel);
      }
      else
      {
        $mode |= self::C_INDENT_DECREMENT_BEFORE;
      }
    }

    return $mode;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   *
   * @param string $line The line of code.
   *
   * @return int
   */
  private function indentationModeHeredoc(string $line): int
  {
    $mode = 0;

    if ($this->heredocIdentifier!==null)
    {
      $mode |= self::C_INDENT_HEREDOC;

      if ($line==$this->heredocIdentifier.';')
      {
        $this->heredocIdentifier = null;
      }
    }
    else
    {
      $n = preg_match('/=\s*<<<\s*([A-Z]+)$/', $line, $parts);
      if ($n==1)
      {
        $this->heredocIdentifier = $parts[1];
      }
    }

    return $mode;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the indentation mode based on a line of code for switch statements.
   *
   * @param string $line The line of code.
   *
   * @return int
   */
  private function indentationModeSwitch(string $line): int
  {
    $mode = 0;

    if ($this->heredocIdentifier!==null) return $mode;

    if (substr($line, 0, 5)=='case ')
    {
      $mode |= self::C_INDENT_INCREMENT_AFTER;
    }

    if (substr($line, 0, 8)=='default:')
    {
      $this->defaultLevel[] = 0;

      $mode |= self::C_INDENT_INCREMENT_AFTER;
    }

    if (substr($line, 0, 6)=='break;')
    {
      $mode |= self::C_INDENT_DECREMENT_AFTER;
    }

    return $mode;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
