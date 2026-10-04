<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/XlsxSpreadsheetCodec.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Editing\XlsxSpreadsheetCodec
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-e7df9d38ad09018ff933fc2be24fd144259e5bed2dd69a822d4768bcf71551e9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Editing\\XlsxSpreadsheetCodec',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/XlsxSpreadsheetCodec.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Editing',
    'name' => 'OCA\\Filinq\\Service\\Editing\\XlsxSpreadsheetCodec',
    'shortName' => 'XlsxSpreadsheetCodec',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Cell access for OOXML spreadsheets.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 38,
    'endLine' => 216,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetFamilyCodec',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'io' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\XlsxSpreadsheetCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\XlsxSpreadsheetCodec',
        'name' => 'io',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Editing\\PackagePartIo',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 46,
        'endLine' => 46,
        'startColumn' => 3,
        'endColumn' => 36,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'io' => 
          array (
            'name' => 'io',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Editing\\PackagePartIo',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 46,
            'endLine' => 46,
            'startColumn' => 3,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor.
 *
 * @param PackagePartIo $io Package reader, for the shared string table.
 */',
        'startLine' => 45,
        'endLine' => 48,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\XlsxSpreadsheetCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\XlsxSpreadsheetCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\XlsxSpreadsheetCodec',
        'aliasName' => NULL,
      ),
      'supports' => 
      array (
        'name' => 'supports',
        'parameters' => 
        array (
          'extension' => 
          array (
            'name' => 'extension',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 59,
            'endLine' => 59,
            'startColumn' => 27,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Whether this codec handles an extension.
 *
 * @param string $extension The lower-case file extension.
 *
 * @return bool True when handled.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
 */',
        'startLine' => 59,
        'endLine' => 61,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\XlsxSpreadsheetCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\XlsxSpreadsheetCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\XlsxSpreadsheetCodec',
        'aliasName' => NULL,
      ),
      'valuePart' => 
      array (
        'name' => 'valuePart',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The package part carrying cell values.
 *
 * @return string The part path.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
 */',
        'startLine' => 70,
        'endLine' => 72,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\XlsxSpreadsheetCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\XlsxSpreadsheetCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\XlsxSpreadsheetCodec',
        'aliasName' => NULL,
      ),
      'readCells' => 
      array (
        'name' => 'readCells',
        'parameters' => 
        array (
          'xml' => 
          array (
            'name' => 'xml',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 84,
            'endLine' => 84,
            'startColumn' => 28,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'packageBytes' => 
          array (
            'name' => 'packageBytes',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 84,
            'endLine' => 84,
            'startColumn' => 41,
            'endColumn' => 60,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Read XLSX cells.
 *
 * @param string $xml The worksheet XML.
 * @param string $packageBytes The package, for the shared string table.
 *
 * @return array<int, array{cell: string, value: string, formula: string|null}> The cells.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
 */',
        'startLine' => 84,
        'endLine' => 116,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\XlsxSpreadsheetCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\XlsxSpreadsheetCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\XlsxSpreadsheetCodec',
        'aliasName' => NULL,
      ),
      'cellValue' => 
      array (
        'name' => 'cellValue',
        'parameters' => 
        array (
          'attributes' => 
          array (
            'name' => 'attributes',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 131,
            'endLine' => 131,
            'startColumn' => 29,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'body' => 
          array (
            'name' => 'body',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 131,
            'endLine' => 131,
            'startColumn' => 49,
            'endColumn' => 60,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'shared' => 
          array (
            'name' => 'shared',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 131,
            'endLine' => 131,
            'startColumn' => 63,
            'endColumn' => 75,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The visible value of a cell.
 *
 * ⚠️ `t="s"` means the value is an INDEX into the shared string table, not
 * the text. Reporting the index would hand a caller a number where the
 * sheet plainly shows a word.
 *
 * @param string $attributes The cell\'s attributes.
 * @param string $body The cell\'s inner markup.
 * @param array<int, string> $shared The shared string table.
 *
 * @return string The value.
 */',
        'startLine' => 131,
        'endLine' => 146,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\XlsxSpreadsheetCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\XlsxSpreadsheetCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\XlsxSpreadsheetCodec',
        'aliasName' => NULL,
      ),
      'writeCell' => 
      array (
        'name' => 'writeCell',
        'parameters' => 
        array (
          'xml' => 
          array (
            'name' => 'xml',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 165,
            'endLine' => 165,
            'startColumn' => 28,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'cell' => 
          array (
            'name' => 'cell',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 165,
            'endLine' => 165,
            'startColumn' => 41,
            'endColumn' => 52,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 165,
            'endLine' => 165,
            'startColumn' => 55,
            'endColumn' => 67,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Write a literal into an XLSX cell, as an inline string.
 *
 * Inline rather than shared: appending to the shared table renumbers
 * nothing but grows a structure every other sheet also indexes into, and an
 * inline string is valid OOXML that no other cell can be affected by.
 *
 * @param string $xml The worksheet XML.
 * @param string $cell The address.
 * @param string $value The literal.
 *
 * @return string The rewritten XML.
 *
 * @throws RuntimeException When the cell is absent.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
 */',
        'startLine' => 165,
        'endLine' => 178,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\XlsxSpreadsheetCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\XlsxSpreadsheetCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\XlsxSpreadsheetCodec',
        'aliasName' => NULL,
      ),
      'sharedStrings' => 
      array (
        'name' => 'sharedStrings',
        'parameters' => 
        array (
          'packageBytes' => 
          array (
            'name' => 'packageBytes',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 187,
            'endLine' => 187,
            'startColumn' => 33,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The shared string table, indexed as the worksheet references it.
 *
 * @param string $packageBytes The package.
 *
 * @return array<int, string> The strings.
 */',
        'startLine' => 187,
        'endLine' => 202,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\XlsxSpreadsheetCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\XlsxSpreadsheetCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\XlsxSpreadsheetCodec',
        'aliasName' => NULL,
      ),
      'bareRef' => 
      array (
        'name' => 'bareRef',
        'parameters' => 
        array (
          'cell' => 
          array (
            'name' => 'cell',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 211,
            'endLine' => 211,
            'startColumn' => 27,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The cell part of a `Sheet!Cell` address.
 *
 * @param string $cell The address.
 *
 * @return string The bare cell reference.
 */',
        'startLine' => 211,
        'endLine' => 215,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\XlsxSpreadsheetCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\XlsxSpreadsheetCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\XlsxSpreadsheetCodec',
        'aliasName' => NULL,
      ),
    ),
    'traitsData' => 
    array (
      'aliases' => 
      array (
      ),
      'modifiers' => 
      array (
      ),
      'precedences' => 
      array (
      ),
      'hashes' => 
      array (
      ),
    ),
  ),
));