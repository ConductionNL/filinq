<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/BatchStateRepository.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\BatchStateRepository
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-3bba4bf9196f1424c621d2bb2b484a0d71af1f9bb3595c10abcbff79ce74392b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/BatchStateRepository.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\BatchStateRepository',
    'shortName' => 'BatchStateRepository',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * OpenRegister-backed store for anonymization batch records.
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
    'startLine' => 58,
    'endLine' => 305,
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
      'REGISTER' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'name' => 'REGISTER',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq\'',
          'attributes' => 
          array (
            'startLine' => 68,
            'endLine' => 68,
            'startTokenPos' => 55,
            'startFilePos' => 2535,
            'endTokenPos' => 55,
            'endFilePos' => 2542,
          ),
        ),
        'docComment' => '/**
 * Register slug the batch schema lives in.
 *
 * `filinq`, not `document`: this app declares ONE register holding all 23
 * schemas. The five it used to declare are retired.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 68,
        'endLine' => 68,
        'startColumn' => 2,
        'endColumn' => 34,
      ),
      'SCHEMA' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'name' => 'SCHEMA',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'anonymizationBatch\'',
          'attributes' => 
          array (
            'startLine' => 75,
            'endLine' => 75,
            'startTokenPos' => 68,
            'startFilePos' => 2635,
            'endTokenPos' => 68,
            'endFilePos' => 2654,
          ),
        ),
        'docComment' => '/**
 * Schema slug for a batch record.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 75,
        'endLine' => 75,
        'startColumn' => 2,
        'endColumn' => 44,
      ),
      'DOCUMENTS_PROPERTY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'name' => 'DOCUMENTS_PROPERTY',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'documents\'',
          'attributes' => 
          array (
            'startLine' => 88,
            'endLine' => 88,
            'startTokenPos' => 81,
            'startFilePos' => 3166,
            'endTokenPos' => 81,
            'endFilePos' => 3176,
          ),
        ),
        'docComment' => '/**
 * Property name the per-file entries are stored under in OpenRegister.
 *
 * The in-process batch record calls this list `files`. It is stored as
 * `documents` because `files` is also the name of an ObjectEntity column
 * (surfaced under `@self.files` for attachments), and a data property that
 * shadows a `@self` key is a collision waiting to happen. The mapping is
 * confined to this class; nothing outside it sees `documents`.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 88,
        'endLine' => 88,
        'startColumn' => 2,
        'endColumn' => 48,
      ),
    ),
    'immediateProperties' => 
    array (
      'openRegister' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'name' => 'openRegister',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\OpenRegisterAvailabilityService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 105,
        'endLine' => 105,
        'startColumn' => 3,
        'endColumn' => 64,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
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
        'startLine' => 106,
        'endLine' => 106,
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
          'openRegister' => 
          array (
            'name' => 'openRegister',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\OpenRegisterAvailabilityService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 105,
            'endLine' => 105,
            'startColumn' => 3,
            'endColumn' => 64,
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
            'startLine' => 106,
            'endLine' => 106,
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
 * Constructor for BatchStateRepository
 *
 * The OpenRegister handle is a CONSTRUCTOR dependency (ADR-083): a bare
 * `$container->get()` inside a method declares the dependency nowhere a
 * reader — or a gate — can see it.
 *
 * @param OpenRegisterAvailabilityService $openRegister Owns the OpenRegister
 *                                                      installed/version probe
 *                                                      and the ObjectService handle.
 * @param LoggerInterface $logger Structured logger.
 *
 * @return void
 */',
        'startLine' => 104,
        'endLine' => 109,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'aliasName' => NULL,
      ),
      'isAvailable' => 
      array (
        'name' => 'isAvailable',
        'parameters' => 
        array (
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
 * Whether OpenRegister is installed and new enough to hold batch state.
 *
 * @return bool True when batch state can be persisted.
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-status-endpoint
 */',
        'startLine' => 118,
        'endLine' => 120,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'aliasName' => NULL,
      ),
      'find' => 
      array (
        'name' => 'find',
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
            'startLine' => 139,
            'endLine' => 139,
            'startColumn' => 23,
            'endColumn' => 37,
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
 * Load one batch record by its identifier.
 *
 * A miss is a miss: `ObjectService::find()` raises DoesNotExistException
 * for an unknown identifier, which is translated to null here. Anything
 * else — a missing schema, a broken register — is NOT swallowed, because a
 * store that reports "no such batch" when it actually cannot reach its
 * backing store is indistinguishable from a store that is working.
 *
 * @param string $batchId Batch identifier (also the OpenRegister object UUID).
 *
 * @return array<string, mixed>|null The batch record, or null when absent.
 *
 * @throws RuntimeException When OpenRegister is unavailable.
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-status-endpoint
 */',
        'startLine' => 139,
        'endLine' => 165,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'aliasName' => NULL,
      ),
      'save' => 
      array (
        'name' => 'save',
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
            'startLine' => 183,
            'endLine' => 183,
            'startColumn' => 23,
            'endColumn' => 37,
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
            'startLine' => 183,
            'endLine' => 183,
            'startColumn' => 40,
            'endColumn' => 51,
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
 * Persist a batch record, creating it when it does not exist yet.
 *
 * The batch identifier is passed as the OpenRegister object UUID, so the
 * write is an upsert keyed by the identifier the rest of the app already
 * hands around; no secondary lookup is needed to update.
 *
 * @param string $batchId Batch identifier (used as the object UUID).
 * @param array<string, mixed> $batch The full batch record.
 *
 * @return void
 *
 * @throws RuntimeException When OpenRegister is unavailable or the write fails.
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
 */',
        'startLine' => 183,
        'endLine' => 216,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'aliasName' => NULL,
      ),
      'delete' => 
      array (
        'name' => 'delete',
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
            'startLine' => 230,
            'endLine' => 230,
            'startColumn' => 25,
            'endColumn' => 39,
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
 * Remove a batch record.
 *
 * Deleting a batch that is already gone is not an error — the caller\'s
 * intent (no batch under this id) is satisfied either way.
 *
 * @param string $batchId Batch identifier.
 *
 * @return void
 *
 * @spec openspec/specs/batch-anonymization/spec.md
 */',
        'startLine' => 230,
        'endLine' => 250,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'aliasName' => NULL,
      ),
      'toStoredShape' => 
      array (
        'name' => 'toStoredShape',
        'parameters' => 
        array (
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
            'startLine' => 259,
            'endLine' => 259,
            'startColumn' => 33,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Translate the in-process batch record into its stored representation.
 *
 * @param array<string, mixed> $batch The in-process batch record.
 *
 * @return array<string, mixed> The payload handed to OpenRegister.
 */',
        'startLine' => 259,
        'endLine' => 265,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'aliasName' => NULL,
      ),
      'fromStoredShape' => 
      array (
        'name' => 'fromStoredShape',
        'parameters' => 
        array (
          'stored' => 
          array (
            'name' => 'stored',
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
            'startLine' => 279,
            'endLine' => 279,
            'startColumn' => 35,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Translate a stored record back into the in-process batch record.
 *
 * OpenRegister decorates every serialised object with `@self` metadata and
 * a top-level `id` mirroring the UUID. Neither belongs to the batch record,
 * and leaving `id` in place would put a key in the batch that the batch
 * never had.
 *
 * @param array<string, mixed> $stored The serialised OpenRegister object.
 *
 * @return array<string, mixed> The batch record.
 */',
        'startLine' => 279,
        'endLine' => 285,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'aliasName' => NULL,
      ),
      'toArray' => 
      array (
        'name' => 'toArray',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 294,
            'endLine' => 294,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Normalise an OpenRegister result to a plain array.
 *
 * @param mixed $value An ObjectEntity, a plain array, or anything else.
 *
 * @return array<string, mixed> The array form.
 */',
        'startLine' => 294,
        'endLine' => 304,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchStateRepository',
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