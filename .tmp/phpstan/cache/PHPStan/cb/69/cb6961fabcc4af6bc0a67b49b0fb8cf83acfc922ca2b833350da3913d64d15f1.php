<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Redaction/RedactionVerdictRecorder.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Redaction\RedactionVerdictRecorder
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-b699e34c14d358122df654e7ba55af68617f04e81fe4fa1fe2aa4377e4bd86ed',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Redaction\\RedactionVerdictRecorder',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Redaction/RedactionVerdictRecorder.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Redaction',
    'name' => 'OCA\\Filinq\\Service\\Redaction\\RedactionVerdictRecorder',
    'shortName' => 'RedactionVerdictRecorder',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Verifies the written copy and puts the verdict in the run result.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 47,
    'endLine' => 169,
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
      'verifier' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionVerdictRecorder',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionVerdictRecorder',
        'name' => 'verifier',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 58,
        'endLine' => 58,
        'startColumn' => 3,
        'endColumn' => 61,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionVerdictRecorder',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionVerdictRecorder',
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
        'startLine' => 59,
        'endLine' => 59,
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
          'verifier' => 
          array (
            'name' => 'verifier',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 58,
            'endLine' => 58,
            'startColumn' => 3,
            'endColumn' => 61,
            'parameterIndex' => 0,
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
            'startLine' => 59,
            'endLine' => 59,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 1,
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
 * @param RedactionIrreversibilityVerifier $verifier The recovery routes.
 * @param LoggerInterface                  $logger   Logger for diagnostics.
 *
 * @return void
 */',
        'startLine' => 57,
        'endLine' => 62,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionVerdictRecorder',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionVerdictRecorder',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionVerdictRecorder',
        'aliasName' => NULL,
      ),
      'record' => 
      array (
        'name' => 'record',
        'parameters' => 
        array (
          'resultInfo' => 
          array (
            'name' => 'resultInfo',
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
            'startLine' => 77,
            'endLine' => 77,
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'anonymisedNode' => 
          array (
            'name' => 'anonymisedNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 78,
            'endLine' => 78,
            'startColumn' => 3,
            'endColumn' => 23,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'redactedValues' => 
          array (
            'name' => 'redactedValues',
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
            'startLine' => 79,
            'endLine' => 79,
            'startColumn' => 3,
            'endColumn' => 23,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'outputMode' => 
          array (
            'name' => 'outputMode',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 80,
                'endLine' => 80,
                'startTokenPos' => 99,
                'startFilePos' => 2964,
                'endTokenPos' => 99,
                'endFilePos' => 2965,
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
            'startLine' => 80,
            'endLine' => 80,
            'startColumn' => 3,
            'endColumn' => 25,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Verify the produced copy and record the verdict on the run result.
 *
 * @param array<string, mixed> $resultInfo     The run result so far.
 * @param mixed                $anonymisedNode The file as it was written.
 * @param array<int, mixed>    $redactedValues The values that must not be recoverable.
 * @param string               $outputMode     The mode that produced it.
 *
 * @return array<string, mixed> The result, carrying `redactionVerification`.
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */',
        'startLine' => 76,
        'endLine' => 104,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionVerdictRecorder',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionVerdictRecorder',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionVerdictRecorder',
        'aliasName' => NULL,
      ),
      'bytesOf' => 
      array (
        'name' => 'bytesOf',
        'parameters' => 
        array (
          'node' => 
          array (
            'name' => 'node',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 115,
            'endLine' => 115,
            'startColumn' => 27,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
 * The bytes of the written copy, or null when they cannot be read.
 *
 * @param mixed $node The file node.
 *
 * @return string|null The bytes.
 *
 * @spec exclude Read helper behind record().
 */',
        'startLine' => 115,
        'endLine' => 137,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionVerdictRecorder',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionVerdictRecorder',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionVerdictRecorder',
        'aliasName' => NULL,
      ),
      'valuesOf' => 
      array (
        'name' => 'valuesOf',
        'parameters' => 
        array (
          'entities' => 
          array (
            'name' => 'entities',
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
            'startLine' => 148,
            'endLine' => 148,
            'startColumn' => 28,
            'endColumn' => 42,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The values a set of entities says must not be recoverable.
 *
 * @param array<int, mixed> $entities The mapped entities.
 *
 * @return array<int, string> The values.
 *
 * @spec exclude Reduction helper behind record().
 */',
        'startLine' => 148,
        'endLine' => 168,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionVerdictRecorder',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionVerdictRecorder',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionVerdictRecorder',
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