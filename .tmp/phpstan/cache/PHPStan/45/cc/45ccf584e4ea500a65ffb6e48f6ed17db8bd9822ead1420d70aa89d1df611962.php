<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/AppInfo/OpenRegisterAutoloader.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\AppInfo\OpenRegisterAutoloader
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-b371df813a2fd88f76c08891f1b52f0b894c864829c7887c72dcdee0c1981d03',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\AppInfo\\OpenRegisterAutoloader',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/AppInfo/OpenRegisterAutoloader.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\AppInfo',
    'name' => 'OCA\\Filinq\\AppInfo\\OpenRegisterAutoloader',
    'shortName' => 'OpenRegisterAutoloader',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Registers OpenRegister\'s autoload prefix before AppHost is referenced.
 *
 * ## Why this is needed (ADR-040)
 *
 * `OC_App::getEnabledApps()` does `sort($apps)`, and
 * `Coordinator::registerApps()` walks THAT sorted list calling
 * `OC_App::registerAutoloading($appId, $path)` and then `$app->register()` for
 * one app at a time. So every app\'s `register()` runs BEFORE the PSR-4 prefix
 * of every alphabetically-LATER app exists.
 *
 * `filinq` sorts before `openregister`, so `OCA\\OpenRegister\\` is NOT
 * autoloadable inside `Application::register()` on a perfectly healthy
 * instance with OpenRegister enabled. Left unguarded, the resulting `\\Error`
 * aborted the whole of `register()` — the audit listener recorded ZERO
 * dispatched events, while `Coordinator` logged an `emergency` and carried on,
 * so the app stayed enabled and looked fine.
 *
 * Lives in its own class rather than inline in `Application::register()` for
 * one reason: `Application` cannot be constructed without a Nextcloud DI
 * container, so an inline prelude is unreachable from a unit test. Here the
 * degraded-path contract — "this NEVER throws, whatever the instance looks
 * like" — is directly assertable, and it is asserted.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 49,
    'endLine' => 99,
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
        'declaringClassName' => 'OCA\\Filinq\\AppInfo\\OpenRegisterAutoloader',
        'implementingClassName' => 'OCA\\Filinq\\AppInfo\\OpenRegisterAutoloader',
        'name' => 'OPENREGISTER_APP_ID',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'openregister\'',
          'attributes' => 
          array (
            'startLine' => 54,
            'endLine' => 54,
            'startTokenPos' => 37,
            'startFilePos' => 1952,
            'endTokenPos' => 37,
            'endFilePos' => 1965,
          ),
        ),
        'docComment' => '/**
 * The app whose autoload prefix this prelude registers.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 54,
        'endLine' => 54,
        'startColumn' => 2,
        'endColumn' => 52,
      ),
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
 * Register OpenRegister\'s PSR-4 prefix on the composer autoloader.
 *
 * MUST be called before any `OCA\\OpenRegister\\…` reference in
 * `Application::register()`, including a `class_exists()` probe — the probe
 * answers FALSE, not "not yet loaded", and a FALSE is indistinguishable
 * from OpenRegister being absent.
 *
 * `OC_App::registerAutoloading()` touches only the autoloader and is
 * idempotent: it early-returns on an `$alreadyRegistered` key, so calling
 * this more than once is free.
 *
 * Deliberately NOT `IAppManager::loadApp(\'openregister\')`: that marks
 * OpenRegister loaded and calls `Coordinator::bootApp()`, booting it before
 * its own `register()` has run.
 *
 * @return bool True when the prefix is registered, false when OpenRegister
 *              is absent, disabled, or otherwise unresolvable — in which
 *              case the caller MUST fall through to its degraded path.
 *
 * @SuppressWarnings(PHPMD.StaticAccess) OC_App is Nextcloud\'s legacy
 * bootstrap class. There is no OCP interface for registering another app\'s
 * autoloader, and this runs at the composition root where no container is
 * available to resolve an adapter from.
 *
 * @spec openspec/specs/adopt-apphost/spec.md
 */',
        'startLine' => 83,
        'endLine' => 98,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'OCA\\Filinq\\AppInfo',
        'declaringClassName' => 'OCA\\Filinq\\AppInfo\\OpenRegisterAutoloader',
        'implementingClassName' => 'OCA\\Filinq\\AppInfo\\OpenRegisterAutoloader',
        'currentClassName' => 'OCA\\Filinq\\AppInfo\\OpenRegisterAutoloader',
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