<?php
declare(strict_types=1);

namespace SetBased\Helper\CodeStore;

/**
 * Helper class for generating statements for importing classes.
 */
class Importing
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The fully qualified names to import.
   *
   * @var array
   */
  private $classes = [];

  /**
   * The import statements.
   *
   * @var string[]
   */
  private $imports;

  /**
   * The namespace.
   *
   * @var string
   */
  private $namespace;

  /**
   * The replace pairs from fully qualified name to imported name.
   *
   * @var array
   */
  private $replace;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param string $namespace The namespace.
   */
  public function __construct(string $namespace)
  {
    $this->namespace = self::fullyQualify($namespace);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the fully qualified name given a (fully) qualified name.
   *
   * @param string $name The (fully) qualified name optionally without leading slash.
   *
   * @return string The fully qualified name with lead slash.
   */
  public static function fullyQualify(string $name): string
  {
    return '\\'.ltrim($name, '\\');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test for name collision.
   *
   * @param array  $rawImports         The (raw) uses data.
   * @param string $fullyQualifiedName The fully qualified class name.
   *
   * @return bool
   */
  private static function collision1(array $rawImports, string $fullyQualifiedName): bool
  {
    [, $name] = self::split($fullyQualifiedName);

    foreach ($rawImports as $rawImport)
    {
      if ($rawImport['fully_qualified_name']!==$fullyQualifiedName && $rawImport['name']===$name)
      {
        return true;
      }
    }

    return false;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test for collision on alias or name.
   *
   * @param array  $rawImports         The (raw) uses data.
   * @param string $fullyQualifiedName The fully qualified class name.
   * @param string $alias              The alias.
   *
   * @return bool
   */
  private static function collision2(array $rawImports, string $fullyQualifiedName, string $alias): bool
  {
    foreach ($rawImports as $rawImport)
    {
      if ($rawImport['fully_qualified_name']!==$fullyQualifiedName &&
        ($rawImport['name']===$alias || $rawImport['alias']===$alias))
      {
        return true;
      }
    }

    return false;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Splits a fully qualified name into a namespace and class name.
   *
   * @param string $name The fully qualified name.
   *
   * @return array
   */
  private static function split(string $name): array
  {
    $parts = explode('\\', self::fullyQualify($name));
    $name  = array_pop($parts);

    return [implode('\\', $parts), $name];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Add a class to import.
   *
   * @param string $class The fully qualified name of the class.
   */
  public function addClass(string $class): void
  {
    $this->classes[] = self::fullyQualify($class);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the import statements.
   *
   * @return string[]
   */
  public function imports(): array
  {
    return $this->imports;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Prepares the data for retrieving import statements and replace pairs (see methods replacePairs() and imports().
   */
  public function prepare(): void
  {
    $rawUses = $this->prepare0();
    $this->prepare1($rawUses);
    $this->prepare2($rawUses);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the replace pairs from fully qualified name to imported name.
   *
   * @return array
   */
  public function replacePairs(): array
  {
    return $this->replace;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the imported name given a fully qualified name.
   *
   * @param string $fullyQualifiedName The fully qualified name.
   *
   * @return string
   */
  public function simplyFullyQualifiedName(string $fullyQualifiedName): string
  {
    $fullyQualifiedName = self::fullyQualify($fullyQualifiedName);

    return $this->replace[$fullyQualifiedName] ?? $fullyQualifiedName;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns true if and only if the a name space is the global or current namespace.
   *
   * @param string $namespace The namespace.
   *
   * @return bool
   */
  private function isGlobalOrCurrentNamespace(string $namespace): bool
  {
    return ($namespace===$this->namespace || $namespace==='');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns raw data about classes to import.
   *
   * @return array
   */
  private function prepare0(): array
  {
    $this->classes = array_unique($this->classes);
    sort($this->classes);

    $rawImports = [];
    foreach ($this->classes as $fullyQualifiedName)
    {
      [$namespace, $name] = self::split($fullyQualifiedName);

      $rawImports[] = ['fully_qualified_name' => $fullyQualifiedName,
                       'namespace'            => $namespace,
                       'name'                 => $name,
                       'alias'                => null,
                       'import'               => !$this->isGlobalOrCurrentNamespace($namespace)];
    }

    foreach ($rawImports as &$rawImport)
    {
      if (!$this->isGlobalOrCurrentNamespace($rawImport['namespace']) &&
        self::collision1($rawImports, $rawImport['fully_qualified_name']))
      {
        $i = 1;
        do
        {
          $alias = sprintf('%s%s%d', $rawImport['name'], 'Alias', $i);
          $i++;
        } while (self::collision2($rawImports, $this->namespace, $alias));

        $rawImport['alias'] = $alias;
      }
    }

    return $rawImports;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Prepares the import statements.
   *
   * @param array $rawImports The raw data about classes to import.
   */
  private function prepare1(array $rawImports): void
  {
    $this->imports = [];
    foreach ($rawImports as $rawImport)
    {
      if ($rawImport['import'])
      {
        if ($rawImport['alias']===null)
        {
          $this->imports[] = sprintf('use %s;', ltrim($rawImport['fully_qualified_name'], '\\'));
        }
        else
        {
          $this->imports[] = sprintf('use %s as %s;', ltrim($rawImport['fully_qualified_name'], '\\'), $rawImport['alias']);
        }
      }
    }

    usort($this->imports, function (string $a, string $b) {
      return str_replace('\\', ' ', $a)<=>str_replace('\\', ' ', $b);
    });
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Prepares the replace pairs.
   *
   * @param array $rawImports The raw data about classes to import.
   */
  private function prepare2(array $rawImports): void
  {
    usort($rawImports, function (array $a, array $b) {
      $cmp = (strlen($b['fully_qualified_name'])<=>strlen($a['fully_qualified_name']));
      if ($cmp==0)
      {
        $cmp = ($a['fully_qualified_name']<=>$b['fully_qualified_name']);
      }

      return $cmp;
    });

    $this->replace = [];
    foreach ($rawImports as $rawImport)
    {
      if ($rawImport['namespace']!=='')
      {
        $this->replace[$rawImport['fully_qualified_name']] = $rawImport['alias'] ?? $rawImport['name'];
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
