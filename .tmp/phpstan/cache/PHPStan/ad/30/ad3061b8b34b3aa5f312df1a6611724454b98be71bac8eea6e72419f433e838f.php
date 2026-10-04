<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Repair/RenameDutchColumns.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Repair\RenameDutchColumns
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-cd59299228a2110587fa4eca5399f5d59a6441c66b2674aaa58815b4f856dfbe',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Repair/RenameDutchColumns.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Repair',
    'name' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
    'shortName' => 'RenameDutchColumns',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Rename shillinq\'s Dutch amount columns to their English equivalents.
 *
 * @spec exclude No canonical spec covers the Dutch-to-English vocabulary
 *  migration. Pointing this at an existing spec would report conformance to a
 *  requirement that says nothing about it.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 69,
    'endLine' => 387,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'OCP\\Migration\\IRepairStep',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'REGISTER_SLUG_PREFIXES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'name' => 'REGISTER_SLUG_PREFIXES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'filinq\', \'consent\', \'signing\', \'templates\', \'document\', \'dossier\']',
          'attributes' => 
          array (
            'startLine' => 89,
            'endLine' => 96,
            'startTokenPos' => 64,
            'startFilePos' => 3739,
            'endTokenPos' => 84,
            'endFilePos' => 3822,
          ),
        ),
        'docComment' => '/**
 * Slug prefix of the registers in scope.
 *
 * Filinq now declares ONE register, `filinq`, holding all 23 schemas — so
 * that is the prefix a migrated install matches on.
 *
 * THE FIVE RETIRED SLUGS STAY IN THIS LIST, and dropping them would be a
 * silent regression. This step is scoped to "wherever the data actually
 * is", not "wherever the register JSON says it should be", and those are
 * not the same place on every install: ConsolidateRegisters skips any
 * (register, schema) pair whose target shard table the import has not
 * created yet, and refuses any pair with a uuid collision. Rows can
 * therefore still be sitting in `..._<oldRegisterId>_<schemaId>` when this
 * runs. A column rename that only visits `filinq` would report success
 * having left those columns Dutch, and every later read of them returns
 * null.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 89,
        'endLine' => 96,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
      'COLUMN_MAP' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'name' => 'COLUMN_MAP',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'zaak_id\' => \'case_id\']',
          'attributes' => 
          array (
            'startLine' => 107,
            'endLine' => 109,
            'startTokenPos' => 97,
            'startFilePos' => 4139,
            'endTokenPos' => 106,
            'endFilePos' => 4168,
          ),
        ),
        'docComment' => '/**
 * Old snake_case column name => new snake_case column name.
 *
 * Snake_case, not camelCase: MagicMapper stores `requestedAmount` as
 * `requested_amount`, and a camelCase column is exactly what its
 * de-duplication path then drops.
 *
 * @var array<string, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 107,
        'endLine' => 109,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'db' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'name' => 'db',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IDBConnection',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 118,
        'endLine' => 118,
        'startColumn' => 3,
        'endColumn' => 36,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'name' => 'logger',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Psr\\Log\\LoggerInterface',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 119,
        'endLine' => 119,
        'startColumn' => 3,
        'endColumn' => 42,
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
          'db' => 
          array (
            'name' => 'db',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IDBConnection',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 118,
            'endLine' => 118,
            'startColumn' => 3,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'logger' => 
          array (
            'name' => 'logger',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Psr\\Log\\LoggerInterface',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 119,
            'endLine' => 119,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 1,
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
 * @param IDBConnection $db Database connection.
 * @param LoggerInterface $logger Logger.
 */',
        'startLine' => 117,
        'endLine' => 121,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'currentClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'aliasName' => NULL,
      ),
      'getName' => 
      array (
        'name' => 'getName',
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
 * Human-readable step name.
 *
 * @return string
 *
 * @spec exclude No canonical spec covers the Dutch-to-English vocabulary
 *  migration. Pointing this at an existing spec would report conformance to a
 *  requirement that says nothing about it.
 */',
        'startLine' => 132,
        'endLine' => 134,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'currentClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'aliasName' => NULL,
      ),
      'run' => 
      array (
        'name' => 'run',
        'parameters' => 
        array (
          'output' => 
          array (
            'name' => 'output',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Migration\\IOutput',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 147,
            'endLine' => 147,
            'startColumn' => 22,
            'endColumn' => 36,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Run the column migration across every filinq shard table.
 *
 * @param IOutput $output Repair output.
 *
 * @return void
 *
 * @spec exclude No canonical spec covers the Dutch-to-English vocabulary
 *  migration. Pointing this at an existing spec would report conformance to a
 *  requirement that says nothing about it.
 */',
        'startLine' => 147,
        'endLine' => 201,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'currentClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'aliasName' => NULL,
      ),
      'hasCollision' => 
      array (
        'name' => 'hasCollision',
        'parameters' => 
        array (
          'columns' => 
          array (
            'name' => 'columns',
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
            'startLine' => 211,
            'endLine' => 211,
            'startColumn' => 32,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'target' => 
          array (
            'name' => 'target',
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
            'startColumn' => 48,
            'endColumn' => 61,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Whether another mapped source already targets the same destination here.
 *
 * @param array<int, string> $columns Column names present in the table.
 * @param string $target The destination column name.
 *
 * @return bool True when two sources compete for one destination.
 */',
        'startLine' => 211,
        'endLine' => 220,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'currentClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'aliasName' => NULL,
      ),
      'shardTables' => 
      array (
        'name' => 'shardTables',
        'parameters' => 
        array (
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
 * Resolve the shard tables of every register whose slug starts with the prefix.
 *
 * Table discovery goes through information_schema, NOT IDBConnection:
 * OCP\\IDBConnection exposes neither getSchema() nor getPrefix(), and calling
 * either is a runtime fatal that `php -l` and phpcs both report as clean.
 * Matching anchors on the `openregister_table_` MARKER rather than a computed
 * prefix, because getTableName(\'\') yields the literal `*PREFIX*` placeholder
 * which a raw information_schema string never resolves.
 *
 * @return array<int, string>
 */',
        'startLine' => 234,
        'endLine' => 261,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'currentClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'aliasName' => NULL,
      ),
      'registerIds' => 
      array (
        'name' => 'registerIds',
        'parameters' => 
        array (
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
 * Ids of every register whose slug starts with the prefix.
 *
 * Split out of shardTables() only to keep that method under phpmd\'s
 * cyclomatic-complexity limit; the behaviour is unchanged.
 *
 * @return array<int, mixed>
 */',
        'startLine' => 271,
        'endLine' => 290,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'currentClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'aliasName' => NULL,
      ),
      'openRegisterTableNames' => 
      array (
        'name' => 'openRegisterTableNames',
        'parameters' => 
        array (
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
 * Every table name containing the openregister shard marker.
 *
 * @return array<int, string>
 */',
        'startLine' => 297,
        'endLine' => 321,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'currentClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'aliasName' => NULL,
      ),
      'columnsOf' => 
      array (
        'name' => 'columnsOf',
        'parameters' => 
        array (
          'table' => 
          array (
            'name' => 'table',
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
            'startLine' => 330,
            'endLine' => 330,
            'startColumn' => 29,
            'endColumn' => 41,
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
 * List the column names of a table.
 *
 * @param string $table Table name.
 *
 * @return array<int, string>
 */',
        'startLine' => 330,
        'endLine' => 354,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'currentClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'aliasName' => NULL,
      ),
      'exec' => 
      array (
        'name' => 'exec',
        'parameters' => 
        array (
          'sql' => 
          array (
            'name' => 'sql',
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
            'startLine' => 363,
            'endLine' => 363,
            'startColumn' => 24,
            'endColumn' => 34,
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
 * Execute one statement, logging and swallowing failure.
 *
 * @param string $sql The statement.
 *
 * @return bool Whether it succeeded.
 */',
        'startLine' => 363,
        'endLine' => 375,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'currentClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'aliasName' => NULL,
      ),
      'quote' => 
      array (
        'name' => 'quote',
        'parameters' => 
        array (
          'identifier' => 
          array (
            'name' => 'identifier',
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
            'startLine' => 384,
            'endLine' => 384,
            'startColumn' => 25,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Quote an identifier for the active platform.
 *
 * @param string $identifier Table or column name.
 *
 * @return string
 */',
        'startLine' => 384,
        'endLine' => 386,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
        'currentClassName' => 'OCA\\Filinq\\Repair\\RenameDutchColumns',
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