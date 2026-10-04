<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Validation/ValidationProfileResolver.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Validation\ValidationProfileResolver
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-b417717d0180c5ad4675d81c8cb5a9f7c95b4c2d97c976576372ab46f24f7599',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Validation/ValidationProfileResolver.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Validation',
    'name' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
    'shortName' => 'ValidationProfileResolver',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Resolves the effective validation profile for a document type.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Validation
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-validation-checks/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 44,
    'endLine' => 241,
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
      'CONFIG_PROFILES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'name' => 'CONFIG_PROFILES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'validation.profiles\'',
          'attributes' => 
          array (
            'startLine' => 51,
            'endLine' => 51,
            'startTokenPos' => 55,
            'startFilePos' => 1453,
            'endTokenPos' => 55,
            'endFilePos' => 1473,
          ),
        ),
        'docComment' => '/**
 * App config key for the validation profiles JSON.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 51,
        'endLine' => 51,
        'startColumn' => 2,
        'endColumn' => 55,
      ),
      'ALL_CHECKS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'name' => 'ALL_CHECKS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\\OCA\\Filinq\\Service\\DocumentValidationService::CHECK_FORMAT_NOT_ALLOWED, \\OCA\\Filinq\\Service\\DocumentValidationService::CHECK_EXTENSION_MIME, \\OCA\\Filinq\\Service\\DocumentValidationService::CHECK_FILE_UNREADABLE, \\OCA\\Filinq\\Service\\DocumentValidationService::CHECK_PDF_ENCRYPTED, \\OCA\\Filinq\\Service\\DocumentValidationService::CHECK_TEXT_LAYER_MISSING, \\OCA\\Filinq\\Service\\DocumentValidationService::CHECK_METADATA_INCOMPLETE]',
          'attributes' => 
          array (
            'startLine' => 58,
            'endLine' => 65,
            'startTokenPos' => 68,
            'startFilePos' => 1623,
            'endTokenPos' => 100,
            'endFilePos' => 1945,
          ),
        ),
        'docComment' => '/**
 * The five file-level checks plus the metadata check, in catalogue order.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 58,
        'endLine' => 65,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
      'ALL_SEVERITIES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'name' => 'ALL_SEVERITIES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\\OCA\\Filinq\\Service\\DocumentValidationService::SEVERITY_OFF, \\OCA\\Filinq\\Service\\DocumentValidationService::SEVERITY_WARNING, \\OCA\\Filinq\\Service\\DocumentValidationService::SEVERITY_BLOCKING]',
          'attributes' => 
          array (
            'startLine' => 72,
            'endLine' => 76,
            'startTokenPos' => 113,
            'startFilePos' => 2075,
            'endTokenPos' => 130,
            'endFilePos' => 2216,
          ),
        ),
        'docComment' => '/**
 * The severities a profile may assign to a check.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 72,
        'endLine' => 76,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
      'DEFAULT_ALLOWED_MIMES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'name' => 'DEFAULT_ALLOWED_MIMES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'application/pdf\', \'application/vnd.openxmlformats-officedocument.wordprocessingml.document\', \'application/msword\', \'application/vnd.oasis.opendocument.text\', \'text/plain\', \'text/markdown\', \'text/html\']',
          'attributes' => 
          array (
            'startLine' => 83,
            'endLine' => 91,
            'startTokenPos' => 143,
            'startFilePos' => 2362,
            'endTokenPos' => 166,
            'endFilePos' => 2582,
          ),
        ),
        'docComment' => '/**
 * Default mime allowlist shipped with the default profile.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 83,
        'endLine' => 91,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
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
        'startLine' => 102,
        'endLine' => 102,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'appConfig' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
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
        'startLine' => 103,
        'endLine' => 103,
        'startColumn' => 3,
        'endColumn' => 40,
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
            'startLine' => 102,
            'endLine' => 102,
            'startColumn' => 3,
            'endColumn' => 42,
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
            'startLine' => 103,
            'endLine' => 103,
            'startColumn' => 3,
            'endColumn' => 40,
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
 * @param LoggerInterface $logger Logger.
 * @param IAppConfig $appConfig App configuration.
 *
 * @return void
 */',
        'startLine' => 101,
        'endLine' => 106,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Validation',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'aliasName' => NULL,
      ),
      'resolve' => 
      array (
        'name' => 'resolve',
        'parameters' => 
        array (
          'documentType' => 
          array (
            'name' => 'documentType',
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
            'startLine' => 117,
            'endLine' => 117,
            'startColumn' => 26,
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
 * Resolve the validation profile for a document type, falling back to default.
 *
 * @param string $documentType The document type.
 *
 * @return array{allowedMimes:array<int,string>, requiredFields:array<int,string>, severities:array<string,string>}
 *
 * @spec openspec/specs/document-validation-checks/spec.md
 */',
        'startLine' => 117,
        'endLine' => 131,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Validation',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'aliasName' => NULL,
      ),
      'rawProfile' => 
      array (
        'name' => 'rawProfile',
        'parameters' => 
        array (
          'documentType' => 
          array (
            'name' => 'documentType',
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
            'startLine' => 140,
            'endLine' => 140,
            'startColumn' => 30,
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
            'name' => 'mixed',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Pick the raw profile entry for a document type, or the \'default\' entry.
 *
 * @param string $documentType The document type.
 *
 * @return mixed The raw profile entry, or null when neither exists.
 */',
        'startLine' => 140,
        'endLine' => 152,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Validation',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'aliasName' => NULL,
      ),
      'overrideList' => 
      array (
        'name' => 'overrideList',
        'parameters' => 
        array (
          'raw' => 
          array (
            'name' => 'raw',
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
            'startLine' => 164,
            'endLine' => 164,
            'startColumn' => 32,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'key' => 
          array (
            'name' => 'key',
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
            'startLine' => 164,
            'endLine' => 164,
            'startColumn' => 44,
            'endColumn' => 54,
            'parameterIndex' => 1,
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
            'startLine' => 164,
            'endLine' => 164,
            'startColumn' => 57,
            'endColumn' => 71,
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
 * Read a list override off a raw profile, keeping the fallback when the
 * key is absent or not a list.
 *
 * @param array<string, mixed> $raw The raw profile entry.
 * @param string $key The override key.
 * @param array<int, string> $fallback The default list.
 *
 * @return array<int, string> The effective list.
 */',
        'startLine' => 164,
        'endLine' => 170,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Validation',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'aliasName' => NULL,
      ),
      'mergeSeverities' => 
      array (
        'name' => 'mergeSeverities',
        'parameters' => 
        array (
          'raw' => 
          array (
            'name' => 'raw',
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
            'startLine' => 181,
            'endLine' => 181,
            'startColumn' => 35,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'defaults' => 
          array (
            'name' => 'defaults',
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
            'startLine' => 181,
            'endLine' => 181,
            'startColumn' => 47,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Merge a raw profile\'s severity overrides onto the defaults, ignoring
 * unknown check ids and invalid severity values.
 *
 * @param array<string, mixed> $raw The raw profile entry.
 * @param array<string, string> $defaults The default severities.
 *
 * @return array<string, string> The effective severities.
 */',
        'startLine' => 181,
        'endLine' => 196,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Validation',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'aliasName' => NULL,
      ),
      'defaultProfile' => 
      array (
        'name' => 'defaultProfile',
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
 * The shipped default profile (every check warn-only).
 *
 * @return array{allowedMimes:array<int,string>, requiredFields:array<int,string>, severities:array<string,string>}
 */',
        'startLine' => 203,
        'endLine' => 215,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Validation',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'aliasName' => NULL,
      ),
      'loadProfiles' => 
      array (
        'name' => 'loadProfiles',
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
 * Load and decode the configured profiles JSON.
 *
 * @return array<string, mixed> The decoded profiles (empty on error).
 */',
        'startLine' => 222,
        'endLine' => 240,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Validation',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
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