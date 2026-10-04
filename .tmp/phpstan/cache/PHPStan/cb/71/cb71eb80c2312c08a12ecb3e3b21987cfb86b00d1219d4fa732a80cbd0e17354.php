<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Intake/ScanFolderIntakeFeeder.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Intake\ScanFolderIntakeFeeder
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-c7058f96e58fbc6e1d621011689e273ab24aade3083c2e95fe73258d9cbd6948',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Intake\\ScanFolderIntakeFeeder',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Intake/ScanFolderIntakeFeeder.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Intake',
    'name' => 'OCA\\Filinq\\Service\\Intake\\ScanFolderIntakeFeeder',
    'shortName' => 'ScanFolderIntakeFeeder',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * What became of one watched file, and what must happen to it next.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 48,
    'endLine' => 226,
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
      'CHANNEL' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Intake\\ScanFolderIntakeFeeder',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Intake\\ScanFolderIntakeFeeder',
        'name' => 'CHANNEL',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'scan\'',
          'attributes' => 
          array (
            'startLine' => 55,
            'endLine' => 55,
            'startTokenPos' => 37,
            'startFilePos' => 2201,
            'endTokenPos' => 37,
            'endFilePos' => 2206,
          ),
        ),
        'docComment' => '/**
 * The channel a watched folder delivers on.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 55,
        'endLine' => 55,
        'startColumn' => 2,
        'endColumn' => 31,
      ),
      'RECEIVED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Intake\\ScanFolderIntakeFeeder',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Intake\\ScanFolderIntakeFeeder',
        'name' => 'RECEIVED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'received\'',
          'attributes' => 
          array (
            'startLine' => 62,
            'endLine' => 62,
            'startTokenPos' => 50,
            'startFilePos' => 2315,
            'endTokenPos' => 50,
            'endFilePos' => 2324,
          ),
        ),
        'docComment' => '/**
 * An intake document was created for this file.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 2,
        'endColumn' => 36,
      ),
      'ALREADY_SEEN' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Intake\\ScanFolderIntakeFeeder',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Intake\\ScanFolderIntakeFeeder',
        'name' => 'ALREADY_SEEN',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'already_seen\'',
          'attributes' => 
          array (
            'startLine' => 69,
            'endLine' => 69,
            'startTokenPos' => 63,
            'startFilePos' => 2435,
            'endTokenPos' => 63,
            'endFilePos' => 2448,
          ),
        ),
        'docComment' => '/**
 * The channel had delivered this file before.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 69,
        'endLine' => 69,
        'startColumn' => 2,
        'endColumn' => 44,
      ),
      'REFUSED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Intake\\ScanFolderIntakeFeeder',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Intake\\ScanFolderIntakeFeeder',
        'name' => 'REFUSED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'refused\'',
          'attributes' => 
          array (
            'startLine' => 76,
            'endLine' => 76,
            'startTokenPos' => 76,
            'startFilePos' => 2555,
            'endTokenPos' => 76,
            'endFilePos' => 2563,
          ),
        ),
        'docComment' => '/**
 * The intake was refused. Nothing was created.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 76,
        'endLine' => 76,
        'startColumn' => 2,
        'endColumn' => 34,
      ),
      'LEAVE_FOR_A_PERSON' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Intake\\ScanFolderIntakeFeeder',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Intake\\ScanFolderIntakeFeeder',
        'name' => 'LEAVE_FOR_A_PERSON',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'leave_in_place_for_a_person\'',
          'attributes' => 
          array (
            'startLine' => 83,
            'endLine' => 83,
            'startTokenPos' => 89,
            'startFilePos' => 2684,
            'endTokenPos' => 89,
            'endFilePos' => 2712,
          ),
        ),
        'docComment' => '/**
 * Where a file must be left so a person finds it.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 83,
        'endLine' => 83,
        'startColumn' => 2,
        'endColumn' => 65,
      ),
      'MOVE_TO_PROCESSED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Intake\\ScanFolderIntakeFeeder',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Intake\\ScanFolderIntakeFeeder',
        'name' => 'MOVE_TO_PROCESSED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'move_to_processed\'',
          'attributes' => 
          array (
            'startLine' => 90,
            'endLine' => 90,
            'startTokenPos' => 102,
            'startFilePos' => 2839,
            'endTokenPos' => 102,
            'endFilePos' => 2857,
          ),
        ),
        'docComment' => '/**
 * Where a file may be moved once it is safely an intake.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 90,
        'endLine' => 90,
        'startColumn' => 2,
        'endColumn' => 54,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'outcomeFor' => 
      array (
        'name' => 'outcomeFor',
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
            'startLine' => 107,
            'endLine' => 107,
            'startColumn' => 29,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'fileName' => 
          array (
            'name' => 'fileName',
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
            'startLine' => 107,
            'endLine' => 107,
            'startColumn' => 46,
            'endColumn' => 61,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'reason' => 
          array (
            'name' => 'reason',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 107,
                'endLine' => 107,
                'startTokenPos' => 129,
                'startFilePos' => 3568,
                'endTokenPos' => 129,
                'endFilePos' => 3569,
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
            'startLine' => 107,
            'endLine' => 107,
            'startColumn' => 64,
            'endColumn' => 82,
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
 * The outcome for one watched file.
 *
 * The caller cannot read this as a boolean, on purpose. `handled` alone
 * would collapse the three states that matter into one, and the collapse is
 * how a refused page ends up in the processed folder.
 *
 * @param string $outcome    received / already_seen / refused.
 * @param string $fileName   The file, so the refusal names it.
 * @param string $reason     Why, in words, when it was refused.
 *
 * @return array<string, mixed> What happened and what must happen next.
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */',
        'startLine' => 107,
        'endLine' => 149,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Intake',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Intake\\ScanFolderIntakeFeeder',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Intake\\ScanFolderIntakeFeeder',
        'currentClassName' => 'OCA\\Filinq\\Service\\Intake\\ScanFolderIntakeFeeder',
        'aliasName' => NULL,
      ),
      'mayMoveOn' => 
      array (
        'name' => 'mayMoveOn',
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Whether this outcome permits the watch to move the file on.
 *
 * A separate method because a caller otherwise tests the outcome string,
 * and the one state that must NOT move is the one an `!== RECEIVED` test
 * gets wrong in the safe direction and an `=== REFUSED` test gets wrong in
 * the dangerous one.
 *
 * @param array<string, mixed> $outcome The outcome.
 *
 * @return bool True when the file may leave the watched folder.
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */',
        'startLine' => 165,
        'endLine' => 167,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Intake',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Intake\\ScanFolderIntakeFeeder',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Intake\\ScanFolderIntakeFeeder',
        'currentClassName' => 'OCA\\Filinq\\Service\\Intake\\ScanFolderIntakeFeeder',
        'aliasName' => NULL,
      ),
      'summarise' => 
      array (
        'name' => 'summarise',
        'parameters' => 
        array (
          'outcomes' => 
          array (
            'name' => 'outcomes',
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
            'startLine' => 182,
            'endLine' => 182,
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
 * A summary of one sweep, with the refusals countable.
 *
 * The three counts are separate. "40 files processed" over 37 receipts, one
 * duplicate and two refusals is a sentence that hides the only two a person
 * has to act on.
 *
 * @param array<int, array<string, mixed>> $outcomes Every file\'s outcome.
 *
 * @return array<string, mixed> The summary.
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */',
        'startLine' => 182,
        'endLine' => 209,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Intake',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Intake\\ScanFolderIntakeFeeder',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Intake\\ScanFolderIntakeFeeder',
        'currentClassName' => 'OCA\\Filinq\\Service\\Intake\\ScanFolderIntakeFeeder',
        'aliasName' => NULL,
      ),
      'sentence' => 
      array (
        'name' => 'sentence',
        'parameters' => 
        array (
          'reason' => 
          array (
            'name' => 'reason',
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
            'startLine' => 218,
            'endLine' => 218,
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
 * A refusal reason as a sentence, never empty.
 *
 * @param string $reason The reason.
 *
 * @return string The sentence.
 */',
        'startLine' => 218,
        'endLine' => 225,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Intake',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Intake\\ScanFolderIntakeFeeder',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Intake\\ScanFolderIntakeFeeder',
        'currentClassName' => 'OCA\\Filinq\\Service\\Intake\\ScanFolderIntakeFeeder',
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