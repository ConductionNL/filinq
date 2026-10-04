<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DocumentMergeService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\DocumentMergeService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-b50384a449b14e9ac94110c5adfc3cd5bc587cc8a3d7d04854164407b2bd79c7',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DocumentMergeService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\DocumentMergeService',
    'shortName' => 'DocumentMergeService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Converts, orders, bookmarks and stores one merged PDF.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 57,
    'endLine' => 625,
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
      'SCHEMA' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'name' => 'SCHEMA',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'mergeJob\'',
          'attributes' => 
          array (
            'startLine' => 64,
            'endLine' => 64,
            'startTokenPos' => 70,
            'startFilePos' => 1967,
            'endTokenPos' => 70,
            'endFilePos' => 1976,
          ),
        ),
        'docComment' => '/**
 * The schema holding the jobs.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 64,
        'endLine' => 64,
        'startColumn' => 2,
        'endColumn' => 34,
      ),
      'STATUS_QUEUED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'name' => 'STATUS_QUEUED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'queued\'',
          'attributes' => 
          array (
            'startLine' => 71,
            'endLine' => 71,
            'startTokenPos' => 83,
            'startFilePos' => 2065,
            'endTokenPos' => 83,
            'endFilePos' => 2072,
          ),
        ),
        'docComment' => '/**
 * Queued, not started.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 71,
        'endLine' => 71,
        'startColumn' => 2,
        'endColumn' => 39,
      ),
      'STATUS_RUNNING' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'name' => 'STATUS_RUNNING',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'running\'',
          'attributes' => 
          array (
            'startLine' => 78,
            'endLine' => 78,
            'startTokenPos' => 96,
            'startFilePos' => 2150,
            'endTokenPos' => 96,
            'endFilePos' => 2158,
          ),
        ),
        'docComment' => '/**
 * Running.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 78,
        'endLine' => 78,
        'startColumn' => 2,
        'endColumn' => 41,
      ),
      'STATUS_DONE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'name' => 'STATUS_DONE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'done\'',
          'attributes' => 
          array (
            'startLine' => 85,
            'endLine' => 85,
            'startTokenPos' => 109,
            'startFilePos' => 2254,
            'endTokenPos' => 109,
            'endFilePos' => 2259,
          ),
        ),
        'docComment' => '/**
 * Finished, with a result file.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 85,
        'endLine' => 85,
        'startColumn' => 2,
        'endColumn' => 35,
      ),
      'STATUS_FAILED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'name' => 'STATUS_FAILED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'failed\'',
          'attributes' => 
          array (
            'startLine' => 92,
            'endLine' => 92,
            'startTokenPos' => 122,
            'startFilePos' => 2370,
            'endTokenPos' => 122,
            'endFilePos' => 2377,
          ),
        ),
        'docComment' => '/**
 * Stopped, with a reason and no result file.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 92,
        'endLine' => 92,
        'startColumn' => 2,
        'endColumn' => 39,
      ),
    ),
    'immediateProperties' => 
    array (
      'jobs' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'name' => 'jobs',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\MergeJobRepository',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 109,
        'endLine' => 109,
        'startColumn' => 3,
        'endColumn' => 43,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'conversion' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'name' => 'conversion',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\PdfConversionService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 110,
        'endLine' => 110,
        'startColumn' => 3,
        'endColumn' => 51,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'archival' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'name' => 'archival',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 111,
        'endLine' => 111,
        'startColumn' => 3,
        'endColumn' => 51,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'documents' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'name' => 'documents',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\PdfDocumentFactory',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 112,
        'endLine' => 112,
        'startColumn' => 3,
        'endColumn' => 48,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'cover' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'name' => 'cover',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\MergeCoverRenderer',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 113,
        'endLine' => 113,
        'startColumn' => 3,
        'endColumn' => 44,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'rootFolder' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
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
        'startLine' => 114,
        'endLine' => 114,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'userSession' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'name' => 'userSession',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IUserSession',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 115,
        'endLine' => 115,
        'startColumn' => 3,
        'endColumn' => 44,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
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
        'startLine' => 116,
        'endLine' => 116,
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
          'jobs' => 
          array (
            'name' => 'jobs',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\MergeJobRepository',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 109,
            'endLine' => 109,
            'startColumn' => 3,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'conversion' => 
          array (
            'name' => 'conversion',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\PdfConversionService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 110,
            'endLine' => 110,
            'startColumn' => 3,
            'endColumn' => 51,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'archival' => 
          array (
            'name' => 'archival',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 111,
            'endLine' => 111,
            'startColumn' => 3,
            'endColumn' => 51,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'documents' => 
          array (
            'name' => 'documents',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\PdfDocumentFactory',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 112,
            'endLine' => 112,
            'startColumn' => 3,
            'endColumn' => 48,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'cover' => 
          array (
            'name' => 'cover',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\MergeCoverRenderer',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 113,
            'endLine' => 113,
            'startColumn' => 3,
            'endColumn' => 44,
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
            'startLine' => 114,
            'endLine' => 114,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
          'userSession' => 
          array (
            'name' => 'userSession',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IUserSession',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 115,
            'endLine' => 115,
            'startColumn' => 3,
            'endColumn' => 44,
            'parameterIndex' => 6,
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
            'startLine' => 116,
            'endLine' => 116,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 7,
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
 * @param MergeJobRepository $jobs The job store.
 * @param PdfConversionService $conversion Converts one input to PDF, and says which backend did it.
 * @param Pdfa3ConversionService $archival Turns the merged PDF into PDF/A-3b, where the instance can.
 * @param PdfDocumentFactory $documents Builds the FPDI document the pages go into.
 * @param MergeCoverRenderer $cover Renders the cover page, when one is asked for.
 * @param IRootFolder $rootFolder The file tree.
 * @param IUserSession $userSession The current session; every input is read as this person.
 * @param LoggerInterface $logger Logger for diagnostics.
 *
 * @return void
 */',
        'startLine' => 108,
        'endLine' => 119,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'aliasName' => NULL,
      ),
      'merge' => 
      array (
        'name' => 'merge',
        'parameters' => 
        array (
          'inputs' => 
          array (
            'name' => 'inputs',
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
            'startLine' => 134,
            'endLine' => 134,
            'startColumn' => 24,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 134,
                'endLine' => 134,
                'startTokenPos' => 232,
                'startFilePos' => 4141,
                'endTokenPos' => 233,
                'endFilePos' => 4142,
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
            'startLine' => 134,
            'endLine' => 134,
            'startColumn' => 39,
            'endColumn' => 57,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'hostObject' => 
          array (
            'name' => 'hostObject',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 134,
                'endLine' => 134,
                'startTokenPos' => 242,
                'startFilePos' => 4165,
                'endTokenPos' => 243,
                'endFilePos' => 4166,
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
            'startLine' => 134,
            'endLine' => 134,
            'startColumn' => 60,
            'endColumn' => 81,
            'parameterIndex' => 2,
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
 * Merge a selection into one PDF.
 *
 * @param array<int, array<string, mixed>> $inputs The documents, in order, each `{fileId, label}`.
 * @param array<string, mixed> $options The cover template, the bookmarks toggle, the target folder and the name.
 * @param array<string, mixed> $hostObject The object the merge was started from.
 *
 * @return array<string, mixed> The finished job.
 *
 * @throws MergeRefusedException When the caller may not read an input or write the target.
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */',
        'startLine' => 134,
        'endLine' => 151,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'aliasName' => NULL,
      ),
      'queue' => 
      array (
        'name' => 'queue',
        'parameters' => 
        array (
          'inputs' => 
          array (
            'name' => 'inputs',
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
            'startLine' => 170,
            'endLine' => 170,
            'startColumn' => 24,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 170,
                'endLine' => 170,
                'startTokenPos' => 433,
                'startFilePos' => 5534,
                'endTokenPos' => 434,
                'endFilePos' => 5535,
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
            'startLine' => 170,
            'endLine' => 170,
            'startColumn' => 39,
            'endColumn' => 57,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'hostObject' => 
          array (
            'name' => 'hostObject',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 170,
                'endLine' => 170,
                'startTokenPos' => 443,
                'startFilePos' => 5558,
                'endTokenPos' => 444,
                'endFilePos' => 5559,
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
            'startLine' => 170,
            'endLine' => 170,
            'startColumn' => 60,
            'endColumn' => 81,
            'parameterIndex' => 2,
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
 * Record a merge to be run later, once the checks have passed.
 *
 * The checks run HERE, while the person who asked is still on the other end
 * of the request. A queued job is the record that they passed, which is why
 * the background job does not repeat them as somebody else.
 *
 * @param array<int, array<string, mixed>> $inputs The documents, in order.
 * @param array<string, mixed> $options What is asked for.
 * @param array<string, mixed> $hostObject The object the merge was started from.
 *
 * @return array<string, mixed> The queued job.
 *
 * @throws MergeRefusedException When the caller may not read an input or write the target.
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */',
        'startLine' => 170,
        'endLine' => 185,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'aliasName' => NULL,
      ),
      'resume' => 
      array (
        'name' => 'resume',
        'parameters' => 
        array (
          'job' => 
          array (
            'name' => 'job',
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
            'startLine' => 203,
            'endLine' => 203,
            'startColumn' => 25,
            'endColumn' => 34,
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
 * Run a job that was queued earlier.
 *
 * Every input is resolved as the person who ASKED, not as whoever the
 * background runner happens to be. A merge that ran as an administrator
 * would quietly include files the requester could not read, and the bundle
 * would look exactly the same.
 *
 * @param array<string, mixed> $job The queued job.
 *
 * @return array<string, mixed> The finished job.
 *
 * @throws MergeRefusedException When the inputs no longer resolve for that person.
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */',
        'startLine' => 203,
        'endLine' => 223,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'aliasName' => NULL,
      ),
      'shouldQueue' => 
      array (
        'name' => 'shouldQueue',
        'parameters' => 
        array (
          'inputs' => 
          array (
            'name' => 'inputs',
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
            'startColumn' => 30,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'threshold' => 
          array (
            'name' => 'threshold',
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
            'startLine' => 241,
            'endLine' => 241,
            'startColumn' => 45,
            'endColumn' => 58,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Whether a selection is large enough to be queued rather than waited for.
 *
 * The page count is an ESTIMATE: the real one is only known after every
 * input has been converted, and converting them to decide whether to
 * convert them in the background defeats the point. A selection that says
 * how many pages each input has is believed; one that does not is estimated
 * from the file sizes.
 *
 * @param array<int, array<string, mixed>> $inputs The requested inputs.
 * @param int $threshold The page count above which a merge is queued.
 *
 * @return bool True when the merge should be queued.
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */',
        'startLine' => 241,
        'endLine' => 260,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'aliasName' => NULL,
      ),
      'run' => 
      array (
        'name' => 'run',
        'parameters' => 
        array (
          'job' => 
          array (
            'name' => 'job',
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
            'startLine' => 274,
            'endLine' => 274,
            'startColumn' => 23,
            'endColumn' => 32,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'resolved' => 
          array (
            'name' => 'resolved',
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
            'startLine' => 274,
            'endLine' => 274,
            'startColumn' => 35,
            'endColumn' => 49,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
            'startLine' => 274,
            'endLine' => 274,
            'startColumn' => 52,
            'endColumn' => 65,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
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
            'startLine' => 274,
            'endLine' => 274,
            'startColumn' => 68,
            'endColumn' => 81,
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
 * Run a job whose checks have already passed.
 *
 * @param array<string, mixed> $job The job, already stored.
 * @param array<int, array<string, mixed>> $resolved The resolved inputs.
 * @param Folder $folder The folder the result goes in.
 * @param array<string, mixed> $options What was asked for.
 *
 * @return array<string, mixed> The finished job.
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */',
        'startLine' => 274,
        'endLine' => 368,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'aliasName' => NULL,
      ),
      'toArchival' => 
      array (
        'name' => 'toArchival',
        'parameters' => 
        array (
          'file' => 
          array (
            'name' => 'file',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Files\\File',
                'isIdentifier' => false,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Turn the merged file into PDF/A-3b where the instance can.
 *
 * An instance without the archival toolchain gets an ordinary PDF and the
 * job SAYS so. Failing the whole merge because a binary is missing would
 * lose the bundle; claiming PDF/A-3b that was never produced would be
 * worse than either.
 *
 * @param File $file The merged file.
 *
 * @return string `pdfa-3b` or `pdf`.
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */',
        'startLine' => 384,
        'endLine' => 414,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'aliasName' => NULL,
      ),
      'fail' => 
      array (
        'name' => 'fail',
        'parameters' => 
        array (
          'uuid' => 
          array (
            'name' => 'uuid',
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
            'startLine' => 427,
            'endLine' => 427,
            'startColumn' => 24,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'job' => 
          array (
            'name' => 'job',
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
            'startLine' => 427,
            'endLine' => 427,
            'startColumn' => 38,
            'endColumn' => 47,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'reason' => 
          array (
            'name' => 'reason',
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
            'startLine' => 427,
            'endLine' => 427,
            'startColumn' => 50,
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
 * Stop a job, naming what went wrong, and leave no file behind.
 *
 * @param string $uuid The job.
 * @param array<string, mixed> $job The job as it stands.
 * @param string $reason What went wrong.
 *
 * @return array<string, mixed> The failed job.
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */',
        'startLine' => 427,
        'endLine' => 438,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'aliasName' => NULL,
      ),
      'resolveInputs' => 
      array (
        'name' => 'resolveInputs',
        'parameters' => 
        array (
          'inputs' => 
          array (
            'name' => 'inputs',
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
            'startLine' => 452,
            'endLine' => 452,
            'startColumn' => 33,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'owner' => 
          array (
            'name' => 'owner',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 452,
                'endLine' => 452,
                'startTokenPos' => 2194,
                'startFilePos' => 14622,
                'endTokenPos' => 2194,
                'endFilePos' => 14623,
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
            'startLine' => 452,
            'endLine' => 452,
            'startColumn' => 48,
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
 * Resolve every input to a file the caller can actually read.
 *
 * @param array<int, array<string, mixed>> $inputs The requested inputs.
 * @param string $owner The person the inputs are read as, or an empty string for the session user.
 *
 * @return array<int, array<string, mixed>> The resolved inputs, in order.
 *
 * @throws MergeRefusedException When an input is missing, unreadable or absent from the request.
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */',
        'startLine' => 452,
        'endLine' => 499,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'aliasName' => NULL,
      ),
      'resolveTarget' => 
      array (
        'name' => 'resolveTarget',
        'parameters' => 
        array (
          'options' => 
          array (
            'name' => 'options',
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
            'startLine' => 514,
            'endLine' => 514,
            'startColumn' => 33,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'first' => 
          array (
            'name' => 'first',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Files\\File',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 514,
            'endLine' => 514,
            'startColumn' => 49,
            'endColumn' => 59,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'owner' => 
          array (
            'name' => 'owner',
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
            'startLine' => 514,
            'endLine' => 514,
            'startColumn' => 62,
            'endColumn' => 74,
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
 * The folder the result goes in, checked for write.
 *
 * @param array<string, mixed> $options What was asked for.
 * @param File $first The first input, whose folder is the default.
 * @param string $owner The person the folder is resolved as.
 *
 * @return Folder The target folder.
 *
 * @throws MergeRefusedException When the caller may not write there.
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */',
        'startLine' => 514,
        'endLine' => 542,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'aliasName' => NULL,
      ),
      'requireWritable' => 
      array (
        'name' => 'requireWritable',
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
            'startLine' => 555,
            'endLine' => 555,
            'startColumn' => 35,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Refuse a folder the caller cannot write.
 *
 * @param Folder $folder The folder.
 *
 * @return void
 *
 * @throws MergeRefusedException When it is not writable.
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */',
        'startLine' => 555,
        'endLine' => 565,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'aliasName' => NULL,
      ),
      'inputSummary' => 
      array (
        'name' => 'inputSummary',
        'parameters' => 
        array (
          'resolved' => 
          array (
            'name' => 'resolved',
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
            'startLine' => 576,
            'endLine' => 576,
            'startColumn' => 32,
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
 * The inputs as they are stored on the job: no file handles, just the order.
 *
 * @param array<int, array<string, mixed>> $resolved The resolved inputs.
 *
 * @return array<int, array<string, mixed>> The stored shape.
 *
 * @spec exclude Shape adapter with no behaviour of its own.
 */',
        'startLine' => 576,
        'endLine' => 584,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'aliasName' => NULL,
      ),
      'resultName' => 
      array (
        'name' => 'resultName',
        'parameters' => 
        array (
          'options' => 
          array (
            'name' => 'options',
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
            'startLine' => 595,
            'endLine' => 595,
            'startColumn' => 30,
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
 * The name the result is written under.
 *
 * @param array<string, mixed> $options What was asked for.
 *
 * @return string The file name, always ending in .pdf.
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */',
        'startLine' => 595,
        'endLine' => 607,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'aliasName' => NULL,
      ),
      'currentUserId' => 
      array (
        'name' => 'currentUserId',
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
 * The user id of the person at the keyboard.
 *
 * @return string The user id, or an empty string when there is no session.
 *
 * @spec exclude Session accessor with no behaviour of its own.
 */',
        'startLine' => 616,
        'endLine' => 624,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentMergeService',
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