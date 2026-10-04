<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Event/SignerChainCompletedEvent.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Event\SignerChainCompletedEvent
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-25fd57b6175c342bf48f1c4de28e21ab4fbdd0131b18148e0d096bb19707c074',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Event/SignerChainCompletedEvent.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Event',
    'name' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
    'shortName' => 'SignerChainCompletedEvent',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Fired when the task sequence of a filinq sign-request completes.
 *
 * @category Event
 * @package  OCA\\Filinq\\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 47,
    'endLine' => 127,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'OCP\\EventDispatcher\\Event',
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
      'sequenceUuid' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
        'name' => 'sequenceUuid',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 63,
        'endLine' => 63,
        'startColumn' => 3,
        'endColumn' => 39,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'finalTaskUuid' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
        'name' => 'finalTaskUuid',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 64,
        'endLine' => 64,
        'startColumn' => 3,
        'endColumn' => 40,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'userId' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
        'name' => 'userId',
        'modifiers' => 132,
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
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 65,
        'endLine' => 65,
        'startColumn' => 3,
        'endColumn' => 34,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'statusOnApprove' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
        'name' => 'statusOnApprove',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 66,
        'endLine' => 66,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'objectUuid' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
        'name' => 'objectUuid',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 67,
        'endLine' => 67,
        'startColumn' => 3,
        'endColumn' => 37,
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 63,
            'endLine' => 63,
            'startColumn' => 3,
            'endColumn' => 39,
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 64,
            'endLine' => 64,
            'startColumn' => 3,
            'endColumn' => 40,
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 65,
            'endLine' => 65,
            'startColumn' => 3,
            'endColumn' => 34,
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 66,
            'endLine' => 66,
            'startColumn' => 3,
            'endColumn' => 42,
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 3,
            'endColumn' => 37,
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
 * @param string $sequenceUuid UUID of the completed OR task sequence.
 * @param string $finalTaskUuid UUID of the final position\'s task.
 * @param string|null $userId The identity that decided the final position.
 * @param string $statusOnApprove The approving status the frozen
 *                                declaration resolves to.
 * @param string $objectUuid UUID of the filinq signing request.
 *
 * @return void
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */',
        'startLine' => 62,
        'endLine' => 71,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
        'aliasName' => NULL,
      ),
      'getSequenceUuid' => 
      array (
        'name' => 'getSequenceUuid',
        'parameters' => 
        array (
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
 * Get the sequence UUID.
 *
 * @return string UUID of the completed OR task sequence.
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */',
        'startLine' => 80,
        'endLine' => 82,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
        'aliasName' => NULL,
      ),
      'getFinalTaskUuid' => 
      array (
        'name' => 'getFinalTaskUuid',
        'parameters' => 
        array (
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
 * Get the final task\'s UUID.
 *
 * @return string UUID of the final position\'s task.
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */',
        'startLine' => 91,
        'endLine' => 93,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
        'aliasName' => NULL,
      ),
      'getUserId' => 
      array (
        'name' => 'getUserId',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the deciding identity.
 *
 * @return string|null Who decided the final position, when known.
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */',
        'startLine' => 102,
        'endLine' => 104,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
        'aliasName' => NULL,
      ),
      'getStatusOnApprove' => 
      array (
        'name' => 'getStatusOnApprove',
        'parameters' => 
        array (
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
 * Get the resolved approving status.
 *
 * @return string The `statusOnApprove` the frozen declaration resolves to.
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */',
        'startLine' => 113,
        'endLine' => 115,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
        'aliasName' => NULL,
      ),
      'getObjectUuid' => 
      array (
        'name' => 'getObjectUuid',
        'parameters' => 
        array (
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
 * Get the signing-request object UUID.
 *
 * @return string UUID of the filinq signing request.
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */',
        'startLine' => 124,
        'endLine' => 126,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
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