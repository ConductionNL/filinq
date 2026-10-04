<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/RegisterDiscoveryService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\RegisterDiscoveryService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-4cfc622fb920ecc7e0ce749fc995cb712791720fe0d02eb46f97ab6e8746ffb7',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/RegisterDiscoveryService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
    'shortName' => 'RegisterDiscoveryService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service for discovering available registers and loading object type config
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
    'startLine' => 43,
    'endLine' => 239,
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
      'appName' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
        'name' => 'appName',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * The application name
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 50,
        'endLine' => 50,
        'startColumn' => 2,
        'endColumn' => 34,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'config' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
        'name' => 'config',
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
        'startLine' => 63,
        'endLine' => 63,
        'startColumn' => 3,
        'endColumn' => 37,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
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
        'startLine' => 64,
        'endLine' => 64,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'registerService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
        'name' => 'registerService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\OpenRegister\\Service\\RegisterService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 65,
        'endLine' => 65,
        'startColumn' => 3,
        'endColumn' => 51,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'schemaMapper' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
        'name' => 'schemaMapper',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\OpenRegister\\Db\\SchemaMapper',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 66,
        'endLine' => 66,
        'startColumn' => 3,
        'endColumn' => 45,
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
          'config' => 
          array (
            'name' => 'config',
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
            'startLine' => 63,
            'endLine' => 63,
            'startColumn' => 3,
            'endColumn' => 37,
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
            'startLine' => 64,
            'endLine' => 64,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'registerService' => 
          array (
            'name' => 'registerService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\OpenRegister\\Service\\RegisterService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 65,
            'endLine' => 65,
            'startColumn' => 3,
            'endColumn' => 51,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'schemaMapper' => 
          array (
            'name' => 'schemaMapper',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\OpenRegister\\Db\\SchemaMapper',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 66,
            'endLine' => 66,
            'startColumn' => 3,
            'endColumn' => 45,
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
 * Constructor for RegisterDiscoveryService
 *
 * @param IAppConfig $config App configuration interface
 * @param LoggerInterface $logger Logger interface
 * @param RegisterService $registerService Register service for getting registers
 * @param SchemaMapper $schemaMapper Schema mapper for expanding schema IDs
 *
 * @return void
 */',
        'startLine' => 62,
        'endLine' => 70,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
        'aliasName' => NULL,
      ),
      'fetchAvailableRegisters' => 
      array (
        'name' => 'fetchAvailableRegisters',
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
 * Fetch available registers from OpenRegister with schemas
 *
 * Calls `RegisterService::findAll`, serializes each register via
 * `jsonSerialize`, then expands schema IDs to full schema objects
 * (with `properties` stripped). On orphan schema IDs, the bare ID is
 * retained in place (heterogeneous array of objects + ints) —
 * `filterSchemaProperties()` passes those through unchanged.
 *
 * Mirrors the expansion logic in OpenRegister\'s
 * `RegistersController::index` for `_extend: [\'schemas\']`, but performed
 * inline so filinq doesn\'t depend on a non-existent serialized helper.
 * Catches `Throwable` (the only common ancestor of `Exception` and
 * `Error` in PHP 7+) so any failure — including a missing method on an
 * older OR sidecar — falls back to an empty registers list rather than
 * bubbling a 500 to the controller.
 *
 * @return array<int, array<string, mixed>> List of register arrays with filtered schemas
 *
 * @spec openspec/specs/admin-settings/spec.md
 */',
        'startLine' => 93,
        'endLine' => 148,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
        'aliasName' => NULL,
      ),
      'filterSchemas' => 
      array (
        'name' => 'filterSchemas',
        'parameters' => 
        array (
          'registerArray' => 
          array (
            'name' => 'registerArray',
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
            'startLine' => 168,
            'endLine' => 168,
            'startColumn' => 33,
            'endColumn' => 52,
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
 * Strip the `properties` field from each schema in a serialized register
 *
 * Input is the already-serialized register array — each entry in `schemas`
 * is either a full schema array (when expansion succeeded) or a bare ID
 * (orphan schema). `filterSchemaProperties()` handles both transparently.
 *
 * @param array<string, mixed> $registerArray Serialized register
 *
 * @return array<string, mixed> Serialized register with filtered schemas
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateMethod) Called as the callable array
 * `[$this, \'filterSchemas\']` from array_map() at line 129. PHPMD resolves
 * only direct `$this->method()` calls, so a callable-array reference reads
 * to it as no caller at all — a false positive, verified by grep.
 *
 * @spec openspec/specs/admin-settings/spec.md
 */',
        'startLine' => 168,
        'endLine' => 177,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
        'aliasName' => NULL,
      ),
      'filterSchemaProperties' => 
      array (
        'name' => 'filterSchemaProperties',
        'parameters' => 
        array (
          'schema' => 
          array (
            'name' => 'schema',
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
            'startLine' => 194,
            'endLine' => 194,
            'startColumn' => 42,
            'endColumn' => 54,
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
 * Filter out the properties field from a schema array
 *
 * @param mixed $schema The schema data
 *
 * @return mixed The filtered schema
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateMethod) Called as the callable array
 * `[$this, \'filterSchemaProperties\']` from array_map() at line 168. PHPMD
 * resolves only direct `$this->method()` calls, so a callable-array
 * reference reads to it as no caller at all — a false positive, verified
 * by grep.
 *
 * @spec openspec/specs/admin-settings/spec.md
 */',
        'startLine' => 194,
        'endLine' => 202,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
        'aliasName' => NULL,
      ),
      'loadObjectTypeConfiguration' => 
      array (
        'name' => 'loadObjectTypeConfiguration',
        'parameters' => 
        array (
          'objectTypes' => 
          array (
            'name' => 'objectTypes',
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
            'startLine' => 213,
            'endLine' => 213,
            'startColumn' => 46,
            'endColumn' => 63,
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
 * Load object type configuration defaults from app config
 *
 * @param array<string> $objectTypes The object types to load config for
 *
 * @return array<string, string> Configuration key-value pairs
 *
 * @spec openspec/specs/admin-settings/spec.md
 */',
        'startLine' => 213,
        'endLine' => 238,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
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