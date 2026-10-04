<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/EventListener/SigningTaskListener.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\EventListener\SigningTaskListener
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-36c98f17fb24cd6c72b9cf7406460c9571b21afd8f406481c4a72994d5789be5',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/EventListener/SigningTaskListener.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\EventListener',
    'name' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
    'shortName' => 'SigningTaskListener',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Listener for OR task-sequence events relevant to filinq signing requests.
 *
 * @category EventListener
 * @package  OCA\\Filinq\\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @implements IEventListener<Event>
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 69,
    'endLine' => 419,
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
      'EVENT_TASK_TRANSITIONED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'name' => 'EVENT_TASK_TRANSITIONED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'OCA\\OpenRegister\\Event\\TaskTransitionedEvent\'',
          'attributes' => 
          array (
            'startLine' => 78,
            'endLine' => 78,
            'startTokenPos' => 64,
            'startFilePos' => 3140,
            'endTokenPos' => 64,
            'endFilePos' => 3188,
          ),
        ),
        'docComment' => '/**
 * FQN of OR\'s committed task-transition event, as a string on purpose:
 * a cross-app class name is a runtime lookup, and a literal cannot
 * accidentally autoload or hard-couple (openregister#3302 mapping).
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 78,
        'endLine' => 78,
        'startColumn' => 2,
        'endColumn' => 90,
      ),
      'EVENT_TASK_TERMINAL' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'name' => 'EVENT_TASK_TERMINAL',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'OCA\\OpenRegister\\Event\\TaskTerminalEvent\'',
          'attributes' => 
          array (
            'startLine' => 85,
            'endLine' => 85,
            'startTokenPos' => 77,
            'startFilePos' => 3295,
            'endTokenPos' => 77,
            'endFilePos' => 3339,
          ),
        ),
        'docComment' => '/**
 * FQN of OR\'s terminal-task event.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 85,
        'endLine' => 85,
        'startColumn' => 2,
        'endColumn' => 82,
      ),
      'EVENT_SEQUENCE_COMPLETED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'name' => 'EVENT_SEQUENCE_COMPLETED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'OCA\\OpenRegister\\Event\\TaskSequenceCompletedEvent\'',
          'attributes' => 
          array (
            'startLine' => 92,
            'endLine' => 92,
            'startTokenPos' => 90,
            'startFilePos' => 3456,
            'endTokenPos' => 90,
            'endFilePos' => 3509,
          ),
        ),
        'docComment' => '/**
 * FQN of OR\'s sequence-completed event.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 92,
        'endLine' => 92,
        'startColumn' => 2,
        'endColumn' => 96,
      ),
      'TASK_STATE_CLASS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'name' => 'TASK_STATE_CLASS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'OCA\\OpenRegister\\Service\\Task\\TaskState\'',
          'attributes' => 
          array (
            'startLine' => 99,
            'endLine' => 99,
            'startTokenPos' => 103,
            'startFilePos' => 3622,
            'endTokenPos' => 103,
            'endFilePos' => 3666,
          ),
        ),
        'docComment' => '/**
 * FQN of OR\'s task-state vocabulary class.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 99,
        'endLine' => 99,
        'startColumn' => 2,
        'endColumn' => 80,
      ),
      'STATE_ENABLED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'name' => 'STATE_ENABLED',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'enabled\'',
          'attributes' => 
          array (
            'startLine' => 106,
            'endLine' => 106,
            'startTokenPos' => 116,
            'startFilePos' => 3806,
            'endTokenPos' => 116,
            'endFilePos' => 3814,
          ),
        ),
        'docComment' => '/**
 * The task state meaning "this position is the one a person can act on".
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 106,
        'endLine' => 106,
        'startColumn' => 2,
        'endColumn' => 41,
      ),
      'STATE_COMPLETED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'name' => 'STATE_COMPLETED',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'completed\'',
          'attributes' => 
          array (
            'startLine' => 113,
            'endLine' => 113,
            'startTokenPos' => 129,
            'startFilePos' => 3954,
            'endTokenPos' => 129,
            'endFilePos' => 3964,
          ),
        ),
        'docComment' => '/**
 * The task state meaning "the work finished with an explicit outcome".
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 113,
        'endLine' => 113,
        'startColumn' => 2,
        'endColumn' => 45,
      ),
      'REJECTING_OUTCOMES_FALLBACK' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'name' => 'REJECTING_OUTCOMES_FALLBACK',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'rejected\', \'returned\', \'declined\', \'denied\']',
          'attributes' => 
          array (
            'startLine' => 124,
            'endLine' => 124,
            'startTokenPos' => 142,
            'startFilePos' => 4380,
            'endTokenPos' => 153,
            'endFilePos' => 4425,
          ),
        ),
        'docComment' => '/**
 * OR\'s published rejecting-outcome vocabulary, as a fallback when
 * `TaskState` cannot be resolved. On the live path it always can — OR
 * just dispatched the event — so the fallback exists for test
 * environments and defence in depth, mirroring
 * `TaskState::REJECTING_OUTCOMES` (approval-events-migration.md).
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 124,
        'endLine' => 124,
        'startColumn' => 2,
        'endColumn' => 92,
      ),
    ),
    'immediateProperties' => 
    array (
      'translator' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'name' => 'translator',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\EventListener\\SignerEventTranslator',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 140,
        'endLine' => 140,
        'startColumn' => 3,
        'endColumn' => 52,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'settingsService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'name' => 'settingsService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\SettingsService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 141,
        'endLine' => 141,
        'startColumn' => 3,
        'endColumn' => 51,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
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
        'startLine' => 142,
        'endLine' => 142,
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
          'translator' => 
          array (
            'name' => 'translator',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\EventListener\\SignerEventTranslator',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 140,
            'endLine' => 140,
            'startColumn' => 3,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'settingsService' => 
          array (
            'name' => 'settingsService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\SettingsService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 141,
            'endLine' => 141,
            'startColumn' => 3,
            'endColumn' => 51,
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
            'startLine' => 142,
            'endLine' => 142,
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
 * @param SignerEventTranslator $translator Translates the extracted
 *                                          scalars into filinq signer
 *                                          events and notifies the provider.
 * @param SettingsService $settingsService Resolves the signingRequest
 *                                         binding and the object service
 *                                         for the ownership check.
 * @param LoggerInterface $logger Logger.
 *
 * @return void
 */',
        'startLine' => 139,
        'endLine' => 145,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
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
            'startLine' => 156,
            'endLine' => 156,
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
 * Handle an OR task-sequence event.
 *
 * @param Event $event The OR-dispatched event.
 *
 * @return void
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */',
        'startLine' => 156,
        'endLine' => 181,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'aliasName' => NULL,
      ),
      'handleTransitioned' => 
      array (
        'name' => 'handleTransitioned',
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
            'startLine' => 192,
            'endLine' => 192,
            'startColumn' => 38,
            'endColumn' => 49,
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
 * A committed task transition: a position becoming enabled is a signer\'s
 * turn — the replacement for the retired `ApprovalStepInitiatedEvent`
 * and the retired approved event\'s `nextStep` branch.
 *
 * @param Event $event OR\'s TaskTransitionedEvent.
 *
 * @return void
 */',
        'startLine' => 192,
        'endLine' => 229,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'aliasName' => NULL,
      ),
      'handleTerminal' => 
      array (
        'name' => 'handleTerminal',
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
            'startLine' => 243,
            'endLine' => 243,
            'startColumn' => 34,
            'endColumn' => 45,
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
 * A terminal task: state `completed` on a filinq sequence task is a
 * signer\'s decision — the replacement for the retired approved and
 * rejected events, told apart by the outcome vocabulary. Uncommitted
 * dispatches (the in-transaction one from TaskMapper) and terminal
 * states that are not completions (cancel, moot, run termination) are
 * skipped: the retired surface had no equivalent for those.
 *
 * @param Event $event OR\'s TaskTerminalEvent.
 *
 * @return void
 */',
        'startLine' => 243,
        'endLine' => 287,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'aliasName' => NULL,
      ),
      'handleSequenceCompleted' => 
      array (
        'name' => 'handleSequenceCompleted',
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
            'startLine' => 298,
            'endLine' => 298,
            'startColumn' => 43,
            'endColumn' => 54,
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
 * A completed sequence: the final position approved — the replacement
 * for the retired `ApprovalStepCompletedEvent`, dispatched by OR at
 * exactly the same moment.
 *
 * @param Event $event OR\'s TaskSequenceCompletedEvent.
 *
 * @return void
 */',
        'startLine' => 298,
        'endLine' => 325,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'aliasName' => NULL,
      ),
      'read' => 
      array (
        'name' => 'read',
        'parameters' => 
        array (
          'subject' => 
          array (
            'name' => 'subject',
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
            'startLine' => 341,
            'endLine' => 341,
            'startColumn' => 24,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'getter' => 
          array (
            'name' => 'getter',
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
            'startLine' => 341,
            'endLine' => 341,
            'startColumn' => 41,
            'endColumn' => 54,
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
            'name' => 'mixed',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Read one getter off a cross-app object, ducking the type system.
 *
 * `is_callable()` rather than `method_exists()`, deliberately: OR\'s
 * Task entity serves its getters through `Entity::__call`, for which
 * `method_exists()` answers false while the call works fine. A getter
 * that is not callable reads as null, and every caller treats null as
 * "absent", which fails closed.
 *
 * @param object $subject The OR event or entity.
 * @param string $getter The getter name.
 *
 * @return mixed The getter\'s value, or null when not callable.
 */',
        'startLine' => 341,
        'endLine' => 347,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'aliasName' => NULL,
      ),
      'isFilinqSequenceTask' => 
      array (
        'name' => 'isFilinqSequenceTask',
        'parameters' => 
        array (
          'sequenceUuid' => 
          array (
            'name' => 'sequenceUuid',
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
            'startLine' => 366,
            'endLine' => 366,
            'startColumn' => 40,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'objectUuid' => 
          array (
            'name' => 'objectUuid',
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
            'startLine' => 366,
            'endLine' => 366,
            'startColumn' => 62,
            'endColumn' => 79,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Decide whether a sequence task (or a sequence) belongs to a filinq
 * signing-request.
 *
 * It does iff BOTH hold: the task is part of a sequence (plain workflow
 * tasks never reach the object lookup), and its anchor object resolves
 * in the configured signingRequest register/schema. With the binding
 * unconfigured every event is foreign — fail closed, exactly as the
 * retired slug filter did.
 *
 * @param string $sequenceUuid The task\'s sequence uuid (\'\' when none).
 * @param string $objectUuid The anchor object uuid (\'\' when none).
 *
 * @return bool True when the event targets a filinq signing-request.
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */',
        'startLine' => 366,
        'endLine' => 396,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'aliasName' => NULL,
      ),
      'isRejectingOutcome' => 
      array (
        'name' => 'isRejectingOutcome',
        'parameters' => 
        array (
          'outcome' => 
          array (
            'name' => 'outcome',
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
            'startLine' => 411,
            'endLine' => 411,
            'startColumn' => 38,
            'endColumn' => 52,
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
 * Classify an outcome against OR\'s rejecting vocabulary.
 *
 * Delegates to `TaskState::isRejectingOutcome()` when the class
 * resolves — on the live path it always does, because OR just
 * dispatched the event — and otherwise falls back to the published
 * vocabulary. The `class_exists()` here runs at event time, never at
 * register() time, so the bootstrap-order invariant holds.
 *
 * @param string $outcome The task\'s outcome.
 *
 * @return bool True when the outcome is rejecting.
 */',
        'startLine' => 411,
        'endLine' => 418,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\SigningTaskListener',
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