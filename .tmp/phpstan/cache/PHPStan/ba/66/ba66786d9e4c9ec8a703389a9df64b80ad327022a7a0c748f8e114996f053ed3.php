<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/CustomDictionaryMatchService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\CustomDictionaryMatchService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-d0e7b1f28688d45446991f3602a1c181ff91a45f77eb07bb62e6f4f329238438',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\CustomDictionaryMatchService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/CustomDictionaryMatchService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\CustomDictionaryMatchService',
    'shortName' => 'CustomDictionaryMatchService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Deterministic term-matching engine for custom dictionaries.
 *
 * Match modes:
 *   - `exact`: byte-for-byte, case-sensitive.
 *   - `caseInsensitive` (default): case-folded on both sides.
 *   - `wordBoundary`: case-insensitive AND delimited by a non-word boundary
 *     (Unicode `\\b` semantics) so a term does not match inside a longer word
 *     (e.g. "Berg" does not match inside "Bergen").
 *
 * Implementation note: all three modes are implemented via `preg_match_all`
 * with the `u` (Unicode) modifier — the same technique OpenRegister\'s own
 * `ChunkTextMatcher::buildPattern()` uses for its whole-word manual-entity
 * matching — so position offsets are produced in the same unit OpenRegister\'s
 * redaction pipeline already consumes elsewhere in this codebase.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 57,
    'endLine' => 317,
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
      'MODE_EXACT' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryMatchService',
        'name' => 'MODE_EXACT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'exact\'',
          'attributes' => 
          array (
            'startLine' => 63,
            'endLine' => 63,
            'startTokenPos' => 40,
            'startFilePos' => 2303,
            'endTokenPos' => 40,
            'endFilePos' => 2309,
          ),
        ),
        'docComment' => '/**
 * Byte-for-byte, case-sensitive match mode.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 63,
        'endLine' => 63,
        'startColumn' => 2,
        'endColumn' => 35,
      ),
      'MODE_CASE_INSENSITIVE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryMatchService',
        'name' => 'MODE_CASE_INSENSITIVE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'caseInsensitive\'',
          'attributes' => 
          array (
            'startLine' => 70,
            'endLine' => 70,
            'startTokenPos' => 53,
            'startFilePos' => 2424,
            'endTokenPos' => 53,
            'endFilePos' => 2440,
          ),
        ),
        'docComment' => '/**
 * Case-insensitive match mode (default).
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 70,
        'endLine' => 70,
        'startColumn' => 2,
        'endColumn' => 56,
      ),
      'MODE_WORD_BOUNDARY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryMatchService',
        'name' => 'MODE_WORD_BOUNDARY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'wordBoundary\'',
          'attributes' => 
          array (
            'startLine' => 77,
            'endLine' => 77,
            'startTokenPos' => 66,
            'startFilePos' => 2567,
            'endTokenPos' => 66,
            'endFilePos' => 2580,
          ),
        ),
        'docComment' => '/**
 * Case-insensitive, word-boundary-delimited match mode.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 77,
        'endLine' => 77,
        'startColumn' => 2,
        'endColumn' => 50,
      ),
      'VALID_MODES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryMatchService',
        'name' => 'VALID_MODES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[self::MODE_EXACT, self::MODE_CASE_INSENSITIVE, self::MODE_WORD_BOUNDARY]',
          'attributes' => 
          array (
            'startLine' => 86,
            'endLine' => 90,
            'startTokenPos' => 79,
            'startFilePos' => 2841,
            'endTokenPos' => 96,
            'endFilePos' => 2923,
          ),
        ),
        'docComment' => '/**
 * The set of match modes this service accepts. An unrecognised mode
 * falls back to {@see MODE_CASE_INSENSITIVE} (mirrors the schema
 * default declared in `filinq_register.json`).
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 86,
        'endLine' => 90,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'match' => 
      array (
        'name' => 'match',
        'parameters' => 
        array (
          'text' => 
          array (
            'name' => 'text',
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
            'startLine' => 122,
            'endLine' => 122,
            'startColumn' => 24,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'terms' => 
          array (
            'name' => 'terms',
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
            'startLine' => 122,
            'endLine' => 122,
            'startColumn' => 38,
            'endColumn' => 49,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'mode' => 
          array (
            'name' => 'mode',
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
            'startLine' => 122,
            'endLine' => 122,
            'startColumn' => 52,
            'endColumn' => 63,
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
 * Find every occurrence of every (non-blank) term in `$text`.
 *
 * `$terms` rows carry `value` (the needle) and an optional `label`
 * (falls back to the term\'s own value when absent — the dictionary-level
 * default-label fallback is the caller\'s responsibility per design.md
 * §D2, so this method never has to resolve a dictionary label itself).
 *
 * Overlap handling: terms are processed longest-value-first so a shorter
 * term cannot pre-empt a longer one at an overlapping position (mirrors
 * OpenRegister\'s redaction longest-needle rule). Once a position range is
 * claimed by a match it is never claimed again by a shorter term\'s match.
 * The `fuzzy` flag is accepted-and-ignored — no approximate matching in
 * this version (design.md Open Questions).
 *
 * Occurrences are returned in document order. Each `value` is the literal
 * substring found at that position, which may differ in case from the
 * declared term under `caseInsensitive`/`wordBoundary`.
 *
 * @param string $text The document text to search.
 * @param array<int, array{value: string, label?: string}> $terms Candidate terms.
 * @param string $mode One of {@see VALID_MODES}; an
 *                     unrecognised value is treated
 *                     as {@see
 *                     MODE_CASE_INSENSITIVE}.
 *
 * @return array<int, array{value: string, label: string, positionStart: int, positionEnd: int}>
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */',
        'startLine' => 122,
        'endLine' => 179,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryMatchService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryMatchService',
        'aliasName' => NULL,
      ),
      'findRawHits' => 
      array (
        'name' => 'findRawHits',
        'parameters' => 
        array (
          'pattern' => 
          array (
            'name' => 'pattern',
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
            'startLine' => 198,
            'endLine' => 198,
            'startColumn' => 31,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'text' => 
          array (
            'name' => 'text',
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
            'startLine' => 198,
            'endLine' => 198,
            'startColumn' => 48,
            'endColumn' => 59,
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
 * Run one term\'s compiled pattern over the text, returning the raw
 * `PREG_OFFSET_CAPTURE` hits for group 0.
 *
 * A malformed operator-supplied term can produce a pattern PCRE refuses to
 * compile. PHP signals that with a `preg_match_all(): Compilation failed`
 * WARNING plus a `false` return. Rather than suppressing the warning with
 * `@` (which also hides every unrelated diagnostic the call could raise),
 * the warning is converted into an `ErrorException` by a narrowly scoped
 * error handler, caught here, and turned into "this one term matched
 * nothing" — every other term in the dictionary still gets its pass.
 *
 * @param string $pattern The compiled pattern from {@see buildPattern()}.
 * @param string $text The document text to search.
 *
 * @return array<int, array{0: string, 1: int}> Raw `[matchedText, byteOffset]` hits.
 */',
        'startLine' => 198,
        'endLine' => 221,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryMatchService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryMatchService',
        'aliasName' => NULL,
      ),
      'normalizeMode' => 
      array (
        'name' => 'normalizeMode',
        'parameters' => 
        array (
          'mode' => 
          array (
            'name' => 'mode',
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
            'startColumn' => 33,
            'endColumn' => 44,
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
 * Sanitise the caller-supplied match mode.
 *
 * @param string $mode Raw mode value.
 *
 * @return string A value from {@see VALID_MODES}.
 */',
        'startLine' => 230,
        'endLine' => 236,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryMatchService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryMatchService',
        'aliasName' => NULL,
      ),
      'buildCandidates' => 
      array (
        'name' => 'buildCandidates',
        'parameters' => 
        array (
          'terms' => 
          array (
            'name' => 'terms',
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
            'startLine' => 252,
            'endLine' => 252,
            'startColumn' => 35,
            'endColumn' => 46,
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
 * Filter blank/whitespace-only terms and default each term\'s label to
 * its own value, preserving the original index for a stable sort
 * tie-break.
 *
 * This is the sanitisation seam, so it takes the RAW row shape — both keys
 * optional — rather than {@see match()}\'s already-validated public
 * contract: a dictionary row loaded from OpenRegister is arbitrary JSON and
 * may legitimately arrive without a `value`.
 *
 * @param array<int, array{value?: string, label?: string}> $terms Raw term rows.
 *
 * @return array<int, array{value: string, label: string, originalIndex: int}> Sanitised candidates.
 */',
        'startLine' => 252,
        'endLine' => 275,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryMatchService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryMatchService',
        'aliasName' => NULL,
      ),
      'buildPattern' => 
      array (
        'name' => 'buildPattern',
        'parameters' => 
        array (
          'needle' => 
          array (
            'name' => 'needle',
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
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'mode' => 
          array (
            'name' => 'mode',
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
            'startColumn' => 48,
            'endColumn' => 59,
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
 * Build the compiled regex pattern for one term + mode.
 *
 * @param string $needle Term value.
 * @param string $mode Normalised match mode.
 *
 * @return string Compiled pattern ready for `preg_match_all`.
 */',
        'startLine' => 285,
        'endLine' => 297,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryMatchService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryMatchService',
        'aliasName' => NULL,
      ),
      'overlapsClaimed' => 
      array (
        'name' => 'overlapsClaimed',
        'parameters' => 
        array (
          'start' => 
          array (
            'name' => 'start',
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
            'startLine' => 308,
            'endLine' => 308,
            'startColumn' => 35,
            'endColumn' => 44,
            'parameterIndex' => 0,
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
            'startLine' => 308,
            'endLine' => 308,
            'startColumn' => 47,
            'endColumn' => 54,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'claimed' => 
          array (
            'name' => 'claimed',
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
            'startLine' => 308,
            'endLine' => 308,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Whether `[start, end)` overlaps any already-claimed range.
 *
 * @param int $start Candidate match start.
 * @param int $end Candidate match end.
 * @param array<int, array{0:int,1:int}> $claimed Already-claimed `[start, end]` pairs.
 *
 * @return bool True when the candidate overlaps a claimed range.
 */',
        'startLine' => 308,
        'endLine' => 316,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryMatchService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryMatchService',
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