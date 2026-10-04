<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/ConsentNotesHelper.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\ConsentNotesHelper
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-3aa13998f1133a5700a7c9ceba2030928434a342c482bc58a000e48c64b14168',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\ConsentNotesHelper',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/ConsentNotesHelper.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\ConsentNotesHelper',
    'shortName' => 'ConsentNotesHelper',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Helper for sentinel-tagged additional-publication-bases region in consent notes.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-3
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 53,
    'endLine' => 195,
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
      'SENTINEL_BEGIN' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentNotesHelper',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentNotesHelper',
        'name' => 'SENTINEL_BEGIN',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'<!-- docudesk:additional-publication-bases:begin -->\'',
          'attributes' => 
          array (
            'startLine' => 71,
            'endLine' => 71,
            'startTokenPos' => 35,
            'startFilePos' => 2552,
            'endTokenPos' => 35,
            'endFilePos' => 2605,
          ),
        ),
        'docComment' => '/**
 * Opening sentinel comment.
 *
 * ⚠️ STILL `docudesk:`, DELIBERATELY, ACROSS THE FILINQ RENAME. These two
 * markers are not source text — they are WRITTEN INTO the `notes` field of
 * every publicationConsent object this app has ever created, and read back
 * out by `extract()`/`replace()` to find the managed region. Renaming them
 * makes the reader stop matching the marker pair in existing records, so
 * the managed region is no longer found: the previously written bases stop
 * being recognised as generated content and the next write appends a
 * SECOND region instead of replacing the first. Silent, cumulative
 * corruption of stored consent notes, with no error anywhere. Changing
 * these needs a data migration over the stored notes, not a rename.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 71,
        'endLine' => 71,
        'startColumn' => 2,
        'endColumn' => 86,
      ),
      'SENTINEL_END' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentNotesHelper',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentNotesHelper',
        'name' => 'SENTINEL_END',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'<!-- docudesk:additional-publication-bases:end -->\'',
          'attributes' => 
          array (
            'startLine' => 80,
            'endLine' => 80,
            'startTokenPos' => 48,
            'startFilePos' => 2755,
            'endTokenPos' => 48,
            'endFilePos' => 2806,
          ),
        ),
        'docComment' => '/**
 * Closing sentinel comment.
 *
 * ⚠️ STILL `docudesk:` — see SENTINEL_BEGIN.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 80,
        'endLine' => 80,
        'startColumn' => 2,
        'endColumn' => 82,
      ),
      'LEGAL_BASIS_MAX_LENGTH' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentNotesHelper',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentNotesHelper',
        'name' => 'LEGAL_BASIS_MAX_LENGTH',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '500',
          'attributes' => 
          array (
            'startLine' => 87,
            'endLine' => 87,
            'startTokenPos' => 61,
            'startFilePos' => 2931,
            'endTokenPos' => 61,
            'endFilePos' => 2933,
          ),
        ),
        'docComment' => '/**
 * Maximum character length for the legalBasis field.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 87,
        'endLine' => 87,
        'startColumn' => 2,
        'endColumn' => 43,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'updateSentinelRegion' => 
      array (
        'name' => 'updateSentinelRegion',
        'parameters' => 
        array (
          'currentNotes' => 
          array (
            'name' => 'currentNotes',
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
            'startLine' => 106,
            'endLine' => 106,
            'startColumn' => 39,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'additionalBases' => 
          array (
            'name' => 'additionalBases',
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
            'startLine' => 106,
            'endLine' => 106,
            'startColumn' => 61,
            'endColumn' => 82,
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
 * Write or replace the sentinel-tagged region in the notes string.
 *
 * Behaviour:
 * - $additionalBases empty → strip the region (and its preceding blank line) and return.
 * - $additionalBases non-empty → build the sentinel block and either
 *   append it (separated by a blank line) or replace the existing block.
 *
 * Operator-authored content before the sentinel is preserved unchanged.
 *
 * @param string $currentNotes Current value of publicationConsent.notes.
 * @param string[] $additionalBases Bases 2..N to render inside the sentinel region.
 *
 * @return string Updated notes string.
 *
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-3
 */',
        'startLine' => 106,
        'endLine' => 121,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentNotesHelper',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentNotesHelper',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentNotesHelper',
        'aliasName' => NULL,
      ),
      'stripSentinelRegion' => 
      array (
        'name' => 'stripSentinelRegion',
        'parameters' => 
        array (
          'notes' => 
          array (
            'name' => 'notes',
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
            'startLine' => 135,
            'endLine' => 135,
            'startColumn' => 38,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Remove the sentinel-tagged region from notes (including its leading blank line).
 *
 * Safe to call even when no sentinel region is present; returns the
 * string unchanged in that case.
 *
 * @param string $notes Source notes string.
 *
 * @return string Notes with the sentinel region removed.
 *
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-3
 */',
        'startLine' => 135,
        'endLine' => 144,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentNotesHelper',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentNotesHelper',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentNotesHelper',
        'aliasName' => NULL,
      ),
      'truncateAtWordBoundary' => 
      array (
        'name' => 'truncateAtWordBoundary',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
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
            'startLine' => 158,
            'endLine' => 158,
            'startColumn' => 41,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'maxLength' => 
          array (
            'name' => 'maxLength',
            'default' => 
            array (
              'code' => 'self::LEGAL_BASIS_MAX_LENGTH',
              'attributes' => 
              array (
                'startLine' => 158,
                'endLine' => 158,
                'startTokenPos' => 324,
                'startFilePos' => 5653,
                'endTokenPos' => 326,
                'endFilePos' => 5680,
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
            'startLine' => 158,
            'endLine' => 158,
            'startColumn' => 56,
            'endColumn' => 100,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Truncate a string at word boundary up to $maxLength characters.
 *
 * Used for truncating `publicationBases[0]` to the `legalBasis` field limit.
 *
 * @param string $value Source string.
 * @param int $maxLength Maximum character length.
 *
 * @return string Truncated string.
 *
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-4
 */',
        'startLine' => 158,
        'endLine' => 172,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentNotesHelper',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentNotesHelper',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentNotesHelper',
        'aliasName' => NULL,
      ),
      'buildSentinelBlock' => 
      array (
        'name' => 'buildSentinelBlock',
        'parameters' => 
        array (
          'bases' => 
          array (
            'name' => 'bases',
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
            'startLine' => 181,
            'endLine' => 181,
            'startColumn' => 38,
            'endColumn' => 49,
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
 * Build the sentinel block for the given list of additional bases.
 *
 * @param string[] $bases Non-empty list of additional publication bases.
 *
 * @return string The fully-formed sentinel block.
 */',
        'startLine' => 181,
        'endLine' => 194,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentNotesHelper',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentNotesHelper',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentNotesHelper',
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