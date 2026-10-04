<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Repair/MigrateAppConfigKeys.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Repair\MigrateAppConfigKeys
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-a597caf56f73c0abc986b1765e406eb65389fd39701e934f645062b95eb8818f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Repair/MigrateAppConfigKeys.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Repair',
    'name' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
    'shortName' => 'MigrateAppConfigKeys',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Copy every stored IAppConfig value from the docudesk namespace to filinq.
 *
 * @spec exclude No canonical spec covers the docudesk -> filinq app-id rename.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 87,
    'endLine' => 272,
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
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
        'name' => 'OLD_APP_ID',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'docudesk\'',
          'attributes' => 
          array (
            'startLine' => 96,
            'endLine' => 96,
            'startTokenPos' => 59,
            'startFilePos' => 4235,
            'endTokenPos' => 59,
            'endFilePos' => 4244,
          ),
        ),
        'docComment' => '/**
 * The app-config namespace this app used before the rename.
 *
 * Deliberately the OLD app id. This constant is one of the few places in
 * the app that is supposed to still say `docudesk`.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 96,
        'endLine' => 96,
        'startColumn' => 2,
        'endColumn' => 39,
      ),
      'NEW_APP_ID' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
        'name' => 'NEW_APP_ID',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq\'',
          'attributes' => 
          array (
            'startLine' => 103,
            'endLine' => 103,
            'startTokenPos' => 72,
            'startFilePos' => 4367,
            'endTokenPos' => 72,
            'endFilePos' => 4374,
          ),
        ),
        'docComment' => '/**
 * The app-config namespace this app uses after the rename.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 103,
        'endLine' => 103,
        'startColumn' => 2,
        'endColumn' => 37,
      ),
      'KEY_PREFIX_MAP' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
        'name' => 'KEY_PREFIX_MAP',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'docudesk.\' => \'filinq.\', \'docudesk_\' => \'filinq_\']',
          'attributes' => 
          array (
            'startLine' => 115,
            'endLine' => 118,
            'startTokenPos' => 85,
            'startFilePos' => 4738,
            'endTokenPos' => 101,
            'endFilePos' => 4797,
          ),
        ),
        'docComment' => '/**
 * Old key-name prefix => new key-name prefix.
 *
 * Both spellings this app has used are listed: dot-separated keys
 * (`docudesk.pdfa3.enabled`) and underscore-separated ones
 * (`docudesk_batch_max_files`). Omitting either would strand that half of
 * the settings surface.
 *
 * @var array<string, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 115,
        'endLine' => 118,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
      'RESERVED_KEYS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
        'name' => 'RESERVED_KEYS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'enabled\', \'installed_version\', \'types\']',
          'attributes' => 
          array (
            'startLine' => 133,
            'endLine' => 137,
            'startTokenPos' => 114,
            'startFilePos' => 5460,
            'endTokenPos' => 125,
            'endFilePos' => 5510,
          ),
        ),
        'docComment' => '/**
 * Config keys Nextcloud owns for every app. These MUST NOT be copied.
 *
 * `AppManager::enableApp()` writes `enabled` through the deprecated
 * `IAppConfig::setValue()`, which stores type MIXED. Copying it here with
 * `setValueString()` stores type STRING, and the next `app:enable` then
 * fails with an `AppConfigTypeConflictException` — permanently, because the
 * conflict is hit before the app can run anything that would repair it.
 * `installed_version` and `types` are Nextcloud\'s own bookkeeping for the
 * app and copying the old app\'s values would misreport the new one.
 *
 * @var string[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 133,
        'endLine' => 137,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'appConfig' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
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
        'startLine' => 148,
        'endLine' => 148,
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
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
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
        'startLine' => 149,
        'endLine' => 149,
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
            'startLine' => 148,
            'endLine' => 148,
            'startColumn' => 3,
            'endColumn' => 40,
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
            'startLine' => 149,
            'endLine' => 149,
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
 * Constructor for MigrateAppConfigKeys.
 *
 * @param IAppConfig $appConfig The app config interface.
 * @param LoggerInterface $logger The logger interface.
 *
 * @return void
 */',
        'startLine' => 147,
        'endLine' => 151,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
        'currentClassName' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
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
 * Get the name of this repair step.
 *
 * @return string
 *
 * @spec exclude No canonical spec covers the docudesk -> filinq app-id rename.
 */',
        'startLine' => 160,
        'endLine' => 162,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
        'currentClassName' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
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
            'startLine' => 173,
            'endLine' => 173,
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
 * Run the repair step to migrate the stored app configuration.
 *
 * @param IOutput $output The output interface for progress reporting.
 *
 * @return void
 *
 * @spec exclude No canonical spec covers the docudesk -> filinq app-id rename.
 */',
        'startLine' => 173,
        'endLine' => 234,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
        'currentClassName' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
        'aliasName' => NULL,
      ),
      'newKeyFor' => 
      array (
        'name' => 'newKeyFor',
        'parameters' => 
        array (
          'oldKey' => 
          array (
            'name' => 'oldKey',
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
            'startLine' => 246,
            'endLine' => 246,
            'startColumn' => 29,
            'endColumn' => 42,
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
 * Translate one stored key name into the name the renamed app reads.
 *
 * Only a LEADING old-app-id prefix is rewritten; every other key is
 * returned unchanged.
 *
 * @param string $oldKey The key as stored under the old app id.
 *
 * @return string The key name to write under the new app id.
 */',
        'startLine' => 246,
        'endLine' => 254,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
        'currentClassName' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
        'aliasName' => NULL,
      ),
      'oldKeys' => 
      array (
        'name' => 'oldKeys',
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
 * Every key currently stored under the old app-config namespace.
 *
 * @return array<int, string> The stored key names, empty when unreadable.
 */',
        'startLine' => 261,
        'endLine' => 271,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
        'currentClassName' => 'OCA\\Filinq\\Repair\\MigrateAppConfigKeys',
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