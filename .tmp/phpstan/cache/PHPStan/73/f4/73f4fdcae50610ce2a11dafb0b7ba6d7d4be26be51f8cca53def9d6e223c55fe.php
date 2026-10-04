<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/EventListener/DocumentRegistrationWriteGuard.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\EventListener\DocumentRegistrationWriteGuard
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-e9f45b42ac9b1a9c1c9c32a952ae08c0f795ad81d3ada6c97cdd619b05a59728',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\EventListener\\DocumentRegistrationWriteGuard',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/EventListener/DocumentRegistrationWriteGuard.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\EventListener',
    'name' => 'OCA\\Filinq\\EventListener\\DocumentRegistrationWriteGuard',
    'shortName' => 'DocumentRegistrationWriteGuard',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Refuses an update that changes a registration number already issued.
 *
 * 🔴 A POST REGISTER\'S WHOLE VALUE IS THAT ITS NUMBERS ARE FIXED. A number is a
 * claim about an official act: a letter went out on this date under this
 * number, and somebody may have quoted it in a reply. Letting it be edited
 * afterwards means a number in a filing cabinet no longer finds the record it
 * names, and nothing anywhere reports that the two came apart.
 *
 * 🔑 THE TASK SAID "IN THE SERVICE EVERY WRITE PATH RESOLVES THROUGH", AND
 * FILINQ HAS NO SUCH SERVICE. Registrations are written through OpenRegister\'s
 * objects API, so a guard in a filinq service would be bypassed by every
 * ordinary write. What filinq does have is the platform\'s PRE-WRITE event, and
 * it was checked rather than assumed:
 *
 *   - `MagicMapper` dispatches `ObjectUpdatingEvent` before an update, carrying
 *     both the new object and the old one;
 *   - `stopPropagation()` plus `setErrors()` on that event makes the mapper
 *     throw `HookStoppedException` with the listener\'s own message.
 *
 * So the refusal lands on every write path that goes through the mapper, which
 * is all of them. The PAST-tense `ObjectUpdatedEvent` filinq already listens to
 * could not have done this: by the time it fires the number is already changed.
 *
 * @template-implements IEventListener<Event>
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 56,
    'endLine' => 177,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'OCP\\EventDispatcher\\IEventListener',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'SCHEMA' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\DocumentRegistrationWriteGuard',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\DocumentRegistrationWriteGuard',
        'name' => 'SCHEMA',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'documentRegistration\'',
          'attributes' => 
          array (
            'startLine' => 61,
            'endLine' => 61,
            'startTokenPos' => 59,
            'startFilePos' => 2271,
            'endTokenPos' => 59,
            'endFilePos' => 2292,
          ),
        ),
        'docComment' => '/**
 * The schema whose numbers are final.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 61,
        'endLine' => 61,
        'startColumn' => 2,
        'endColumn' => 46,
      ),
      'NUMBER' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\DocumentRegistrationWriteGuard',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\DocumentRegistrationWriteGuard',
        'name' => 'NUMBER',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'registrationNumber\'',
          'attributes' => 
          array (
            'startLine' => 66,
            'endLine' => 66,
            'startTokenPos' => 72,
            'startFilePos' => 2366,
            'endTokenPos' => 72,
            'endFilePos' => 2385,
          ),
        ),
        'docComment' => '/**
 * The property that must not move.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 66,
        'endLine' => 66,
        'startColumn' => 2,
        'endColumn' => 44,
      ),
      'WITHDRAWN_REASON' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\DocumentRegistrationWriteGuard',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\DocumentRegistrationWriteGuard',
        'name' => 'WITHDRAWN_REASON',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'withdrawnReason\'',
          'attributes' => 
          array (
            'startLine' => 71,
            'endLine' => 71,
            'startTokenPos' => 85,
            'startFilePos' => 2469,
            'endTokenPos' => 85,
            'endFilePos' => 2485,
          ),
        ),
        'docComment' => '/**
 * Why an allocation was abandoned.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 71,
        'endLine' => 71,
        'startColumn' => 2,
        'endColumn' => 51,
      ),
    ),
    'immediateProperties' => 
    array (
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\DocumentRegistrationWriteGuard',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\DocumentRegistrationWriteGuard',
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
        'startLine' => 79,
        'endLine' => 79,
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
            'startLine' => 79,
            'endLine' => 79,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 0,
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
 * @param LoggerInterface $logger Structured logger.
 */',
        'startLine' => 78,
        'endLine' => 81,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\DocumentRegistrationWriteGuard',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\DocumentRegistrationWriteGuard',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\DocumentRegistrationWriteGuard',
        'aliasName' => NULL,
      ),
      'handle' => 
      array (
        'name' => 'handle',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\EventDispatcher\\Event',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 90,
            'endLine' => 90,
            'startColumn' => 25,
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
 * Refuse an update that moves a number already issued.
 *
 * @param Event $event The dispatched event.
 *
 * @return void
 */',
        'startLine' => 90,
        'endLine' => 141,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\DocumentRegistrationWriteGuard',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\DocumentRegistrationWriteGuard',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\DocumentRegistrationWriteGuard',
        'aliasName' => NULL,
      ),
      'isRegistration' => 
      array (
        'name' => 'isRegistration',
        'parameters' => 
        array (
          'schema' => 
          array (
            'name' => 'schema',
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
            'startColumn' => 34,
            'endColumn' => 47,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Whether this is a post-register entry.
 *
 * 🔑 MATCHED ON THE SLUG, WHICH IS WHAT THE EVENT CARRIES. The schema id is
 * numeric and differs per instance, so comparing against one would make the
 * guard silently inert everywhere but the instance it was written on.
 *
 * @param string $schema The schema reference on the object.
 *
 * @return bool Whether the guard applies.
 */',
        'startLine' => 154,
        'endLine' => 156,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\DocumentRegistrationWriteGuard',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\DocumentRegistrationWriteGuard',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\DocumentRegistrationWriteGuard',
        'aliasName' => NULL,
      ),
      'numberOf' => 
      array (
        'name' => 'numberOf',
        'parameters' => 
        array (
          'object' => 
          array (
            'name' => 'object',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'object',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 165,
            'endLine' => 165,
            'startColumn' => 28,
            'endColumn' => 41,
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
 * The registration number an object carries, or \'\'.
 *
 * @param object $object The object entity.
 *
 * @return string The number.
 */',
        'startLine' => 165,
        'endLine' => 176,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\DocumentRegistrationWriteGuard',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\DocumentRegistrationWriteGuard',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\DocumentRegistrationWriteGuard',
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