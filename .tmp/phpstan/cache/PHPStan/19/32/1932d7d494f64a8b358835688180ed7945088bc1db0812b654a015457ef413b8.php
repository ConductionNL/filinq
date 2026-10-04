<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/EntityDetectionService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\EntityDetectionService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-971808ec4cd45de53fad4957b47a7b5d84beb24cf71b483820094cccd2bfdd22',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\EntityDetectionService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/EntityDetectionService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\EntityDetectionService',
    'shortName' => 'EntityDetectionService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service for entity detection, normalization, and anonymization mapping
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/anonymization/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 40,
    'endLine' => 229,
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
      'TYPED_PII_TYPES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\EntityDetectionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EntityDetectionService',
        'name' => 'TYPED_PII_TYPES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'BSN\', \'IBAN\', \'PHONE\', \'PHONE_NUMBER\', \'POSTCODE\', \'POSTAL_CODE\', \'ZIP\', \'ACCOUNT\', \'ACCOUNT_NUMBER\', \'BANK_ACCOUNT\', \'CASE_NUMBER\', \'CASE_REFERENCE\', \'KVK\', \'BTW\', \'VAT\', \'EMAIL\', \'PERSON\', \'ADDRESS\', \'LOCATION\', \'ORGANIZATION\', \'DATE\', \'DATE_TIME\', \'CREDIT_CARD\', \'SSN\']',
          'attributes' => 
          array (
            'startLine' => 50,
            'endLine' => 75,
            'startTokenPos' => 35,
            'startFilePos' => 1585,
            'endTokenPos' => 109,
            'endFilePos' => 1910,
          ),
        ),
        'docComment' => '/**
 * Entity types that represent sensitive identifiers which may be short
 * and/or fully numeric (BSN, IBAN, phone numbers, postcodes, bank/account
 * numbers, case reference numbers, KvK / BTW identifiers). These MUST be
 * forwarded to the redaction step regardless of length or numeric-ness
 * (closes #285).
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 50,
        'endLine' => 75,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
      'UNTYPED_MIN_LENGTH' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\EntityDetectionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EntityDetectionService',
        'name' => 'UNTYPED_MIN_LENGTH',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '2',
          'attributes' => 
          array (
            'startLine' => 86,
            'endLine' => 86,
            'startTokenPos' => 122,
            'startFilePos' => 2339,
            'endTokenPos' => 122,
            'endFilePos' => 2339,
          ),
        ),
        'docComment' => '/**
 * Minimum length (in characters, not bytes) for an entity value of an
 * UNKNOWN / unclassified type to be considered worth redacting. Single-
 * character tokens are almost always NER noise; values of length 2 are
 * already eligible. This floor is NEVER applied to a typed PII entity
 * (see TYPED_PII_TYPES) — those are always redacted (closes #285).
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 86,
        'endLine' => 86,
        'startColumn' => 2,
        'endColumn' => 38,
      ),
    ),
    'immediateProperties' => 
    array (
      'resultParser' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\EntityDetectionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EntityDetectionService',
        'name' => 'resultParser',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\AnonymizationResultParser',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 96,
        'endLine' => 96,
        'startColumn' => 3,
        'endColumn' => 58,
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
          'resultParser' => 
          array (
            'name' => 'resultParser',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\AnonymizationResultParser',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 96,
            'endLine' => 96,
            'startColumn' => 3,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor for EntityDetectionService
 *
 * @param AnonymizationResultParser $resultParser Anonymization result parser
 *
 * @return void
 */',
        'startLine' => 95,
        'endLine' => 99,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\EntityDetectionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EntityDetectionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\EntityDetectionService',
        'aliasName' => NULL,
      ),
      'normalizeEntities' => 
      array (
        'name' => 'normalizeEntities',
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
            'startLine' => 110,
            'endLine' => 110,
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
 * Normalize entity data to a consistent format
 *
 * @param array<mixed> $entities Raw entity objects or arrays
 *
 * @return array<int, array<string, mixed>> Normalized entity list
 *
 * @spec openspec/specs/anonymization/spec.md
 */',
        'startLine' => 110,
        'endLine' => 132,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\EntityDetectionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EntityDetectionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\EntityDetectionService',
        'aliasName' => NULL,
      ),
      'mapEntitiesForAnonymization' => 
      array (
        'name' => 'mapEntitiesForAnonymization',
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
            'startLine' => 163,
            'endLine' => 163,
            'startColumn' => 46,
            'endColumn' => 60,
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
 * Map entities to the format expected by OpenRegister\'s anonymizeDocument
 *
 * Per-entity `bases[]` is forwarded verbatim when present so OpenRegister
 * can persist the legal basis on the EntityRelation row.
 *
 * Filtering rules (closes #285):
 *   - An empty value is always skipped (nothing to redact).
 *   - A typed PII value (see TYPED_PII_TYPES — BSN, IBAN, PHONE,
 *     POSTCODE, ACCOUNT, CASE_NUMBER, KVK, BTW, EMAIL, PERSON, …) is
 *     ALWAYS forwarded for redaction, regardless of length or
 *     numeric-ness. The previous heuristic silently dropped these,
 *     letting the most sensitive identifiers survive verbatim in the
 *     "anonymized" output.
 *   - Only for UNKNOWN / unclassified entity types do we still apply a
 *     length floor of UNTYPED_MIN_LENGTH characters, measured with
 *     mb_strlen() so multibyte text is counted by characters, not
 *     bytes.
 *   - The previous is_numeric() exclusion is removed entirely: numeric
 *     values (BSN, postcode, phone, case reference) are among the
 *     most sensitive PII and must be redacted.
 *
 * @param array<array<string, mixed>> $entities The raw entities
 *
 * @return array<int, array<string, mixed>> Mapped entities
 *
 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-2
 * @spec openspec/specs/anonymization/spec.md
 */',
        'startLine' => 163,
        'endLine' => 202,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\EntityDetectionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EntityDetectionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\EntityDetectionService',
        'aliasName' => NULL,
      ),
      'parseAnonymizationResult' => 
      array (
        'name' => 'parseAnonymizationResult',
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
            'startLine' => 213,
            'endLine' => 213,
            'startColumn' => 43,
            'endColumn' => 55,
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
 * Parse anonymization result into a structured array
 *
 * @param mixed $result The raw anonymization result
 *
 * @return array{anonymizedFileId: mixed, anonymizedFileName: mixed, anonymizedFilePath: mixed}
 *
 * @spec openspec/specs/anonymization/spec.md
 */',
        'startLine' => 213,
        'endLine' => 215,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\EntityDetectionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EntityDetectionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\EntityDetectionService',
        'aliasName' => NULL,
      ),
      'generateUuid' => 
      array (
        'name' => 'generateUuid',
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
 * Generate a UUID v4 string
 *
 * @return string A UUID v4 string
 */',
        'startLine' => 222,
        'endLine' => 228,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\EntityDetectionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EntityDetectionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\EntityDetectionService',
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