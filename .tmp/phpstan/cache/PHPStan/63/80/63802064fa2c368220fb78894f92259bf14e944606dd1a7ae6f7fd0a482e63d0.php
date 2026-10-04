<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/BatchRequestService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\BatchRequestService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-e7bb446923acef70c3ed9d1b3b4d13940aa608172b714c05b9abe96f6a05fd38',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/BatchRequestService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\BatchRequestService',
    'shortName' => 'BatchRequestService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Validation and aggregation helpers for the batch anonymization endpoints.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 49,
    'endLine' => 391,
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
      'unredactedValidator' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'name' => 'unredactedValidator',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\UnredactedEntitiesValidator',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Validator for the shared `unredactedEntities[]` payload shape.
 *
 * @var UnredactedEntitiesValidator
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 56,
        'endLine' => 56,
        'startColumn' => 2,
        'endColumn' => 67,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'l10n' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'name' => 'l10n',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IL10N',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 66,
        'endLine' => 66,
        'startColumn' => 3,
        'endColumn' => 30,
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
          'l10n' => 
          array (
            'name' => 'l10n',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IL10N',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 66,
            'endLine' => 66,
            'startColumn' => 3,
            'endColumn' => 30,
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
 * Constructor for BatchRequestService
 *
 * @param IL10N $l10n Translator for the user-facing validation messages.
 *
 * @return void
 */',
        'startLine' => 65,
        'endLine' => 70,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'aliasName' => NULL,
      ),
      'validateBody' => 
      array (
        'name' => 'validateBody',
        'parameters' => 
        array (
          'params' => 
          array (
            'name' => 'params',
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
            'startLine' => 89,
            'endLine' => 89,
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
 * Validate the batch-anonymize request body up to the outputFormat gate.
 *
 * The unredactedEntities entries themselves are validated separately (see
 * validateUnredactedEntries) because the controller resolves the
 * outputFormat in between, and that ordering determines which HTTP 400 a
 * doubly-malformed body receives.
 *
 * @param array<string, mixed> $params Request parameters.
 *
 * @return array{error: array{status: int, body: array<string, mixed>}|null,
 *               request: array<string, mixed>|null} The first validation failure, or the
 *                                                   normalised request under `request`.
 *
 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-1
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
 */',
        'startLine' => 89,
        'endLine' => 122,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'aliasName' => NULL,
      ),
      'validateUnredactedEntries' => 
      array (
        'name' => 'validateUnredactedEntries',
        'parameters' => 
        array (
          'entries' => 
          array (
            'name' => 'entries',
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
            'startLine' => 137,
            'endLine' => 137,
            'startColumn' => 44,
            'endColumn' => 57,
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
                  'name' => 'array',
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
 * Validate the structure of each unredactedEntities[] entry.
 *
 * Mirrors the single-file anonymize endpoint so the batch endpoint rejects
 * malformed payloads with HTTP 400 before forwarding to the service layer.
 *
 * @param array<int, mixed> $entries The unredactedEntities array from the request.
 *
 * @return array{status: int, body: array<string, mixed>}|null HTTP 400 payload for the first
 *                                                             invalid entry, null when all valid.
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
 */',
        'startLine' => 137,
        'endLine' => 139,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'aliasName' => NULL,
      ),
      'resolveScope' => 
      array (
        'name' => 'resolveScope',
        'parameters' => 
        array (
          'params' => 
          array (
            'name' => 'params',
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
            'startLine' => 154,
            'endLine' => 154,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolve the placeholder-numbering scope for a batch.
 *
 * A batch IS a folder/dossier, so the default is \'dossier\' — a person gets
 * the same scope-local number across all the batch\'s files. Any value other
 * than \'document\' keeps the dossier default.
 *
 * @param array<string, mixed> $params Request parameters.
 *
 * @return string Either \'dossier\' or \'document\'.
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
 */',
        'startLine' => 154,
        'endLine' => 160,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'aliasName' => NULL,
      ),
      'resolveHttpStatus' => 
      array (
        'name' => 'resolveHttpStatus',
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
            'startLine' => 175,
            'endLine' => 175,
            'startColumn' => 36,
            'endColumn' => 48,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolve the HTTP status for a batch anonymization result.
 *
 * HTTP 200 — all files processed without prohibition failures.
 * HTTP 207 — some files had per-file 422 prohibition violations; others succeeded.
 * HTTP 422 — every processed file had a prohibition violation (none succeeded).
 *
 * @param array<string, mixed> $result Batch anonymization result.
 *
 * @return int HTTP status code.
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
 */',
        'startLine' => 175,
        'endLine' => 197,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'aliasName' => NULL,
      ),
      'summariseBatch' => 
      array (
        'name' => 'summariseBatch',
        'parameters' => 
        array (
          'batch' => 
          array (
            'name' => 'batch',
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
            'startLine' => 208,
            'endLine' => 208,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Aggregate a batch\'s per-file entries into the status snapshot.
 *
 * @param array<string, mixed> $batch The stored batch record.
 *
 * @return array{totalEntities: int, progress: float|int, totalFiles: int} The snapshot counters.
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-status-endpoint
 */',
        'startLine' => 208,
        'endLine' => 230,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'aliasName' => NULL,
      ),
      'countProcessedFiles' => 
      array (
        'name' => 'countProcessedFiles',
        'parameters' => 
        array (
          'batch' => 
          array (
            'name' => 'batch',
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
            'startLine' => 241,
            'endLine' => 241,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Count the files of a batch whose extraction has finished (or failed).
 *
 * @param array<string, mixed> $batch The stored batch record.
 *
 * @return int Number of files in a terminal extraction state.
 *
 * @spec openspec/specs/anonymization-entity-review/spec.md#requirement-consolidated-entity-list-endpoint
 */',
        'startLine' => 241,
        'endLine' => 250,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'aliasName' => NULL,
      ),
      'resolveFolderParams' => 
      array (
        'name' => 'resolveFolderParams',
        'parameters' => 
        array (
          'rawFolderId' => 
          array (
            'name' => 'rawFolderId',
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
            'startLine' => 266,
            'endLine' => 266,
            'startColumn' => 38,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'rawFolderPath' => 
          array (
            'name' => 'rawFolderPath',
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
            'startLine' => 266,
            'endLine' => 266,
            'startColumn' => 58,
            'endColumn' => 77,
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
 * Coerce and XOR-validate the folder-batch request parameters.
 *
 * @param mixed $rawFolderId Raw `folderId` param value from the request.
 * @param mixed $rawFolderPath Raw `folderPath` param value from the request.
 *
 * @return array{folderId: int|null, folderPath: string|null,
 *               error: array{status: int, body: array<string, mixed>}|null} The coerced params,
 *                                                                          plus the validation
 *                                                                          failure when both or
 *                                                                          neither were supplied.
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
 */',
        'startLine' => 266,
        'endLine' => 276,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'aliasName' => NULL,
      ),
      'coerceFolderId' => 
      array (
        'name' => 'coerceFolderId',
        'parameters' => 
        array (
          'raw' => 
          array (
            'name' => 'raw',
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
            'startLine' => 287,
            'endLine' => 287,
            'startColumn' => 34,
            'endColumn' => 43,
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
                  'name' => 'int',
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
 * Coerce the raw folderId request param to an int, or null when absent/empty.
 *
 * @param mixed $raw Raw param value from the request.
 *
 * @return int|null Integer folder ID, or null when the caller did not supply one.
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
 */',
        'startLine' => 287,
        'endLine' => 293,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'aliasName' => NULL,
      ),
      'coerceFolderPath' => 
      array (
        'name' => 'coerceFolderPath',
        'parameters' => 
        array (
          'raw' => 
          array (
            'name' => 'raw',
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
            'startLine' => 304,
            'endLine' => 304,
            'startColumn' => 36,
            'endColumn' => 45,
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
 * Coerce the raw folderPath request param to a string, or null when absent/empty.
 *
 * @param mixed $raw Raw param value from the request.
 *
 * @return string|null Path string, or null when the caller did not supply one.
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
 */',
        'startLine' => 304,
        'endLine' => 310,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'aliasName' => NULL,
      ),
      'validateFolderParams' => 
      array (
        'name' => 'validateFolderParams',
        'parameters' => 
        array (
          'folderId' => 
          array (
            'name' => 'folderId',
            'default' => NULL,
            'type' => 
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
                      'name' => 'int',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 323,
            'endLine' => 323,
            'startColumn' => 40,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'folderPath' => 
          array (
            'name' => 'folderPath',
            'default' => NULL,
            'type' => 
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 323,
            'endLine' => 323,
            'startColumn' => 56,
            'endColumn' => 74,
            'parameterIndex' => 1,
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
                  'name' => 'array',
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
 * Validate XOR between folderId and folderPath at the controller boundary.
 *
 * @param int|null $folderId Coerced folder ID.
 * @param string|null $folderPath Coerced folder path.
 *
 * @return array{status: int, body: array<string, mixed>}|null Error payload when validation
 *                                                             fails, null when OK.
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
 */',
        'startLine' => 323,
        'endLine' => 337,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'aliasName' => NULL,
      ),
      'hasStrayBases' => 
      array (
        'name' => 'hasStrayBases',
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
            'startLine' => 352,
            'endLine' => 352,
            'startColumn' => 33,
            'endColumn' => 47,
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
 * Detect stray `bases[]` fields on entity entries.
 *
 * Bases are set through OpenRegister\'s PATCH /api/entity-relations/{id};
 * a stray field on the anonymize payload is ignored but reported back as
 * `ignoredFields` for GDPR accountability.
 *
 * @param array<int, mixed> $entities The submitted entity entries.
 *
 * @return bool True when at least one entry carries a `bases` key.
 *
 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-1
 */',
        'startLine' => 352,
        'endLine' => 360,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'aliasName' => NULL,
      ),
      'rejected' => 
      array (
        'name' => 'rejected',
        'parameters' => 
        array (
          'error' => 
          array (
            'name' => 'error',
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
            'startLine' => 371,
            'endLine' => 371,
            'startColumn' => 28,
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
 * Wrap a validation failure in the validateBody() return shape.
 *
 * @param array{status: int, body: array<string, mixed>} $error The failing status/body pair.
 *
 * @return array{error: array{status: int, body: array<string, mixed>}, request: null} The rejection.
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
 */',
        'startLine' => 371,
        'endLine' => 373,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'aliasName' => NULL,
      ),
      'badRequest' => 
      array (
        'name' => 'badRequest',
        'parameters' => 
        array (
          'message' => 
          array (
            'name' => 'message',
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
            'startLine' => 384,
            'endLine' => 384,
            'startColumn' => 30,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Build the HTTP 400 status/body pair for a validation failure.
 *
 * @param string $message The already-translated error message.
 *
 * @return array{status: int, body: array<string, mixed>} The 400 payload.
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
 */',
        'startLine' => 384,
        'endLine' => 390,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
        'currentClassName' => 'OCA\\Filinq\\Service\\BatchRequestService',
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