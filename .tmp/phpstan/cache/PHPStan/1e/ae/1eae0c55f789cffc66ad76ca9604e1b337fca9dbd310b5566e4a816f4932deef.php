<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/FolderFileEnumerator.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\FolderFileEnumerator
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-35f1a1091c54f8933057003d2f57b854537d0200f3b3a3e8368c77e9e45dd41b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\FolderFileEnumerator',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/FolderFileEnumerator.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\FolderFileEnumerator',
    'shortName' => 'FolderFileEnumerator',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Resolves a source folder and enumerates its analysable files.
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
    'startLine' => 48,
    'endLine' => 273,
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
      'PRIORITISE_ANALYSIS_KEY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FolderFileEnumerator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FolderFileEnumerator',
        'name' => 'PRIORITISE_ANALYSIS_KEY',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq.confidentiality.prioritise_analysis\'',
          'attributes' => 
          array (
            'startLine' => 57,
            'endLine' => 57,
            'startTokenPos' => 75,
            'startFilePos' => 1797,
            'endTokenPos' => 75,
            'endFilePos' => 1840,
          ),
        ),
        'docComment' => '/**
 * App config key for the optional confidentiality-based analysis-priority
 * hint. Defaults to off — ordering is byte-for-byte identical to the
 * pre-change behaviour until an admin opts in (files-confidential-labels,
 * design.md D3).
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 2,
        'endColumn' => 86,
      ),
    ),
    'immediateProperties' => 
    array (
      'layout' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FolderFileEnumerator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FolderFileEnumerator',
        'name' => 'layout',
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
        'startLine' => 77,
        'endLine' => 77,
        'startColumn' => 3,
        'endColumn' => 47,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'confidentialityLabel' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FolderFileEnumerator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FolderFileEnumerator',
        'name' => 'confidentialityLabel',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
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
        'endColumn' => 68,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'appConfig' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FolderFileEnumerator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FolderFileEnumerator',
        'name' => 'appConfig',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IAppConfig',
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
        'endColumn' => 40,
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
          'layout' => 
          array (
            'name' => 'layout',
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
            'startLine' => 77,
            'endLine' => 77,
            'startColumn' => 3,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'confidentialityLabel' => 
          array (
            'name' => 'confidentialityLabel',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
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
            'endColumn' => 68,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'appConfig' => 
          array (
            'name' => 'appConfig',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IAppConfig',
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
            'endColumn' => 40,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor for FolderFileEnumerator
 *
 * @param OutputLayoutResolver $layout Output-layout helper (used here to
 *                                     identify legacy `_anonymized` outputs
 *                                     and exclude them from source discovery).
 * @param ConfidentialityLabelService $confidentialityLabel Read-only files_confidential signal,
 *                                                          used (only when
 *                                                          `filinq.confidentiality.prioritise_analysis`
 *                                                          is on) as a secondary, tie-breaking
 *                                                          sort key so higher-confidentiality
 *                                                          files are analysed sooner
 *                                                          (files-confidential-labels).
 * @param IAppConfig $appConfig App configuration for the priority-hint flag
 *
 * @return void
 */',
        'startLine' => 76,
        'endLine' => 82,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FolderFileEnumerator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FolderFileEnumerator',
        'currentClassName' => 'OCA\\Filinq\\Service\\FolderFileEnumerator',
        'aliasName' => NULL,
      ),
      'resolveFolder' => 
      array (
        'name' => 'resolveFolder',
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
            'startLine' => 105,
            'endLine' => 105,
            'startColumn' => 32,
            'endColumn' => 45,
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
            'startLine' => 105,
            'endLine' => 105,
            'startColumn' => 48,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'userFolder' => 
          array (
            'name' => 'userFolder',
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
            'startLine' => 105,
            'endLine' => 105,
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
            'name' => 'OCP\\Files\\Folder',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolve the source folder from either a folder ID or folder path
 *
 * Enforces XOR on the inputs (exactly one must be provided). When ID is
 * used, chooses a writable mount first, falling back to the first
 * readable node. When path is used, preserves the existing lookup via
 * Folder::get(). Maps the "not found" case to HTTP 404 for both inputs,
 * and a resolved non-folder node to HTTP 400.
 *
 * @param int|null $folderId Node ID of the folder, or null
 * @param string|null $folderPath Relative path of the folder, or null
 * @param Folder $userFolder The current user\'s root folder
 *
 * @return Folder The resolved folder
 *
 * @throws Exception If neither/both inputs provided (400), folder not found (404),
 *                   or the resolved node is not a folder (400)
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
 * @spec openspec/changes/folder-batch-accept-folder-id/tasks.md#task-4
 */',
        'startLine' => 105,
        'endLine' => 117,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FolderFileEnumerator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FolderFileEnumerator',
        'currentClassName' => 'OCA\\Filinq\\Service\\FolderFileEnumerator',
        'aliasName' => NULL,
      ),
      'resolveFolderNode' => 
      array (
        'name' => 'resolveFolderNode',
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
            'startLine' => 132,
            'endLine' => 132,
            'startColumn' => 37,
            'endColumn' => 50,
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
            'startLine' => 132,
            'endLine' => 132,
            'startColumn' => 53,
            'endColumn' => 71,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'userFolder' => 
          array (
            'name' => 'userFolder',
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
            'startLine' => 132,
            'endLine' => 132,
            'startColumn' => 74,
            'endColumn' => 91,
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
            'name' => 'OCP\\Files\\Node',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolve the folder node from either a folder ID or folder path
 *
 * @param int|null $folderId Node ID of the folder, or null
 * @param string|null $folderPath Relative path of the folder, or null
 * @param Folder $userFolder The current user\'s root folder
 *
 * @return Node The resolved node (type is validated by the caller)
 *
 * @throws Exception If neither/both inputs provided (400), or folder not found (404)
 *
 * @spec openspec/changes/folder-batch-accept-folder-id/tasks.md#task-4
 */',
        'startLine' => 132,
        'endLine' => 159,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FolderFileEnumerator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FolderFileEnumerator',
        'currentClassName' => 'OCA\\Filinq\\Service\\FolderFileEnumerator',
        'aliasName' => NULL,
      ),
      'pickPreferredNode' => 
      array (
        'name' => 'pickPreferredNode',
        'parameters' => 
        array (
          'nodes' => 
          array (
            'name' => 'nodes',
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
            'startColumn' => 37,
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
            'name' => 'OCP\\Files\\Node',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Pick the preferred node when getById returns multiple mounts
 *
 * The same file ID can surface through multiple mounts in one user\'s
 * tree (personal storage + share + group folder). Prefer a writable
 * mount because the batch anonymization flow writes output files back
 * into the source folder; a read-only mount would succeed at extraction
 * but fail at write-back time. Fall back to the first readable node
 * when no writable mount exists — extraction-only use remains valid.
 *
 * @param Node[] $nodes Non-empty array of nodes returned by getById
 *
 * @return Node The preferred node
 */',
        'startLine' => 175,
        'endLine' => 183,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FolderFileEnumerator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FolderFileEnumerator',
        'currentClassName' => 'OCA\\Filinq\\Service\\FolderFileEnumerator',
        'aliasName' => NULL,
      ),
      'enumerate' => 
      array (
        'name' => 'enumerate',
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
            'startLine' => 204,
            'endLine' => 204,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Enumerate direct file children of a folder (flat, no recursion)
 *
 * Files whose base name ends with the legacy `_anonymized` suffix are
 * excluded so a re-run of folder-analysis on a folder that already
 * contains prior anonymisation outputs does not pick up the redacted
 * copies as fresh source material. The discriminator lives on
 * `OutputLayoutResolver::isLegacyAnonymizedOutput()` so the same
 * filter is reused across the folder flow and any future
 * folder-flow integration point.
 *
 * @param Folder $folder The folder to enumerate
 *
 * @return File[] Array of file nodes
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-3
 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md#requirement-optionally-suggest-batchfolder-analysis-priority-req-ddfcl-003
 */',
        'startLine' => 204,
        'endLine' => 220,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FolderFileEnumerator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FolderFileEnumerator',
        'currentClassName' => 'OCA\\Filinq\\Service\\FolderFileEnumerator',
        'aliasName' => NULL,
      ),
      'applyConfidentialityPriorityOrdering' => 
      array (
        'name' => 'applyConfidentialityPriorityOrdering',
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
            'startLine' => 240,
            'endLine' => 240,
            'startColumn' => 56,
            'endColumn' => 67,
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
 * Optionally reorder enumerated files by confidentiality level.
 *
 * A pure suggestion signal: when
 * `filinq.confidentiality.prioritise_analysis` is off (default),
 * returns `$files` untouched — ordering stays byte-for-byte identical to
 * today. When on, sorts by the normalised confidentiality level
 * descending (unlabelled files = level 0), using each file\'s original
 * position as an explicit, deterministic tie-break — it never skips,
 * blocks or redacts anything, it only reorders the work queue
 * (files-confidential-labels, design.md D3).
 *
 * @param File[] $files Enumerated files, in their original (directory-listing) order
 *
 * @return File[] Files in analysis order
 *
 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md#requirement-optionally-suggest-batchfolder-analysis-priority-req-ddfcl-003
 */',
        'startLine' => 240,
        'endLine' => 272,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FolderFileEnumerator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FolderFileEnumerator',
        'currentClassName' => 'OCA\\Filinq\\Service\\FolderFileEnumerator',
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