<?php declare(strict_types = 1);

// osfsl-/home/rubenlinde/memcap-work/lq-lanes/fq/vendor/composer/../psr/event-dispatcher/src/StoppableEventInterface.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Psr\EventDispatcher\StoppableEventInterface
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-eafe42b0fa6f2704d8c034bf447451927652e6908a5349d6da9e2f3c3982c91f-8.3-6.70.0.6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Psr\\EventDispatcher\\StoppableEventInterface',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/vendor/composer/../psr/event-dispatcher/src/StoppableEventInterface.php',
      ),
    ),
    'namespace' => 'Psr\\EventDispatcher',
    'name' => 'Psr\\EventDispatcher\\StoppableEventInterface',
    'shortName' => 'StoppableEventInterface',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * An Event whose processing may be interrupted when the event has been handled.
 *
 * A Dispatcher implementation MUST check to determine if an Event
 * is marked as stopped after each listener is called.  If it is then it should
 * return immediately without calling any further Listeners.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 13,
    'endLine' => 26,
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
      'isPropagationStopped' => 
      array (
        'name' => 'isPropagationStopped',
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
 * Is propagation stopped?
 *
 * This will typically only be used by the Dispatcher to determine if the
 * previous listener halted propagation.
 *
 * @return bool
 *   True if the Event is complete and no further listeners should be called.
 *   False to continue calling listeners.
 */',
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 50,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Psr\\EventDispatcher',
        'declaringClassName' => 'Psr\\EventDispatcher\\StoppableEventInterface',
        'implementingClassName' => 'Psr\\EventDispatcher\\StoppableEventInterface',
        'currentClassName' => 'Psr\\EventDispatcher\\StoppableEventInterface',
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