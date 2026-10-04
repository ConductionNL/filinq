<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DossierEntityCollector.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\DossierEntityCollector
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-50ff5a477dbb34d9f5daaf0dcba9ffc2de80aba52c493f7c11a1021d686f5e01',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DossierEntityCollector.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
    'shortName' => 'DossierEntityCollector',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Collects and shapes the anonymised entity rows of a file or dossier.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 45,
    'endLine' => 361,
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
      'OUTPUT_FOLDER_NAMES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'name' => 'OUTPUT_FOLDER_NAMES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'anonymised\', \'anonymized\', \'redacted\']',
          'attributes' => 
          array (
            'startLine' => 56,
            'endLine' => 60,
            'startTokenPos' => 55,
            'startFilePos' => 1765,
            'endTokenPos' => 66,
            'endFilePos' => 1814,
          ),
        ),
        'docComment' => '/**
 * Folder names holding redacted OUTPUT, excluded from the source-file walk.
 *
 * The EntityRelation rows are keyed by the SOURCE file ids, so the
 * redacted outputs produced by `anonymisation-output-folder-layout` must
 * not be walked.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 56,
        'endLine' => 60,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'repository' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'name' => 'repository',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 3,
        'endColumn' => 54,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'labelResolver' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'name' => 'labelResolver',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 74,
        'endLine' => 74,
        'startColumn' => 3,
        'endColumn' => 51,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'ranker' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'name' => 'ranker',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 75,
        'endLine' => 75,
        'startColumn' => 3,
        'endColumn' => 51,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
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
        'startLine' => 76,
        'endLine' => 76,
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
          'repository' => 
          array (
            'name' => 'repository',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 73,
            'endLine' => 73,
            'startColumn' => 3,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'labelResolver' => 
          array (
            'name' => 'labelResolver',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 74,
            'endLine' => 74,
            'startColumn' => 3,
            'endColumn' => 51,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'ranker' => 
          array (
            'name' => 'ranker',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 75,
            'endLine' => 75,
            'startColumn' => 3,
            'endColumn' => 51,
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
            'startLine' => 76,
            'endLine' => 76,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor.
 *
 * @param DossierObjectRepository $repository OpenRegister object access.
 * @param BaseLabelResolver $labelResolver Grondslag label resolution.
 * @param DossierPlaceholderRanker $ranker Placeholder sort keys.
 * @param LoggerInterface $logger Structured logger.
 *
 * @return void
 */',
        'startLine' => 72,
        'endLine' => 79,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'aliasName' => NULL,
      ),
      'walkDossierFiles' => 
      array (
        'name' => 'walkDossierFiles',
        'parameters' => 
        array (
          'folder' => 
          array (
            'name' => 'folder',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Files\\Folder',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 95,
            'endLine' => 95,
            'startColumn' => 35,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'placeholderMap' => 
          array (
            'name' => 'placeholderMap',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 95,
                'endLine' => 95,
                'startTokenPos' => 140,
                'startFilePos' => 3187,
                'endTokenPos' => 141,
                'endFilePos' => 3188,
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
            'startLine' => 95,
            'endLine' => 95,
            'startColumn' => 51,
            'endColumn' => 76,
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
 * Walk every file under the dossier folder and collect its anonymised entities.
 *
 * Folders found inside the dossier folder are recursed; the redacted-output
 * subfolders are skipped.
 *
 * @param Folder $folder The dossier folder.
 * @param array<string, string> $placeholderMap Dossier scope-local placeholder map
 *                                              (global entity id → "[DATUM: 6]")
 *                                              so each file\'s rows render the
 *                                              dossier number, not the global id.
 *
 * @return array<int, array{fileId: int, filename: string, entities: array<int, array<string, mixed>>}> Per-file rows.
 */',
        'startLine' => 95,
        'endLine' => 124,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'aliasName' => NULL,
      ),
      'loadAnonymisedEntitiesForFile' => 
      array (
        'name' => 'loadAnonymisedEntitiesForFile',
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
            'startLine' => 144,
            'endLine' => 144,
            'startColumn' => 48,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'placeholderMap' => 
          array (
            'name' => 'placeholderMap',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 144,
                'endLine' => 144,
                'startTokenPos' => 387,
                'startFilePos' => 5051,
                'endTokenPos' => 388,
                'endFilePos' => 5052,
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
            'startColumn' => 61,
            'endColumn' => 86,
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
 * Load the EntityRelation rows that this service cares about for a file.
 *
 * Filters to relations where `anonymized = true` (the report is "what was
 * redacted under which grondslag" — non-anonymised relations are out of
 * scope) and attaches the resolved base-label list onto each row for the
 * template to render.
 *
 * @param int $fileId The Nextcloud file ID.
 * @param array<string, string> $placeholderMap Optional global entity id → emitted
 *                                              placeholder map; when set, each row uses that
 *                                              placeholder (scope-local number + localized
 *                                              label) instead of the per-document placeholder
 *                                              OpenRegister persisted.
 *
 * @return array<int, array<string, mixed>> Rows shaped as
 *                                          `{placeholder, entityId, entityType, entityText, count, bases, baseLabels, basesText}`.
 */',
        'startLine' => 144,
        'endLine' => 155,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'aliasName' => NULL,
      ),
      'fetchAnonymisedRows' => 
      array (
        'name' => 'fetchAnonymisedRows',
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
            'startLine' => 167,
            'endLine' => 167,
            'startColumn' => 39,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Fetch the raw anonymised EntityRelation rows for one file.
 *
 * Best-effort: an unavailable mapper or a failing query yields an empty
 * set so the report still renders.
 *
 * @param int $fileId The Nextcloud file ID.
 *
 * @return array<int, array<string, mixed>> Raw relation rows.
 */',
        'startLine' => 167,
        'endLine' => 192,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'aliasName' => NULL,
      ),
      'labelMapFor' => 
      array (
        'name' => 'labelMapFor',
        'parameters' => 
        array (
          'rawRows' => 
          array (
            'name' => 'rawRows',
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
            'startLine' => 201,
            'endLine' => 201,
            'startColumn' => 31,
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
 * Resolve every distinct base reference across the rows in one batch.
 *
 * @param array<int, array<string, mixed>> $rawRows Raw relation rows.
 *
 * @return array<string, array{name: string, description: string}> The label map.
 */',
        'startLine' => 201,
        'endLine' => 215,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'aliasName' => NULL,
      ),
      'groupRows' => 
      array (
        'name' => 'groupRows',
        'parameters' => 
        array (
          'rawRows' => 
          array (
            'name' => 'rawRows',
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
            'startLine' => 231,
            'endLine' => 231,
            'startColumn' => 29,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'placeholderMap' => 
          array (
            'name' => 'placeholderMap',
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
            'startLine' => 231,
            'endLine' => 231,
            'startColumn' => 45,
            'endColumn' => 65,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
            'startLine' => 231,
            'endLine' => 231,
            'startColumn' => 68,
            'endColumn' => 78,
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
 * Group raw relation rows by `(entity_type, entity_id)`.
 *
 * Each group carries its scope-local placeholder, the number of relation
 * rows it covers, and the set-union of its base references. Entities with
 * no resolvable scope-local placeholder are omitted and counted for a
 * PII-free log line.
 *
 * @param array<int, array<string, mixed>> $rawRows Raw relation rows.
 * @param array<string, string> $placeholderMap Scope-local placeholder map.
 * @param int $fileId The file id (log context).
 *
 * @return array<string, array<string, mixed>> The grouped entities.
 */',
        'startLine' => 231,
        'endLine' => 280,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'aliasName' => NULL,
      ),
      'resolvePlaceholder' => 
      array (
        'name' => 'resolvePlaceholder',
        'parameters' => 
        array (
          'row' => 
          array (
            'name' => 'row',
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
            'startLine' => 303,
            'endLine' => 303,
            'startColumn' => 38,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'entityId' => 
          array (
            'name' => 'entityId',
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
            'startLine' => 303,
            'endLine' => 303,
            'startColumn' => 50,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'placeholderMap' => 
          array (
            'name' => 'placeholderMap',
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
            'startLine' => 303,
            'endLine' => 303,
            'startColumn' => 65,
            'endColumn' => 85,
            'parameterIndex' => 2,
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
 * Resolve one relation\'s SCOPE-LOCAL placeholder, or null to omit it.
 *
 * We NEVER fall back to the global entity_id: a global id is a relatable
 * cross-disclosure handle, so an entity for which we cannot establish a
 * scope-local placeholder is OMITTED from the summary entirely rather
 * than leaked.
 *
 * The caller-supplied map wins: it is computed for THIS report\'s scope
 * (dossier-wide for the per-dossier report, per-document for the
 * single-file report), so it carries the numbering the reader expects.
 * The placeholder OpenRegister persisted in `anonymized_value` is
 * per-document; it is only a fallback for when no live map is available
 * (e.g. a report regenerated long after anonymisation).
 *
 * @param array<string, mixed> $row One raw relation row.
 * @param int $entityId The row\'s global entity id.
 * @param array<string, string> $placeholderMap Scope-local placeholder map.
 *
 * @return string|null The placeholder, or null when the entity must be omitted.
 */',
        'startLine' => 303,
        'endLine' => 316,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'aliasName' => NULL,
      ),
      'shapeGroups' => 
      array (
        'name' => 'shapeGroups',
        'parameters' => 
        array (
          'grouped' => 
          array (
            'name' => 'grouped',
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
            'startLine' => 330,
            'endLine' => 330,
            'startColumn' => 31,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'labelMap' => 
          array (
            'name' => 'labelMap',
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
            'startLine' => 330,
            'endLine' => 330,
            'startColumn' => 47,
            'endColumn' => 61,
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
 * Shape the grouped entities into the row set the templates render.
 *
 * Rows are ordered by TYPE then NUMERIC id ascending, so the blocks of
 * each type are grouped and ordered 1,2,3,…,10,11 (not the lexical
 * 1,10,11,2 a plain string sort produces). Diff-friendly across re-runs.
 *
 * @param array<string, array<string, mixed>> $grouped The grouped entities.
 * @param array<string, array{name: string, description: string}> $labelMap Resolved base labels.
 *
 * @return array<int, array<string, mixed>> The shaped, sorted rows.
 */',
        'startLine' => 330,
        'endLine' => 360,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
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