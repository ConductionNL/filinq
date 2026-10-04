<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/WooProfileService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\WooProfileService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-c9cc14a5b78f43a3100af417c266ddb9b65169b8b8bf77f425dafa17f332c8de',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\WooProfileService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/WooProfileService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\WooProfileService',
    'shortName' => 'WooProfileService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Persists and applies the WOO anonymization profile.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 40,
    'endLine' => 110,
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
      'DEFAULT_ANONYMIZE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\WooProfileService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\WooProfileService',
        'name' => 'DEFAULT_ANONYMIZE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'PERSON\', \'BSN\', \'PHONE\', \'EMAIL\', \'IBAN\', \'ADDRESS\']',
          'attributes' => 
          array (
            'startLine' => 41,
            'endLine' => 41,
            'startTokenPos' => 38,
            'startFilePos' => 1224,
            'endTokenPos' => 55,
            'endFilePos' => 1277,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 41,
        'endLine' => 41,
        'startColumn' => 2,
        'endColumn' => 90,
      ),
      'DEFAULT_KEEP' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\WooProfileService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\WooProfileService',
        'name' => 'DEFAULT_KEEP',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'ORGANIZATION\', \'LOCATION\', \'DATE\']',
          'attributes' => 
          array (
            'startLine' => 42,
            'endLine' => 42,
            'startTokenPos' => 66,
            'startFilePos' => 1310,
            'endTokenPos' => 74,
            'endFilePos' => 1345,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 42,
        'endLine' => 42,
        'startColumn' => 2,
        'endColumn' => 67,
      ),
    ),
    'immediateProperties' => 
    array (
      'appConfig' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\WooProfileService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\WooProfileService',
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
        'startLine' => 52,
        'endLine' => 52,
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
            'startLine' => 52,
            'endLine' => 52,
            'startColumn' => 3,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor for WooProfileService
 *
 * @param IAppConfig $appConfig App config store backing the persisted profile.
 *
 * @return void
 */',
        'startLine' => 51,
        'endLine' => 55,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\WooProfileService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\WooProfileService',
        'currentClassName' => 'OCA\\Filinq\\Service\\WooProfileService',
        'aliasName' => NULL,
      ),
      'getProfile' => 
      array (
        'name' => 'getProfile',
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
 * Return the active WOO anonymization profile.
 *
 * @return array{anonymize: array<string>, keep: array<string>} Active profile (configured or default).
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-woo-entity-category-profiles
 */',
        'startLine' => 64,
        'endLine' => 82,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\WooProfileService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\WooProfileService',
        'currentClassName' => 'OCA\\Filinq\\Service\\WooProfileService',
        'aliasName' => NULL,
      ),
      'saveProfile' => 
      array (
        'name' => 'saveProfile',
        'parameters' => 
        array (
          'profile' => 
          array (
            'name' => 'profile',
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
            'startLine' => 93,
            'endLine' => 93,
            'startColumn' => 30,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Persist a WOO anonymization profile.
 *
 * @param array{anonymize: array<string>, keep: array<string>} $profile Profile to store.
 *
 * @return void
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-woo-entity-category-profiles
 */',
        'startLine' => 93,
        'endLine' => 96,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\WooProfileService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\WooProfileService',
        'currentClassName' => 'OCA\\Filinq\\Service\\WooProfileService',
        'aliasName' => NULL,
      ),
      'shouldAnonymize' => 
      array (
        'name' => 'shouldAnonymize',
        'parameters' => 
        array (
          'entityType' => 
          array (
            'name' => 'entityType',
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
            'startLine' => 107,
            'endLine' => 107,
            'startColumn' => 34,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Check whether the given entity type is subject to anonymization under the active profile.
 *
 * @param string $entityType Entity type to check (e.g., "PERSON", "BSN").
 *
 * @return bool True when the type should be anonymized.
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-woo-entity-category-profiles
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
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\WooProfileService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\WooProfileService',
        'currentClassName' => 'OCA\\Filinq\\Service\\WooProfileService',
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