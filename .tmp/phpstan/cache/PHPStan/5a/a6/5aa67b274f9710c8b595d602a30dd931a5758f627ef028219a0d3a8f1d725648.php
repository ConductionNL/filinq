<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DossierObjectReader.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\DossierObjectReader
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-2ae1299ab9624e4fd5f6e18bb0ce1f6c33b8d52981fe80ad6684a17ecd28773a',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\DossierObjectReader',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DossierObjectReader.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\DossierObjectReader',
    'shortName' => 'DossierObjectReader',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Reads the shape of an OpenRegister dossier object.
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 39,
    'endLine' => 194,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierObjectReader',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierObjectReader',
        'name' => 'REGISTER',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq\'',
          'attributes' => 
          array (
            'startLine' => 46,
            'endLine' => 46,
            'startTokenPos' => 35,
            'startFilePos' => 1492,
            'endTokenPos' => 35,
            'endFilePos' => 1499,
          ),
        ),
        'docComment' => '/**
 * The register every filinq schema lives in.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 46,
        'endLine' => 46,
        'startColumn' => 2,
        'endColumn' => 34,
      ),
      'SCHEMA' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierObjectReader',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierObjectReader',
        'name' => 'SCHEMA',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'dossier\'',
          'attributes' => 
          array (
            'startLine' => 53,
            'endLine' => 53,
            'startTokenPos' => 48,
            'startFilePos' => 1585,
            'endTokenPos' => 48,
            'endFilePos' => 1593,
          ),
        ),
        'docComment' => '/**
 * The dossier schema slug.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 53,
        'endLine' => 53,
        'startColumn' => 2,
        'endColumn' => 33,
      ),
      'DEFAULT_STATUS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierObjectReader',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierObjectReader',
        'name' => 'DEFAULT_STATUS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'open\'',
          'attributes' => 
          array (
            'startLine' => 63,
            'endLine' => 63,
            'startTokenPos' => 61,
            'startFilePos' => 1849,
            'endTokenPos' => 61,
            'endFilePos' => 1854,
          ),
        ),
        'docComment' => '/**
 * The status a dossier without one is read as.
 *
 * `status` is optional and existing objects were deliberately not
 * migrated, so absence is a value with a meaning, not missing data.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 63,
        'endLine' => 63,
        'startColumn' => 2,
        'endColumn' => 38,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'payloadOf' => 
      array (
        'name' => 'payloadOf',
        'parameters' => 
        array (
          'object' => 
          array (
            'name' => 'object',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'object',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 74,
            'endLine' => 74,
            'startColumn' => 28,
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
 * The object\'s payload as an array.
 *
 * @param object $object The OpenRegister object.
 *
 * @return array<string, mixed> The payload.
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 */',
        'startLine' => 74,
        'endLine' => 91,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierObjectReader',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierObjectReader',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierObjectReader',
        'aliasName' => NULL,
      ),
      'uuidOf' => 
      array (
        'name' => 'uuidOf',
        'parameters' => 
        array (
          'object' => 
          array (
            'name' => 'object',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'object',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 103,
            'endLine' => 103,
            'startColumn' => 25,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'payload' => 
          array (
            'name' => 'payload',
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
            'startLine' => 103,
            'endLine' => 103,
            'startColumn' => 41,
            'endColumn' => 54,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The object\'s UUID.
 *
 * @param object $object The OpenRegister object.
 * @param array<string, mixed> $payload Its payload.
 *
 * @return string The UUID, or \'\' when it has none.
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 */',
        'startLine' => 103,
        'endLine' => 111,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierObjectReader',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierObjectReader',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierObjectReader',
        'aliasName' => NULL,
      ),
      'statusOf' => 
      array (
        'name' => 'statusOf',
        'parameters' => 
        array (
          'payload' => 
          array (
            'name' => 'payload',
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
            'startLine' => 122,
            'endLine' => 122,
            'startColumn' => 27,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The dossier\'s status, defaulting for objects that predate the property.
 *
 * @param array<string, mixed> $payload The dossier payload.
 *
 * @return string The status.
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 */',
        'startLine' => 122,
        'endLine' => 127,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierObjectReader',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierObjectReader',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierObjectReader',
        'aliasName' => NULL,
      ),
      'documentRefs' => 
      array (
        'name' => 'documentRefs',
        'parameters' => 
        array (
          'payload' => 
          array (
            'name' => 'payload',
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
            'startLine' => 138,
            'endLine' => 138,
            'startColumn' => 31,
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
 * The dossier\'s explicit membership references.
 *
 * @param array<string, mixed> $payload The dossier payload.
 *
 * @return array<int, string> The references.
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 */',
        'startLine' => 138,
        'endLine' => 146,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierObjectReader',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierObjectReader',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierObjectReader',
        'aliasName' => NULL,
      ),
      'findAllOf' => 
      array (
        'name' => 'findAllOf',
        'parameters' => 
        array (
          'objectService' => 
          array (
            'name' => 'objectService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'object',
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
            'startColumn' => 28,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'schema' => 
          array (
            'name' => 'schema',
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
            'startColumn' => 51,
            'endColumn' => 64,
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
 * Every object of one schema in this app\'s register.
 *
 * `ObjectService::findAll()` takes a CONFIG ARRAY, not `register:` /
 * `schema:` named arguments — those exist on `find()` and `saveObject()`
 * but not here. Calling it the other way throws "Unknown named parameter
 * $register" at runtime, which every guarded caller then swallows.
 *
 * @param object $objectService OpenRegister\'s object service.
 * @param string $schema The schema slug.
 *
 * @return array<int, object> The objects.
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 */',
        'startLine' => 163,
        'endLine' => 173,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierObjectReader',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierObjectReader',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierObjectReader',
        'aliasName' => NULL,
      ),
      'firstNonEmpty' => 
      array (
        'name' => 'firstNonEmpty',
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
            'startLine' => 185,
            'endLine' => 185,
            'startColumn' => 32,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'fallback' => 
          array (
            'name' => 'fallback',
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
            'startLine' => 185,
            'endLine' => 185,
            'startColumn' => 47,
            'endColumn' => 62,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The first value that is not an empty string.
 *
 * @param string $value The preferred value.
 * @param string $fallback The value to use when $value is empty.
 *
 * @return string Whichever is non-empty.
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 */',
        'startLine' => 185,
        'endLine' => 192,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierObjectReader',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierObjectReader',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierObjectReader',
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