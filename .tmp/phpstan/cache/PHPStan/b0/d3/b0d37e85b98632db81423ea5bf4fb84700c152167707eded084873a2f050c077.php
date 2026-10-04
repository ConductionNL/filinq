<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/CustomDictionaryService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\CustomDictionaryService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-0944ab386294e89a95a164c0dda5b4e5fea2305eaa65842f79c73b2ef3949daf',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/CustomDictionaryService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
    'shortName' => 'CustomDictionaryService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Organisation-gated CRUD wrapper for custom dictionaries + terms.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 50,
    'endLine' => 562,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'name' => 'REGISTER',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq\'',
          'attributes' => 
          array (
            'startLine' => 60,
            'endLine' => 60,
            'startTokenPos' => 65,
            'startFilePos' => 1908,
            'endTokenPos' => 65,
            'endFilePos' => 1915,
          ),
        ),
        'docComment' => '/**
 * Register slug both schemas live in.
 *
 * `filinq`, not `document`: this app declares ONE register holding all 23
 * schemas. The five it used to declare are retired.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 60,
        'endLine' => 60,
        'startColumn' => 2,
        'endColumn' => 34,
      ),
      'SCHEMA_DICTIONARY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'name' => 'SCHEMA_DICTIONARY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'customDictionary\'',
          'attributes' => 
          array (
            'startLine' => 67,
            'endLine' => 67,
            'startTokenPos' => 78,
            'startFilePos' => 2011,
            'endTokenPos' => 78,
            'endFilePos' => 2028,
          ),
        ),
        'docComment' => '/**
 * Dictionary schema slug.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 67,
        'endLine' => 67,
        'startColumn' => 2,
        'endColumn' => 53,
      ),
      'SCHEMA_TERM' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'name' => 'SCHEMA_TERM',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'customDictionaryTerm\'',
          'attributes' => 
          array (
            'startLine' => 74,
            'endLine' => 74,
            'startTokenPos' => 91,
            'startFilePos' => 2112,
            'endTokenPos' => 91,
            'endFilePos' => 2133,
          ),
        ),
        'docComment' => '/**
 * Term schema slug.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 74,
        'endLine' => 74,
        'startColumn' => 2,
        'endColumn' => 51,
      ),
      'MAX_IMPORT_ROWS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'name' => 'MAX_IMPORT_ROWS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '2000',
          'attributes' => 
          array (
            'startLine' => 83,
            'endLine' => 83,
            'startTokenPos' => 104,
            'startFilePos' => 2384,
            'endTokenPos' => 104,
            'endFilePos' => 2387,
          ),
        ),
        'docComment' => '/**
 * Maximum number of term rows accepted in a single import call —
 * bounds the request against a denial-of-service via an oversized
 * upload (design.md §Security Considerations).
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 83,
        'endLine' => 83,
        'startColumn' => 2,
        'endColumn' => 38,
      ),
      'MAX_IMPORT_BYTES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'name' => 'MAX_IMPORT_BYTES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '2097152',
          'attributes' => 
          array (
            'startLine' => 90,
            'endLine' => 90,
            'startTokenPos' => 117,
            'startFilePos' => 2494,
            'endTokenPos' => 117,
            'endFilePos' => 2500,
          ),
        ),
        'docComment' => '/**
 * Maximum import payload size in bytes.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 90,
        'endLine' => 90,
        'startColumn' => 2,
        'endColumn' => 42,
      ),
    ),
    'immediateProperties' => 
    array (
      'repository' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'name' => 'repository',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\CustomDictionaryRepository',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 101,
        'endLine' => 101,
        'startColumn' => 3,
        'endColumn' => 57,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'accessGate' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'name' => 'accessGate',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\CustomDictionaryAccessGate',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 102,
        'endLine' => 102,
        'startColumn' => 3,
        'endColumn' => 57,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'normaliser' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'name' => 'normaliser',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\CustomDictionaryPayloadNormaliser',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 103,
        'endLine' => 103,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
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
        'startLine' => 104,
        'endLine' => 104,
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
          'repository' => 
          array (
            'name' => 'repository',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\CustomDictionaryRepository',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 101,
            'endLine' => 101,
            'startColumn' => 3,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'accessGate' => 
          array (
            'name' => 'accessGate',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\CustomDictionaryAccessGate',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 102,
            'endLine' => 102,
            'startColumn' => 3,
            'endColumn' => 57,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'normaliser' => 
          array (
            'name' => 'normaliser',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\CustomDictionaryPayloadNormaliser',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 103,
            'endLine' => 103,
            'startColumn' => 3,
            'endColumn' => 64,
            'parameterIndex' => 2,
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
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 3,
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
 * @param CustomDictionaryRepository $repository Persistence primitives.
 * @param CustomDictionaryAccessGate $accessGate Fail-closed organisation gate.
 * @param CustomDictionaryPayloadNormaliser $normaliser Import parsing and payload coercion.
 * @param LoggerInterface $logger Structured logger.
 */',
        'startLine' => 100,
        'endLine' => 107,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
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
 * Whether OpenRegister is installed. Callers use this to return an
 * explanatory unavailable state instead of crashing (REQ-DDCDR-004).
 *
 * @return bool
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */',
        'startLine' => 117,
        'endLine' => 119,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'aliasName' => NULL,
      ),
      'listDictionaries' => 
      array (
        'name' => 'listDictionaries',
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
 * List dictionaries visible to the caller\'s accessible organisations,
 * each enriched with its live `termCount`.
 *
 * @return array<int, array<string, mixed>>
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */',
        'startLine' => 129,
        'endLine' => 147,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'aliasName' => NULL,
      ),
      'getDictionary' => 
      array (
        'name' => 'getDictionary',
        'parameters' => 
        array (
          'uuid' => 
          array (
            'name' => 'uuid',
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
            'startLine' => 163,
            'endLine' => 163,
            'startColumn' => 32,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get a single dictionary, organisation-gated.
 *
 * @param string $uuid Dictionary UUID.
 *
 * @return array<string, mixed>
 *
 * @throws DoesNotExistException When no dictionary exists for the UUID.
 * @throws RuntimeException When the dictionary exists but the caller\'s
 *                          accessible organisations do not include it
 *                          (mapped to HTTP 403 by the controller).
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */',
        'startLine' => 163,
        'endLine' => 165,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'aliasName' => NULL,
      ),
      'createDictionary' => 
      array (
        'name' => 'createDictionary',
        'parameters' => 
        array (
          'data' => 
          array (
            'name' => 'data',
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
            'startLine' => 180,
            'endLine' => 180,
            'startColumn' => 35,
            'endColumn' => 45,
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
 * Create a dictionary. Organisation is stamped by OpenRegister\'s
 * `SaveObject` from the caller\'s active organisation (design.md §D1) —
 * this service does not set `@self.organisation` explicitly.
 *
 * @param array<string, mixed> $data Caller-supplied dictionary data.
 *
 * @return array<string, mixed> The created record, enriched with `termCount`.
 *
 * @throws Exception On write failure.
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */',
        'startLine' => 180,
        'endLine' => 188,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'aliasName' => NULL,
      ),
      'updateDictionary' => 
      array (
        'name' => 'updateDictionary',
        'parameters' => 
        array (
          'uuid' => 
          array (
            'name' => 'uuid',
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
            'startColumn' => 35,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'data' => 
          array (
            'name' => 'data',
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
            'startLine' => 205,
            'endLine' => 205,
            'startColumn' => 49,
            'endColumn' => 59,
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
 * Update a dictionary. Organisation-gated: throws before any write is
 * attempted when the caller cannot access the existing record.
 *
 * @param string $uuid Dictionary UUID.
 * @param array<string, mixed> $data Updated fields.
 *
 * @return array<string, mixed> The updated record, enriched with `termCount`.
 *
 * @throws DoesNotExistException When no dictionary exists for the UUID.
 * @throws RuntimeException When the caller is not permitted (HTTP 403).
 * @throws Exception On write failure.
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */',
        'startLine' => 205,
        'endLine' => 214,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'aliasName' => NULL,
      ),
      'deleteDictionary' => 
      array (
        'name' => 'deleteDictionary',
        'parameters' => 
        array (
          'uuid' => 
          array (
            'name' => 'uuid',
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
            'startLine' => 228,
            'endLine' => 228,
            'startColumn' => 35,
            'endColumn' => 46,
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
 * Delete a dictionary and cascade-delete its terms.
 *
 * @param string $uuid Dictionary UUID.
 *
 * @return void
 *
 * @throws DoesNotExistException When no dictionary exists for the UUID.
 * @throws RuntimeException When the caller is not permitted (HTTP 403).
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */',
        'startLine' => 228,
        'endLine' => 240,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'aliasName' => NULL,
      ),
      'listTerms' => 
      array (
        'name' => 'listTerms',
        'parameters' => 
        array (
          'dictionaryUuid' => 
          array (
            'name' => 'dictionaryUuid',
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
            'startLine' => 254,
            'endLine' => 254,
            'startColumn' => 28,
            'endColumn' => 49,
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
 * List the terms of one dictionary, organisation-gated via the parent.
 *
 * @param string $dictionaryUuid Dictionary UUID.
 *
 * @return array<int, array<string, mixed>>
 *
 * @throws DoesNotExistException When no dictionary exists for the UUID.
 * @throws RuntimeException When the caller is not permitted (HTTP 403).
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */',
        'startLine' => 254,
        'endLine' => 256,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'aliasName' => NULL,
      ),
      'createTerm' => 
      array (
        'name' => 'createTerm',
        'parameters' => 
        array (
          'dictionaryUuid' => 
          array (
            'name' => 'dictionaryUuid',
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
            'startLine' => 272,
            'endLine' => 272,
            'startColumn' => 29,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'data' => 
          array (
            'name' => 'data',
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
            'startLine' => 272,
            'endLine' => 272,
            'startColumn' => 53,
            'endColumn' => 63,
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
 * Add a single term to a dictionary.
 *
 * @param string $dictionaryUuid Dictionary UUID.
 * @param array<string, mixed> $data Term data (`value`, optional `label`).
 *
 * @return array<string, mixed> The created term.
 *
 * @throws DoesNotExistException When no dictionary exists for the UUID.
 * @throws RuntimeException When the caller is not permitted (HTTP 403).
 * @throws InvalidArgumentException When `value` is blank.
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */',
        'startLine' => 272,
        'endLine' => 289,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'aliasName' => NULL,
      ),
      'deleteTerm' => 
      array (
        'name' => 'deleteTerm',
        'parameters' => 
        array (
          'dictionaryUuid' => 
          array (
            'name' => 'dictionaryUuid',
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
            'startLine' => 305,
            'endLine' => 305,
            'startColumn' => 29,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'termUuid' => 
          array (
            'name' => 'termUuid',
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
            'startLine' => 305,
            'endLine' => 305,
            'startColumn' => 53,
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
 * Delete a single term, verifying it belongs to the given dictionary.
 *
 * @param string $dictionaryUuid Dictionary UUID.
 * @param string $termUuid Term UUID.
 *
 * @return void
 *
 * @throws DoesNotExistException When the dictionary or the term does not exist,
 *                               or the term does not belong to this dictionary.
 * @throws RuntimeException When the caller is not permitted (HTTP 403).
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */',
        'startLine' => 305,
        'endLine' => 315,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'aliasName' => NULL,
      ),
      'importTerms' => 
      array (
        'name' => 'importTerms',
        'parameters' => 
        array (
          'dictionaryUuid' => 
          array (
            'name' => 'dictionaryUuid',
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
            'startLine' => 337,
            'endLine' => 337,
            'startColumn' => 30,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'content' => 
          array (
            'name' => 'content',
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
            'startLine' => 337,
            'endLine' => 337,
            'startColumn' => 54,
            'endColumn' => 68,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'isCsv' => 
          array (
            'name' => 'isCsv',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
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
            'startColumn' => 71,
            'endColumn' => 81,
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
 * Import terms from a CSV upload or a newline-separated plain-text list.
 *
 * Server-side only (design.md §D5 — parsing MUST NOT be delegated to the
 * browser). Trims values, skips blank lines, de-duplicates
 * case-insensitively against the dictionary\'s existing terms (and within
 * the same import batch), and bounds the import size.
 *
 * @param string $dictionaryUuid Dictionary UUID.
 * @param string $content Raw uploaded/pasted content.
 * @param bool $isCsv True for CSV parsing, false for newline-list parsing.
 *
 * @return array{added: int, skipped: int, total: int}
 *
 * @throws DoesNotExistException When no dictionary exists for the UUID.
 * @throws RuntimeException When the caller is not permitted (HTTP 403).
 * @throws InvalidArgumentException When the payload exceeds the size/row bound.
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */',
        'startLine' => 337,
        'endLine' => 401,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'aliasName' => NULL,
      ),
      'listActiveDictionariesForDetection' => 
      array (
        'name' => 'listActiveDictionariesForDetection',
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
 * Active dictionaries + their non-blank terms, scoped to the caller\'s
 * accessible organisations, shaped for
 * {@see CustomDictionaryMatchService::match()}.
 *
 * Reuses the same organisation gate the CRUD surface enforces so
 * automatic detection (hooked from `AnonymizationService`) only ever
 * runs dictionaries the acting user — and therefore the file they are
 * working on — can see. Best-effort: any failure returns an empty list
 * rather than throwing, so a detection call degrades to "no dictionary
 * hits" instead of blocking OpenRegister\'s own detection.
 *
 * @return array<int, array{label: string, matchMode: string, terms: array<int, array{value: string, label: string}>}>
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */',
        'startLine' => 419,
        'endLine' => 452,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'aliasName' => NULL,
      ),
      'buildDetectionTermRows' => 
      array (
        'name' => 'buildDetectionTermRows',
        'parameters' => 
        array (
          'dictionary' => 
          array (
            'name' => 'dictionary',
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
            'startLine' => 465,
            'endLine' => 465,
            'startColumn' => 42,
            'endColumn' => 58,
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
 * Build one dictionary\'s detection-shaped term rows: non-blank values
 * only, each carrying a resolved label (the term\'s own label, else the
 * dictionary\'s label, else the term value itself).
 *
 * @param array<string, mixed> $dictionary The parent dictionary record.
 *
 * @return array<int, array{value: string, label: string}> Term rows, possibly empty.
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */',
        'startLine' => 465,
        'endLine' => 485,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'aliasName' => NULL,
      ),
      'findDictionaryOrFail' => 
      array (
        'name' => 'findDictionaryOrFail',
        'parameters' => 
        array (
          'uuid' => 
          array (
            'name' => 'uuid',
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
            'startLine' => 498,
            'endLine' => 498,
            'startColumn' => 40,
            'endColumn' => 51,
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
 * Resolve a dictionary by UUID, enforcing the organisation gate.
 *
 * @param string $uuid Dictionary UUID.
 *
 * @return array<string, mixed>
 *
 * @throws DoesNotExistException When no dictionary exists for the UUID.
 * @throws RuntimeException When the caller\'s accessible organisations do
 *                          not include the dictionary\'s organisation.
 */',
        'startLine' => 498,
        'endLine' => 509,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'aliasName' => NULL,
      ),
      'listTermsForDictionary' => 
      array (
        'name' => 'listTermsForDictionary',
        'parameters' => 
        array (
          'dictionary' => 
          array (
            'name' => 'dictionary',
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
            'startLine' => 520,
            'endLine' => 520,
            'startColumn' => 42,
            'endColumn' => 58,
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
 * List terms belonging to one dictionary (matched by uuid OR slug —
 * seed data references the parent by slug per the codebase\'s existing
 * `dossier.bases` convention; API-created terms carry the parent\'s uuid).
 *
 * @param array<string, mixed> $dictionary The parent dictionary record.
 *
 * @return array<int, array<string, mixed>>
 */',
        'startLine' => 520,
        'endLine' => 529,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'aliasName' => NULL,
      ),
      'termBelongsToDictionary' => 
      array (
        'name' => 'termBelongsToDictionary',
        'parameters' => 
        array (
          'term' => 
          array (
            'name' => 'term',
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
            'startLine' => 539,
            'endLine' => 539,
            'startColumn' => 43,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'dictionary' => 
          array (
            'name' => 'dictionary',
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
            'startLine' => 539,
            'endLine' => 539,
            'startColumn' => 56,
            'endColumn' => 72,
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
 * Whether a term row references the given dictionary.
 *
 * @param array<string, mixed> $term Term record.
 * @param array<string, mixed> $dictionary Dictionary record.
 *
 * @return bool
 */',
        'startLine' => 539,
        'endLine' => 549,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'aliasName' => NULL,
      ),
      'enrichWithTermCount' => 
      array (
        'name' => 'enrichWithTermCount',
        'parameters' => 
        array (
          'dictionary' => 
          array (
            'name' => 'dictionary',
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
            'startLine' => 558,
            'endLine' => 558,
            'startColumn' => 39,
            'endColumn' => 55,
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
 * Enrich a dictionary record with its live term count.
 *
 * @param array<string, mixed> $dictionary Dictionary record.
 *
 * @return array<string, mixed>
 */',
        'startLine' => 558,
        'endLine' => 561,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryService',
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