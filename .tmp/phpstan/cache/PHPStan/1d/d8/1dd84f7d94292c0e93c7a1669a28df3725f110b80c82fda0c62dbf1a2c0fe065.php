<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Controller/SetupController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Controller\SetupController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-ca9aca3569cea253d410ae81c21dcec2af73694e36823d96f77c69238de41e1a',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Controller\\SetupController',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Controller/SetupController.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Controller',
    'name' => 'OCA\\Filinq\\Controller\\SetupController',
    'shortName' => 'SetupController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * First-time setup wizard endpoints.
 *
 * @spec exclude First-time-setup action dispatch; ADR-042 contract, no per-app behavioural spec.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 48,
    'endLine' => 347,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'OCP\\AppFramework\\Controller',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'SETUP_VERSION' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'name' => 'SETUP_VERSION',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '1',
          'attributes' => 
          array (
            'startLine' => 54,
            'endLine' => 54,
            'startTokenPos' => 98,
            'startFilePos' => 1684,
            'endTokenPos' => 98,
            'endFilePos' => 1684,
          ),
        ),
        'docComment' => '/**
 * Setup contract version; matches manifest.setup.version.
 *
 * @var integer
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 54,
        'endLine' => 54,
        'startColumn' => 2,
        'endColumn' => 33,
      ),
      'DEMO_DECIDED_KEY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'name' => 'DEMO_DECIDED_KEY',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'demo_data_decided\'',
          'attributes' => 
          array (
            'startLine' => 68,
            'endLine' => 68,
            'startTokenPos' => 111,
            'startFilePos' => 2228,
            'endTokenPos' => 111,
            'endFilePos' => 2246,
          ),
        ),
        'docComment' => '/**
 * App-config key recording that the demo-data step was DEALT WITH.
 *
 * Not "objects exist": an operator who declines has finished the step, and
 * re-offering the import on every visit would make "no thanks" impossible to
 * express. Since @conduction/nextcloud-vue 2.21 that also matters visually —
 * an OUTSTANDING OPTIONAL step opens the wizard over every page
 * (nextcloud-vue#806), so a step that can never be marked done is a dialog
 * that never closes.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 68,
        'endLine' => 68,
        'startColumn' => 2,
        'endColumn' => 54,
      ),
      'DATASET_KEY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'name' => 'DATASET_KEY',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'demo_dataset\'',
          'attributes' => 
          array (
            'startLine' => 81,
            'endLine' => 81,
            'startTokenPos' => 124,
            'startFilePos' => 2723,
            'endTokenPos' => 124,
            'endFilePos' => 2736,
          ),
        ),
        'docComment' => '/**
 * App-config key holding the dataset the operator picked.
 *
 * The wizard\'s `choice` step writes it through `POST /api/setup/config`, and
 * the `run-action` step that follows reads it back. Two steps rather than
 * one because `CnSetupWizard::runAction()` posts to
 * `/api/setup/action/{action}` with no body: an action cannot carry the
 * answer, so the answer has to be stored before the action runs.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 81,
        'endLine' => 81,
        'startColumn' => 2,
        'endColumn' => 44,
      ),
    ),
    'immediateProperties' => 
    array (
      'appConfig' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\SetupController',
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
        'startLine' => 96,
        'endLine' => 96,
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
        'declaringClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\SetupController',
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
        'startLine' => 97,
        'endLine' => 97,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'demoDataService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'name' => 'demoDataService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\DemoDataService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 98,
        'endLine' => 98,
        'startColumn' => 3,
        'endColumn' => 51,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'mountValidator' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'name' => 'mountValidator',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\ExternalMountValidator',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 99,
        'endLine' => 99,
        'startColumn' => 3,
        'endColumn' => 57,
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
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IRequest',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 95,
            'endLine' => 95,
            'startColumn' => 3,
            'endColumn' => 19,
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
            'startLine' => 96,
            'endLine' => 96,
            'startColumn' => 3,
            'endColumn' => 40,
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
            'startLine' => 97,
            'endLine' => 97,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'demoDataService' => 
          array (
            'name' => 'demoDataService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\DemoDataService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 98,
            'endLine' => 98,
            'startColumn' => 3,
            'endColumn' => 51,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'mountValidator' => 
          array (
            'name' => 'mountValidator',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\ExternalMountValidator',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 3,
            'endColumn' => 57,
            'parameterIndex' => 4,
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
 * @param IRequest        $request         The request.
 * @param IAppConfig      $appConfig       Records the demo-data decision.
 * @param LoggerInterface $logger          Records a failed import.
 * @param DemoDataService $demoDataService Imports the shipped demo dataset.
 * @param ExternalMountValidator $mountValidator Names what the domain store cannot keep.
 *
 * @return void
 */',
        'startLine' => 94,
        'endLine' => 103,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'aliasName' => NULL,
      ),
      'status' => 
      array (
        'name' => 'status',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\AuthorizedAdminSetting',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\\OCA\\Filinq\\Settings\\FilinqAdmin::class',
                'attributes' => 
                array (
                  'startLine' => 116,
                  'endLine' => 116,
                  'startTokenPos' => 208,
                  'startFilePos' => 3944,
                  'endTokenPos' => 210,
                  'endFilePos' => 3961,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * Report per-step setup status for the wizard.
 *
 * `completed` is deliberately TRUE: this app declares no REQUIRED step, so
 * setup must never gate the app. The demo-data step is reported so the wizard
 * can stop asking once it has an answer.
 *
 * @return JSONResponse The status document.
 *
 * @spec exclude Setup status document; ADR-042 contract, no per-app behavioural spec.
 */',
        'startLine' => 116,
        'endLine' => 147,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'aliasName' => NULL,
      ),
      'domainStoreStep' => 
      array (
        'name' => 'domainStoreStep',
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
 * What the store behind the domain folders can and cannot keep.
 *
 * 🔑 IT NEVER BLOCKS SETUP. `done` is true whatever the findings say: an
 * administrator may legitimately run filinq on a store whose permissions are
 * managed outside Nextcloud, and an outstanding optional step opens the
 * wizard over every page. What they may not do is decide that without the
 * consequence in front of them, so the findings travel with the step.
 *
 * 🔴 A VALIDATOR THAT THROWS MUST NOT TAKE THE WIZARD DOWN. This runs on the
 * first screen an administrator sees; a storage backend that raises here
 * would make setup unreachable, which is a worse failure than the one the
 * check exists to report.
 *
 * @return array<string, mixed> The step.
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
        'startLine' => 167,
        'endLine' => 197,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'aliasName' => NULL,
      ),
      'saveConfig' => 
      array (
        'name' => 'saveConfig',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\AuthorizedAdminSetting',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\\OCA\\Filinq\\Settings\\FilinqAdmin::class',
                'attributes' => 
                array (
                  'startLine' => 206,
                  'endLine' => 206,
                  'startTokenPos' => 611,
                  'startFilePos' => 7354,
                  'endTokenPos' => 613,
                  'endFilePos' => 7371,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * Persist the wizard\'s `choice` answer.
 *
 * @return JSONResponse `{ success, config }`.
 *
 * @spec exclude Setup config write; ADR-042 contract, no per-app behavioural spec.
 */',
        'startLine' => 206,
        'endLine' => 245,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'aliasName' => NULL,
      ),
      'runAction' => 
      array (
        'name' => 'runAction',
        'parameters' => 
        array (
          'actionId' => 
          array (
            'name' => 'actionId',
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
            'startLine' => 259,
            'endLine' => 259,
            'startColumn' => 28,
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
            'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\AuthorizedAdminSetting',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\\OCA\\Filinq\\Settings\\FilinqAdmin::class',
                'attributes' => 
                array (
                  'startLine' => 258,
                  'endLine' => 258,
                  'startTokenPos' => 952,
                  'startFilePos' => 9269,
                  'endTokenPos' => 954,
                  'endFilePos' => 9286,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * Run a privileged server-side setup action.
 *
 * Admin-only by Nextcloud\'s default for an un-attributed method.
 *
 * @param string $actionId One of `install-demo-data` | `skip-demo-data`.
 *
 * @return JSONResponse `{ success, message }`.
 *
 * @spec exclude Setup action dispatch; ADR-042 contract, no per-app behavioural spec.
 */',
        'startLine' => 258,
        'endLine' => 284,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'aliasName' => NULL,
      ),
      'loadDataset' => 
      array (
        'name' => 'loadDataset',
        'parameters' => 
        array (
          'actionId' => 
          array (
            'name' => 'actionId',
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
            'startLine' => 298,
            'endLine' => 298,
            'startColumn' => 31,
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
            'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Import the dataset the operator picked in the previous step.
 *
 * Reports the FAILURE rather than a quiet success: an operator who asked for
 * example data and got none must be told, which is why
 * DemoDataService::install() throws instead of returning an empty result.
 *
 * @param string $actionId The action that asked, which decides whether an
 *                         unanswered choice is refused or means the shipped set.
 *
 * @return JSONResponse `{ success, message }`.
 */',
        'startLine' => 298,
        'endLine' => 346,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\SetupController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\SetupController',
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