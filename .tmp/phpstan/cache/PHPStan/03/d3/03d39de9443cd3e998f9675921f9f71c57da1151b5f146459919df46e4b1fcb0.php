<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Repair/MigrateSchemaApplicationId.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Repair\MigrateSchemaApplicationId
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-e20f6f76c428c943b9a2325087fc30d83e71e75410e8cccbb2d690b09e9b5149',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Repair\\MigrateSchemaApplicationId',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Repair/MigrateSchemaApplicationId.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Repair',
    'name' => 'OCA\\Filinq\\Repair\\MigrateSchemaApplicationId',
    'shortName' => 'MigrateSchemaApplicationId',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Moves this app\'s schema rows onto the new application id.
 *
 * @spec exclude No canonical spec covers the `docudesk` -> `filinq` schema
 *  application-id migration. Pointing this at an existing spec would report
 *  conformance to a requirement that says nothing about it.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 66,
    'endLine' => 243,
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
      'OLD_APP_ID' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateSchemaApplicationId',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateSchemaApplicationId',
        'name' => 'OLD_APP_ID',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'docudesk\'',
          'attributes' => 
          array (
            'startLine' => 73,
            'endLine' => 73,
            'startTokenPos' => 64,
            'startFilePos' => 3053,
            'endTokenPos' => 64,
            'endFilePos' => 3062,
          ),
        ),
        'docComment' => '/**
 * The application id these schemas were written under.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 2,
        'endColumn' => 38,
      ),
      'NEW_APP_ID' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateSchemaApplicationId',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateSchemaApplicationId',
        'name' => 'NEW_APP_ID',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq\'',
          'attributes' => 
          array (
            'startLine' => 80,
            'endLine' => 80,
            'startTokenPos' => 77,
            'startFilePos' => 3179,
            'endTokenPos' => 77,
            'endFilePos' => 3186,
          ),
        ),
        'docComment' => '/**
 * The application id the import now looks them up by.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 80,
        'endLine' => 80,
        'startColumn' => 2,
        'endColumn' => 36,
      ),
    ),
    'immediateProperties' => 
    array (
      'db' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateSchemaApplicationId',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateSchemaApplicationId',
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
        'startLine' => 93,
        'endLine' => 93,
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
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateSchemaApplicationId',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateSchemaApplicationId',
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
        'startLine' => 94,
        'endLine' => 94,
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
            'startLine' => 93,
            'endLine' => 93,
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
            'startLine' => 94,
            'endLine' => 94,
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
 *
 * @spec exclude No canonical spec covers the `docudesk` -> `filinq` schema
 *  application-id migration. Pointing this at an existing spec would report
 *  conformance to a requirement that says nothing about it.
 */',
        'startLine' => 92,
        'endLine' => 96,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateSchemaApplicationId',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateSchemaApplicationId',
        'currentClassName' => 'OCA\\Filinq\\Repair\\MigrateSchemaApplicationId',
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
 * Step name shown by `occ maintenance:repair`.
 *
 * @return string
 *
 * @spec exclude No canonical spec covers the `docudesk` -> `filinq` schema
 *  application-id migration. Pointing this at an existing spec would report
 *  conformance to a requirement that says nothing about it.
 */',
        'startLine' => 107,
        'endLine' => 109,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateSchemaApplicationId',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateSchemaApplicationId',
        'currentClassName' => 'OCA\\Filinq\\Repair\\MigrateSchemaApplicationId',
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
            'startLine' => 122,
            'endLine' => 122,
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
 * Re-point every schema that has no twin under the new application id.
 *
 * @param IOutput $output Repair output.
 *
 * @return void
 *
 * @spec exclude No canonical spec covers the `docudesk` -> `filinq` schema
 *  application-id migration. Pointing this at an existing spec would report
 *  conformance to a requirement that says nothing about it.
 */',
        'startLine' => 122,
        'endLine' => 161,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateSchemaApplicationId',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateSchemaApplicationId',
        'currentClassName' => 'OCA\\Filinq\\Repair\\MigrateSchemaApplicationId',
        'aliasName' => NULL,
      ),
      'slugsUnderNewAppId' => 
      array (
        'name' => 'slugsUnderNewAppId',
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
 * Slugs already claimed under the NEW application id.
 *
 * Returns null on a read failure, which the caller treats as "do nothing".
 * An empty list and a failed read must not look the same: an empty list
 * says every move is safe, and a failed read says nothing at all.
 *
 * @return array<int, string>|null Lower-cased slugs, or null when unreadable.
 */',
        'startLine' => 172,
        'endLine' => 190,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateSchemaApplicationId',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateSchemaApplicationId',
        'currentClassName' => 'OCA\\Filinq\\Repair\\MigrateSchemaApplicationId',
        'aliasName' => NULL,
      ),
      'slugsUnderOldAppId' => 
      array (
        'name' => 'slugsUnderOldAppId',
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
 * Slugs still sitting under the OLD application id.
 *
 * @return array<int, string>
 */',
        'startLine' => 197,
        'endLine' => 215,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateSchemaApplicationId',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateSchemaApplicationId',
        'currentClassName' => 'OCA\\Filinq\\Repair\\MigrateSchemaApplicationId',
        'aliasName' => NULL,
      ),
      'repoint' => 
      array (
        'name' => 'repoint',
        'parameters' => 
        array (
          'slug' => 
          array (
            'name' => 'slug',
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
            'startLine' => 227,
            'endLine' => 227,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Move one schema onto the new application id.
 *
 * Scoped to the (old application, slug) pair so it can never touch a row
 * belonging to another app that happens to share a slug.
 *
 * @param string $slug The schema slug to move.
 *
 * @return bool True when the row was updated.
 */',
        'startLine' => 227,
        'endLine' => 242,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateSchemaApplicationId',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateSchemaApplicationId',
        'currentClassName' => 'OCA\\Filinq\\Repair\\MigrateSchemaApplicationId',
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