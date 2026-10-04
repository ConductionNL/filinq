<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/SettingsInitializer.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\SettingsInitializer
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-c32f6669ec18d665db38bda8bff724045096f823ee214e6a08142688bb1872e8',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/SettingsInitializer.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\SettingsInitializer',
    'shortName' => 'SettingsInitializer',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service for initializing Filinq configuration from JSON settings
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
    'startLine' => 46,
    'endLine' => 429,
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
      'OPENREGISTER_APP_ID' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'name' => 'OPENREGISTER_APP_ID',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'openregister\'',
          'attributes' => 
          array (
            'startLine' => 60,
            'endLine' => 60,
            'startTokenPos' => 76,
            'startFilePos' => 1524,
            'endTokenPos' => 76,
            'endFilePos' => 1537,
          ),
        ),
        'docComment' => '/**
 * The unique identifier for the OpenRegister application
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 60,
        'endLine' => 60,
        'startColumn' => 2,
        'endColumn' => 52,
      ),
      'MIN_OPENREGISTER_VERSION' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'name' => 'MIN_OPENREGISTER_VERSION',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'0.2.10\'',
          'attributes' => 
          array (
            'startLine' => 67,
            'endLine' => 67,
            'startTokenPos' => 89,
            'startFilePos' => 1678,
            'endTokenPos' => 89,
            'endFilePos' => 1685,
          ),
        ),
        'docComment' => '/**
 * The minimum version of the OpenRegister application required
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 67,
        'endLine' => 67,
        'startColumn' => 2,
        'endColumn' => 51,
      ),
    ),
    'immediateProperties' => 
    array (
      'appName' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
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
        'startLine' => 53,
        'endLine' => 53,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
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
        'startLine' => 80,
        'endLine' => 80,
        'startColumn' => 3,
        'endColumn' => 37,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'container' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
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
        'startLine' => 81,
        'endLine' => 81,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
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
        'startLine' => 82,
        'endLine' => 82,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
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
        'startLine' => 83,
        'endLine' => 83,
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
            'startLine' => 80,
            'endLine' => 80,
            'startColumn' => 3,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 81,
            'endLine' => 81,
            'startColumn' => 3,
            'endColumn' => 48,
            'parameterIndex' => 1,
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
            'startLine' => 82,
            'endLine' => 82,
            'startColumn' => 3,
            'endColumn' => 42,
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
            'startLine' => 83,
            'endLine' => 83,
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
 * Constructor for SettingsInitializer
 *
 * @param IAppConfig $config App configuration interface
 * @param ContainerInterface $container Container for dependency injection
 * @param IAppManager $appManager App manager interface
 * @param LoggerInterface $logger Logger interface
 *
 * @return void
 */',
        'startLine' => 79,
        'endLine' => 87,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'currentClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'aliasName' => NULL,
      ),
      'isOpenRegisterInstalled' => 
      array (
        'name' => 'isOpenRegisterInstalled',
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
 * Checks if OpenRegister is installed and meets version requirements
 *
 * @return bool True if OpenRegister is installed and meets version requirements
 */',
        'startLine' => 94,
        'endLine' => 101,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'currentClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'aliasName' => NULL,
      ),
      'isOpenRegisterEnabled' => 
      array (
        'name' => 'isOpenRegisterEnabled',
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
 * Checks if OpenRegister is enabled
 *
 * @return bool True if OpenRegister is enabled
 */',
        'startLine' => 108,
        'endLine' => 110,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'currentClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'aliasName' => NULL,
      ),
      'getConfigurationService' => 
      array (
        'name' => 'getConfigurationService',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\OpenRegister\\Service\\ConfigurationService',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Attempts to retrieve the Configuration service from the container
 *
 * @return \\OCA\\OpenRegister\\Service\\ConfigurationService The Configuration service
 *
 * @throws \\RuntimeException If the service is not available
 */',
        'startLine' => 119,
        'endLine' => 130,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'currentClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'aliasName' => NULL,
      ),
      'loadSettings' => 
      array (
        'name' => 'loadSettings',
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
 * Load settings from the filinq_register.json file
 *
 * @return array<string, mixed> The loaded settings configuration
 *
 * @throws \\RuntimeException If settings loading fails
 *
 * @spec openspec/specs/admin-settings/spec.md
 */',
        'startLine' => 141,
        'endLine' => 170,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'currentClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'aliasName' => NULL,
      ),
      'initialize' => 
      array (
        'name' => 'initialize',
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
 * Initializes the app with all required components
 *
 * @return array<string, mixed> The initialization results
 *
 * @throws \\RuntimeException If initialization fails
 *
 * @spec openspec/specs/admin-settings/spec.md
 */',
        'startLine' => 181,
        'endLine' => 266,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'currentClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'aliasName' => NULL,
      ),
      'provisionTemplateVersionConfig' => 
      array (
        'name' => 'provisionTemplateVersionConfig',
        'parameters' => 
        array (
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
 * Provision the templateVersion register/schema app-config keys
 *
 * Template editing writes a version-history snapshot before saving, which
 * needs the `templateVersion_register` and `templateVersion_schema` app
 * config keys. Unlike `template_*`, these were never provisioned, so every
 * template update threw RegisterNotConfiguredException and surfaced a 500.
 *
 * The `templateVersion` schema lives in the same register(s) as
 * `template`. This method resolves the schema ID from OpenRegister by slug
 * and prefers to co-locate version objects in the same register the
 * templates themselves use (`template_register`), falling back to whichever
 * register holds the `templateVersion` schema. It is idempotent: keys that
 * are already populated are left untouched, and any resolution failure is
 * logged without aborting initialization.
 *
 * @return void
 *
 * @spec openspec/specs/template-management/spec.md
 */',
        'startLine' => 288,
        'endLine' => 339,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'currentClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'aliasName' => NULL,
      ),
      'resolveTemplateVersionLocation' => 
      array (
        'name' => 'resolveTemplateVersionLocation',
        'parameters' => 
        array (
          'registers' => 
          array (
            'name' => 'registers',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 358,
            'endLine' => 358,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 0,
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
            'startLine' => 359,
            'endLine' => 359,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'templateRegister' => 
          array (
            'name' => 'templateRegister',
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
            'startLine' => 360,
            'endLine' => 360,
            'startColumn' => 3,
            'endColumn' => 26,
            'parameterIndex' => 2,
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
 * Locate the register + schema IDs that should hold templateVersion objects
 *
 * Scans every register\'s schema list for the `templateVersion` slug. An
 * exact match with the templates\' own register wins immediately; otherwise
 * the first register carrying the schema is used as a fallback. A schema
 * that cannot be loaded is skipped rather than aborting the scan.
 *
 * @param iterable<mixed> $registers Registers returned by OpenRegister\'s RegisterService.
 * @param mixed $schemaMapper OpenRegister\'s SchemaMapper, used to resolve schema IDs.
 * @param string $templateRegister The register ID the templates themselves live in.
 *
 * @return array{registerId: string, schemaId: string}|null The resolved location, or null when absent.
 *
 * @spec openspec/specs/template-management/spec.md
 */',
        'startLine' => 357,
        'endLine' => 405,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'currentClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'aliasName' => NULL,
      ),
      'writeTemplateVersionConfig' => 
      array (
        'name' => 'writeTemplateVersionConfig',
        'parameters' => 
        array (
          'registerId' => 
          array (
            'name' => 'registerId',
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
            'startLine' => 415,
            'endLine' => 415,
            'startColumn' => 46,
            'endColumn' => 63,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'schemaId' => 
          array (
            'name' => 'schemaId',
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
            'startLine' => 415,
            'endLine' => 415,
            'startColumn' => 66,
            'endColumn' => 81,
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
 * Write the resolved templateVersion register/schema config keys
 *
 * @param string $registerId The OpenRegister register ID to store.
 * @param string $schemaId The templateVersion schema ID to store.
 *
 * @return void
 */',
        'startLine' => 415,
        'endLine' => 428,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
        'currentClassName' => 'OCA\\Filinq\\Service\\SettingsInitializer',
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