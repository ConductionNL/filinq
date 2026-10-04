<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Suggestion/HistoryRanker.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Suggestion\HistoryRanker
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-bdd98cbca386944f862288e328d32cc583c6f1c2e4e500845bf4da9e4f8945f5',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Suggestion\\HistoryRanker',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Suggestion/HistoryRanker.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Suggestion',
    'name' => 'OCA\\Filinq\\Service\\Suggestion\\HistoryRanker',
    'shortName' => 'HistoryRanker',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Ranks GL account codes by windowed booking frequency.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Suggestion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 40,
    'endLine' => 177,
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
      'HISTORY_WINDOW' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Suggestion\\HistoryRanker',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Suggestion\\HistoryRanker',
        'name' => 'HISTORY_WINDOW',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '10',
          'attributes' => 
          array (
            'startLine' => 47,
            'endLine' => 47,
            'startTokenPos' => 35,
            'startFilePos' => 1380,
            'endTokenPos' => 35,
            'endFilePos' => 1381,
          ),
        ),
        'docComment' => '/**
 * Number of most-recent bookings considered when ranking.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 47,
        'endLine' => 47,
        'startColumn' => 2,
        'endColumn' => 35,
      ),
      'MAX_SUGGESTIONS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Suggestion\\HistoryRanker',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Suggestion\\HistoryRanker',
        'name' => 'MAX_SUGGESTIONS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '3',
          'attributes' => 
          array (
            'startLine' => 54,
            'endLine' => 54,
            'startTokenPos' => 48,
            'startFilePos' => 1495,
            'endTokenPos' => 48,
            'endFilePos' => 1495,
          ),
        ),
        'docComment' => '/**
 * Maximum number of ranked candidates returned.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 54,
        'endLine' => 54,
        'startColumn' => 2,
        'endColumn' => 35,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'rank' => 
      array (
        'name' => 'rank',
        'parameters' => 
        array (
          'bookings' => 
          array (
            'name' => 'bookings',
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
            'startLine' => 73,
            'endLine' => 73,
            'startColumn' => 23,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'candidateCodes' => 
          array (
            'name' => 'candidateCodes',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 73,
                'endLine' => 73,
                'startTokenPos' => 70,
                'startFilePos' => 2615,
                'endTokenPos' => 71,
                'endFilePos' => 2616,
              ),
            ),
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
            'startLine' => 73,
            'endLine' => 73,
            'startColumn' => 40,
            'endColumn' => 65,
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
 * Rank candidate GL accounts by frequency over the most recent bookings.
 *
 * @param array<int, array<string, mixed>> $bookings Booking history for a single resolved
 *                                                   supplier identity (each `{accountCode,
 *                                                   accountLabel?, bookedAt}`), in any order.
 * @param array<int, string> $candidateCodes Optional allow-list of account codes; when
 *                                           non-empty, only these codes may appear in
 *                                           the result (REQ-GLS-02 candidate-constrained
 *                                           scenario).
 *
 * @return array<int, array<string, mixed>> Ranked candidates (each `{code, label, confidence,
 *                                          rationale}`), highest confidence first, capped to {@see MAX_SUGGESTIONS}. Empty when there
 *                                          is no history.
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 */',
        'startLine' => 73,
        'endLine' => 91,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Suggestion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Suggestion\\HistoryRanker',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Suggestion\\HistoryRanker',
        'currentClassName' => 'OCA\\Filinq\\Service\\Suggestion\\HistoryRanker',
        'aliasName' => NULL,
      ),
      'mostRecentWindow' => 
      array (
        'name' => 'mostRecentWindow',
        'parameters' => 
        array (
          'bookings' => 
          array (
            'name' => 'bookings',
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
            'startLine' => 101,
            'endLine' => 101,
            'startColumn' => 36,
            'endColumn' => 50,
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
 * Sort bookings by `bookedAt` descending and take the most recent window.
 *
 * @param array<int, array<string, mixed>> $bookings Booking history (each `{accountCode,
 *                                                   accountLabel?, bookedAt}`).
 *
 * @return array<int, array<string, mixed>> The most recent window.
 */',
        'startLine' => 101,
        'endLine' => 108,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Suggestion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Suggestion\\HistoryRanker',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Suggestion\\HistoryRanker',
        'currentClassName' => 'OCA\\Filinq\\Service\\Suggestion\\HistoryRanker',
        'aliasName' => NULL,
      ),
      'tallyByCode' => 
      array (
        'name' => 'tallyByCode',
        'parameters' => 
        array (
          'window' => 
          array (
            'name' => 'window',
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
            'startLine' => 119,
            'endLine' => 119,
            'startColumn' => 31,
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
 * Tally occurrences per account code within the window, and remember the
 * first-seen label for each code.
 *
 * @param array<int, array<string, mixed>> $window The recency window (each `{accountCode,
 *                                                 accountLabel?, bookedAt}`).
 *
 * @return array{0: array<string, int>, 1: array<string, string|null>} `[counts, labels]` keyed by account code.
 */',
        'startLine' => 119,
        'endLine' => 136,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Suggestion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Suggestion\\HistoryRanker',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Suggestion\\HistoryRanker',
        'currentClassName' => 'OCA\\Filinq\\Service\\Suggestion\\HistoryRanker',
        'aliasName' => NULL,
      ),
      'buildResults' => 
      array (
        'name' => 'buildResults',
        'parameters' => 
        array (
          'counts' => 
          array (
            'name' => 'counts',
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
            'startLine' => 149,
            'endLine' => 149,
            'startColumn' => 32,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'labels' => 
          array (
            'name' => 'labels',
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
            'startLine' => 149,
            'endLine' => 149,
            'startColumn' => 47,
            'endColumn' => 59,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'windowSize' => 
          array (
            'name' => 'windowSize',
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
            'startLine' => 149,
            'endLine' => 149,
            'startColumn' => 62,
            'endColumn' => 76,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'candidateCodes' => 
          array (
            'name' => 'candidateCodes',
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
            'startLine' => 149,
            'endLine' => 149,
            'startColumn' => 79,
            'endColumn' => 99,
            'parameterIndex' => 3,
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
 * Build the ranked-candidate rows for each tallied code, honouring the
 * optional candidate allow-list.
 *
 * @param array<string, int> $counts Occurrence count per code (within the window).
 * @param array<string, string|null> $labels First-seen label per code.
 * @param int $windowSize Total bookings considered (the rationale denominator).
 * @param array<int, string> $candidateCodes Optional allow-list of codes.
 *
 * @return array<int, array{code: string, label: string|null, confidence: float, rationale: string}>
 */',
        'startLine' => 149,
        'endLine' => 176,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Suggestion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Suggestion\\HistoryRanker',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Suggestion\\HistoryRanker',
        'currentClassName' => 'OCA\\Filinq\\Service\\Suggestion\\HistoryRanker',
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