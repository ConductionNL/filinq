<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Repair/MigrateUserPreferences.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Repair\MigrateUserPreferences
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-a0605367efdc347357b5ed97902de5c9661aef83c0cb91d473716d0a166196e2',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Repair\\MigrateUserPreferences',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Repair/MigrateUserPreferences.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Repair',
    'name' => 'OCA\\Filinq\\Repair\\MigrateUserPreferences',
    'shortName' => 'MigrateUserPreferences',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Copy per-user preferences from the docudesk app id to filinq.
 *
 * @spec exclude No canonical spec covers the docudesk -> filinq app-id rename.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 93,
    'endLine' => 214,
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
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateUserPreferences',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateUserPreferences',
        'name' => 'OLD_APP_ID',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'docudesk\'',
          'attributes' => 
          array (
            'startLine' => 101,
            'endLine' => 101,
            'startTokenPos' => 59,
            'startFilePos' => 4422,
            'endTokenPos' => 59,
            'endFilePos' => 4431,
          ),
        ),
        'docComment' => '/**
 * The `oc_preferences` namespace this app used before the rename.
 *
 * Deliberately the OLD app id.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 101,
        'endLine' => 101,
        'startColumn' => 2,
        'endColumn' => 39,
      ),
      'NEW_APP_ID' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateUserPreferences',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateUserPreferences',
        'name' => 'NEW_APP_ID',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq\'',
          'attributes' => 
          array (
            'startLine' => 108,
            'endLine' => 108,
            'startTokenPos' => 72,
            'startFilePos' => 4560,
            'endTokenPos' => 72,
            'endFilePos' => 4567,
          ),
        ),
        'docComment' => '/**
 * The `oc_preferences` namespace this app uses after the rename.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 108,
        'endLine' => 108,
        'startColumn' => 2,
        'endColumn' => 37,
      ),
      'MIGRATED_KEYS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateUserPreferences',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateUserPreferences',
        'name' => 'MIGRATED_KEYS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'anonymiser_warning_dismissed\' => [\'1\'], \'pref_support-dialog-seen\' => [\'1\']]',
          'attributes' => 
          array (
            'startLine' => 117,
            'endLine' => 120,
            'startTokenPos' => 85,
            'startFilePos' => 4878,
            'endTokenPos' => 105,
            'endFilePos' => 4963,
          ),
        ),
        'docComment' => '/**
 * Every per-user key this app has ever written, with the values it can
 * hold. Add to this list when a new per-user preference is introduced;
 * a key missing here is a key that silently resets on the next rename.
 *
 * @var array<string, array<int, string>>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 117,
        'endLine' => 120,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'config' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateUserPreferences',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateUserPreferences',
        'name' => 'config',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IConfig',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 131,
        'endLine' => 131,
        'startColumn' => 3,
        'endColumn' => 34,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateUserPreferences',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateUserPreferences',
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
        'startLine' => 132,
        'endLine' => 132,
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
                'name' => 'OCP\\IConfig',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 131,
            'endLine' => 131,
            'startColumn' => 3,
            'endColumn' => 34,
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
            'startLine' => 132,
            'endLine' => 132,
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
 * Constructor.
 *
 * @param IConfig $config The user-value store to read and write.
 * @param LoggerInterface $logger Logger for preferences that fail to copy.
 *
 * @return void
 */',
        'startLine' => 130,
        'endLine' => 134,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateUserPreferences',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateUserPreferences',
        'currentClassName' => 'OCA\\Filinq\\Repair\\MigrateUserPreferences',
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
        'startLine' => 143,
        'endLine' => 145,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateUserPreferences',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateUserPreferences',
        'currentClassName' => 'OCA\\Filinq\\Repair\\MigrateUserPreferences',
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
            'startLine' => 156,
            'endLine' => 156,
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
 * Copy every known per-user preference from the old app id to the new one.
 *
 * @param IOutput $output Repair output channel.
 *
 * @return void
 *
 * @spec exclude No canonical spec covers the docudesk -> filinq app-id rename.
 */',
        'startLine' => 156,
        'endLine' => 213,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\MigrateUserPreferences',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\MigrateUserPreferences',
        'currentClassName' => 'OCA\\Filinq\\Repair\\MigrateUserPreferences',
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