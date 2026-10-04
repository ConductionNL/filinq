<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/AppInfo/ObservabilityRegistrar.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\AppInfo\ObservabilityRegistrar
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-68f1063ac8991b4d3ceb403b7fbe5f4ce44fa1b573500c4d7ee51ffc168a89c4',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\AppInfo\\ObservabilityRegistrar',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/AppInfo/ObservabilityRegistrar.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\AppInfo',
    'name' => 'OCA\\Filinq\\AppInfo\\ObservabilityRegistrar',
    'shortName' => 'ObservabilityRegistrar',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Registers the AppHost MetricsEngine under its OpenRegister FQCN.
 *
 * @category AppInfo
 * @package  OCA\\Filinq\\AppInfo
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/adopt-apphost/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 41,
    'endLine' => 91,
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
            'startLine' => 80,
            'endLine' => 80,
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
 * Wire the AppHost metrics engine.
 *
 * Filinq\'s HealthController / MetricsController do NOT subclass the
 * OpenRegister generics and do NOT take engine collaborators as constructor
 * parameters — they resolve them out of the container BY FQCN STRING at
 * dispatch time and degrade (health `degraded` / metrics 503) when the
 * lookup fails. Both auto-wire from OCP alone, so neither is registered
 * here.
 *
 * ⚠️ Do NOT re-introduce a `registerService(HealthController::class, …)` /
 * `registerService(MetricsController::class, …)` pair that passes engine
 * collaborators, and do NOT turn those controllers back into subclasses of
 * the OpenRegister generics. Nextcloud\'s router `ReflectionClass()`es every
 * file in lib/Controller/ while MATCHING a route, so one unresolvable parent
 * returns HTTP 500 for EVERY filinq route — and Filinq does not declare
 * `<app>openregister</app>`, so an admin can create exactly that
 * configuration. `extends` is resolved by the AUTOLOADER, not this
 * container, so lazy registration cannot rescue it. See filinq#369 /
 * decidesk#377.
 *
 * MetricsEngine still needs an explicit factory: OpenRegister\'s own
 * MetricsEngine factory is registered under the `openregister` app container
 * and is not visible here, and auto-wiring it fresh would fail on the
 * multi-arg constructor. Registering it under its own FQCN string keeps that
 * explicit construction while letting MetricsController find it with a plain
 * `$container->get()`.
 *
 * ⚠️ The closure body is the ONLY place the OpenRegister name is resolved,
 * and a closure body runs on `get()`, never at registration. The closure
 * therefore declares `object` as its return type, not the engine\'s own type.
 *
 * @param IRegistrationContext $context The registration context.
 *
 * @return void
 *
 * @spec openspec/specs/adopt-apphost/spec.md
 */',
        'startLine' => 80,
        'endLine' => 90,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\AppInfo',
        'declaringClassName' => 'OCA\\Filinq\\AppInfo\\ObservabilityRegistrar',
        'implementingClassName' => 'OCA\\Filinq\\AppInfo\\ObservabilityRegistrar',
        'currentClassName' => 'OCA\\Filinq\\AppInfo\\ObservabilityRegistrar',
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