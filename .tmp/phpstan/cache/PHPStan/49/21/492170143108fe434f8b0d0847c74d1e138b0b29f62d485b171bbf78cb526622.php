<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Repair/ConsolidateRegistersGateway.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Repair\ConsolidateRegistersGateway
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-4d8197749006cb3f00c48952a5bad91bec29fcd843bc5d15e5e8b5c46b3458ba',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Repair/ConsolidateRegistersGateway.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Repair',
    'name' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
    'shortName' => 'ConsolidateRegistersGateway',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Reads and writes the OpenRegister shard tables on behalf of the repair step.
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq\'s five
 *  OpenRegister registers into one. Pointing this at an existing spec would
 *  report conformance to a requirement that says nothing about it.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 56,
    'endLine' => 363,
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
    ),
    'immediateProperties' => 
    array (
      'db' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
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
        'startLine' => 70,
        'endLine' => 70,
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
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
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
        'startLine' => 71,
        'endLine' => 71,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'decisions' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'name' => 'decisions',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
            'isIdentifier' => false,
          ),
        ),
        'default' => 
        array (
          'code' => 'new \\OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions()',
          'attributes' => 
          array (
            'startLine' => 72,
            'endLine' => 72,
            'startTokenPos' => 77,
            'startFilePos' => 2986,
            'endTokenPos' => 81,
            'endFilePos' => 3020,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 72,
        'endLine' => 72,
        'startColumn' => 3,
        'endColumn' => 97,
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
            'startLine' => 70,
            'endLine' => 70,
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
            'startLine' => 71,
            'endLine' => 71,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'decisions' => 
          array (
            'name' => 'decisions',
            'default' => 
            array (
              'code' => 'new \\OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions()',
              'attributes' => 
              array (
                'startLine' => 72,
                'endLine' => 72,
                'startTokenPos' => 77,
                'startFilePos' => 2986,
                'endTokenPos' => 81,
                'endFilePos' => 3020,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 72,
            'endLine' => 72,
            'startColumn' => 3,
            'endColumn' => 97,
            'parameterIndex' => 2,
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
 * @param IDBConnection $db Database connection.
 * @param LoggerInterface $logger Logger.
 * @param ConsolidateRegistersDecisions $decisions The pure predicates.
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq\'s five
 *  OpenRegister registers into one. Pointing this at an existing spec would
 *  report conformance to a requirement that says nothing about it.
 */',
        'startLine' => 69,
        'endLine' => 74,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'aliasName' => NULL,
      ),
      'insertBatch' => 
      array (
        'name' => 'insertBatch',
        'parameters' => 
        array (
          'sourceTable' => 
          array (
            'name' => 'sourceTable',
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
            'startLine' => 97,
            'endLine' => 97,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'targetTable' => 
          array (
            'name' => 'targetTable',
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
            'startLine' => 98,
            'endLine' => 98,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'targetId' => 
          array (
            'name' => 'targetId',
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
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 3,
            'endColumn' => 15,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
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
            'startLine' => 100,
            'endLine' => 100,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'batch' => 
          array (
            'name' => 'batch',
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
            'startLine' => 101,
            'endLine' => 101,
            'startColumn' => 3,
            'endColumn' => 14,
            'parameterIndex' => 4,
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
 * Copy one batch across, rewriting `_register` on the way.
 *
 * The table names are interpolated because a table name cannot be bound;
 * both have passed isSafeTableName() for their own (register, schema) pair
 * and both came out of an `information_schema` result. Every VALUE — the
 * new register id and every uuid — is bound.
 *
 * @param string $sourceTable Source table name.
 * @param string $targetTable Target table name.
 * @param int $targetId The new register id.
 * @param array<int, string> $columns Columns to carry.
 * @param array<int, string> $batch The uuids in this batch.
 *
 * @return bool True when the statement executed.
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq\'s five
 *  OpenRegister registers into one. Pointing this at an existing spec would
 *  report conformance to a requirement that says nothing about it.
 */',
        'startLine' => 96,
        'endLine' => 128,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'aliasName' => NULL,
      ),
      'deleteBatch' => 
      array (
        'name' => 'deleteBatch',
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
            'startLine' => 147,
            'endLine' => 147,
            'startColumn' => 30,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'uuids' => 
          array (
            'name' => 'uuids',
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
            'startLine' => 147,
            'endLine' => 147,
            'startColumn' => 45,
            'endColumn' => 56,
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
 * Remove one batch from the source table.
 *
 * Scoped to the exact uuids that were just confirmed present in the target.
 * Never `DELETE FROM source WHERE _uuid IN (SELECT ... FROM target)`: that
 * form would also delete the CONFLICTING rows, which are precisely the ones
 * this step promised to leave alone.
 *
 * @param string $table The source table.
 * @param array<int, string> $uuids The uuids to remove.
 *
 * @return bool True when the statement executed.
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq\'s five
 *  OpenRegister registers into one. Pointing this at an existing spec would
 *  report conformance to a requirement that says nothing about it.
 */',
        'startLine' => 147,
        'endLine' => 162,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'aliasName' => NULL,
      ),
      'fetchRows' => 
      array (
        'name' => 'fetchRows',
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
            'startLine' => 182,
            'endLine' => 182,
            'startColumn' => 29,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'params' => 
          array (
            'name' => 'params',
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
            'startLine' => 182,
            'endLine' => 182,
            'startColumn' => 42,
            'endColumn' => 54,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'failure' => 
          array (
            'name' => 'failure',
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
            'startLine' => 182,
            'endLine' => 182,
            'startColumn' => 57,
            'endColumn' => 71,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'context' => 
          array (
            'name' => 'context',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 182,
                'endLine' => 182,
                'startTokenPos' => 538,
                'startFilePos' => 7081,
                'endTokenPos' => 539,
                'endFilePos' => 7082,
              ),
            ),
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
            'startLine' => 182,
            'endLine' => 182,
            'startColumn' => 74,
            'endColumn' => 92,
            'parameterIndex' => 3,
            'isOptional' => true,
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Run one read, or report that nothing is known.
 *
 * EVERY READ IN THIS STEP GOES THROUGH HERE, and that is the point. The
 * `null` return is the whole of property 4: a query that failed and a query
 * that returned no rows must not look alike, because "no rows" authorises
 * the migration to conclude there is nothing to move — and concluding that
 * over an unreadable table is exactly how a migration reports success having
 * moved nothing. One place to get that right beats five places to get it
 * wrong.
 *
 * @param string $sql The query.
 * @param array<int, mixed> $params Bound parameters.
 * @param string $failure The warning to log when it fails.
 * @param array<string, mixed> $context Extra log context.
 *
 * @return array<int, array<string, mixed>>|null Rows, or null when unreadable.
 */',
        'startLine' => 182,
        'endLine' => 189,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'aliasName' => NULL,
      ),
      'readColumn' => 
      array (
        'name' => 'readColumn',
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
            'startLine' => 203,
            'endLine' => 203,
            'startColumn' => 3,
            'endColumn' => 13,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'params' => 
          array (
            'name' => 'params',
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
            'startLine' => 204,
            'endLine' => 204,
            'startColumn' => 3,
            'endColumn' => 15,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'column' => 
          array (
            'name' => 'column',
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
            'startLine' => 205,
            'endLine' => 205,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'failure' => 
          array (
            'name' => 'failure',
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
            'startLine' => 206,
            'endLine' => 206,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'context' => 
          array (
            'name' => 'context',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 207,
                'endLine' => 207,
                'startTokenPos' => 657,
                'startFilePos' => 7873,
                'endTokenPos' => 658,
                'endFilePos' => 7874,
              ),
            ),
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
            'startLine' => 207,
            'endLine' => 207,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 4,
            'isOptional' => true,
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Read one column out of one query, preserving the failure/empty distinction.
 *
 * @param string $sql The query.
 * @param array<int, mixed> $params Bound parameters.
 * @param string $column The column to pull.
 * @param string $failure The warning to log when it fails.
 * @param array<string, mixed> $context Extra log context.
 *
 * @return array<int, string>|null The values, or null when unreadable.
 */',
        'startLine' => 202,
        'endLine' => 215,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The register ids this step cares about, keyed by slug.
 *
 * Returns null on a read failure. An empty map says "this install has none
 * of these registers", which is a completed migration or a fresh install;
 * null says "the register table could not be read", which is neither.
 *
 * @return array<string, int>|null
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq\'s five
 *  OpenRegister registers into one. Pointing this at an existing spec would
 *  report conformance to a requirement that says nothing about it.
 */',
        'startLine' => 230,
        'endLine' => 254,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'aliasName' => NULL,
      ),
      'tableNames' => 
      array (
        'name' => 'tableNames',
        'parameters' => 
        array (
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Every table name on this connection that carries the shard-table marker.
 *
 * The LIKE pattern is bound and deliberately loose — `_` is a single-char
 * wildcard in LIKE, which only widens it — because the authoritative filter
 * is the anchored PCRE in ConsolidateRegistersDecisions, not the SQL. What
 * matters is that the names come FROM the database rather than being
 * composed here, and that the match is on the `openregister_table_` marker
 * rather than on a guessed `oc_` prefix.
 *
 * On a database with no `information_schema` this query fails, which
 * returns null and stops the step: "cannot see the install" is not
 * "the install is empty".
 *
 * @return array<int, string>|null Table names, or null when unreadable.
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq\'s five
 *  OpenRegister registers into one. Pointing this at an existing spec would
 *  report conformance to a requirement that says nothing about it.
 */',
        'startLine' => 276,
        'endLine' => 283,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
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
            'startLine' => 296,
            'endLine' => 296,
            'startColumn' => 28,
            'endColumn' => 40,
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The columns of one table, as the database reports them.
 *
 * @param string $table The table name.
 *
 * @return array<int, string>|null Column names, or null when unreadable.
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq\'s five
 *  OpenRegister registers into one. Pointing this at an existing spec would
 *  report conformance to a requirement that says nothing about it.
 */',
        'startLine' => 296,
        'endLine' => 304,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'aliasName' => NULL,
      ),
      'uuidsIn' => 
      array (
        'name' => 'uuidsIn',
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
            'startLine' => 317,
            'endLine' => 317,
            'startColumn' => 26,
            'endColumn' => 38,
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Every uuid held by one shard table.
 *
 * @param string $table The table name, already shape-checked.
 *
 * @return array<int, string>|null Uuids, or null when unreadable.
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq\'s five
 *  OpenRegister registers into one. Pointing this at an existing spec would
 *  report conformance to a requirement that says nothing about it.
 */',
        'startLine' => 317,
        'endLine' => 327,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'aliasName' => NULL,
      ),
      'uuidsPresent' => 
      array (
        'name' => 'uuidsPresent',
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
            'startLine' => 341,
            'endLine' => 341,
            'startColumn' => 31,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'uuids' => 
          array (
            'name' => 'uuids',
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
            'startLine' => 341,
            'endLine' => 341,
            'startColumn' => 46,
            'endColumn' => 57,
            'parameterIndex' => 1,
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Which of these uuids the target table already holds.
 *
 * @param string $table The target table, already shape-checked.
 * @param array<int, string> $uuids The uuids to look for.
 *
 * @return array<int, string>|null The subset present, or null when unreadable.
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq\'s five
 *  OpenRegister registers into one. Pointing this at an existing spec would
 *  report conformance to a requirement that says nothing about it.
 */',
        'startLine' => 341,
        'endLine' => 362,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
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