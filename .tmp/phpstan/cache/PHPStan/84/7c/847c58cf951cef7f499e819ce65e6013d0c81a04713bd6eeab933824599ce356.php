<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/BatchAnonymizeService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\BatchAnonymizeService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-607185678b64a5b956ce25cb4820796e82c59cc23d091489d25ec33697fd2acf',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/BatchAnonymizeService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
    'shortName' => 'BatchAnonymizeService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Applies a user-reviewed entity list across every file in a batch.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 44,
    'endLine' => 397,
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
      'anonService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
        'name' => 'anonService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\AnonymizationService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 54,
        'endLine' => 54,
        'startColumn' => 3,
        'endColumn' => 52,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'stateService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
        'name' => 'stateService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\BatchStateService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 55,
        'endLine' => 55,
        'startColumn' => 3,
        'endColumn' => 50,
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
          'anonService' => 
          array (
            'name' => 'anonService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\AnonymizationService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 54,
            'endLine' => 54,
            'startColumn' => 3,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'stateService' => 
          array (
            'name' => 'stateService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\BatchStateService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 55,
            'endLine' => 55,
            'startColumn' => 3,
            'endColumn' => 50,
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
 * Constructor for BatchAnonymizeService
 *
 * @param AnonymizationService $anonService Service that performs single-document anonymization.
 * @param BatchStateService $stateService Service that persists per-batch state between calls.
 *
 * @return void
 */',
        'startLine' => 53,
        'endLine' => 58,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
        'aliasName' => NULL,
      ),
      'anonymizeBatch' => 
      array (
        'name' => 'anonymizeBatch',
        'parameters' => 
        array (
          'batchId' => 
          array (
            'name' => 'batchId',
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
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 100,
            'endLine' => 100,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'unredactedEntities' => 
          array (
            'name' => 'unredactedEntities',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 101,
                'endLine' => 101,
                'startTokenPos' => 94,
                'startFilePos' => 4137,
                'endTokenPos' => 95,
                'endFilePos' => 4138,
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
            'startLine' => 101,
            'endLine' => 101,
            'startColumn' => 3,
            'endColumn' => 32,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'outputFormat' => 
          array (
            'name' => 'outputFormat',
            'default' => 
            array (
              'code' => '\'pdf-only\'',
              'attributes' => 
              array (
                'startLine' => 102,
                'endLine' => 102,
                'startTokenPos' => 104,
                'startFilePos' => 4166,
                'endTokenPos' => 104,
                'endFilePos' => 4175,
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
            'startLine' => 102,
            'endLine' => 102,
            'startColumn' => 3,
            'endColumn' => 35,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'scope' => 
          array (
            'name' => 'scope',
            'default' => 
            array (
              'code' => '\'dossier\'',
              'attributes' => 
              array (
                'startLine' => 103,
                'endLine' => 103,
                'startTokenPos' => 113,
                'startFilePos' => 4196,
                'endTokenPos' => 113,
                'endFilePos' => 4204,
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
            'startLine' => 103,
            'endLine' => 103,
            'startColumn' => 3,
            'endColumn' => 27,
            'parameterIndex' => 4,
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
 * Anonymize every extracted file in a batch using the approved entity list.
 *
 * No grondslagen summary is produced; see
 * {@see anonymizeBatchWithBasisSummary()} for the summary-producing variant.
 * Files with a non-extracted status are skipped (previous errors are
 * recorded in the skipped list; other states are ignored).
 *
 * When unredactedEntities is non-empty, the prohibition gate is checked
 * per file. Files with prohibition violations are recorded with a
 * `prohibitionViolation` status and counted in prohibitionSkippedFiles.
 *
 * @param string $batchId Identifier of the batch to anonymize.
 * @param array<int, array<string, mixed>> $entities User-approved entities to anonymize.
 * @param array<int, array<string, mixed>> $unredactedEntities Entities to publish unredacted with consent creation.
 * @param string $outputFormat Per-batch output format gate
 *                             (\'pdf-only\'|\'pdf\'|\'preserve\',
 *                             default \'pdf-only\'). Passed
 *                             through to each per-file
 *                             anonymise call. Per-file
 *                             ConversionFailedException is
 *                             recorded as an error on that
 *                             file\'s batch entry and the
 *                             batch continues with the
 *                             next file.
 * @param string $scope Placeholder-numbering scope forwarded to OpenRegister for every file in
 *                      the batch. Defaults to `"dossier"` because a batch IS a folder/dossier:
 *                      a person gets the SAME scope-local number across all the batch\'s files
 *                      (OpenRegister derives the dossier from each file\'s parent folder).
 *
 * @return array<string, mixed> Summary of the run — see runBatch().
 *
 * @throws Exception When the batch cannot be found.
 *
 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-3
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
 */',
        'startLine' => 98,
        'endLine' => 116,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
        'aliasName' => NULL,
      ),
      'anonymizeBatchWithBasisSummary' => 
      array (
        'name' => 'anonymizeBatchWithBasisSummary',
        'parameters' => 
        array (
          'batchId' => 
          array (
            'name' => 'batchId',
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
            'startLine' => 142,
            'endLine' => 142,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 143,
            'endLine' => 143,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'unredactedEntities' => 
          array (
            'name' => 'unredactedEntities',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 144,
                'endLine' => 144,
                'startTokenPos' => 208,
                'startFilePos' => 5854,
                'endTokenPos' => 209,
                'endFilePos' => 5855,
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
            'startLine' => 144,
            'endLine' => 144,
            'startColumn' => 3,
            'endColumn' => 32,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'outputFormat' => 
          array (
            'name' => 'outputFormat',
            'default' => 
            array (
              'code' => '\'pdf-only\'',
              'attributes' => 
              array (
                'startLine' => 145,
                'endLine' => 145,
                'startTokenPos' => 218,
                'startFilePos' => 5883,
                'endTokenPos' => 218,
                'endFilePos' => 5892,
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
            'startColumn' => 3,
            'endColumn' => 35,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'scope' => 
          array (
            'name' => 'scope',
            'default' => 
            array (
              'code' => '\'dossier\'',
              'attributes' => 
              array (
                'startLine' => 146,
                'endLine' => 146,
                'startTokenPos' => 227,
                'startFilePos' => 5913,
                'endTokenPos' => 227,
                'endFilePos' => 5921,
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
            'startLine' => 146,
            'endLine' => 146,
            'startColumn' => 3,
            'endColumn' => 27,
            'parameterIndex' => 4,
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
 * Anonymize every extracted file in a batch AND append a grondslagen summary.
 *
 * Identical to {@see anonymizeBatch()} except that every per-file anonymise
 * call also renders the grondslagen summary. Summary failures are collected
 * as per-file `warning` entries and do not abort the batch; the batch always
 * completes as HTTP 200.
 *
 * @param string $batchId Identifier of the batch to anonymize.
 * @param array<int, array<string, mixed>> $entities User-approved entities to anonymize.
 * @param array<int, array<string, mixed>> $unredactedEntities Entities to publish unredacted with consent creation.
 * @param string $outputFormat Per-batch output format gate
 *                             (\'pdf-only\'|\'pdf\'|\'preserve\', default \'pdf-only\').
 * @param string $scope Placeholder-numbering scope forwarded to OpenRegister.
 *
 * @return array<string, mixed> Summary of the run — see runBatch().
 *
 * @throws Exception When the batch cannot be found.
 *
 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-3
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
 */',
        'startLine' => 141,
        'endLine' => 159,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
        'aliasName' => NULL,
      ),
      'runBatch' => 
      array (
        'name' => 'runBatch',
        'parameters' => 
        array (
          'batchId' => 
          array (
            'name' => 'batchId',
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
            'startLine' => 185,
            'endLine' => 185,
            'startColumn' => 28,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 185,
            'endLine' => 185,
            'startColumn' => 45,
            'endColumn' => 59,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'callOptions' => 
          array (
            'name' => 'callOptions',
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
            'startLine' => 185,
            'endLine' => 185,
            'startColumn' => 62,
            'endColumn' => 79,
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
 * Shared implementation behind both batch entry points.
 *
 * @param string $batchId Identifier of the batch to anonymize.
 * @param array<int, array<string, mixed>> $entities User-approved entities to anonymize.
 * @param array<string, mixed> $callOptions Per-batch options (appendBasisSummary,
 *                                          unredactedEntities, outputFormat, scope)
 *                                          forwarded verbatim to each per-file call.
 *
 * @return array Summary of the run, with shape:
 *               {
 *               batchId: string,
 *               batchStatus: string,
 *               processedFiles: int,
 *               skippedFiles: array<int, array{fileId: mixed, reason: string}>,
 *               prohibitionSkippedFiles: int,
 *               totalFiles: int,
 *               }
 *
 * @throws Exception When the batch cannot be found.
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
 */',
        'startLine' => 185,
        'endLine' => 253,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
        'aliasName' => NULL,
      ),
      'prohibitionViolationsFor' => 
      array (
        'name' => 'prohibitionViolationsFor',
        'parameters' => 
        array (
          'unredactedEntities' => 
          array (
            'name' => 'unredactedEntities',
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
            'startLine' => 268,
            'endLine' => 268,
            'startColumn' => 44,
            'endColumn' => 68,
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
 * Resolve the prohibition violations for the batch\'s unredacted entities.
 *
 * Returns an empty array without consulting the prohibition gate when the
 * caller supplied no unredacted entities, mirroring the original inline
 * guard.
 *
 * @param array<int, array<string, mixed>> $unredactedEntities Entities to publish unredacted.
 *
 * @return array<int, array<string, mixed>> Violation records; empty when the entries may proceed.
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
 */',
        'startLine' => 268,
        'endLine' => 277,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
        'aliasName' => NULL,
      ),
      'anonymizeBatchFile' => 
      array (
        'name' => 'anonymizeBatchFile',
        'parameters' => 
        array (
          'entry' => 
          array (
            'name' => 'entry',
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
            'startLine' => 299,
            'endLine' => 299,
            'startColumn' => 38,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 299,
            'endLine' => 299,
            'startColumn' => 52,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'callOptions' => 
          array (
            'name' => 'callOptions',
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
            'startLine' => 299,
            'endLine' => 299,
            'startColumn' => 69,
            'endColumn' => 86,
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
 * Anonymize one batch file and fold the outcome into its batch entry.
 *
 * A per-file failure is recorded on the entry (status `error`, plus the
 * conversion attempts surface when the PDF cascade was exhausted) and
 * reported back through the `error` key so the batch can continue with the
 * next file.
 *
 * @param array<string, mixed> $entry The batch entry for this file.
 * @param array<int, array<string, mixed>> $entities User-approved entities to anonymize.
 * @param array<string, mixed> $callOptions Per-batch options; `appendBasisSummary` selects
 *                                          which AnonymizationService entry point is used,
 *                                          the rest are forwarded verbatim.
 *
 * @return array{entry: array<string, mixed>, error: string|null} The updated entry plus the
 *                                                                failure reason, or null when
 *                                                                the file was anonymized.
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
 */',
        'startLine' => 299,
        'endLine' => 327,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
        'aliasName' => NULL,
      ),
      'anonymizeOneFile' => 
      array (
        'name' => 'anonymizeOneFile',
        'parameters' => 
        array (
          'fileId' => 
          array (
            'name' => 'fileId',
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
            'startLine' => 344,
            'endLine' => 344,
            'startColumn' => 36,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 344,
            'endLine' => 344,
            'startColumn' => 49,
            'endColumn' => 63,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'callOptions' => 
          array (
            'name' => 'callOptions',
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
            'startLine' => 344,
            'endLine' => 344,
            'startColumn' => 66,
            'endColumn' => 83,
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
 * Call the AnonymizationService entry point this batch asked for.
 *
 * `appendBasisSummary` selects between the plain and the summary-producing
 * entry point; every other option is forwarded verbatim. The dossierKey:null
 * argument makes OpenRegister fall back to the file\'s parent folder.
 *
 * @param int $fileId The Nextcloud file id to anonymize.
 * @param array<int, array<string, mixed>> $entities User-approved entities to anonymize.
 * @param array<string, mixed> $callOptions Per-batch options.
 *
 * @return array<string, mixed> The AnonymizationService result payload.
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
 */',
        'startLine' => 344,
        'endLine' => 365,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
        'aliasName' => NULL,
      ),
      'applyAnonymizedResult' => 
      array (
        'name' => 'applyAnonymizedResult',
        'parameters' => 
        array (
          'entry' => 
          array (
            'name' => 'entry',
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
            'startLine' => 377,
            'endLine' => 377,
            'startColumn' => 41,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 377,
            'endLine' => 377,
            'startColumn' => 55,
            'endColumn' => 67,
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
 * Fold a successful anonymise result into the file\'s batch entry.
 *
 * @param array<string, mixed> $entry The batch entry for this file.
 * @param array<string, mixed> $result The AnonymizationService result payload.
 *
 * @return array<string, mixed> The entry with the anonymised status and result fields applied.
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
 */',
        'startLine' => 377,
        'endLine' => 396,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
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