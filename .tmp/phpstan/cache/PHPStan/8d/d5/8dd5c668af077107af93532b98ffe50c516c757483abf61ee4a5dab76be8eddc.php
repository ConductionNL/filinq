<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/AppInfo/ObjectEventRegistrar.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\AppInfo\ObjectEventRegistrar
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-b63714c2861b0fa37b6f758b0a33428778659bf1a2dfb5488fa071f4fcc55aa5',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\AppInfo\\ObjectEventRegistrar',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/AppInfo/ObjectEventRegistrar.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\AppInfo',
    'name' => 'OCA\\Filinq\\AppInfo\\ObjectEventRegistrar',
    'shortName' => 'ObjectEventRegistrar',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Registers dashboard widgets and OpenRegister object-lifecycle listeners.
 *
 * @category AppInfo
 * @package  OCA\\Filinq\\AppInfo
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) This class IS the list of
 * listeners the app registers, so its coupling is the count of them. The
 * fourteenth is DocumentRegistrationWriteGuard, which refuses a change to a
 * registration number before the write lands.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 56,
    'endLine' => 209,
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
            'startLine' => 70,
            'endLine' => 70,
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
 * Register dashboard widgets and the unfiltered object-lifecycle listeners.
 *
 * Deliberately NOT narrowed to a register/schema set: FilinqEventHandler
 * identifies its work by PAYLOAD SHAPE (`looksLikeDossier()`,
 * `detectPolicyShape()`) rather than by schema, and EnrichmentRunner
 * enriches metadata on EVERY object on the instance regardless of
 * register. Declaring any slug list here would silently drop work.
 *
 * @param IRegistrationContext $context The registration context.
 *
 * @return void
 */',
        'startLine' => 70,
        'endLine' => 122,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\AppInfo',
        'declaringClassName' => 'OCA\\Filinq\\AppInfo\\ObjectEventRegistrar',
        'implementingClassName' => 'OCA\\Filinq\\AppInfo\\ObjectEventRegistrar',
        'currentClassName' => 'OCA\\Filinq\\AppInfo\\ObjectEventRegistrar',
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
            'startLine' => 136,
            'endLine' => 136,
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
            'startLine' => 136,
            'endLine' => 136,
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
 * Declare the register/schema-filtered dossier listener.
 *
 * Auto-regen the dossier grondslagen summary when `checkedOn` is updated.
 * Declared from boot() rather than register() so the OpenRegister guard is
 * independent of this app\'s position in the bootstrap order.
 *
 * @param ContainerInterface $container The server container.
 * @param string $appName The filinq app id, for log context.
 *
 * @return void
 */',
        'startLine' => 136,
        'endLine' => 146,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\AppInfo',
        'declaringClassName' => 'OCA\\Filinq\\AppInfo\\ObjectEventRegistrar',
        'implementingClassName' => 'OCA\\Filinq\\AppInfo\\ObjectEventRegistrar',
        'currentClassName' => 'OCA\\Filinq\\AppInfo\\ObjectEventRegistrar',
        'aliasName' => NULL,
      ),
      'registerFilteredObjectListener' => 
      array (
        'name' => 'registerFilteredObjectListener',
        'parameters' => 
        array (
          'dispatcher' => 
          array (
            'name' => 'dispatcher',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\EventDispatcher\\IEventDispatcher',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 178,
            'endLine' => 178,
            'startColumn' => 3,
            'endColumn' => 30,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'event' => 
          array (
            'name' => 'event',
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
            'startLine' => 179,
            'endLine' => 179,
            'startColumn' => 3,
            'endColumn' => 15,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'listener' => 
          array (
            'name' => 'listener',
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
            'startLine' => 180,
            'endLine' => 180,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'registers' => 
          array (
            'name' => 'registers',
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
            'startLine' => 181,
            'endLine' => 181,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'schemas' => 
          array (
            'name' => 'schemas',
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
            'startLine' => 182,
            'endLine' => 182,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 4,
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
            'startLine' => 183,
            'endLine' => 183,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 5,
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
 * Register an object-lifecycle listener that declares its interest up front.
 *
 * OpenRegister\'s `ObjectEventSubscription` records the register/schema slugs
 * a listener reacts to and routes dispatches through a single shared proxy,
 * so an uninterested listener is neither constructed nor invoked. When
 * OpenRegister is absent — Filinq carries no hard dependency on it — this
 * degrades to the plain global registration it replaced, which is exactly
 * the behaviour every listener had before.
 *
 * MUST be called from boot(), never from register(). Nextcloud enables each
 * app\'s autoloader immediately before calling that app\'s own register()
 * (`OC\\AppFramework\\Bootstrap\\Coordinator::registerApps()`), so at register()
 * time OpenRegister\'s classes are only autoloadable to apps that boot after
 * it. Filinq is app 21 of 92 and OpenRegister is 52, so the class_exists()
 * guard below was ALWAYS false there and this app silently fell back to an
 * unfiltered registration — one of seven fleet conversions that looked
 * successful while being inert. boot() runs only after every app\'s
 * register() has completed, which makes the guard order-independent.
 *
 * @param IEventDispatcher $dispatcher The live event dispatcher.
 * @param string $event OpenRegister event class name.
 * @param string $listener Listener class name.
 * @param array<int,string> $registers Register slugs the listener reacts to.
 * @param array<int,string> $schemas Schema slugs the listener reacts to.
 * @param string $appName The filinq app id, for log context.
 *
 * @return void
 */',
        'startLine' => 177,
        'endLine' => 208,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\AppInfo',
        'declaringClassName' => 'OCA\\Filinq\\AppInfo\\ObjectEventRegistrar',
        'implementingClassName' => 'OCA\\Filinq\\AppInfo\\ObjectEventRegistrar',
        'currentClassName' => 'OCA\\Filinq\\AppInfo\\ObjectEventRegistrar',
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