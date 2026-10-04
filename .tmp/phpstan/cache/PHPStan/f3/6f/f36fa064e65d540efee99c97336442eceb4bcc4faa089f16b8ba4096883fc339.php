<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Intake/IntakeReadingProgress.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Intake\IntakeReadingProgress
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-2c6396a0c7ce7a773fcfe90130c0efd5342a2424e7f60a6a88fe497fcd243d81',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Intake\\IntakeReadingProgress',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Intake/IntakeReadingProgress.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Intake',
    'name' => 'OCA\\Filinq\\Service\\Intake\\IntakeReadingProgress',
    'shortName' => 'IntakeReadingProgress',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * The reading states an intake document moves through, and what the inbox shows.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 49,
    'endLine' => 197,
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
      'QUEUED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeReadingProgress',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeReadingProgress',
        'name' => 'QUEUED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'queued\'',
          'attributes' => 
          array (
            'startLine' => 56,
            'endLine' => 56,
            'startTokenPos' => 35,
            'startFilePos' => 2243,
            'endTokenPos' => 35,
            'endFilePos' => 2250,
          ),
        ),
        'docComment' => '/**
 * Nothing has been attempted yet.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 56,
        'endLine' => 56,
        'startColumn' => 2,
        'endColumn' => 32,
      ),
      'READING' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeReadingProgress',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeReadingProgress',
        'name' => 'READING',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'reading\'',
          'attributes' => 
          array (
            'startLine' => 63,
            'endLine' => 63,
            'startTokenPos' => 48,
            'startFilePos' => 2337,
            'endTokenPos' => 48,
            'endFilePos' => 2345,
          ),
        ),
        'docComment' => '/**
 * A job is reading it now.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 63,
        'endLine' => 63,
        'startColumn' => 2,
        'endColumn' => 34,
      ),
      'READ' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeReadingProgress',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeReadingProgress',
        'name' => 'READ',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'read\'',
          'attributes' => 
          array (
            'startLine' => 70,
            'endLine' => 70,
            'startTokenPos' => 61,
            'startFilePos' => 2467,
            'endTokenPos' => 61,
            'endFilePos' => 2472,
          ),
        ),
        'docComment' => '/**
 * The text was recognised and written to the searchable content.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 70,
        'endLine' => 70,
        'startColumn' => 2,
        'endColumn' => 28,
      ),
      'FAILED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeReadingProgress',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeReadingProgress',
        'name' => 'FAILED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'failed\'',
          'attributes' => 
          array (
            'startLine' => 77,
            'endLine' => 77,
            'startTokenPos' => 74,
            'startFilePos' => 2567,
            'endTokenPos' => 74,
            'endFilePos' => 2574,
          ),
        ),
        'docComment' => '/**
 * The reading failed, and says why.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 77,
        'endLine' => 77,
        'startColumn' => 2,
        'endColumn' => 32,
      ),
      'STATES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeReadingProgress',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeReadingProgress',
        'name' => 'STATES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[self::QUEUED, self::READING, self::READ, self::FAILED]',
          'attributes' => 
          array (
            'startLine' => 84,
            'endLine' => 84,
            'startTokenPos' => 87,
            'startFilePos' => 2706,
            'endTokenPos' => 106,
            'endFilePos' => 2760,
          ),
        ),
        'docComment' => '/**
 * Every state, so an inbox can account for each document exactly once.
 *
 * @var string[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 84,
        'endLine' => 84,
        'startColumn' => 2,
        'endColumn' => 79,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'progressFor' => 
      array (
        'name' => 'progressFor',
        'parameters' => 
        array (
          'state' => 
          array (
            'name' => 'state',
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
            'startLine' => 97,
            'endLine' => 97,
            'startColumn' => 30,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'error' => 
          array (
            'name' => 'error',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 97,
                'endLine' => 97,
                'startTokenPos' => 128,
                'startFilePos' => 3235,
                'endTokenPos' => 128,
                'endFilePos' => 3236,
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
            'startLine' => 97,
            'endLine' => 97,
            'startColumn' => 45,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'moment' => 
          array (
            'name' => 'moment',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 97,
                'endLine' => 97,
                'startTokenPos' => 137,
                'startFilePos' => 3256,
                'endTokenPos' => 137,
                'endFilePos' => 3257,
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
            'startLine' => 97,
            'endLine' => 97,
            'startColumn' => 65,
            'endColumn' => 83,
            'parameterIndex' => 2,
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
 * The progress to record when a reading step ends.
 *
 * @param string $state  The state reached.
 * @param string $error  What went wrong, when it did.
 * @param string $moment When, as an ATOM timestamp.
 *
 * @return array<string, mixed> The progress to store on the intake record.
 *
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 */',
        'startLine' => 97,
        'endLine' => 129,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Intake',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeReadingProgress',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeReadingProgress',
        'currentClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeReadingProgress',
        'aliasName' => NULL,
      ),
      'showsInInbox' => 
      array (
        'name' => 'showsInInbox',
        'parameters' => 
        array (
          'document' => 
          array (
            'name' => 'document',
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
            'startColumn' => 31,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Whether this document should still appear in the inbox.
 *
 * Always. A document being read, and one that could not be read, both stay:
 * the text is what failed, not the document, and it remains assignable by
 * hand. The method exists so the rule is assertable rather than implicit in
 * whatever a query happens to filter on.
 *
 * @param array<string, mixed> $document The intake document.
 *
 * @return bool True, always.
 *
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 */',
        'startLine' => 145,
        'endLine' => 149,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Intake',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeReadingProgress',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeReadingProgress',
        'currentClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeReadingProgress',
        'aliasName' => NULL,
      ),
      'summarise' => 
      array (
        'name' => 'summarise',
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
            'startLine' => 164,
            'endLine' => 164,
            'startColumn' => 28,
            'endColumn' => 43,
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
 * What the inbox says it is doing, counted by state.
 *
 * 🔴 STILL-READING AND COULD-NOT-BE-READ ARE COUNTED APART. Folding them
 * together produces "3 being read" over one queue and two breakages, and
 * the two are the ones somebody has to do something about.
 *
 * @param array<int, array<string, mixed>> $documents The inbox contents.
 *
 * @return array<string, mixed> The counts and what they mean.
 *
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 */',
        'startLine' => 164,
        'endLine' => 196,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Intake',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeReadingProgress',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeReadingProgress',
        'currentClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeReadingProgress',
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