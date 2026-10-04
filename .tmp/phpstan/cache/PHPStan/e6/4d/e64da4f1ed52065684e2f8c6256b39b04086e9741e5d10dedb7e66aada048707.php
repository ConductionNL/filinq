<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/PrintJobService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\PrintJobService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-b0c6a3aab584074ee357152ec490fa24470e592f358b8d7c6d4a03881e928ffb',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\PrintJobService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/PrintJobService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\PrintJobService',
    'shortName' => 'PrintJobService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service for managing print jobs and batch print generation
 *
 * Handles job creation, status tracking via IAppConfig, and dispatching
 * large batches to the BatchPrintJob background job.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/print-functionality/tasks.md#task-3
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 50,
    'endLine' => 559,
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
      'SYNC_BATCH_LIMIT' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'name' => 'SYNC_BATCH_LIMIT',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '10',
          'attributes' => 
          array (
            'startLine' => 57,
            'endLine' => 57,
            'startTokenPos' => 55,
            'startFilePos' => 1526,
            'endTokenPos' => 55,
            'endFilePos' => 1527,
          ),
        ),
        'docComment' => '/**
 * Maximum items for synchronous batch processing.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 2,
        'endColumn' => 37,
      ),
      'JOB_KEY_PREFIX' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'name' => 'JOB_KEY_PREFIX',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'print_job_\'',
          'attributes' => 
          array (
            'startLine' => 64,
            'endLine' => 64,
            'startTokenPos' => 68,
            'startFilePos' => 1635,
            'endTokenPos' => 68,
            'endFilePos' => 1646,
          ),
        ),
        'docComment' => '/**
 * App config key prefix for print jobs.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 64,
        'endLine' => 64,
        'startColumn' => 2,
        'endColumn' => 45,
      ),
    ),
    'immediateProperties' => 
    array (
      'pdfService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'name' => 'pdfService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\PdfService',
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
        'endColumn' => 41,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'templateSvc' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'name' => 'templateSvc',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\TemplateService',
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
        'endColumn' => 47,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'container' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'name' => 'container',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Psr\\Container\\ContainerInterface',
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
        'endColumn' => 48,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'jobList' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'name' => 'jobList',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\BackgroundJob\\IJobList',
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
        'endColumn' => 36,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
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
        'startLine' => 82,
        'endLine' => 82,
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
          'pdfService' => 
          array (
            'name' => 'pdfService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\PdfService',
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
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'templateSvc' => 
          array (
            'name' => 'templateSvc',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\TemplateService',
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
            'endColumn' => 47,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'container' => 
          array (
            'name' => 'container',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Psr\\Container\\ContainerInterface',
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
            'endColumn' => 48,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'jobList' => 
          array (
            'name' => 'jobList',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\BackgroundJob\\IJobList',
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
            'endColumn' => 36,
            'parameterIndex' => 3,
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
            'startLine' => 82,
            'endLine' => 82,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor for PrintJobService
 *
 * @param PdfService $pdfService Service for PDF generation
 * @param TemplateService $templateSvc Service for template retrieval
 * @param ContainerInterface $container DI container for IAppConfig access
 * @param IJobList $jobList Nextcloud job list for async dispatch
 * @param LoggerInterface $logger Logger for error reporting
 *
 * @return void
 */',
        'startLine' => 77,
        'endLine' => 85,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'aliasName' => NULL,
      ),
      'generateJobId' => 
      array (
        'name' => 'generateJobId',
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
 * Generate a unique job ID
 *
 * @return string UUID v4 format job identifier
 *
 * @spec openspec/changes/print-functionality/tasks.md#task-3
 */',
        'startLine' => 94,
        'endLine' => 107,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'aliasName' => NULL,
      ),
      'createJob' => 
      array (
        'name' => 'createJob',
        'parameters' => 
        array (
          'templateId' => 
          array (
            'name' => 'templateId',
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
            'startLine' => 129,
            'endLine' => 129,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'data' => 
          array (
            'name' => 'data',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 130,
                'endLine' => 130,
                'startTokenPos' => 260,
                'startFilePos' => 3703,
                'endTokenPos' => 261,
                'endFilePos' => 3704,
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
            'startLine' => 130,
            'endLine' => 130,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 131,
                'endLine' => 131,
                'startTokenPos' => 270,
                'startFilePos' => 3726,
                'endTokenPos' => 271,
                'endFilePos' => 3727,
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
            'startLine' => 131,
            'endLine' => 131,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'userId' => 
          array (
            'name' => 'userId',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 132,
                'endLine' => 132,
                'startTokenPos' => 280,
                'startFilePos' => 3749,
                'endTokenPos' => 280,
                'endFilePos' => 3750,
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
            'startLine' => 132,
            'endLine' => 132,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'filename' => 
          array (
            'name' => 'filename',
            'default' => 
            array (
              'code' => '\'document.pdf\'',
              'attributes' => 
              array (
                'startLine' => 133,
                'endLine' => 133,
                'startTokenPos' => 289,
                'startFilePos' => 3774,
                'endTokenPos' => 289,
                'endFilePos' => 3787,
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
            'startLine' => 133,
            'endLine' => 133,
            'startColumn' => 3,
            'endColumn' => 35,
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
 * Create a single-document print job
 *
 * Generates the PDF immediately (synchronous), stores the result and
 * returns job info including the print configuration.
 *
 * @param string $templateId Template UUID to render
 * @param array $data Data context for template rendering
 * @param array $options Print options: format, orientation, pdfa, cropMarks,
 *                       duplex, color, paperTray, stapling, author, caseReference
 * @param string $userId UID of the requesting user (for ownership check)
 * @param string $filename Desired download filename
 *
 * @return array{jobId: string, status: string, printConfig: array}
 *
 * @throws Exception If template retrieval or PDF generation fails
 *
 * @spec openspec/changes/print-functionality/tasks.md#task-3
 */',
        'startLine' => 128,
        'endLine' => 183,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'aliasName' => NULL,
      ),
      'createBatchJob' => 
      array (
        'name' => 'createBatchJob',
        'parameters' => 
        array (
          'templateId' => 
          array (
            'name' => 'templateId',
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
            'startLine' => 203,
            'endLine' => 203,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'items' => 
          array (
            'name' => 'items',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 204,
                'endLine' => 204,
                'startTokenPos' => 702,
                'startFilePos' => 5891,
                'endTokenPos' => 703,
                'endFilePos' => 5892,
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
            'startLine' => 204,
            'endLine' => 204,
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 205,
                'endLine' => 205,
                'startTokenPos' => 712,
                'startFilePos' => 5914,
                'endTokenPos' => 713,
                'endFilePos' => 5915,
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
            'startLine' => 205,
            'endLine' => 205,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'userId' => 
          array (
            'name' => 'userId',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 206,
                'endLine' => 206,
                'startTokenPos' => 722,
                'startFilePos' => 5937,
                'endTokenPos' => 722,
                'endFilePos' => 5938,
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
            'startLine' => 206,
            'endLine' => 206,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 3,
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
 * Create and dispatch a batch print job
 *
 * For batches of SYNC_BATCH_LIMIT or fewer items, generates PDFs synchronously.
 * For larger batches, dispatches a BatchPrintJob background job.
 *
 * @param string $templateId Template UUID to render
 * @param array $items Array of items, each with optional \'data\' and \'filename\' keys
 * @param array $options Print options (same as createJob)
 * @param string $userId UID of the requesting user
 *
 * @return array{jobId: string, status: string, total: int, printConfig: array}
 *
 * @throws Exception If template retrieval fails
 *
 * @spec openspec/changes/print-functionality/tasks.md#task-3
 */',
        'startLine' => 202,
        'endLine' => 226,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'aliasName' => NULL,
      ),
      'processBatchSync' => 
      array (
        'name' => 'processBatchSync',
        'parameters' => 
        array (
          'templateId' => 
          array (
            'name' => 'templateId',
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
            'startLine' => 241,
            'endLine' => 241,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'items' => 
          array (
            'name' => 'items',
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
            'startLine' => 242,
            'endLine' => 242,
            'startColumn' => 3,
            'endColumn' => 14,
            'parameterIndex' => 1,
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
            'startLine' => 243,
            'endLine' => 243,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 2,
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
            'startLine' => 244,
            'endLine' => 244,
            'startColumn' => 3,
            'endColumn' => 16,
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
 * Process a batch synchronously
 *
 * @param string $templateId Template UUID
 * @param array $items Array of items to process
 * @param array $options Print options
 * @param string $userId Requesting user UID
 *
 * @return array{jobId: string, status: string, total: int, printConfig: array}
 *
 * @spec openspec/changes/print-functionality/tasks.md#task-3
 */',
        'startLine' => 240,
        'endLine' => 320,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'aliasName' => NULL,
      ),
      'dispatchBatchJob' => 
      array (
        'name' => 'dispatchBatchJob',
        'parameters' => 
        array (
          'templateId' => 
          array (
            'name' => 'templateId',
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
            'startLine' => 335,
            'endLine' => 335,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'items' => 
          array (
            'name' => 'items',
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
            'startLine' => 336,
            'endLine' => 336,
            'startColumn' => 3,
            'endColumn' => 14,
            'parameterIndex' => 1,
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
            'startLine' => 337,
            'endLine' => 337,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 2,
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
            'startLine' => 338,
            'endLine' => 338,
            'startColumn' => 3,
            'endColumn' => 16,
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
 * Dispatch a large batch to a background job
 *
 * @param string $templateId Template UUID
 * @param array $items Array of items
 * @param array $options Print options
 * @param string $userId Requesting user UID
 *
 * @return array{jobId: string, status: string, total: int, printConfig: array}
 *
 * @spec openspec/changes/print-functionality/tasks.md#task-3
 */',
        'startLine' => 334,
        'endLine' => 374,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'aliasName' => NULL,
      ),
      'getJob' => 
      array (
        'name' => 'getJob',
        'parameters' => 
        array (
          'jobId' => 
          array (
            'name' => 'jobId',
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
            'startLine' => 385,
            'endLine' => 385,
            'startColumn' => 25,
            'endColumn' => 37,
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
 * Retrieve job info including manifest and print config
 *
 * @param string $jobId Job UUID
 *
 * @return array|null Job data or null if not found
 *
 * @spec openspec/changes/print-functionality/tasks.md#task-3
 */',
        'startLine' => 385,
        'endLine' => 387,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'aliasName' => NULL,
      ),
      'buildManifest' => 
      array (
        'name' => 'buildManifest',
        'parameters' => 
        array (
          'items' => 
          array (
            'name' => 'items',
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
            'startLine' => 402,
            'endLine' => 402,
            'startColumn' => 32,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'printConfig' => 
          array (
            'name' => 'printConfig',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 402,
                'endLine' => 402,
                'startTokenPos' => 1736,
                'startFilePos' => 10961,
                'endTokenPos' => 1737,
                'endFilePos' => 10962,
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
            'startLine' => 402,
            'endLine' => 402,
            'startColumn' => 46,
            'endColumn' => 68,
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
 * Build a manifest listing all documents with metadata
 *
 * Per acceptance criterion 2: each entry includes filename, status, and
 * print configuration metadata.
 *
 * @param array $items Array of items with \'filename\' and \'status\' keys
 * @param array $printConfig Print configuration for all items in the batch
 *
 * @return array Manifest array
 *
 * @spec openspec/changes/print-functionality/tasks.md#task-3
 */',
        'startLine' => 402,
        'endLine' => 415,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'aliasName' => NULL,
      ),
      'buildPrintConfig' => 
      array (
        'name' => 'buildPrintConfig',
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
            'startLine' => 426,
            'endLine' => 426,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Extract print configuration keys from options array
 *
 * @param array $options Full options array
 *
 * @return array{duplex: bool, color: bool, paperTray: string, stapling: bool}
 *
 * @spec openspec/changes/print-functionality/tasks.md#task-3
 */',
        'startLine' => 426,
        'endLine' => 434,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'aliasName' => NULL,
      ),
      'storeJobStatus' => 
      array (
        'name' => 'storeJobStatus',
        'parameters' => 
        array (
          'jobId' => 
          array (
            'name' => 'jobId',
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
            'startLine' => 446,
            'endLine' => 446,
            'startColumn' => 33,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'data' => 
          array (
            'name' => 'data',
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
            'startLine' => 446,
            'endLine' => 446,
            'startColumn' => 48,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Store job status in IAppConfig
 *
 * @param string $jobId Job UUID
 * @param array $data Status data to persist
 *
 * @return void
 *
 * @spec openspec/changes/print-functionality/tasks.md#task-3
 */',
        'startLine' => 446,
        'endLine' => 461,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'aliasName' => NULL,
      ),
      'storeJobPdf' => 
      array (
        'name' => 'storeJobPdf',
        'parameters' => 
        array (
          'jobId' => 
          array (
            'name' => 'jobId',
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
            'startLine' => 477,
            'endLine' => 477,
            'startColumn' => 30,
            'endColumn' => 42,
            'parameterIndex' => 0,
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
            'startLine' => 477,
            'endLine' => 477,
            'startColumn' => 45,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Store generated PDF binary for a job
 *
 * PDF content is stored as base64 in IAppConfig to avoid filesystem
 * dependencies. For large batches the BatchPrintJob stores per-item
 * PDFs with suffixed keys (jobId-0, jobId-1, …).
 *
 * @param string $jobId Job UUID (may include item index suffix)
 * @param string $content PDF binary content
 *
 * @return void
 *
 * @spec openspec/changes/print-functionality/tasks.md#task-3
 */',
        'startLine' => 477,
        'endLine' => 492,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'aliasName' => NULL,
      ),
      'loadJobPdf' => 
      array (
        'name' => 'loadJobPdf',
        'parameters' => 
        array (
          'jobId' => 
          array (
            'name' => 'jobId',
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
            'startLine' => 503,
            'endLine' => 503,
            'startColumn' => 29,
            'endColumn' => 41,
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
 * Load a stored job PDF from IAppConfig
 *
 * @param string $jobId Job UUID
 *
 * @return string|null Base64-decoded PDF binary or null if not found
 *
 * @spec openspec/changes/print-functionality/tasks.md#task-3
 */',
        'startLine' => 503,
        'endLine' => 525,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'aliasName' => NULL,
      ),
      'loadJobStatus' => 
      array (
        'name' => 'loadJobStatus',
        'parameters' => 
        array (
          'jobId' => 
          array (
            'name' => 'jobId',
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
            'startLine' => 536,
            'endLine' => 536,
            'startColumn' => 33,
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
 * Load job status from IAppConfig
 *
 * @param string $jobId Job UUID
 *
 * @return array|null Decoded job data or null if not found
 *
 * @spec openspec/changes/print-functionality/tasks.md#task-3
 */',
        'startLine' => 536,
        'endLine' => 558,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PrintJobService',
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