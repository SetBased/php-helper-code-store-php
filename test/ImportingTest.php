<?php
declare(strict_types=1);

namespace SetBased\Helper\CodeStore\Test;

use PHPUnit\Framework\TestCase;
use SetBased\Helper\CodeStore\Importing;

/**
 * Test cases for Importing.
 */
class ImportingTest extends TestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test with distinct classes and collisions.
   */
  public function testCollision1(): void
  {
    $importing = new Importing(__NAMESPACE__);
    $importing->addClass('\\Foo\\Bar\\ClassOne');
    $importing->addClass('\\Foo\\Bar\\ClassTwo');
    $importing->addClass('\\Bar\\Foo\\ClassTwo');
    $importing->addClass('\\Foo\\Bar\\ClassThree');

    $importing->prepare();

    $expected = ['use Bar\\Foo\\ClassTwo as ClassTwoAlias1;',
                 'use Foo\\Bar\\ClassOne;',
                 'use Foo\\Bar\\ClassThree;',
                 'use Foo\\Bar\\ClassTwo as ClassTwoAlias2;'];

    self::assertSame($expected, $importing->imports());

    $expected = ['\\Foo\\Bar\\ClassThree' => 'ClassThree',
                 '\\Bar\\Foo\\ClassTwo'   => 'ClassTwoAlias1',
                 '\\Foo\\Bar\\ClassOne'   => 'ClassOne',
                 '\\Foo\\Bar\\ClassTwo'   => 'ClassTwoAlias2'];

    self::assertSame($expected, $importing->replacePairs());
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test with distinct classes and colliding collisions.
   */
  public function testCollision2(): void
  {
    $importing = new Importing(__NAMESPACE__);
    $importing->addClass('\\Foo\\Bar\\ClassOne');
    $importing->addClass('\\Foo\\Bar\\ClassTwo');
    $importing->addClass('\\Bar\\Foo\\ClassTwo');
    $importing->addClass('\\Foo\\Bar\\ClassTwoAlias1');
    $importing->addClass('\\Foo\\Bar\\ClassTwoAlias2');
    $importing->addClass('\\Foo\\Bar\\ClassThree');

    $importing->prepare();

    $expected = ['use Bar\\Foo\\ClassTwo as ClassTwoAlias3;',
                 'use Foo\\Bar\\ClassOne;',
                 'use Foo\\Bar\\ClassThree;',
                 'use Foo\\Bar\\ClassTwo as ClassTwoAlias4;',
                 'use Foo\\Bar\\ClassTwoAlias1;',
                 'use Foo\\Bar\\ClassTwoAlias2;'];

    self::assertSame($expected, $importing->imports());

    $expected = ['\\Foo\\Bar\\ClassTwoAlias1' => 'ClassTwoAlias1',
                 '\\Foo\\Bar\\ClassTwoAlias2' => 'ClassTwoAlias2',
                 '\\Foo\\Bar\\ClassThree'     => 'ClassThree',
                 '\\Bar\\Foo\\ClassTwo'       => 'ClassTwoAlias3',
                 '\\Foo\\Bar\\ClassOne'       => 'ClassOne',
                 '\\Foo\\Bar\\ClassTwo'       => 'ClassTwoAlias4'];

    self::assertSame($expected, $importing->replacePairs());
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test with distinct classes, colliding collisions, and import from same namespace.
   */
  public function testCollision3(): void
  {
    $importing = new Importing(__NAMESPACE__);
    $importing->addClass('\\Foo\\Bar\\ClassOne');
    $importing->addClass('\\Foo\\Bar\\ClassTwo');
    $importing->addClass('\\Bar\\Foo\\ClassTwo');
    $importing->addClass(__NAMESPACE__.'\\ClassTwo');
    $importing->addClass('\\Foo\\Bar\\ClassTwoAlias1');
    $importing->addClass('\\Foo\\Bar\\ClassTwoAlias2');
    $importing->addClass('\\Foo\\Bar\\ClassThree');

    $importing->prepare();

    $expected = ['use Bar\\Foo\\ClassTwo as ClassTwoAlias3;',
                 'use Foo\\Bar\\ClassOne;',
                 'use Foo\\Bar\\ClassThree;',
                 'use Foo\\Bar\\ClassTwo as ClassTwoAlias4;',
                 'use Foo\\Bar\\ClassTwoAlias1;',
                 'use Foo\\Bar\\ClassTwoAlias2;'];

    self::assertSame($expected, $importing->imports());

    $expected = ['\\Foo\\Bar\\ClassTwoAlias1' => 'ClassTwoAlias1',
                 '\\Foo\\Bar\\ClassTwoAlias2' => 'ClassTwoAlias2',
                 '\\Foo\\Bar\\ClassThree'     => 'ClassThree',
                 '\\Bar\\Foo\\ClassTwo'       => 'ClassTwoAlias3',
                 '\\Foo\\Bar\\ClassOne'       => 'ClassOne',
                 '\\Foo\\Bar\\ClassTwo'       => 'ClassTwoAlias4'];

    self::assertSame($expected, $importing->replacePairs());
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test with no imports.
   */
  public function testNoImports(): void
  {
    $importing = new Importing(__NAMESPACE__);

    $importing->prepare();

    self::assertSame([], $importing->imports());
    self::assertSame([], $importing->replacePairs());
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Simple test with 3 distinct classes.
   */
  public function testSimpleCase(): void
  {
    $importing = new Importing(__NAMESPACE__);
    $importing->addClass('\\Foo\\Bar\\ClassOne');
    $importing->addClass('Foo\\Bar\\ClassTwo');
    $importing->addClass('\\Foo\\Bar\\ClassThree');

    $importing->prepare();

    $expected = ['use Foo\\Bar\\ClassOne;',
                 'use Foo\\Bar\\ClassThree;',
                 'use Foo\\Bar\\ClassTwo;'];

    self::assertSame($expected, $importing->imports());

    $expected = ['\\Foo\\Bar\\ClassThree' => 'ClassThree',
                 '\\Foo\\Bar\\ClassOne'   => 'ClassOne',
                 '\\Foo\\Bar\\ClassTwo'   => 'ClassTwo'];

    self::assertSame($expected, $importing->replacePairs());

    self::assertSame('ClassThree', $importing->simplyFullyQualifiedName('Foo\\Bar\\ClassThree'));
    self::assertSame('ClassThree', $importing->simplyFullyQualifiedName('\\Foo\\Bar\\ClassThree'));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Simple test with 3 distinct classes and a global name.
   */
  public function testSimpleCaseWIthGlobalName(): void
  {
    $importing = new Importing(__NAMESPACE__);
    $importing->addClass('\\Foo\\Bar\\ClassOne');
    $importing->addClass('Foo\\Bar\\ClassTwo');
    $importing->addClass('\\Foo\\Bar\\ClassThree');
    $importing->addClass('\\Throwable');

    $importing->prepare();

    $expected = ['use Foo\\Bar\\ClassOne;',
                 'use Foo\\Bar\\ClassThree;',
                 'use Foo\\Bar\\ClassTwo;'];

    self::assertSame($expected, $importing->imports());

    $expected = ['\\Foo\\Bar\\ClassThree' => 'ClassThree',
                 '\\Foo\\Bar\\ClassOne'   => 'ClassOne',
                 '\\Foo\\Bar\\ClassTwo'   => 'ClassTwo'];

    self::assertSame($expected, $importing->replacePairs());

    self::assertSame('ClassThree', $importing->simplyFullyQualifiedName('Foo\\Bar\\ClassThree'));
    self::assertSame('ClassThree', $importing->simplyFullyQualifiedName('\\Foo\\Bar\\ClassThree'));
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
