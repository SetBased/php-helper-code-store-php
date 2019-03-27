<?php
declare(strict_types=1);

namespace SetBased\Helper\CodeStore\Test;

use PHPUnit\Framework\TestCase;
use SetBased\Helper\CodeStore\PhpCodeStore;

/**
 * Test cases for class PhpCodeStore.
 */
class PhpCodeStoreTest extends TestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test indentation levels for different coding styles.
   */
  public function testIndentationLevel(): void
  {
    $store = new PhpCodeStore(2, 80);

    $store->append('<?php');
    $store->appendSeparator();
    $store->append('namespace SetBased\Helper\CodeStore\Test;');
    $store->append('');
    $store->append('use SetBased\Helper\CodeStore\PhpCodeStore;');
    $store->append('');
    $store->appendSeparator();
    $store->append('class PhpCodeStoreTest extends \PHPUnit_Framework_TestCase');
    $store->append('{');
    $store->appendSeparator();
    $store->append('public function testIndentationLevel()');
    $store->append('{');
    $store->append('// Mix some coding styles.');
    $store->append('if (true) {');
    $store->append('echo "true";');
    $store->append('} else {');
    $store->append('echo "false";');
    $store->append('}');
    $store->append('}');
    $store->append('');
    $store->appendSeparator();
    $store->append('}');
    $store->append('');
    $store->appendSeparator();

    $expected = <<< EOL
<?php
//------------------------------------------------------------------------------
namespace SetBased\Helper\CodeStore\Test;

use SetBased\Helper\CodeStore\PhpCodeStore;

//------------------------------------------------------------------------------
class PhpCodeStoreTest extends \PHPUnit_Framework_TestCase
{
  //----------------------------------------------------------------------------
  public function testIndentationLevel()
  {
    // Mix some coding styles.
    if (true) {
      echo "true";
    } else {
      echo "false";
    }
  }

  //----------------------------------------------------------------------------
}

//------------------------------------------------------------------------------

EOL;

    $code = $store->getCode();

    self::assertEquals($expected, $code);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
