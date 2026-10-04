<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/AppInfo/RegistrationBootstrap.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\AppInfo\RegistrationBootstrap
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-3b52bf464a6863bf6feae56d3d13cd46c51212d0a360c2a451ef5602b24f7e6d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\AppInfo\\RegistrationBootstrap',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/AppInfo/RegistrationBootstrap.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\AppInfo',
    'name' => 'OCA\\Filinq\\AppInfo\\RegistrationBootstrap',
    'shortName' => 'RegistrationBootstrap',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Runs every registration and boot step Filinq needs.
 *
 * @category AppInfo
 * @package  OCA\\Filinq\\AppInfo
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) This IS the composition
 * root: its whole job is to name every class the app wires together, so the
 * coupling count measures the size of the app rather than a design fault.
 * Splitting it to satisfy the metric would scatter the wiring across files
 * that each know part of the answer, which is what the registrars it calls
 * already do one layer down.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 53,
    'endLine' => 220,
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
    ),
    'immediateMethods' => 
    array (
      'register' => 
      array (
        'name' => 'register',
        'parameters' => 
        array (
          'context' => 
          array (
            'name' => 'context',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\AppFramework\\Bootstrap\\IRegistrationContext',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 61,
            'endLine' => 61,
            'startColumn' => 27,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Register every Filinq service, listener and middleware.
 *
 * @param IRegistrationContext $context The registration context.
 *
 * @return void
 */',
        'startLine' => 61,
        'endLine' => 144,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\AppInfo',
        'declaringClassName' => 'OCA\\Filinq\\AppInfo\\RegistrationBootstrap',
        'implementingClassName' => 'OCA\\Filinq\\AppInfo\\RegistrationBootstrap',
        'currentClassName' => 'OCA\\Filinq\\AppInfo\\RegistrationBootstrap',
        'aliasName' => NULL,
      ),
      'boot' => 
      array (
        'name' => 'boot',
        'parameters' => 
        array (
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 154,
            'endLine' => 154,
            'startColumn' => 23,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'appName' => 
          array (
            'name' => 'appName',
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
            'startLine' => 154,
            'endLine' => 154,
            'startColumn' => 54,
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
 * Run every boot-time step.
 *
 * @param ContainerInterface $container The server container.
 * @param string $appName The filinq app id.
 *
 * @return void
 */',
        'startLine' => 154,
        'endLine' => 165,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\AppInfo',
        'declaringClassName' => 'OCA\\Filinq\\AppInfo\\RegistrationBootstrap',
        'implementingClassName' => 'OCA\\Filinq\\AppInfo\\RegistrationBootstrap',
        'currentClassName' => 'OCA\\Filinq\\AppInfo\\RegistrationBootstrap',
        'aliasName' => NULL,
      ),
      'bindStoreController' => 
      array (
        'name' => 'bindStoreController',
        'parameters' => 
        array (
          'context' => 
          array (
            'name' => 'context',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\AppFramework\\Bootstrap\\IRegistrationContext',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 193,
            'endLine' => 193,
            'startColumn' => 39,
            'endColumn' => 67,
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
 * Bind the store controller the adopted route table already declares.
 *
 * 🔴 THIS ROUTE ARRIVES WHETHER THE APP WANTS IT OR NOT.
 *
 * `Routes::standard()`, which appinfo/routes.php adopts, declares
 * `/api/store/items`. The binding normally comes from
 * `Bootstrap::register()`, and filinq does not call that: it composes its
 * own registrars and keeps its own settings, signing and conversion
 * classes. The store controller was simply never bound.
 *
 * So the route matched a controller class that does not exist, and every
 * request to it returned HTTP 500 rather than 404. Measured on a running
 * instance 2026-09-03, alongside decidiq and planninq.
 *
 * @param IRegistrationContext $context The registration context.
 *
 * @return void
 *
 * @SuppressWarnings(PHPMD.StaticAccess) OCA\\OpenRegister\\AppHost\\Bootstrap
 * is a cross-app static entry point in a SIBLING app that may be absent or
 * unloadable here — the call is guarded by class_exists() and wrapped in a
 * catch(\\Throwable) for exactly that reason. It cannot be injected: this
 * runs at the composition root, so there is no container to resolve an
 * adapter from. OpenRegisterAutoloader::register() is static for the same
 * reason.
 */',
        'startLine' => 193,
        'endLine' => 218,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\AppInfo',
        'declaringClassName' => 'OCA\\Filinq\\AppInfo\\RegistrationBootstrap',
        'implementingClassName' => 'OCA\\Filinq\\AppInfo\\RegistrationBootstrap',
        'currentClassName' => 'OCA\\Filinq\\AppInfo\\RegistrationBootstrap',
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