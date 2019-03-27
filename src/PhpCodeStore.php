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
    $mode = 0;

    $line = trim($line);

    if (substr($line, -1, 1)=='{')
    {
      $mode |= self::C_INDENT_INCREMENT_AFTER;
    }

    if (substr($line, 0, 1)=='}')
    {
      $mode |= self::C_INDENT_DECREMENT_BEFORE;
    }

    return $mode;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
