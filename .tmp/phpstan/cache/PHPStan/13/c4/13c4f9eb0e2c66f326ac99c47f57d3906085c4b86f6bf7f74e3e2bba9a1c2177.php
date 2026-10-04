<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/BackgroundJob/FolderExtractionJob.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\BackgroundJob\FolderExtractionJob
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-06a74f3cba5f12a02a9d70bc94600c38cbeeefaa2b1a3654b3ff586051ff5c29',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/BackgroundJob/FolderExtractionJob.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\BackgroundJob',
    'name' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
    'shortName' => 'FolderExtractionJob',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Background job for extracting, anonymising and laying-out all files in a
 * folder-based batch.
 *
 * Processes files sequentially, updating batch state after each file:
 *   - `_anonymized`-suffixed source files are skipped (status `skipped`).
 *   - Files not in the `uploaded` state are left untouched (idempotent retry).
 *   - Each uploaded file is extracted, anonymised, and the redacted output is
 *     moved into the configured output subfolder; on success the file entry
 *     records the new target path, on move failure it keeps the legacy path
 *     and records a `MOVE_FAILED` warning.
 *   - Extraction or anonymisation errors mark the single file as `error`
 *     without aborting the batch.
 *
 * @category BackgroundJob
 * @package  OCA\\Filinq\\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-2
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 62,
    'endLine' => 345,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'OCP\\BackgroundJob\\QueuedJob',
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
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
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
        'startLine' => 77,
        'endLine' => 77,
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
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
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
        'startLine' => 78,
        'endLine' => 78,
        'startColumn' => 3,
        'endColumn' => 50,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
        'name' => 'logger',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Psr\\Log\\LoggerInterface',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 79,
        'endLine' => 79,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'layoutResolver' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
        'name' => 'layoutResolver',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 80,
        'endLine' => 80,
        'startColumn' => 3,
        'endColumn' => 55,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'rootFolder' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
        'name' => 'rootFolder',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\Files\\IRootFolder',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 81,
        'endLine' => 81,
        'startColumn' => 3,
        'endColumn' => 42,
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
          'time' => 
          array (
            'name' => 'time',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\AppFramework\\Utility\\ITimeFactory',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 76,
            'endLine' => 76,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 77,
            'endLine' => 77,
            'startColumn' => 3,
            'endColumn' => 52,
            'parameterIndex' => 1,
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
            'startLine' => 78,
            'endLine' => 78,
            'startColumn' => 3,
            'endColumn' => 50,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'logger' => 
          array (
            'name' => 'logger',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Psr\\Log\\LoggerInterface',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 79,
            'endLine' => 79,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'layoutResolver' => 
          array (
            'name' => 'layoutResolver',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 80,
            'endLine' => 80,
            'startColumn' => 3,
            'endColumn' => 55,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'rootFolder' => 
          array (
            'name' => 'rootFolder',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Files\\IRootFolder',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 81,
            'endLine' => 81,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor for FolderExtractionJob.
 *
 * @param ITimeFactory $time Time factory.
 * @param AnonymizationService $anonService Anonymization/extraction service.
 * @param BatchStateService $stateService Batch state management.
 * @param LoggerInterface $logger Logger for error reporting.
 * @param OutputLayoutResolver $layoutResolver Output-folder layout resolver.
 * @param IRootFolder $rootFolder Root folder for file lookups.
 *
 * @return void
 */',
        'startLine' => 75,
        'endLine' => 85,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\BackgroundJob',
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
        'currentClassName' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
        'aliasName' => NULL,
      ),
      'run' => 
      array (
        'name' => 'run',
        'parameters' => 
        array (
          'argument' => 
          array (
            'name' => 'argument',
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
            'startLine' => 100,
            'endLine' => 100,
            'startColumn' => 25,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Run the folder extraction + anonymisation job.
 *
 * Processes each uploaded file in the batch sequentially. Sets batch
 * status to `extracting` at the start and `completed` once every file has
 * been attempted.
 *
 * @param mixed $argument Job arguments containing batchId.
 *
 * @return void
 *
 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-2
 */',
        'startLine' => 100,
        'endLine' => 144,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'OCA\\Filinq\\BackgroundJob',
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
        'currentClassName' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
        'aliasName' => NULL,
      ),
      'processFileEntry' => 
      array (
        'name' => 'processFileEntry',
        'parameters' => 
        array (
          'fileEntry' => 
          array (
            'name' => 'fileEntry',
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
            'startLine' => 161,
            'endLine' => 161,
            'startColumn' => 36,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 161,
            'endLine' => 161,
            'startColumn' => 54,
            'endColumn' => 68,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'userId' => 
          array (
            'name' => 'userId',
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
            'startLine' => 161,
            'endLine' => 161,
            'startColumn' => 71,
            'endColumn' => 84,
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
 * Extract, anonymise and re-file a single freshly-uploaded batch entry.
 *
 * Returns the updated entry; the caller persists the batch afterwards. A
 * prior `_anonymized` output is skipped, and either processing step failing
 * marks the entry as `error` without aborting the batch.
 *
 * @param array<string, mixed> $fileEntry The batch file entry to process.
 * @param string $batchId The batch id, for log context.
 * @param string $userId Owning user id.
 *
 * @return array<string, mixed> The updated file entry.
 *
 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-2
 */',
        'startLine' => 161,
        'endLine' => 214,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\BackgroundJob',
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
        'currentClassName' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
        'aliasName' => NULL,
      ),
      'markEntryFailed' => 
      array (
        'name' => 'markEntryFailed',
        'parameters' => 
        array (
          'fileEntry' => 
          array (
            'name' => 'fileEntry',
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
            'startLine' => 228,
            'endLine' => 228,
            'startColumn' => 35,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 228,
            'endLine' => 228,
            'startColumn' => 53,
            'endColumn' => 67,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
            'startLine' => 228,
            'endLine' => 228,
            'startColumn' => 70,
            'endColumn' => 84,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'error' => 
          array (
            'name' => 'error',
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
            'startLine' => 228,
            'endLine' => 228,
            'startColumn' => 87,
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
 * Mark a batch entry as failed and log the underlying reason.
 *
 * @param array<string, mixed> $fileEntry The batch file entry to mark.
 * @param string $batchId The batch id, for log context.
 * @param string $message The log message describing the failed step.
 * @param string $error The underlying exception message.
 *
 * @return array<string, mixed> The updated file entry.
 *
 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-2
 */',
        'startLine' => 228,
        'endLine' => 238,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\BackgroundJob',
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
        'currentClassName' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
        'aliasName' => NULL,
      ),
      'summariseOutcome' => 
      array (
        'name' => 'summariseOutcome',
        'parameters' => 
        array (
          'files' => 
          array (
            'name' => 'files',
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
            'startLine' => 249,
            'endLine' => 249,
            'startColumn' => 36,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Count anonymised and errored entries for the completion log line.
 *
 * @param array<int|string, array<string, mixed>> $files The batch\'s file entries.
 *
 * @return array{anonymized: int, errors: int} The outcome tally.
 *
 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-2
 */',
        'startLine' => 249,
        'endLine' => 266,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\BackgroundJob',
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
        'currentClassName' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
        'aliasName' => NULL,
      ),
      'applyOutputLayout' => 
      array (
        'name' => 'applyOutputLayout',
        'parameters' => 
        array (
          'fileEntry' => 
          array (
            'name' => 'fileEntry',
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
            'startLine' => 285,
            'endLine' => 285,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'userId' => 
          array (
            'name' => 'userId',
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
            'startLine' => 286,
            'endLine' => 286,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'anonymizedFileId' => 
          array (
            'name' => 'anonymizedFileId',
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
            'startLine' => 287,
            'endLine' => 287,
            'startColumn' => 3,
            'endColumn' => 23,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'legacyPath' => 
          array (
            'name' => 'legacyPath',
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
            'startLine' => 288,
            'endLine' => 288,
            'startColumn' => 3,
            'endColumn' => 20,
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
 * Move the anonymized output into the configured output subfolder.
 *
 * Looks the anonymized node up via the user\'s root folder, resolves the
 * canonical destination via {@see OutputLayoutResolver}, and moves the
 * node there. On success the returned file entry records the new target
 * path; on any failure it keeps the legacy path and attaches a
 * `MOVE_FAILED` warning so the reviewer sees the truth.
 *
 * @param array<string,mixed> $fileEntry The file entry to update.
 * @param string $userId Owning user id.
 * @param int $anonymizedFileId OR file id of the redacted output.
 * @param string $legacyPath Legacy `_anonymized` output path.
 *
 * @return array<string,mixed> The updated file entry.
 */',
        'startLine' => 284,
        'endLine' => 344,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\BackgroundJob',
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
        'currentClassName' => 'OCA\\Filinq\\BackgroundJob\\FolderExtractionJob',
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