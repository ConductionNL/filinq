<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DemoDataService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\DemoDataService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-258cc55c3bd49cbe18b81d8e0caa1378d2775a4d33815dc3f9cd604c53450e52',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\DemoDataService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DemoDataService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\DemoDataService',
    'shortName' => 'DemoDataService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Imports the shipped demo dataset on request.
 *
 * @spec exclude Demo-data import; ADR-111 rule 1, no per-app behavioural spec.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 40,
    'endLine' => 289,
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
      'DESCRIPTOR' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'name' => 'DESCRIPTOR',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'/lib/Settings/filinq_mock_register.json\'',
          'attributes' => 
          array (
            'startLine' => 46,
            'endLine' => 46,
            'startTokenPos' => 59,
            'startFilePos' => 1342,
            'endTokenPos' => 59,
            'endFilePos' => 1382,
          ),
        ),
        'docComment' => '/**
 * App-relative path to the generated mock descriptor.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 46,
        'endLine' => 46,
        'startColumn' => 2,
        'endColumn' => 70,
      ),
      'CONFIG_APP_ID' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'name' => 'CONFIG_APP_ID',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\\OCA\\Filinq\\AppInfo\\Application::APP_ID . \'.demo\'',
          'attributes' => 
          array (
            'startLine' => 58,
            'endLine' => 58,
            'startTokenPos' => 72,
            'startFilePos' => 1758,
            'endTokenPos' => 78,
            'endFilePos' => 1786,
          ),
        ),
        'docComment' => '/**
 * Configuration identity for the demo import.
 *
 * 🔴 ITS OWN NAMESPACE, not the app id. Sharing the app\'s identity would make
 * the demo import and the real configuration import share one version gate, so
 * installing demo data could mask a pending configuration update — or be
 * masked by one.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 58,
        'endLine' => 58,
        'startColumn' => 2,
        'endColumn' => 61,
      ),
      'NONE_DATASET' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'name' => 'NONE_DATASET',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'none\'',
          'attributes' => 
          array (
            'startLine' => 96,
            'endLine' => 96,
            'startTokenPos' => 168,
            'startFilePos' => 2900,
            'endTokenPos' => 168,
            'endFilePos' => 2905,
          ),
        ),
        'docComment' => '/**
 * The answer that means "plant nothing".
 *
 * 🔴 NOT THE ABSENCE OF AN ANSWER. An operator who declines has FINISHED the
 * step; a step that can never be marked done reopens the wizard over every
 * page (nextcloud-vue#806).
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 96,
        'endLine' => 96,
        'startColumn' => 2,
        'endColumn' => 36,
      ),
      'DEMO_DATASET' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'name' => 'DEMO_DATASET',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'demo\'',
          'attributes' => 
          array (
            'startLine' => 103,
            'endLine' => 103,
            'startTokenPos' => 181,
            'startFilePos' => 3010,
            'endTokenPos' => 181,
            'endFilePos' => 3015,
          ),
        ),
        'docComment' => '/**
 * The id of the dataset this app ships.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 103,
        'endLine' => 103,
        'startColumn' => 2,
        'endColumn' => 36,
      ),
    ),
    'immediateProperties' => 
    array (
      'appManager' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
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
        'startLine' => 70,
        'endLine' => 70,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'container' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
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
        'startLine' => 71,
        'endLine' => 71,
        'startColumn' => 3,
        'endColumn' => 48,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
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
        'startLine' => 72,
        'endLine' => 72,
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
            'startLine' => 70,
            'endLine' => 70,
            'startColumn' => 3,
            'endColumn' => 42,
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
            'startLine' => 71,
            'endLine' => 71,
            'startColumn' => 3,
            'endColumn' => 48,
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
            'startLine' => 72,
            'endLine' => 72,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 2,
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
 * @param IAppManager        $appManager Resolves this app\'s path and version.
 * @param ContainerInterface $container  Resolves OpenRegister\'s importer.
 * @param LoggerInterface    $logger     Records what was imported.
 *
 * @return void
 */',
        'startLine' => 69,
        'endLine' => 74,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
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
 * Whether this app ships a demo dataset at all.
 *
 * @return boolean True when the descriptor is present on disk.
 *
 * @spec exclude Demo-data availability probe; ADR-111 rule 1 has no per-app behavioural spec.
 */',
        'startLine' => 83,
        'endLine' => 85,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'aliasName' => NULL,
      ),
      'listChoices' => 
      array (
        'name' => 'listChoices',
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
 * Every answer the wizard\'s choice step may offer, declining included.
 *
 * 🔴 THE SERVER OWNS THIS LIST, AND THAT IS THE POINT. The step declares
 * `optionsSource: datasets` and no options of its own, so the label, the
 * description and the object count come from the descriptor that will
 * actually be imported. A manifest that restated them could disagree with
 * what lands, and nothing would notice.
 *
 * @return array<int, array{id: string, label: string, description: string, objectCount: integer, icon: string}> The answers.
 *
 * @spec exclude Demo-data choice list; ADR-111 rule 1 has no per-app behavioural spec.
 */',
        'startLine' => 118,
        'endLine' => 153,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'aliasName' => NULL,
      ),
      'shippedObjectCount' => 
      array (
        'name' => 'shippedObjectCount',
        'parameters' => 
        array (
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
                  'name' => 'int',
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
 * How many objects the shipped descriptor carries, or null when it ships none.
 *
 * Counted from the FILE, so the card promises the number that will actually
 * be imported. A missing or malformed descriptor returns null and the app
 * then offers only "None" — honest, rather than an import that cannot run.
 *
 * @return integer|null The object count, or null when there is no usable descriptor.
 */',
        'startLine' => 164,
        'endLine' => 187,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'aliasName' => NULL,
      ),
      'install' => 
      array (
        'name' => 'install',
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
 * Import the demo dataset.
 *	/**
 * Import the demo dataset.
 *
 * 🔴 THROWS RATHER THAN RETURNING A QUIET FAILURE. The caller reports the
 * outcome to an operator who just asked for this, so "nothing happened" must
 * not be presentable as success.
 *
 * @return array{objects: integer, registers: integer, schemas: integer} What was imported.
 *
 * @throws RuntimeException When the descriptor is missing, unreadable, or OpenRegister is absent.
 *
 * @spec exclude Demo-data import; ADR-111 rule 1 has no per-app behavioural spec.
 */',
        'startLine' => 204,
        'endLine' => 252,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'aliasName' => NULL,
      ),
      'descriptorPath' => 
      array (
        'name' => 'descriptorPath',
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
 * Absolute path to the shipped descriptor.
 *
 * @return string The path.
 */',
        'startLine' => 259,
        'endLine' => 261,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'aliasName' => NULL,
      ),
      'configurationService' => 
      array (
        'name' => 'configurationService',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'object',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * OpenRegister\'s configuration importer.
 *
 * 🔴 A CROSS-APP CLASS IS A RUNTIME LOOKUP. OpenRegister may not be installed,
 * and asking the container for a class from a missing app raises something the
 * caller cannot act on. Check first and say which app is missing.
 *
 * 🔴 THE RETURN TYPE IS `object`, NOT THE CLASS, AND THAT IS THE POINT. Naming
 * a class from an OPTIONAL app in a native return type makes PHP resolve it
 * whenever this method returns, so on an instance without OpenRegister the
 * failure is a TypeError about a class nobody mentioned instead of the
 * RuntimeException above that names the missing app.
 *
 * @return object The importer — an OCA\\OpenRegister\\Service\\ConfigurationService.
 *
 * @psalm-return \\OCA\\OpenRegister\\Service\\ConfigurationService
 *
 * @throws RuntimeException When OpenRegister is not installed.
 */',
        'startLine' => 282,
        'endLine' => 288,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DemoDataService',
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