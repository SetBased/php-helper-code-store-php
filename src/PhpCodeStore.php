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
  private array $defaultLevel = [];

  /**
   * The heredoc identifier.
   *
   * @var string|null
   */
  private ?string $heredocIdentifier = null;

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
    $mode |= $this->indentationModeBlock($line);

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
   * Returns whether the indent level of the current switch statement (if any) is zero.
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

    if (str_ends_with($line, '{'))
    {
      $mode |= self::C_INDENT_INCREMENT_AFTER;

      $this->defaultLevelIncrement();
    }

    if (str_starts_with($line, '}'))
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
   * Returns the indentation mode based on a line of code starting a heredoc.
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

    if (str_starts_with($line, 'case '))
    {
      $mode |= self::C_INDENT_INCREMENT_AFTER;
    }

    if (str_starts_with($line, 'default:'))
    {
      $this->defaultLevel[] = 0;

      $mode |= self::C_INDENT_INCREMENT_AFTER;
    }

    if (str_starts_with($line, 'break;'))
    {
      $mode |= self::C_INDENT_DECREMENT_AFTER;
    }

    return $mode;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
