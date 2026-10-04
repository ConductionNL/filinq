<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DocumentStorageService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\DocumentStorageService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-3801cc35afbd6d1ad09e9f6b0febf6d7a8d06598ec31187a5893670637d80e47',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\DocumentStorageService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DocumentStorageService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\DocumentStorageService',
    'shortName' => 'DocumentStorageService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service for storing generated documents in a user\'s Nextcloud Files
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-output-destinations-and-bulk-retention/tasks.md#task-1
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 54,
    'endLine' => 315,
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
      'ERROR_CODE_INVALID_PATH' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentStorageService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentStorageService',
        'name' => 'ERROR_CODE_INVALID_PATH',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '400',
          'attributes' => 
          array (
            'startLine' => 61,
            'endLine' => 61,
            'startTokenPos' => 65,
            'startFilePos' => 1870,
            'endTokenPos' => 65,
            'endFilePos' => 1872,
          ),
        ),
        'docComment' => '/**
 * HTTP status code used for a targetPath validation failure.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 61,
        'endLine' => 61,
        'startColumn' => 2,
        'endColumn' => 44,
      ),
      'ERROR_CODE_STORAGE_FAILURE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentStorageService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentStorageService',
        'name' => 'ERROR_CODE_STORAGE_FAILURE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '507',
          'attributes' => 
          array (
            'startLine' => 70,
            'endLine' => 70,
            'startTokenPos' => 78,
            'startFilePos' => 2142,
            'endTokenPos' => 78,
            'endFilePos' => 2144,
          ),
        ),
        'docComment' => '/**
 * HTTP status code used for a storage-layer execution failure
 * (quota exceeded, permission denied, or any other Files-layer error
 * encountered after targetPath validation already passed).
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 70,
        'endLine' => 70,
        'startColumn' => 2,
        'endColumn' => 47,
      ),
      'SEGMENT_PATTERN' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentStorageService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentStorageService',
        'name' => 'SEGMENT_PATTERN',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'/^[A-Za-z0-9 _.\\-]+$/\'',
          'attributes' => 
          array (
            'startLine' => 77,
            'endLine' => 77,
            'startTokenPos' => 91,
            'startFilePos' => 2264,
            'endTokenPos' => 91,
            'endFilePos' => 2286,
          ),
        ),
        'docComment' => '/**
 * Allowed character set for a single path segment.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 77,
        'endLine' => 77,
        'startColumn' => 2,
        'endColumn' => 57,
      ),
    ),
    'immediateProperties' => 
    array (
      'rootFolder' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentStorageService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentStorageService',
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
        'startLine' => 89,
        'endLine' => 89,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentStorageService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentStorageService',
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
        'startLine' => 90,
        'endLine' => 90,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'finalDocuments' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentStorageService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentStorageService',
        'name' => 'finalDocuments',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\FinalDocumentService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 91,
        'endLine' => 91,
        'startColumn' => 3,
        'endColumn' => 55,
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
            'startLine' => 89,
            'endLine' => 89,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 0,
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
            'startLine' => 90,
            'endLine' => 90,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'finalDocuments' => 
          array (
            'name' => 'finalDocuments',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\FinalDocumentService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 91,
            'endLine' => 91,
            'startColumn' => 3,
            'endColumn' => 55,
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
 * Constructor for DocumentStorageService.
 *
 * @param IRootFolder $rootFolder Root folder for per-user file operations
 * @param LoggerInterface $logger Logger for error reporting
 * @param FinalDocumentService $finalDocuments The final-document guard
 *
 * @return void
 */',
        'startLine' => 88,
        'endLine' => 94,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentStorageService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentStorageService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentStorageService',
        'aliasName' => NULL,
      ),
      'validateTargetPath' => 
      array (
        'name' => 'validateTargetPath',
        'parameters' => 
        array (
          'targetPath' => 
          array (
            'name' => 'targetPath',
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
            'startLine' => 110,
            'endLine' => 110,
            'startColumn' => 37,
            'endColumn' => 54,
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
 * Validate a targetPath supplied by the caller.
 *
 * Must be relative (no leading \'/\'), must not contain a \'..\' path
 * segment, and every path segment must match the allowed charset.
 *
 * @param string $targetPath The target path to validate
 *
 * @return void
 *
 * @throws Exception Code 400 if the path is invalid
 *
 * @spec openspec/changes/document-output-destinations-and-bulk-retention/tasks.md#task-1
 */',
        'startLine' => 110,
        'endLine' => 149,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentStorageService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentStorageService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentStorageService',
        'aliasName' => NULL,
      ),
      'store' => 
      array (
        'name' => 'store',
        'parameters' => 
        array (
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
            'startLine' => 174,
            'endLine' => 174,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'targetPath' => 
          array (
            'name' => 'targetPath',
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
            'startLine' => 175,
            'endLine' => 175,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'filename' => 
          array (
            'name' => 'filename',
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
            'startLine' => 176,
            'endLine' => 176,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'content' => 
          array (
            'name' => 'content',
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
            'startLine' => 177,
            'endLine' => 177,
            'startColumn' => 3,
            'endColumn' => 17,
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
 * Store generated document bytes in a user\'s Files.
 *
 * Validates targetPath, creates the destination folder recursively
 * (idempotent — existing segments are reused, never recreated), dedupes
 * the filename via the platform\'s own `name (2).ext` convention, and
 * writes the file.
 *
 * @param string $userId The Nextcloud user id to store the file for
 * @param string $targetPath Relative folder path within the user\'s Files
 * @param string $filename The desired filename (extension included)
 * @param string $content The raw file content to write
 *
 * @return array{fileId: int, path: string, name: string, size: int}
 *
 * @throws DocumentFinalException When a document of this name is already final here
 * @throws Exception Code 400 for an invalid targetPath, code 507 for a
 *                   storage-layer execution failure
 *
 * @spec openspec/changes/document-output-destinations-and-bulk-retention/tasks.md#task-1
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */',
        'startLine' => 173,
        'endLine' => 225,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentStorageService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentStorageService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentStorageService',
        'aliasName' => NULL,
      ),
      'resolveFolder' => 
      array (
        'name' => 'resolveFolder',
        'parameters' => 
        array (
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
            'startLine' => 238,
            'endLine' => 238,
            'startColumn' => 33,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'targetPath' => 
          array (
            'name' => 'targetPath',
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
            'startLine' => 238,
            'endLine' => 238,
            'startColumn' => 49,
            'endColumn' => 66,
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
            'name' => 'OCP\\Files\\Folder',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolve the destination folder for a user, creating any missing
 * path segments idempotently.
 *
 * @param string $userId The Nextcloud user id
 * @param string $targetPath The relative folder path to resolve/create
 *
 * @return Folder The resolved destination folder
 *
 * @throws Exception Code 507 if a path segment exists but is not a folder
 */',
        'startLine' => 238,
        'endLine' => 259,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentStorageService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentStorageService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentStorageService',
        'aliasName' => NULL,
      ),
      'refuseWhenTargetIsFinal' => 
      array (
        'name' => 'refuseWhenTargetIsFinal',
        'parameters' => 
        array (
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
            'startLine' => 285,
            'endLine' => 285,
            'startColumn' => 43,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'targetPath' => 
          array (
            'name' => 'targetPath',
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
            'startColumn' => 59,
            'endColumn' => 76,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'filename' => 
          array (
            'name' => 'filename',
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
            'startColumn' => 79,
            'endColumn' => 94,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Refuse a generation that would land beside a final document of the same name.
 *
 * This is the guard on the merge and the batch correspondence path, which
 * reach Files through this one method. Without it a regeneration writes
 * `besluit (2).docx` next to a frozen `besluit.docx`: nothing is
 * overwritten, and nothing links the two either, so the folder grows a
 * second document that looks like the besluit and is not it. A correction
 * supersedes the final version instead, which keeps the chain readable.
 *
 * A caller with no acting session is normal here: the async bulk job has
 * only a captured user id. The check reads the finalisation record by file
 * id, so it needs no session.
 *
 * @param string $userId The Nextcloud user id to store the file for.
 * @param string $targetPath Relative folder path within the user\'s Files.
 * @param string $filename The desired filename (extension included).
 *
 * @return void
 *
 * @throws DocumentFinalException When a document of this name is already final here.
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */',
        'startLine' => 285,
        'endLine' => 314,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentStorageService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentStorageService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentStorageService',
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