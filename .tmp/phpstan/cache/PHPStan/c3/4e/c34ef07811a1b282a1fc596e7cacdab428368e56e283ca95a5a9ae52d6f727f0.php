<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/SubjectErasure/SubjectErasureJob.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\SubjectErasure\SubjectErasureJob
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-9ec28a25b2e76ac203df9da37546b58fe2bfe38b58a78bfd3cde659c54779d28',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureJob',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/SubjectErasure/SubjectErasureJob.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\SubjectErasure',
    'name' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureJob',
    'shortName' => 'SubjectErasureJob',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Decides the order, the resume point and the state a stopped run leaves.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 58,
    'endLine' => 184,
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
      'RUNNING' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureJob',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureJob',
        'name' => 'RUNNING',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'running\'',
          'attributes' => 
          array (
            'startLine' => 65,
            'endLine' => 65,
            'startTokenPos' => 35,
            'startFilePos' => 2773,
            'endTokenPos' => 35,
            'endFilePos' => 2781,
          ),
        ),
        'docComment' => '/**
 * The run is going.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 65,
        'endLine' => 65,
        'startColumn' => 2,
        'endColumn' => 34,
      ),
      'PARTIALLY_COMPLETED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureJob',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureJob',
        'name' => 'PARTIALLY_COMPLETED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'partially_completed\'',
          'attributes' => 
          array (
            'startLine' => 72,
            'endLine' => 72,
            'startTokenPos' => 48,
            'startFilePos' => 2920,
            'endTokenPos' => 48,
            'endFilePos' => 2940,
          ),
        ),
        'docComment' => '/**
 * The run stopped with work left. Not finished, not never-started.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 72,
        'endLine' => 72,
        'startColumn' => 2,
        'endColumn' => 58,
      ),
      'COMPLETED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureJob',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureJob',
        'name' => 'COMPLETED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'completed\'',
          'attributes' => 
          array (
            'startLine' => 79,
            'endLine' => 79,
            'startTokenPos' => 61,
            'startFilePos' => 3041,
            'endTokenPos' => 61,
            'endFilePos' => 3051,
          ),
        ),
        'docComment' => '/**
 * Every document in scope was reached.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 79,
        'endLine' => 79,
        'startColumn' => 2,
        'endColumn' => 38,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'resumeFrom' => 
      array (
        'name' => 'resumeFrom',
        'parameters' => 
        array (
          'documents' => 
          array (
            'name' => 'documents',
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
            'startLine' => 96,
            'endLine' => 96,
            'startColumn' => 29,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'progress' => 
          array (
            'name' => 'progress',
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
            'startLine' => 96,
            'endLine' => 96,
            'startColumn' => 47,
            'endColumn' => 61,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The documents still to do, given what a previous run recorded.
 *
 * Resumes AT the last recorded document rather than after it, because that
 * one may have been mid-write when the run stopped. Erasing an occurrence
 * that is already gone finds nothing and changes nothing, so re-doing one
 * document is free and skipping one is not.
 *
 * @param array<int, string>   $documents The documents in scope, in order.
 * @param array<string, mixed> $progress  What the last run recorded.
 *
 * @return array<int, string> The documents still to do.
 *
 * @spec openspec/changes/erase-a-person-while-the-records-stay/specs/anonymization-link/spec.md
 */',
        'startLine' => 96,
        'endLine' => 111,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\SubjectErasure',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureJob',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureJob',
        'currentClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureJob',
        'aliasName' => NULL,
      ),
      'statusFor' => 
      array (
        'name' => 'statusFor',
        'parameters' => 
        array (
          'total' => 
          array (
            'name' => 'total',
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
            'startLine' => 123,
            'endLine' => 123,
            'startColumn' => 28,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'done' => 
          array (
            'name' => 'done',
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
            'startLine' => 123,
            'endLine' => 123,
            'startColumn' => 40,
            'endColumn' => 48,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The state a run is left in after processing some of its scope.
 *
 * @param int $total The documents in scope.
 * @param int $done  How many were completed.
 *
 * @return string The status.
 *
 * @spec openspec/changes/erase-a-person-while-the-records-stay/specs/anonymization-link/spec.md
 */',
        'startLine' => 123,
        'endLine' => 132,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\SubjectErasure',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureJob',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureJob',
        'currentClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureJob',
        'aliasName' => NULL,
      ),
      'accountOf' => 
      array (
        'name' => 'accountOf',
        'parameters' => 
        array (
          'progress' => 
          array (
            'name' => 'progress',
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
            'startLine' => 145,
            'endLine' => 145,
            'startColumn' => 28,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'dueAt' => 
          array (
            'name' => 'dueAt',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 145,
                'endLine' => 145,
                'startTokenPos' => 272,
                'startFilePos' => 5420,
                'endTokenPos' => 272,
                'endFilePos' => 5421,
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
            'startLine' => 145,
            'endLine' => 145,
            'startColumn' => 45,
            'endColumn' => 62,
            'parameterIndex' => 1,
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
 * What a stopped run leaves behind, in words, for the request and the
 * operator reading it.
 *
 * @param array<string, mixed> $progress What was recorded.
 * @param string               $dueAt    When the request is due.
 *
 * @return array<string, mixed> The account of the stopped run.
 *
 * @spec openspec/changes/erase-a-person-while-the-records-stay/specs/anonymization-link/spec.md
 */',
        'startLine' => 145,
        'endLine' => 183,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\SubjectErasure',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureJob',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureJob',
        'currentClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureJob',
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