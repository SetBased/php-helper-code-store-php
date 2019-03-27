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
   * The levels of nested switch statements.
   *
   * @var int[]
   */
  private $switchLevel = [];

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
    $mode |= $this->indentationModeSwitch($line);
    $mode |= $this->indentationModeBLock($line);

    return $mode;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the indentation mode based blocks of code.
   *
   * @param string $line The line of code.
   *
   * @return int
   */
  protected function indentationModeBlock(string $line): int
  {
    $mode = 0;

    if (substr($line, -1, 1)=='{')
    {
      $mode |= self::C_INDENT_INCREMENT_AFTER;

      $this->switchLevelIncrement();
    }

    if (substr($line, 0, 1)=='}')
    {
      $this->switchLevelDecrement();

      if ($this->switchLevelIsZero())
      {
        $mode |= self::C_INDENT_DECREMENT_BEFORE_DOUBLE;

        array_pop($this->switchLevel);
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
   * Returns the indentation mode based on a line of code for switch statements.
   *
   * @param string $line The line of code.
   *
   * @return int
   */
  private function indentationModeSwitch(string $line): int
  {
    $mode = 0;

    if (substr($line, 0, 7)=='switch ')
    {
      $this->switchLevel[] = 0;
    }

    if (substr($line, 0, 5)=='case ')
    {
      $mode |= self::C_INDENT_INCREMENT_AFTER;
    }

    if (substr($line, 0, 8)=='default:')
    {
      $mode |= self::C_INDENT_INCREMENT_AFTER;
    }

    if (substr($line, 0, 6)=='break;')
    {
      $mode |= self::C_INDENT_DECREMENT_AFTER;
    }

    return $mode;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Decrements indent level of the current switch statement (if any).
   */
  private function switchLevelDecrement(): void
  {
    if (!empty($this->switchLevel) && $this->switchLevel[sizeof($this->switchLevel) - 1]>0)
    {
      $this->switchLevel[sizeof($this->switchLevel) - 1]--;
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Increments indent level of the current switch statement (if any).
   */
  private function switchLevelIncrement(): void
  {
    if (!empty($this->switchLevel))
    {
      $this->switchLevel[sizeof($this->switchLevel) - 1]++;
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns true if the indent level of the current switch statement (if any) is zero. Otherwise, returns false.
   */
  private function switchLevelIsZero(): bool
  {
    return (!empty($this->switchLevel) && $this->switchLevel[sizeof($this->switchLevel) - 1]==0);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
