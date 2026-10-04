<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/BatchStateService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\BatchStateService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-d5afdf7485540538a45c2ff6057c539536febb216462cfc3b677bb65e4d1daf5',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\BatchStateService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/BatchStateService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\BatchStateService',
    'shortName' => 'BatchStateService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Read/write store for anonymization batch state, backed by OpenRegister and
 * fronted by the distributed cache.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-status-endpoint
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 60,
    'endLine' => 309,
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
      'CACHE_TTL' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'name' => 'CACHE_TTL',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '7200',
          'attributes' => 
          array (
            'startLine' => 61,
            'endLine' => 61,
            'startTokenPos' => 68,
            'startFilePos' => 2295,
            'endTokenPos' => 68,
            'endFilePos' => 2298,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 61,
        'endLine' => 61,
        'startColumn' => 2,
        'endColumn' => 32,
      ),
      'DEFAULT_MAX_FILES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'name' => 'DEFAULT_MAX_FILES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '100',
          'attributes' => 
          array (
            'startLine' => 62,
            'endLine' => 62,
            'startTokenPos' => 79,
            'startFilePos' => 2336,
            'endTokenPos' => 79,
            'endFilePos' => 2338,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 2,
        'endColumn' => 39,
      ),
      'CACHE_PREFIX' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'name' => 'CACHE_PREFIX',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq_batch_\'',
          'attributes' => 
          array (
            'startLine' => 63,
            'endLine' => 63,
            'startTokenPos' => 90,
            'startFilePos' => 2371,
            'endTokenPos' => 90,
            'endFilePos' => 2385,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 63,
        'endLine' => 63,
        'startColumn' => 2,
        'endColumn' => 46,
      ),
    ),
    'immediateProperties' => 
    array (
      'cache' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'name' => 'cache',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\ICache',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Distributed cache instance used to persist batch records.
 *
 * @var ICache
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 70,
        'endLine' => 70,
        'startColumn' => 2,
        'endColumn' => 23,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'appConfig' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'name' => 'appConfig',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IAppConfig',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 90,
        'endLine' => 90,
        'startColumn' => 3,
        'endColumn' => 40,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
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
        'startLine' => 91,
        'endLine' => 91,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'userSession' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'name' => 'userSession',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IUserSession',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 92,
        'endLine' => 92,
        'startColumn' => 3,
        'endColumn' => 44,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'groupManager' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'name' => 'groupManager',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IGroupManager',
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
        'endColumn' => 46,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'repository' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'name' => 'repository',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\BatchStateRepository',
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
        'endColumn' => 51,
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
          'cacheFactory' => 
          array (
            'name' => 'cacheFactory',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\ICacheFactory',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 89,
            'endLine' => 89,
            'startColumn' => 3,
            'endColumn' => 29,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'appConfig' => 
          array (
            'name' => 'appConfig',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IAppConfig',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 90,
            'endLine' => 90,
            'startColumn' => 3,
            'endColumn' => 40,
            'parameterIndex' => 1,
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
            'startLine' => 91,
            'endLine' => 91,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'userSession' => 
          array (
            'name' => 'userSession',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IUserSession',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 92,
            'endLine' => 92,
            'startColumn' => 3,
            'endColumn' => 44,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'groupManager' => 
          array (
            'name' => 'groupManager',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IGroupManager',
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
            'endColumn' => 46,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'repository' => 
          array (
            'name' => 'repository',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\BatchStateRepository',
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
            'endColumn' => 51,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor for BatchStateService
 *
 * @param ICacheFactory $cacheFactory Factory used to obtain the distributed cache.
 * @param IAppConfig $appConfig App-config lookup for runtime limits.
 * @param LoggerInterface $logger Logger for lifecycle events.
 * @param IUserSession $userSession User session for ownership checks.
 * @param IGroupManager $groupManager Group manager for admin bypass.
 * @param BatchStateRepository $repository OpenRegister-backed store of record
 *                                         for batch state. Injected via the
 *                                         constructor (ADR-083) so the
 *                                         dependency is declared where a
 *                                         reader and a gate can both see it.
 *
 * @return void
 */',
        'startLine' => 88,
        'endLine' => 98,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'aliasName' => NULL,
      ),
      'getMaxFiles' => 
      array (
        'name' => 'getMaxFiles',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Return the maximum number of files allowed in a single batch.
 *
 * @return int Configured or default maximum.
 *
 * @spec openspec/specs/batch-anonymization/spec.md
 */',
        'startLine' => 107,
        'endLine' => 125,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'aliasName' => NULL,
      ),
      'getCacheTtl' => 
      array (
        'name' => 'getCacheTtl',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the cache TTL in seconds for batch records.
 *
 * Reads `filinq.batch.cache_ttl_seconds` from app-config (canonical key
 * declared in manifest.yaml under docudesk-adopt-or-abstractions task 11).
 * Returns the in-class constant when unset so existing deployments behave
 * identically until an admin overrides it.
 *
 * @return int TTL in seconds.
 */',
        'startLine' => 137,
        'endLine' => 148,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'aliasName' => NULL,
      ),
      'createBatch' => 
      array (
        'name' => 'createBatch',
        'parameters' => 
        array (
          'userId' => 
          array (
            'name' => 'userId',
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
            'startLine' => 162,
            'endLine' => 162,
            'startColumn' => 30,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'files' => 
          array (
            'name' => 'files',
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
            'startLine' => 162,
            'endLine' => 162,
            'startColumn' => 46,
            'endColumn' => 57,
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
 * Create and persist a new batch record for a user.
 *
 * @param string $userId User identifier to associate with the batch.
 * @param array<int, array<string, mixed>> $files Per-file entries to seed the batch with.
 *
 * @return array<string, mixed> The newly created batch record.
 *
 * @throws RuntimeException When the batch cannot be persisted.
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
 */',
        'startLine' => 162,
        'endLine' => 179,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'aliasName' => NULL,
      ),
      'getBatch' => 
      array (
        'name' => 'getBatch',
        'parameters' => 
        array (
          'batchId' => 
          array (
            'name' => 'batchId',
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
            'startLine' => 190,
            'endLine' => 190,
            'startColumn' => 27,
            'endColumn' => 41,
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
 * Load a batch record by ID and refresh its TTL.
 *
 * @param string $batchId Batch identifier.
 *
 * @return array<string, mixed>|null The decoded batch record, or null when missing or corrupt.
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-status-endpoint
 */',
        'startLine' => 190,
        'endLine' => 223,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'aliasName' => NULL,
      ),
      'readThrough' => 
      array (
        'name' => 'readThrough',
        'parameters' => 
        array (
          'batchId' => 
          array (
            'name' => 'batchId',
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
            'startLine' => 239,
            'endLine' => 239,
            'startColumn' => 31,
            'endColumn' => 45,
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
 * Load a batch record from the cache, falling back to OpenRegister.
 *
 * The cache is a fast path only. A miss — or a corrupt/legacy entry that
 * does not decode to an array — falls through to the store of record
 * rather than being reported as "no such batch", because on an instance
 * with no distributed cache configured EVERY read is a miss.
 *
 * @param string $batchId Batch identifier.
 *
 * @return array<string, mixed>|null The batch record, or null when absent.
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-status-endpoint
 */',
        'startLine' => 239,
        'endLine' => 254,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'aliasName' => NULL,
      ),
      'updateBatch' => 
      array (
        'name' => 'updateBatch',
        'parameters' => 
        array (
          'batchId' => 
          array (
            'name' => 'batchId',
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
            'startLine' => 268,
            'endLine' => 268,
            'startColumn' => 30,
            'endColumn' => 44,
            'parameterIndex' => 0,
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
            'startLine' => 268,
            'endLine' => 268,
            'startColumn' => 47,
            'endColumn' => 58,
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
 * Persist an updated batch record.
 *
 * @param string $batchId Batch identifier.
 * @param array<string, mixed> $batch Full batch record to store.
 *
 * @return void
 *
 * @throws RuntimeException When the batch cannot be persisted.
 *
 * @spec openspec/specs/batch-anonymization/spec.md
 */',
        'startLine' => 268,
        'endLine' => 274,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'aliasName' => NULL,
      ),
      'deleteBatch' => 
      array (
        'name' => 'deleteBatch',
        'parameters' => 
        array (
          'batchId' => 
          array (
            'name' => 'batchId',
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
            'startLine' => 292,
            'endLine' => 292,
            'startColumn' => 30,
            'endColumn' => 44,
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
 * Remove a batch record from the store.
 *
 * @param string $batchId Batch identifier.
 *
 * @return void
 *
 * @spec openspec/specs/batch-anonymization/spec.md
 *
 * @orphaned-write-capability exclude completes the store\'s CRUD surface
 * beside createBatch/getBatch/updateBatch. No caller has needed it yet —
 * but a store that can create and update and not delete is the odd shape,
 * and now that records outlive the request in OpenRegister rather than
 * expiring with a cache TTL, the ability to remove one is the only way a
 * batch ever leaves the store.
 */',
        'startLine' => 292,
        'endLine' => 296,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'aliasName' => NULL,
      ),
      'generateUuid' => 
      array (
        'name' => 'generateUuid',
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
 * Generate an RFC 4122 version-4 UUID for use as a batch identifier.
 *
 * @return string Hyphenated UUID string.
 */',
        'startLine' => 303,
        'endLine' => 308,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchStateService',
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