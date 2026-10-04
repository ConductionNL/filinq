<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/SpreadsheetCodec.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Editing\SpreadsheetCodec
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-6c78ffd45ad44b11ce4934e04e5067340d2889bf91a3aac17545dc441322bd6f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/SpreadsheetCodec.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Editing',
    'name' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
    'shortName' => 'SpreadsheetCodec',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Cell-addressed reading and editing for ODS and XLSX packages.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 39,
    'endLine' => 366,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'MAX_CELLS_PER_CALL' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'name' => 'MAX_CELLS_PER_CALL',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '200',
          'attributes' => 
          array (
            'startLine' => 50,
            'endLine' => 50,
            'startTokenPos' => 40,
            'startFilePos' => 1541,
            'endTokenPos' => 40,
            'endFilePos' => 1543,
          ),
        ),
        'docComment' => '/**
 * The most cells one call may write.
 *
 * ⚠️ Exceeding this REFUSES the call rather than truncating it. A truncated
 * bulk write reports success for edits it never made, and the caller — a
 * language model — has no way to tell which half landed.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 50,
        'endLine' => 50,
        'startColumn' => 2,
        'endColumn' => 39,
      ),
      'ERROR_VALUES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'name' => 'ERROR_VALUES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'#REF!\', \'#DIV/0!\', \'#VALUE!\', \'#N/A\', \'#NAME?\', \'#NULL!\', \'#NUM!\']',
          'attributes' => 
          array (
            'startLine' => 62,
            'endLine' => 62,
            'startTokenPos' => 53,
            'startFilePos' => 1958,
            'endTokenPos' => 73,
            'endFilePos' => 2025,
          ),
        ),
        'docComment' => '/**
 * Spreadsheet error literals.
 *
 * A dependent already holding one of these is reported separately from an
 * ordinary stale dependent: "this number no longer follows from its inputs"
 * and "this cell is broken" are different things for a caller to hear, and
 * an error value that persists silently through an edit reads as data.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 2,
        'endColumn' => 98,
      ),
    ),
    'immediateProperties' => 
    array (
      'families' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'name' => 'families',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * The per-family codecs, tried in order.
 *
 * @var array<int, SpreadsheetFamilyCodec>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 69,
        'endLine' => 69,
        'startColumn' => 2,
        'endColumn' => 25,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'io' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
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
        'startLine' => 78,
        'endLine' => 78,
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
            'startLine' => 78,
            'endLine' => 78,
            'startColumn' => 3,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'families' => 
          array (
            'name' => 'families',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 79,
                'endLine' => 79,
                'startTokenPos' => 110,
                'startFilePos' => 2420,
                'endTokenPos' => 110,
                'endFilePos' => 2423,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'array',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 79,
            'endLine' => 79,
            'startColumn' => 3,
            'endColumn' => 25,
            'parameterIndex' => 1,
            'isOptional' => true,
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
 * @param PackagePartIo $io Package reader/writer.
 * @param array<int, SpreadsheetFamilyCodec>|null $families Optional override, for tests.
 */',
        'startLine' => 77,
        'endLine' => 82,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
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
            'startLine' => 93,
            'endLine' => 93,
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
        'startLine' => 93,
        'endLine' => 95,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'aliasName' => NULL,
      ),
      'readCells' => 
      array (
        'name' => 'readCells',
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
            'startLine' => 109,
            'endLine' => 109,
            'startColumn' => 28,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 109,
            'endLine' => 109,
            'startColumn' => 50,
            'endColumn' => 66,
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
 * Read every populated cell, addressed as `Sheet!Cell`.
 *
 * @param string $packageBytes The package.
 * @param string $extension The file extension.
 *
 * @return array<int, array{cell: string, value: string, formula: string|null}> The cells.
 *
 * @throws RuntimeException When the extension is not a spreadsheet.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
 */',
        'startLine' => 109,
        'endLine' => 114,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'aliasName' => NULL,
      ),
      'applyCellEdits' => 
      array (
        'name' => 'applyCellEdits',
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
            'startLine' => 133,
            'endLine' => 133,
            'startColumn' => 33,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 133,
            'endLine' => 133,
            'startColumn' => 55,
            'endColumn' => 71,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'edits' => 
          array (
            'name' => 'edits',
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
            'startLine' => 133,
            'endLine' => 133,
            'startColumn' => 74,
            'endColumn' => 85,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Write literal values into addressed cells.
 *
 * The GUARDS live here rather than in each family: they are policy, not
 * file format, and two copies of "may this write proceed" is how one family
 * ends up permitting what the other refuses.
 *
 * @param string $packageBytes The package.
 * @param string $extension The file extension.
 * @param array $edits Each `{cell, value, replaceFormula?}`.
 *
 * @return array{bytes: string, applied: array<int, string>, staleDependents: array<int, string>, erroredDependents: array<int, string>} The result.
 *
 * @throws RuntimeException When an edit is refused.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
 */',
        'startLine' => 133,
        'endLine' => 174,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'aliasName' => NULL,
      ),
      'codecFor' => 
      array (
        'name' => 'codecFor',
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
            'startLine' => 183,
            'endLine' => 183,
            'startColumn' => 28,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
          'data' => 
          array (
            'types' => 
            array (
              0 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetFamilyCodec',
                  'isIdentifier' => false,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'null',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The codec for an extension, or null.
 *
 * @param string $extension The file extension.
 *
 * @return SpreadsheetFamilyCodec|null The codec, or null when unhandled.
 */',
        'startLine' => 183,
        'endLine' => 191,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'aliasName' => NULL,
      ),
      'requireCodec' => 
      array (
        'name' => 'requireCodec',
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
            'startLine' => 202,
            'endLine' => 202,
            'startColumn' => 32,
            'endColumn' => 48,
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
            'name' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetFamilyCodec',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The codec for an extension, refusing by name when absent.
 *
 * @param string $extension The file extension.
 *
 * @return SpreadsheetFamilyCodec The codec.
 *
 * @throws RuntimeException When unsupported.
 */',
        'startLine' => 202,
        'endLine' => 211,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'aliasName' => NULL,
      ),
      'assertFormulaIntent' => 
      array (
        'name' => 'assertFormulaIntent',
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
            'startLine' => 232,
            'endLine' => 232,
            'startColumn' => 39,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'edit' => 
          array (
            'name' => 'edit',
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
            'startLine' => 232,
            'endLine' => 232,
            'startColumn' => 53,
            'endColumn' => 63,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'existing' => 
          array (
            'name' => 'existing',
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
            'startLine' => 232,
            'endLine' => 232,
            'startColumn' => 66,
            'endColumn' => 80,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'position' => 
          array (
            'name' => 'position',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 232,
            'endLine' => 232,
            'startColumn' => 83,
            'endColumn' => 95,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Refuse a literal write over a formula without per-cell intent.
 *
 * 🔴 The intent must be given FOR THAT CELL. A per-call or global flag is
 * not acceptable: a bulk write would carry one cell\'s permission across
 * every other cell in the same call, and the formula the caller never
 * looked at is exactly the one that gets destroyed.
 *
 * @param string $cell The cell address.
 * @param array $edit The edit.
 * @param array $existing Cells indexed by address.
 * @param int $position The edit\'s position, for the message.
 *
 * @return void
 *
 * @throws RuntimeException When the cell holds a formula and intent is absent.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.3
 */',
        'startLine' => 232,
        'endLine' => 252,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'aliasName' => NULL,
      ),
      'staleDependents' => 
      array (
        'name' => 'staleDependents',
        'parameters' => 
        array (
          'existing' => 
          array (
            'name' => 'existing',
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
            'startLine' => 269,
            'endLine' => 269,
            'startColumn' => 35,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'written' => 
          array (
            'name' => 'written',
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
            'startLine' => 269,
            'endLine' => 269,
            'startColumn' => 52,
            'endColumn' => 65,
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
 * Cells whose cached value is now stale because a cell they reference changed.
 *
 * ⚠️ Reported, never recalculated. Recalculation needs a formula engine this
 * app does not have, and a cached value left in place is a number that
 * LOOKS current and is not. Saying which cells went stale is honest; quietly
 * leaving them is the failure this reporting exists to prevent.
 *
 * @param array<string, array{cell: string, value: string, formula: string|null}> $existing Cells by address.
 * @param array<int, string> $written Addresses written.
 *
 * @return array<int, string> Addresses whose cached values no longer follow.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.4
 */',
        'startLine' => 269,
        'endLine' => 288,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'aliasName' => NULL,
      ),
      'erroredDependents' => 
      array (
        'name' => 'erroredDependents',
        'parameters' => 
        array (
          'existing' => 
          array (
            'name' => 'existing',
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
            'startLine' => 305,
            'endLine' => 305,
            'startColumn' => 37,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'stale' => 
          array (
            'name' => 'stale',
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
            'startLine' => 305,
            'endLine' => 305,
            'startColumn' => 54,
            'endColumn' => 65,
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
 * Dependents whose cached value is a spreadsheet ERROR.
 *
 * ⚠️ Reported separately, and never repaired. A `#REF!` or `#DIV/0!` sitting
 * behind a changed input is not merely out of date — it is a cell the sheet
 * itself could not compute, and letting it persist through an edit without
 * a word makes it look like content.
 *
 * @param array<string, array{cell: string, value: string, formula: string|null}> $existing Cells by address.
 * @param array<int, string> $stale Stale addresses.
 *
 * @return array<int, string> Addresses whose cached value is an error literal.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-4.2
 */',
        'startLine' => 305,
        'endLine' => 315,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
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
            'startLine' => 324,
            'endLine' => 324,
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
        'startLine' => 324,
        'endLine' => 328,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'aliasName' => NULL,
      ),
      'indexByCell' => 
      array (
        'name' => 'indexByCell',
        'parameters' => 
        array (
          'cells' => 
          array (
            'name' => 'cells',
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
            'startLine' => 337,
            'endLine' => 337,
            'startColumn' => 31,
            'endColumn' => 42,
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
 * Index cells by their address.
 *
 * @param array<int, array{cell: string, value: string, formula: string|null}> $cells The cells.
 *
 * @return array<string, array{cell: string, value: string, formula: string|null}> Indexed cells.
 */',
        'startLine' => 337,
        'endLine' => 344,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'aliasName' => NULL,
      ),
      'normaliseAddress' => 
      array (
        'name' => 'normaliseAddress',
        'parameters' => 
        array (
          'address' => 
          array (
            'name' => 'address',
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
            'startLine' => 358,
            'endLine' => 358,
            'startColumn' => 36,
            'endColumn' => 50,
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
 * Normalise an address for comparison.
 *
 * ⚠️ Only the CELL REFERENCE is upper-cased. A sheet name is case-sensitive
 * in every spreadsheet application, so upper-casing the whole address turns
 * `Sales!b2` into `SALES!B2` and it matches no sheet at all — the write then
 * fails on a document where the sheet plainly exists.
 *
 * @param string $address The `Sheet!Cell` address.
 *
 * @return string The normalised address.
 */',
        'startLine' => 358,
        'endLine' => 365,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\SpreadsheetCodec',
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