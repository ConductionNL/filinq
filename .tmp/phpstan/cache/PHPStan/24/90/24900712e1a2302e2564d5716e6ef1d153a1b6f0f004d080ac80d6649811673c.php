<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Exception/PolicyRejectedException.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Exception\PolicyRejectedException
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-08bb494b400ccb2e0bb58d927e471d931e9a9d1531eca05210eb67814524c4bc',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Exception\\PolicyRejectedException',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Exception/PolicyRejectedException.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Exception',
    'name' => 'OCA\\Filinq\\Exception\\PolicyRejectedException',
    'shortName' => 'PolicyRejectedException',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Thrown when PolicyMatchService returns a prohibition match during consent creation.
 *
 * @category Exception
 * @package  OCA\\Filinq\\Exception
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-1
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 39,
    'endLine' => 81,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'RuntimeException',
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
      'ruleUuid' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Exception\\PolicyRejectedException',
        'implementingClassName' => 'OCA\\Filinq\\Exception\\PolicyRejectedException',
        'name' => 'ruleUuid',
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
        'startLine' => 50,
        'endLine' => 50,
        'startColumn' => 3,
        'endColumn' => 35,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'ruleName' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Exception\\PolicyRejectedException',
        'implementingClassName' => 'OCA\\Filinq\\Exception\\PolicyRejectedException',
        'name' => 'ruleName',
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
        'startLine' => 51,
        'endLine' => 51,
        'startColumn' => 3,
        'endColumn' => 35,
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
          'ruleUuid' => 
          array (
            'name' => 'ruleUuid',
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
            'startLine' => 50,
            'endLine' => 50,
            'startColumn' => 3,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'ruleName' => 
          array (
            'name' => 'ruleName',
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
            'startLine' => 51,
            'endLine' => 51,
            'startColumn' => 3,
            'endColumn' => 35,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'Publication prohibited by policy rule\'',
              'attributes' => 
              array (
                'startLine' => 52,
                'endLine' => 52,
                'startTokenPos' => 67,
                'startFilePos' => 1632,
                'endTokenPos' => 67,
                'endFilePos' => 1670,
              ),
            ),
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
            'startLine' => 52,
            'endLine' => 52,
            'startColumn' => 3,
            'endColumn' => 59,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'code' => 
          array (
            'name' => 'code',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 53,
                'endLine' => 53,
                'startTokenPos' => 76,
                'startFilePos' => 1687,
                'endTokenPos' => 76,
                'endFilePos' => 1687,
              ),
            ),
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
            'startLine' => 53,
            'endLine' => 53,
            'startColumn' => 3,
            'endColumn' => 15,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'previous' => 
          array (
            'name' => 'previous',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 54,
                'endLine' => 54,
                'startTokenPos' => 86,
                'startFilePos' => 1716,
                'endTokenPos' => 86,
                'endFilePos' => 1719,
              ),
            ),
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
                      'name' => 'Throwable',
                      'isIdentifier' => false,
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
            'startLine' => 54,
            'endLine' => 54,
            'startColumn' => 3,
            'endColumn' => 30,
            'parameterIndex' => 4,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Construct a PolicyRejectedException.
 *
 * @param string $ruleUuid UUID of the matching prohibition rule.
 * @param string $ruleName Human-readable name of the rule.
 * @param string $message Optional detail message.
 * @param int $code Optional error code.
 * @param \\Throwable $previous Optional previous exception.
 */',
        'startLine' => 49,
        'endLine' => 58,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Exception',
        'declaringClassName' => 'OCA\\Filinq\\Exception\\PolicyRejectedException',
        'implementingClassName' => 'OCA\\Filinq\\Exception\\PolicyRejectedException',
        'currentClassName' => 'OCA\\Filinq\\Exception\\PolicyRejectedException',
        'aliasName' => NULL,
      ),
      'getRuleUuid' => 
      array (
        'name' => 'getRuleUuid',
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
 * Get the UUID of the matching prohibition rule.
 *
 * @return string
 *
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-1
 */',
        'startLine' => 67,
        'endLine' => 69,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Exception',
        'declaringClassName' => 'OCA\\Filinq\\Exception\\PolicyRejectedException',
        'implementingClassName' => 'OCA\\Filinq\\Exception\\PolicyRejectedException',
        'currentClassName' => 'OCA\\Filinq\\Exception\\PolicyRejectedException',
        'aliasName' => NULL,
      ),
      'getRuleName' => 
      array (
        'name' => 'getRuleName',
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
 * Get the human-readable name of the matching prohibition rule.
 *
 * @return string
 *
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-1
 */',
        'startLine' => 78,
        'endLine' => 80,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Exception',
        'declaringClassName' => 'OCA\\Filinq\\Exception\\PolicyRejectedException',
        'implementingClassName' => 'OCA\\Filinq\\Exception\\PolicyRejectedException',
        'currentClassName' => 'OCA\\Filinq\\Exception\\PolicyRejectedException',
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