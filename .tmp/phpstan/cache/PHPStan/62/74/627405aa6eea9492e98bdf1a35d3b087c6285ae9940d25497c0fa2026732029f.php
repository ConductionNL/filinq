<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Redaction/RedactionIrreversibilityVerifier.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Redaction\RedactionIrreversibilityVerifier
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-6510c36499a6f78a532424619a840bc50963be57e33eed7595a47e9313379bb7',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Redaction/RedactionIrreversibilityVerifier.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Redaction',
    'name' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
    'shortName' => 'RedactionIrreversibilityVerifier',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Checks produced bytes for every way a redacted value survives.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 49,
    'endLine' => 295,
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
      'CLEAN' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
        'name' => 'CLEAN',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'clean\'',
          'attributes' => 
          array (
            'startLine' => 56,
            'endLine' => 56,
            'startTokenPos' => 35,
            'startFilePos' => 2189,
            'endTokenPos' => 35,
            'endFilePos' => 2195,
          ),
        ),
        'docComment' => '/**
 * The document was examined for every route and nothing was found.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 56,
        'endLine' => 56,
        'startColumn' => 2,
        'endColumn' => 30,
      ),
      'LEAKING' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
        'name' => 'LEAKING',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'leaking\'',
          'attributes' => 
          array (
            'startLine' => 63,
            'endLine' => 63,
            'startTokenPos' => 48,
            'startFilePos' => 2307,
            'endTokenPos' => 48,
            'endFilePos' => 2315,
          ),
        ),
        'docComment' => '/**
 * At least one redacted value is still recoverable.
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
      'UNVERIFIABLE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
        'name' => 'UNVERIFIABLE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'unverifiable\'',
          'attributes' => 
          array (
            'startLine' => 70,
            'endLine' => 70,
            'startTokenPos' => 61,
            'startFilePos' => 2436,
            'endTokenPos' => 61,
            'endFilePos' => 2449,
          ),
        ),
        'docComment' => '/**
 * The bytes could not be examined, so nothing is known.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 70,
        'endLine' => 70,
        'startColumn' => 2,
        'endColumn' => 44,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'verify' => 
      array (
        'name' => 'verify',
        'parameters' => 
        array (
          'bytes' => 
          array (
            'name' => 'bytes',
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
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 25,
            'endColumn' => 37,
            'parameterIndex' => 0,
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
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 40,
            'endColumn' => 60,
            'parameterIndex' => 1,
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
                'startLine' => 83,
                'endLine' => 83,
                'startTokenPos' => 88,
                'startFilePos' => 3038,
                'endTokenPos' => 88,
                'endFilePos' => 3039,
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
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 63,
            'endColumn' => 85,
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
 * Verify one written copy against every leak route.
 *
 * @param string   $bytes           The produced file, exactly as written.
 * @param string[] $redactedValues  The values that must not be recoverable.
 * @param string   $outputMode      The mode that produced it, for the record.
 *
 * @return array<string, mixed> The verdict, the routes checked, and every finding.
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */',
        'startLine' => 83,
        'endLine' => 131,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
        'aliasName' => NULL,
      ),
      'mayBePublished' => 
      array (
        'name' => 'mayBePublished',
        'parameters' => 
        array (
          'result' => 
          array (
            'name' => 'result',
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
            'startLine' => 147,
            'endLine' => 147,
            'startColumn' => 33,
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
 * Whether a verification result permits publication.
 *
 * A separate method because callers otherwise test the verdict string, and
 * the two states that must NOT publish are `leaking` and `unverifiable`. A
 * caller comparing against `leaking` alone publishes every document the
 * verifier could not read.
 *
 * @param array<string, mixed> $result The verification result.
 *
 * @return bool True only when the document was examined and found clean.
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */',
        'startLine' => 147,
        'endLine' => 149,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
        'aliasName' => NULL,
      ),
      'examine' => 
      array (
        'name' => 'examine',
        'parameters' => 
        array (
          'route' => 
          array (
            'name' => 'route',
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
            'startLine' => 160,
            'endLine' => 160,
            'startColumn' => 27,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'bytes' => 
          array (
            'name' => 'bytes',
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
            'startLine' => 160,
            'endLine' => 160,
            'startColumn' => 42,
            'endColumn' => 54,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'needles' => 
          array (
            'name' => 'needles',
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
            'startLine' => 160,
            'endLine' => 160,
            'startColumn' => 57,
            'endColumn' => 70,
            'parameterIndex' => 2,
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
 * Look for the needles on one route.
 *
 * @param string   $route   The leak route.
 * @param string   $bytes   The produced file.
 * @param string[] $needles The values, lower-cased.
 *
 * @return string[] The values found on this route.
 */',
        'startLine' => 160,
        'endLine' => 174,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
        'aliasName' => NULL,
      ),
      'regionFor' => 
      array (
        'name' => 'regionFor',
        'parameters' => 
        array (
          'route' => 
          array (
            'name' => 'route',
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
            'startLine' => 188,
            'endLine' => 188,
            'startColumn' => 29,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'bytes' => 
          array (
            'name' => 'bytes',
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
            'startLine' => 188,
            'endLine' => 188,
            'startColumn' => 44,
            'endColumn' => 56,
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
 * The part of the file one route lives in.
 *
 * Deliberately generous: a route whose region cannot be isolated falls back
 * to the whole file rather than to nothing. A false positive costs somebody
 * a second look; a false negative publishes a name.
 *
 * @param string $route The leak route.
 * @param string $bytes The produced file.
 *
 * @return string The region to search.
 */',
        'startLine' => 188,
        'endLine' => 219,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
        'aliasName' => NULL,
      ),
      'between' => 
      array (
        'name' => 'between',
        'parameters' => 
        array (
          'haystack' => 
          array (
            'name' => 'haystack',
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
            'startLine' => 230,
            'endLine' => 230,
            'startColumn' => 27,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'start' => 
          array (
            'name' => 'start',
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
            'startLine' => 230,
            'endLine' => 230,
            'startColumn' => 45,
            'endColumn' => 57,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'end' => 
          array (
            'name' => 'end',
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
            'startLine' => 230,
            'endLine' => 230,
            'startColumn' => 60,
            'endColumn' => 70,
            'parameterIndex' => 2,
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
 * Everything between two markers, every time they occur.
 *
 * @param string $haystack The lower-cased file.
 * @param string $start    The opening marker.
 * @param string $end      The closing marker.
 *
 * @return string The concatenated regions, or an empty string.
 */',
        'startLine' => 230,
        'endLine' => 251,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
        'aliasName' => NULL,
      ),
      'needles' => 
      array (
        'name' => 'needles',
        'parameters' => 
        array (
          'values' => 
          array (
            'name' => 'values',
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
            'startLine' => 263,
            'endLine' => 263,
            'startColumn' => 27,
            'endColumn' => 39,
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
 * The values to look for, normalised and without the trivially short.
 *
 * A one or two character value matches almost any file, and a verification
 * that always fails is switched off within a week.
 *
 * @param string[] $values The redacted values.
 *
 * @return string[] The needles.
 */',
        'startLine' => 263,
        'endLine' => 275,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
        'aliasName' => NULL,
      ),
      'unverifiable' => 
      array (
        'name' => 'unverifiable',
        'parameters' => 
        array (
          'outputMode' => 
          array (
            'name' => 'outputMode',
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
            'startLine' => 285,
            'endLine' => 285,
            'startColumn' => 32,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'why' => 
          array (
            'name' => 'why',
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
            'startLine' => 285,
            'endLine' => 285,
            'startColumn' => 52,
            'endColumn' => 62,
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
 * A result that says nothing is known, and why.
 *
 * @param string $outputMode The mode.
 * @param string $why        The reason, in words.
 *
 * @return array<string, mixed> The result.
 */',
        'startLine' => 285,
        'endLine' => 294,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionIrreversibilityVerifier',
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