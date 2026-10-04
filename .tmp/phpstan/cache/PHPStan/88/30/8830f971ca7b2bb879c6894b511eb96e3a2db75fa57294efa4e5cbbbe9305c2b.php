<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/AppInfo/SigningEventRegistrar.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\AppInfo\SigningEventRegistrar
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-a0de68d7b0094e4b14614d7c0a139528af2009b131b15474db8e2d3e628b98ca',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\AppInfo\\SigningEventRegistrar',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/AppInfo/SigningEventRegistrar.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\AppInfo',
    'name' => 'OCA\\Filinq\\AppInfo\\SigningEventRegistrar',
    'shortName' => 'SigningEventRegistrar',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Registers the task-sequence bridge and the cross-app signing-request listener.
 *
 * @category AppInfo
 * @package  OCA\\Filinq\\AppInfo
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 44,
    'endLine' => 101,
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
      'TASK_EVENTS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\AppInfo\\SigningEventRegistrar',
        'implementingClassName' => 'OCA\\Filinq\\AppInfo\\SigningEventRegistrar',
        'name' => 'TASK_EVENTS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'OCA\\OpenRegister\\Event\\TaskTransitionedEvent\', \'OCA\\OpenRegister\\Event\\TaskTerminalEvent\', \'OCA\\OpenRegister\\Event\\TaskSequenceCompletedEvent\']',
          'attributes' => 
          array (
            'startLine' => 64,
            'endLine' => 68,
            'startTokenPos' => 55,
            'startFilePos' => 2465,
            'endTokenPos' => 66,
            'endFilePos' => 2628,
          ),
        ),
        'docComment' => '/**
 * The OpenRegister task events the signing bridge consumes, as FQN
 * string literals on purpose. `::class` on an imported name is a
 * compile-time string too, but a literal keeps that true even if
 * someone later adds the import — and during our own register() the
 * `OCA\\OpenRegister\\` prefix is not on the autoloader yet, so neither a
 * `class_exists()` probe (always false here) nor an eager reference
 * (aborts register()) is an option; `BootstrapOrderIndependenceTest`
 * pins both rules. Registering for an event class that never comes to
 * exist is harmless: the dispatcher keys listeners by name, and the
 * name is simply never dispatched. Mapping per openregister#3302
 * (flow-approval-consolidation, approval-events-migration.md):
 * transitioned-to-enabled replaces the retired step-initiated signal,
 * committed terminality replaces step-approved and step-rejected, and
 * sequence completion replaces chain completion.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 64,
        'endLine' => 68,
        'startColumn' => 2,
        'endColumn' => 3,
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
            'startLine' => 79,
            'endLine' => 79,
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
 * Register the signing event listeners.
 *
 * @param IRegistrationContext $context The registration context.
 *
 * @return void
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */',
        'startLine' => 79,
        'endLine' => 100,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\AppInfo',
        'declaringClassName' => 'OCA\\Filinq\\AppInfo\\SigningEventRegistrar',
        'implementingClassName' => 'OCA\\Filinq\\AppInfo\\SigningEventRegistrar',
        'currentClassName' => 'OCA\\Filinq\\AppInfo\\SigningEventRegistrar',
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