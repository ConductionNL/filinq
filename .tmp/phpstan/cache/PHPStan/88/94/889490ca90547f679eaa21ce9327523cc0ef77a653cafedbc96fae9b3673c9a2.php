<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/EventListener/EntityRelationDecisionListener.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\EventListener\EntityRelationDecisionListener
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-5a6b1043d3399df1c1a633c291a4a992ce3b2f17b7d39bcfaadae5c3f6adba77',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\EventListener\\EntityRelationDecisionListener',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/EventListener/EntityRelationDecisionListener.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\EventListener',
    'name' => 'OCA\\Filinq\\EventListener\\EntityRelationDecisionListener',
    'shortName' => 'EntityRelationDecisionListener',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Subscribes to OpenRegister\'s `EntityRelationDecisionUpdatedEvent`.
 *
 * WHAT THIS CLOSES. OpenRegister has dispatched this event from
 * `EntityRelationMapper` for some time and this app never subscribed to it —
 * measured 2026-08-25, zero references outside `openspec/` and no commit in the
 * repository\'s history that ever added one. So every decision to publish an
 * entity unredacted was dropped on the floor, and the consent record that
 * decision is supposed to create was never created.
 *
 * That failure was invisible from outside for a reason worth stating: three of
 * the four scenarios in the spec are NEGATIVE ("no consent record is created"),
 * and they were all satisfied — by nothing happening, rather than by the guards
 * working. THE ABSENCE OF A BUG AND THE ABSENCE OF THE FEATURE LOOK IDENTICAL.
 * See ConductionNL/filinq#805.
 *
 * WHAT IT DELIBERATELY DOES NOT DO. It never throws. Nextcloud dispatches events
 * synchronously inside the request that changed the relation, so an exception
 * here would fail the operator\'s PATCH — turning a consent-bookkeeping problem
 * into "you cannot save this decision". Every failure is logged and swallowed,
 * which is the same posture `FilinqEventListener` takes.
 *
 * @spec openspec/specs/consent-management/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 53,
    'endLine' => 253,
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
      'OR_EVENT' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\EntityRelationDecisionListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\EntityRelationDecisionListener',
        'name' => 'OR_EVENT',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'OCA\\OpenRegister\\Event\\EntityRelationDecisionUpdatedEvent\'',
          'attributes' => 
          array (
            'startLine' => 69,
            'endLine' => 69,
            'startTokenPos' => 78,
            'startFilePos' => 2822,
            'endTokenPos' => 78,
            'endFilePos' => 2880,
          ),
        ),
        'docComment' => '/**
 * The OpenRegister event this listener answers to.
 *
 * A CONSTANT, NOT A LITERAL AT THE CALL SITE. Passing the name inline to
 * `is_a()` reads as a type assertion to static analysis, so psalm tries to
 * resolve a class this app does not depend on and reports UndefinedClass.
 * Behind a constant the runtime behaviour is identical and the analysis
 * stays honest: nothing here claims the class is loadable.
 *
 * It is also the one place the coupling is written down, so a rename on
 * OpenRegister\'s side has a single site to fix rather than three.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 69,
        'endLine' => 69,
        'startColumn' => 2,
        'endColumn' => 86,
      ),
    ),
    'immediateProperties' => 
    array (
      'consentService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\EntityRelationDecisionListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\EntityRelationDecisionListener',
        'name' => 'consentService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\ConsentService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 81,
        'endLine' => 81,
        'startColumn' => 3,
        'endColumn' => 49,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'consentCrud' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\EntityRelationDecisionListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\EntityRelationDecisionListener',
        'name' => 'consentCrud',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\ConsentCrudService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 82,
        'endLine' => 82,
        'startColumn' => 3,
        'endColumn' => 50,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'appManager' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\EntityRelationDecisionListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\EntityRelationDecisionListener',
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
        'startLine' => 83,
        'endLine' => 83,
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
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\EntityRelationDecisionListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\EntityRelationDecisionListener',
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
        'startLine' => 84,
        'endLine' => 84,
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
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\EntityRelationDecisionListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\EntityRelationDecisionListener',
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
        'startLine' => 85,
        'endLine' => 85,
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
          'consentService' => 
          array (
            'name' => 'consentService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\ConsentService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 81,
            'endLine' => 81,
            'startColumn' => 3,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'consentCrud' => 
          array (
            'name' => 'consentCrud',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\ConsentCrudService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 82,
            'endLine' => 82,
            'startColumn' => 3,
            'endColumn' => 50,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 2,
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
            'startLine' => 84,
            'endLine' => 84,
            'startColumn' => 3,
            'endColumn' => 48,
            'parameterIndex' => 3,
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
            'startLine' => 85,
            'endLine' => 85,
            'startColumn' => 3,
            'endColumn' => 42,
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
 * @param ConsentService     $consentService Creates the consent request.
 * @param ConsentCrudService $consentCrud    Resolves the configured register/schema.
 * @param IAppManager        $appManager     Tells us whether OpenRegister is installed.
 * @param ContainerInterface $container      Resolves OpenRegister classes by name.
 * @param LoggerInterface    $logger         Records every path that declines to act.
 */',
        'startLine' => 80,
        'endLine' => 87,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\EntityRelationDecisionListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\EntityRelationDecisionListener',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\EntityRelationDecisionListener',
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
            'startLine' => 96,
            'endLine' => 96,
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
 * Handle the event.
 *
 * @param Event $event The dispatched event.
 *
 * @return void
 */',
        'startLine' => 96,
        'endLine' => 191,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\EntityRelationDecisionListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\EntityRelationDecisionListener',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\EntityRelationDecisionListener',
        'aliasName' => NULL,
      ),
      'asDecision' => 
      array (
        'name' => 'asDecision',
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
            'startLine' => 213,
            'endLine' => 213,
            'startColumn' => 30,
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
            'name' => 'mixed',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Widen a verified event to `mixed` so its OpenRegister accessors are callable.
 *
 * THIS EXISTS TO SATISFY TWO TOOLS THAT DISAGREE, and the disagreement is
 * worth recording rather than suppressing:
 *
 *   * psalm narrows `$event` through the `is_a()` check above, then reports
 *     UndefinedMethod on every accessor, because the class it narrowed to
 *     belongs to an app this one does not depend on.
 *   * phpcs forbids the inline `/** @var * /` annotation that would widen it
 *     back at the call site.
 *
 * A method with a real docblock is the one form both accept. The runtime
 * behaviour is a plain assignment; the shape is guaranteed by the `is_a()`
 * check in `handle()`, not by anything either tool can see.
 *
 * @param Event $event An event already verified to be the OpenRegister one.
 *
 * @return mixed The same object, untyped.
 */',
        'startLine' => 213,
        'endLine' => 215,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\EntityRelationDecisionListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\EntityRelationDecisionListener',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\EntityRelationDecisionListener',
        'aliasName' => NULL,
      ),
      'resolveEntity' => 
      array (
        'name' => 'resolveEntity',
        'parameters' => 
        array (
          'entityId' => 
          array (
            'name' => 'entityId',
            'default' => NULL,
            'type' => 
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 229,
            'endLine' => 229,
            'startColumn' => 33,
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
            'name' => 'mixed',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolve the GDPR entity a relation points at.
 *
 * Looked up through the container by class NAME rather than injected, for
 * the same reason `handle()` avoids `instanceof`: OpenRegister is an
 * optional peer, and a constructor type-hint on one of its classes would
 * make this app unbootable without it.
 *
 * @param int|null $entityId The relation\'s entity id.
 *
 * @return mixed The entity, or null when it cannot be resolved.
 */',
        'startLine' => 229,
        'endLine' => 252,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\EntityRelationDecisionListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\EntityRelationDecisionListener',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\EntityRelationDecisionListener',
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