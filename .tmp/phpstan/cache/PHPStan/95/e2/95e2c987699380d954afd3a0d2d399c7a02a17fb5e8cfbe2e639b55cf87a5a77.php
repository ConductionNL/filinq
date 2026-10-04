<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/ListReferenceResolver.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\ListReferenceResolver
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-a989b4dbe9c48344a75337d059f5203ac795a4b1b4c182bc1a48b0537b50361e',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/ListReferenceResolver.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
    'shortName' => 'ListReferenceResolver',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Resolves listRefs (collection references) against OpenRegister\'s
 * slug-aware search API.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-generation-list-refs/specs/document-creatie-sjablonen/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 48,
    'endLine' => 445,
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
      'MAX_LIST_REFS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'name' => 'MAX_LIST_REFS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '10',
          'attributes' => 
          array (
            'startLine' => 55,
            'endLine' => 55,
            'startTokenPos' => 55,
            'startFilePos' => 1672,
            'endTokenPos' => 55,
            'endFilePos' => 1673,
          ),
        ),
        'docComment' => '/**
 * Maximum number of listRefs accepted per request
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 55,
        'endLine' => 55,
        'startColumn' => 2,
        'endColumn' => 34,
      ),
      'DEFAULT_LIST_LIMIT' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'name' => 'DEFAULT_LIST_LIMIT',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '50',
          'attributes' => 
          array (
            'startLine' => 62,
            'endLine' => 62,
            'startTokenPos' => 68,
            'startFilePos' => 1810,
            'endTokenPos' => 68,
            'endFilePos' => 1811,
          ),
        ),
        'docComment' => '/**
 * Default per-list result limit when a listRef does not specify one
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 2,
        'endColumn' => 39,
      ),
      'MAX_LIST_LIMIT' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'name' => 'MAX_LIST_LIMIT',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '500',
          'attributes' => 
          array (
            'startLine' => 69,
            'endLine' => 69,
            'startTokenPos' => 81,
            'startFilePos' => 1943,
            'endTokenPos' => 81,
            'endFilePos' => 1945,
          ),
        ),
        'docComment' => '/**
 * Hard cap on a listRef\'s \'limit\', regardless of what is requested
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 69,
        'endLine' => 69,
        'startColumn' => 2,
        'endColumn' => 36,
      ),
      'AS_KEY_PATTERN' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'name' => 'AS_KEY_PATTERN',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'/^[a-zA-Z_][a-zA-Z0-9_]{0,63}$/\'',
          'attributes' => 
          array (
            'startLine' => 76,
            'endLine' => 76,
            'startTokenPos' => 94,
            'startFilePos' => 2062,
            'endTokenPos' => 94,
            'endFilePos' => 2094,
          ),
        ),
        'docComment' => '/**
 * Allowed shape for a listRef\'s \'as\' context key
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 76,
        'endLine' => 76,
        'startColumn' => 2,
        'endColumn' => 66,
      ),
    ),
    'immediateProperties' => 
    array (
      'container' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'name' => 'container',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Psr\\Container\\ContainerInterface',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 87,
        'endLine' => 87,
        'startColumn' => 3,
        'endColumn' => 48,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'appManager' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'name' => 'appManager',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\App\\IAppManager',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 88,
        'endLine' => 88,
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
          'container' => 
          array (
            'name' => 'container',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Psr\\Container\\ContainerInterface',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 87,
            'endLine' => 87,
            'startColumn' => 3,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'appManager' => 
          array (
            'name' => 'appManager',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\App\\IAppManager',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 88,
            'endLine' => 88,
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
 * Constructor for ListReferenceResolver
 *
 * @param ContainerInterface $container Container for dependency injection
 * @param IAppManager $appManager App manager interface
 *
 * @return void
 */',
        'startLine' => 86,
        'endLine' => 91,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'aliasName' => NULL,
      ),
      'getObjectService' => 
      array (
        'name' => 'getObjectService',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\OpenRegister\\Service\\ObjectService',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the ObjectService from OpenRegister
 *
 * @return \\OCA\\OpenRegister\\Service\\ObjectService The ObjectService instance
 *
 * @throws RuntimeException If OpenRegister is not available
 */',
        'startLine' => 100,
        'endLine' => 111,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'aliasName' => NULL,
      ),
      'resolve' => 
      array (
        'name' => 'resolve',
        'parameters' => 
        array (
          'listRefs' => 
          array (
            'name' => 'listRefs',
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
            'startLine' => 139,
            'endLine' => 139,
            'startColumn' => 26,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'reservedKeys' => 
          array (
            'name' => 'reservedKeys',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 139,
                'endLine' => 139,
                'startTokenPos' => 234,
                'startFilePos' => 4468,
                'endTokenPos' => 235,
                'endFilePos' => 4469,
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
            'startLine' => 139,
            'endLine' => 139,
            'startColumn' => 43,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => true,
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
 * Resolve listRefs (collection references) into arrays of objects.
 *
 * Every listRef is validated up front (fail-fast) before any
 * OpenRegister search runs, so a malformed listRef never triggers a
 * partial lookup. Once validated, each listRef is resolved
 * independently; a search failure for one listRef does not abort the
 * others and is instead collected as a soft error (mirroring dataRefs
 * in DataResolverService::resolve()).
 *
 * @param array $listRefs Array of collection references, each with
 *                        \'register\', \'schema\' and optional
 *                        \'filter\', \'limit\', \'order\', \'as\' keys
 * @param array $reservedKeys Context keys already used by dataRefs; a
 *                            listRef\'s \'as\' key must not collide with
 *                            these
 *
 * @return array{data: array<string, array>, errors: array} Resolved
 *                                                          lists keyed by \'as\', and any per-item resolution errors
 *
 * @throws Exception If a listRef violates a request-level guardrail
 *                   (too many entries, invalid filter values, invalid
 *                   or colliding \'as\' key) — all HTTP 400
 *
 * @spec openspec/changes/document-generation-list-refs/specs/document-creatie-sjablonen/spec.md
 */',
        'startLine' => 139,
        'endLine' => 168,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'aliasName' => NULL,
      ),
      'resolveValidatedListRefs' => 
      array (
        'name' => 'resolveValidatedListRefs',
        'parameters' => 
        array (
          'listRefs' => 
          array (
            'name' => 'listRefs',
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
            'startLine' => 178,
            'endLine' => 178,
            'startColumn' => 44,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'asKeyByIndex' => 
          array (
            'name' => 'asKeyByIndex',
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
            'startLine' => 178,
            'endLine' => 178,
            'startColumn' => 61,
            'endColumn' => 79,
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
 * Resolve a set of already-validated listRefs against OpenRegister.
 *
 * @param array $listRefs The validated listRefs
 * @param array $asKeyByIndex The \'as\' key each listRef resolves under, by index
 *
 * @return array{data: array<string, array>, errors: array}
 */',
        'startLine' => 178,
        'endLine' => 203,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'aliasName' => NULL,
      ),
      'validateListReference' => 
      array (
        'name' => 'validateListReference',
        'parameters' => 
        array (
          'ref' => 
          array (
            'name' => 'ref',
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
            'startLine' => 220,
            'endLine' => 220,
            'startColumn' => 41,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'index' => 
          array (
            'name' => 'index',
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
            'startLine' => 220,
            'endLine' => 220,
            'startColumn' => 53,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'usedKeys' => 
          array (
            'name' => 'usedKeys',
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
            'startLine' => 220,
            'endLine' => 220,
            'startColumn' => 65,
            'endColumn' => 79,
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
 * Validate a single listRef and return its resolved \'as\' context key.
 *
 * @param array $ref The listRef to validate
 * @param int $index The index of the listRef in the array (for error messages)
 * @param array $usedKeys Context keys already claimed by dataRefs or earlier listRefs
 *
 * @return string The validated \'as\' key this listRef will resolve under
 *
 * @throws Exception If the listRef is missing required fields, has a
 *                   non-scalar filter value, an out-of-range limit, or
 *                   an invalid/colliding \'as\' key — all HTTP 400
 *
 * @spec openspec/changes/document-generation-list-refs/specs/document-creatie-sjablonen/spec.md
 */',
        'startLine' => 220,
        'endLine' => 226,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'aliasName' => NULL,
      ),
      'validateListReferenceFields' => 
      array (
        'name' => 'validateListReferenceFields',
        'parameters' => 
        array (
          'ref' => 
          array (
            'name' => 'ref',
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
            'startLine' => 238,
            'endLine' => 238,
            'startColumn' => 47,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'index' => 
          array (
            'name' => 'index',
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
            'startLine' => 238,
            'endLine' => 238,
            'startColumn' => 59,
            'endColumn' => 68,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Validate that the listRef carries its required \'register\' and \'schema\' fields.
 *
 * @param array $ref The listRef to validate
 * @param int $index The index of the listRef in the array (for error messages)
 *
 * @return void
 *
 * @throws Exception If a required field is missing
 */',
        'startLine' => 238,
        'endLine' => 248,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'aliasName' => NULL,
      ),
      'validateListReferenceFilter' => 
      array (
        'name' => 'validateListReferenceFilter',
        'parameters' => 
        array (
          'ref' => 
          array (
            'name' => 'ref',
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
            'startLine' => 260,
            'endLine' => 260,
            'startColumn' => 47,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'index' => 
          array (
            'name' => 'index',
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
            'startLine' => 260,
            'endLine' => 260,
            'startColumn' => 59,
            'endColumn' => 68,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Validate that the listRef\'s \'filter\' (if any) is an object of scalar values.
 *
 * @param array $ref The listRef to validate
 * @param int $index The index of the listRef in the array (for error messages)
 *
 * @return void
 *
 * @throws Exception If \'filter\' is not an object, or a value is non-scalar
 */',
        'startLine' => 260,
        'endLine' => 279,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'aliasName' => NULL,
      ),
      'validateListReferenceLimit' => 
      array (
        'name' => 'validateListReferenceLimit',
        'parameters' => 
        array (
          'ref' => 
          array (
            'name' => 'ref',
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
            'startLine' => 291,
            'endLine' => 291,
            'startColumn' => 46,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'index' => 
          array (
            'name' => 'index',
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
            'startLine' => 291,
            'endLine' => 291,
            'startColumn' => 58,
            'endColumn' => 67,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Validate that the listRef\'s \'limit\' (if any) is an integer within bounds.
 *
 * @param array $ref The listRef to validate
 * @param int $index The index of the listRef in the array (for error messages)
 *
 * @return void
 *
 * @throws Exception If \'limit\' is not an integer between 1 and MAX_LIST_LIMIT
 */',
        'startLine' => 291,
        'endLine' => 307,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'aliasName' => NULL,
      ),
      'validateListReferenceAsKey' => 
      array (
        'name' => 'validateListReferenceAsKey',
        'parameters' => 
        array (
          'ref' => 
          array (
            'name' => 'ref',
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
            'startLine' => 327,
            'endLine' => 327,
            'startColumn' => 46,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'index' => 
          array (
            'name' => 'index',
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
            'startLine' => 327,
            'endLine' => 327,
            'startColumn' => 58,
            'endColumn' => 67,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'usedKeys' => 
          array (
            'name' => 'usedKeys',
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
            'startLine' => 327,
            'endLine' => 327,
            'startColumn' => 70,
            'endColumn' => 84,
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
 * Validate the listRef\'s \'as\' context key: shape and non-collision.
 *
 * An explicit \'as\' MUST already match AS_KEY_PATTERN (a caller mistake
 * is a 400, not silently corrected). The DEFAULT (\'schema\' + \'_list\')
 * is derived from the schema slug, which routinely contains hyphens
 * (e.g. \'v-app-competitors\' — not a legal Twig variable name), so it is
 * sanitised via {@see slugToIdentifier()} before the pattern check.
 *
 * @param array $ref The listRef to validate
 * @param int $index The index of the listRef in the array (for error messages)
 * @param array $usedKeys Context keys already claimed by dataRefs or earlier listRefs
 *
 * @return string The validated \'as\' key
 *
 * @throws Exception If \'as\' does not match the allowed pattern, or collides
 *                   with an existing data key
 */',
        'startLine' => 327,
        'endLine' => 350,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'aliasName' => NULL,
      ),
      'slugToIdentifier' => 
      array (
        'name' => 'slugToIdentifier',
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
            'startLine' => 365,
            'endLine' => 365,
            'startColumn' => 36,
            'endColumn' => 47,
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
 * Convert a register/schema slug into a legal Twig identifier fragment.
 *
 * Slugs are conventionally kebab-case (e.g. \'v-app-competitors\'), which
 * is not a legal Twig variable name — Twig would parse the hyphens as
 * subtraction. Every character outside [a-zA-Z0-9_] becomes \'_\', and a
 * leading digit is prefixed with \'_\' so the result is always a legal
 * identifier start.
 *
 * @param string $slug The register or schema slug
 *
 * @return string A string safe to use as (part of) a Twig context key
 */',
        'startLine' => 365,
        'endLine' => 372,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'aliasName' => NULL,
      ),
      'resolveList' => 
      array (
        'name' => 'resolveList',
        'parameters' => 
        array (
          'ref' => 
          array (
            'name' => 'ref',
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
            'startLine' => 401,
            'endLine' => 401,
            'startColumn' => 31,
            'endColumn' => 40,
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
 * Resolve a single listRef against OpenRegister\'s paginated search.
 *
 * Uses `setRegister()`/`setSchema()` (both slug-aware) to put the
 * ObjectService instance into register/schema context, then
 * {@see \\OCA\\OpenRegister\\Service\\ObjectService::searchObjectsPaginated()}
 * — the SAME method + context pattern OpenRegister\'s own
 * `ObjectsController::index()` uses. This matters: the sibling
 * `searchObjects()`/`searchObjectsBySlug()` path never consults a
 * schema\'s `x-openregister-object-source` and silently returns nothing
 * for schemas backed by an external DBAL register (e.g. `spectr-live`)
 * — only `searchObjectsPaginated()` checks `$this->currentSchema`
 * (set by `setSchema()`) and delegates to the object-source provider
 * when one is configured. `setRegister()`/`setSchema()` in turn
 * auto-inject the resolved numeric `_register`/`_schema` into the
 * query, so `filter` stays plain top-level keys.
 *
 * @param array $ref The listRef, already validated by {@see validateListReference()}
 *
 * @return array The resolved objects, each serialized via jsonSerialize()
 *               where available
 *
 * @throws Exception If the register/schema slug does not resolve, or the
 *                   OpenRegister search fails
 *
 * @spec openspec/changes/document-generation-list-refs/specs/document-creatie-sjablonen/spec.md
 */',
        'startLine' => 401,
        'endLine' => 444,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\ListReferenceResolver',
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