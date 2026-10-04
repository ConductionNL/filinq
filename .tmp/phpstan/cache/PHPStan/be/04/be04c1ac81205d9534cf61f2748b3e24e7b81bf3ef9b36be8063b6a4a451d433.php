<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/EventListener/SignerEventTranslator.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\EventListener\SignerEventTranslator
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-b6214c2d89a52394841f6496b7ec7b10b81deafc49b41f4e2f21b06d96629b35',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\EventListener\\SignerEventTranslator',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/EventListener/SignerEventTranslator.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\EventListener',
    'name' => 'OCA\\Filinq\\EventListener\\SignerEventTranslator',
    'shortName' => 'SignerEventTranslator',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Re-emits task-sequence transitions as Filinq signer events.
 *
 * @category EventListener
 * @package  OCA\\Filinq\\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 52,
    'endLine' => 238,
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
      'providerFactory' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\SignerEventTranslator',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\SignerEventTranslator',
        'name' => 'providerFactory',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Signing\\SigningProviderFactory',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 68,
        'endLine' => 68,
        'startColumn' => 3,
        'endColumn' => 58,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'dispatcher' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\SignerEventTranslator',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\SignerEventTranslator',
        'name' => 'dispatcher',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 69,
        'endLine' => 69,
        'startColumn' => 3,
        'endColumn' => 47,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\SignerEventTranslator',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\SignerEventTranslator',
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
        'startLine' => 70,
        'endLine' => 70,
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
          'providerFactory' => 
          array (
            'name' => 'providerFactory',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Signing\\SigningProviderFactory',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 68,
            'endLine' => 68,
            'startColumn' => 3,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 69,
            'endLine' => 69,
            'startColumn' => 3,
            'endColumn' => 47,
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
            'startLine' => 70,
            'endLine' => 70,
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
 * @param SigningProviderFactory $providerFactory Provider factory for invoking
 *                                                the configured provider on
 *                                                position-enabled transitions.
 * @param IEventDispatcher $dispatcher Dispatcher used to re-emit
 *                                     typed filinq-side events.
 * @param LoggerInterface $logger Logger.
 *
 * @return void
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */',
        'startLine' => 67,
        'endLine' => 73,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\SignerEventTranslator',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\SignerEventTranslator',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\SignerEventTranslator',
        'aliasName' => NULL,
      ),
      'onPositionEnabled' => 
      array (
        'name' => 'onPositionEnabled',
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
            'startLine' => 95,
            'endLine' => 95,
            'startColumn' => 3,
            'endColumn' => 22,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'taskUuid' => 
          array (
            'name' => 'taskUuid',
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
            'startLine' => 96,
            'endLine' => 96,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'position' => 
          array (
            'name' => 'position',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 97,
            'endLine' => 97,
            'startColumn' => 3,
            'endColumn' => 15,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'role' => 
          array (
            'name' => 'role',
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
                      'name' => 'string',
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
            'startLine' => 98,
            'endLine' => 98,
            'startColumn' => 3,
            'endColumn' => 15,
            'parameterIndex' => 3,
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
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 4,
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
 * Handle a sequence position becoming enabled (a signer\'s turn).
 *
 * Re-emits the typed pending event and invokes the configured provider.
 * Both the first position (sequence provisioned) and every next position
 * (previous position approved) arrive here: OR enables the next position
 * in the same request as the approving decision, so no separate
 * "next step" payload exists.
 *
 * @param string $sequenceUuid UUID of the OR task sequence.
 * @param string $taskUuid UUID of the now-enabled task.
 * @param int $position Ordinal of the position (1-based).
 * @param string|null $role The position\'s signer group, when one is set.
 * @param string $objectUuid Signing-request UUID.
 *
 * @return void
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */',
        'startLine' => 94,
        'endLine' => 116,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\SignerEventTranslator',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\SignerEventTranslator',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\SignerEventTranslator',
        'aliasName' => NULL,
      ),
      'onTaskDecided' => 
      array (
        'name' => 'onTaskDecided',
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
            'startLine' => 141,
            'endLine' => 141,
            'startColumn' => 3,
            'endColumn' => 22,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'taskUuid' => 
          array (
            'name' => 'taskUuid',
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
            'startLine' => 142,
            'endLine' => 142,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'position' => 
          array (
            'name' => 'position',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 143,
            'endLine' => 143,
            'startColumn' => 3,
            'endColumn' => 15,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'userId' => 
          array (
            'name' => 'userId',
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
                      'name' => 'string',
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
            'startLine' => 144,
            'endLine' => 144,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'comment' => 
          array (
            'name' => 'comment',
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
                      'name' => 'string',
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
            'startLine' => 145,
            'endLine' => 145,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 4,
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
            'startLine' => 146,
            'endLine' => 146,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
          'isRejecting' => 
          array (
            'name' => 'isRejecting',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 147,
            'endLine' => 147,
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 6,
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
 * Handle a sequence task completing with a decision.
 *
 * Approving outcomes re-emit the typed approved event; rejecting
 * outcomes the typed rejected event. When an approval was not the final
 * position, the next position\'s own enabled transition follows through
 * {@see onPositionEnabled()}; the final approval additionally arrives
 * through {@see onSequenceCompleted()}.
 *
 * @param string $sequenceUuid UUID of the OR task sequence.
 * @param string $taskUuid UUID of the completed task.
 * @param int $position Ordinal of the position (1-based).
 * @param string|null $userId The completing identity.
 * @param string|null $comment The completion comment, when one was given.
 * @param string $objectUuid Signing-request UUID.
 * @param bool $isRejecting TRUE when the outcome is in the rejecting
 *                          vocabulary.
 *
 * @return void
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */',
        'startLine' => 140,
        'endLine' => 174,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\SignerEventTranslator',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\SignerEventTranslator',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\SignerEventTranslator',
        'aliasName' => NULL,
      ),
      'onSequenceCompleted' => 
      array (
        'name' => 'onSequenceCompleted',
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
            'startLine' => 190,
            'endLine' => 190,
            'startColumn' => 3,
            'endColumn' => 22,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'finalTaskUuid' => 
          array (
            'name' => 'finalTaskUuid',
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
            'startLine' => 191,
            'endLine' => 191,
            'startColumn' => 3,
            'endColumn' => 23,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'userId' => 
          array (
            'name' => 'userId',
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
                      'name' => 'string',
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
            'startLine' => 192,
            'endLine' => 192,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'statusOnApprove' => 
          array (
            'name' => 'statusOnApprove',
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
            'startLine' => 193,
            'endLine' => 193,
            'startColumn' => 3,
            'endColumn' => 25,
            'parameterIndex' => 3,
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
            'startLine' => 194,
            'endLine' => 194,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 4,
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
 * Handle a sequence completing (final position approved).
 *
 * @param string $sequenceUuid UUID of the completed OR task sequence.
 * @param string $finalTaskUuid UUID of the final position\'s task.
 * @param string|null $userId The identity that decided the final position.
 * @param string $statusOnApprove The resolved approving status.
 * @param string $objectUuid Signing-request UUID.
 *
 * @return void
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */',
        'startLine' => 189,
        'endLine' => 206,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\SignerEventTranslator',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\SignerEventTranslator',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\SignerEventTranslator',
        'aliasName' => NULL,
      ),
      'invokeProviderForEnabledPosition' => 
      array (
        'name' => 'invokeProviderForEnabledPosition',
        'parameters' => 
        array (
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
            'startLine' => 223,
            'endLine' => 223,
            'startColumn' => 52,
            'endColumn' => 69,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'position' => 
          array (
            'name' => 'position',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 223,
            'endLine' => 223,
            'startColumn' => 72,
            'endColumn' => 84,
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
 * Resolve the active provider and ask it to handle an enabled position.
 *
 * The `NativeSigningProvider` is a no-op for this call today: it waits
 * for the filinq UI signer-action endpoint, whose reply path (once the
 * deferred write-path rewrite lands) is `TaskService::complete()` with
 * an approving or rejecting outcome. External providers
 * (`ValidSignProvider` and future plugins) may use this hook to push a
 * signing-request to the external service or send the signer email.
 *
 * @param string $objectUuid Signing-request UUID.
 * @param int $position Position ordinal (1-based).
 *
 * @return void
 */',
        'startLine' => 223,
        'endLine' => 237,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\SignerEventTranslator',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\SignerEventTranslator',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\SignerEventTranslator',
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