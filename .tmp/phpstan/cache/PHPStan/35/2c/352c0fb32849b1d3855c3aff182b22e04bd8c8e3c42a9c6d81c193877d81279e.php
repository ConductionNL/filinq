<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/CorrespondenceService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\CorrespondenceService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-10ecf768767e13ebe010826835a0eb6fd0f11340f579607c79361d2a7ced3274',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/CorrespondenceService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\CorrespondenceService',
    'shortName' => 'CorrespondenceService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service for generating correspondence from templates with recipient data
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 *
 * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-2
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 57,
    'endLine' => 866,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'name' => 'SYNC_BATCH_LIMIT',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '10',
          'attributes' => 
          array (
            'startLine' => 64,
            'endLine' => 64,
            'startTokenPos' => 75,
            'startFilePos' => 2333,
            'endTokenPos' => 75,
            'endFilePos' => 2334,
          ),
        ),
        'docComment' => '/**
 * Maximum number of recipients for synchronous batch processing
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 64,
        'endLine' => 64,
        'startColumn' => 2,
        'endColumn' => 37,
      ),
      'DEFAULT_FORMAT' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'name' => 'DEFAULT_FORMAT',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'pdf\'',
          'attributes' => 
          array (
            'startLine' => 71,
            'endLine' => 71,
            'startTokenPos' => 88,
            'startFilePos' => 2426,
            'endTokenPos' => 88,
            'endFilePos' => 2430,
          ),
        ),
        'docComment' => '/**
 * Default output format
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 71,
        'endLine' => 71,
        'startColumn' => 2,
        'endColumn' => 38,
      ),
      'VALID_FORMATS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'name' => 'VALID_FORMATS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'pdf\', \'docx\', \'html\', \'email\']',
          'attributes' => 
          array (
            'startLine' => 78,
            'endLine' => 78,
            'startTokenPos' => 101,
            'startFilePos' => 2522,
            'endTokenPos' => 112,
            'endFilePos' => 2553,
          ),
        ),
        'docComment' => '/**
 * Valid output formats
 *
 * @var string[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 78,
        'endLine' => 78,
        'startColumn' => 2,
        'endColumn' => 64,
      ),
    ),
    'immediateProperties' => 
    array (
      'templateService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'name' => 'templateService',
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
        'startLine' => 96,
        'endLine' => 96,
        'startColumn' => 3,
        'endColumn' => 51,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'dataResolver' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'name' => 'dataResolver',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\DataResolverService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 97,
        'endLine' => 97,
        'startColumn' => 3,
        'endColumn' => 52,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'templateRenderer' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'name' => 'templateRenderer',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\TemplateRenderer',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 98,
        'endLine' => 98,
        'startColumn' => 3,
        'endColumn' => 53,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'pdfService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
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
        'startLine' => 99,
        'endLine' => 99,
        'startColumn' => 3,
        'endColumn' => 41,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'container' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
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
        'startLine' => 100,
        'endLine' => 100,
        'startColumn' => 3,
        'endColumn' => 48,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'appManager' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'name' => 'appManager',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\App\\IAppManager',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 101,
        'endLine' => 101,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'jobList' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
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
        'startLine' => 102,
        'endLine' => 102,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
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
        'startLine' => 103,
        'endLine' => 103,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'appConfig' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
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
        'startLine' => 104,
        'endLine' => 104,
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
          'templateService' => 
          array (
            'name' => 'templateService',
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
            'startLine' => 96,
            'endLine' => 96,
            'startColumn' => 3,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'dataResolver' => 
          array (
            'name' => 'dataResolver',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\DataResolverService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 97,
            'endLine' => 97,
            'startColumn' => 3,
            'endColumn' => 52,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'templateRenderer' => 
          array (
            'name' => 'templateRenderer',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\TemplateRenderer',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 98,
            'endLine' => 98,
            'startColumn' => 3,
            'endColumn' => 53,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
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
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 3,
            'endColumn' => 41,
            'parameterIndex' => 3,
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
            'startLine' => 100,
            'endLine' => 100,
            'startColumn' => 3,
            'endColumn' => 48,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'appManager' => 
          array (
            'name' => 'appManager',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\App\\IAppManager',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 101,
            'endLine' => 101,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 5,
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
            'startLine' => 102,
            'endLine' => 102,
            'startColumn' => 3,
            'endColumn' => 36,
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
            'startLine' => 103,
            'endLine' => 103,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 7,
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
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 3,
            'endColumn' => 40,
            'parameterIndex' => 8,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor for CorrespondenceService
 *
 * @param TemplateService $templateService Service for template CRUD
 * @param DataResolverService $dataResolver Service for data resolution
 * @param TemplateRenderer $templateRenderer Service for Twig rendering
 * @param PdfService $pdfService Service for PDF generation
 * @param ContainerInterface $container Container for DI
 * @param IAppManager $appManager App manager interface
 * @param IJobList $jobList Nextcloud job list for async
 * @param LoggerInterface $logger Logger for error reporting
 * @param IAppConfig $appConfig App configuration accessor
 *
 * @return void
 */',
        'startLine' => 95,
        'endLine' => 107,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'aliasName' => NULL,
      ),
      'getSyncBatchLimit' => 
      array (
        'name' => 'getSyncBatchLimit',
        'parameters' => 
        array (
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
 * Resolve the configured sync-batch limit, falling back to the constant.
 *
 * Reads `filinq.correspondence.sync_batch_limit` (canonical key declared
 * in manifest.yaml under docudesk-adopt-or-abstractions task 11).
 *
 * @return int
 */',
        'startLine' => 117,
        'endLine' => 128,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'aliasName' => NULL,
      ),
      'getDefaultFormat' => 
      array (
        'name' => 'getDefaultFormat',
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
 * Resolve the configured default output format, falling back to the constant.
 *
 * Reads `filinq.correspondence.default_format` (canonical key declared in
 * manifest.yaml under docudesk-adopt-or-abstractions task 11).
 *
 * @return string
 */',
        'startLine' => 138,
        'endLine' => 149,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'aliasName' => NULL,
      ),
      'getObjectService' => 
      array (
        'name' => 'getObjectService',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\OpenRegister\\Service\\ObjectService',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the ObjectService from OpenRegister
 *
 * @return \\OCA\\OpenRegister\\Service\\ObjectService The ObjectService instance
 *
 * @throws RuntimeException If OpenRegister is not available
 */',
        'startLine' => 158,
        'endLine' => 169,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'aliasName' => NULL,
      ),
      'generate' => 
      array (
        'name' => 'generate',
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
            'startLine' => 205,
            'endLine' => 205,
            'startColumn' => 27,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'dataRefs' => 
          array (
            'name' => 'dataRefs',
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
            'startLine' => 205,
            'endLine' => 205,
            'startColumn' => 47,
            'endColumn' => 61,
            'parameterIndex' => 1,
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
                'startLine' => 205,
                'endLine' => 205,
                'startTokenPos' => 527,
                'startFilePos' => 6942,
                'endTokenPos' => 528,
                'endFilePos' => 6943,
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
            'startColumn' => 64,
            'endColumn' => 82,
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
          0 => 
          array (
            'name' => 'OCA\\OpenRegister\\Mcp\\Attribute\\McpTool',
            'isRepeated' => false,
            'arguments' => 
            array (
              'name' => 
              array (
                'code' => '\'generateCorrespondence\'',
                'attributes' => 
                array (
                  'startLine' => 190,
                  'endLine' => 190,
                  'startTokenPos' => 438,
                  'startFilePos' => 6145,
                  'endTokenPos' => 438,
                  'endFilePos' => 6168,
                ),
              ),
              'subject' => 
              array (
                'code' => '\'correspondence\'',
                'attributes' => 
                array (
                  'startLine' => 193,
                  'endLine' => 193,
                  'startTokenPos' => 448,
                  'startFilePos' => 6326,
                  'endTokenPos' => 448,
                  'endFilePos' => 6341,
                ),
              ),
              'action' => 
              array (
                'code' => '\'generate\'',
                'attributes' => 
                array (
                  'startLine' => 194,
                  'endLine' => 194,
                  'startTokenPos' => 454,
                  'startFilePos' => 6354,
                  'endTokenPos' => 454,
                  'endFilePos' => 6363,
                ),
              ),
              'description' => 
              array (
                'code' => '\'Generate one letter from a Filinq template for one recipient: resolves the \' . \'recipient\\\'s data from OpenRegister, applies the organisation huisstijl, renders the \' . \'template and logs the result to the correspondence register. Use searchTemplate first \' . \'to find the template id. Generates a single letter only -- batch mail-merge is not \' . \'available to agents.\'',
                'attributes' => 
                array (
                  'startLine' => 195,
                  'endLine' => 199,
                  'startTokenPos' => 460,
                  'startFilePos' => 6381,
                  'endTokenPos' => 476,
                  'endFilePos' => 6763,
                ),
              ),
              'readOnlyHint' => 
              array (
                'code' => 'false',
                'attributes' => 
                array (
                  'startLine' => 200,
                  'endLine' => 200,
                  'startTokenPos' => 482,
                  'startFilePos' => 6782,
                  'endTokenPos' => 482,
                  'endFilePos' => 6786,
                ),
              ),
              'destructiveHint' => 
              array (
                'code' => 'false',
                'attributes' => 
                array (
                  'startLine' => 201,
                  'endLine' => 201,
                  'startTokenPos' => 488,
                  'startFilePos' => 6808,
                  'endTokenPos' => 488,
                  'endFilePos' => 6812,
                ),
              ),
              'idempotentHint' => 
              array (
                'code' => 'false',
                'attributes' => 
                array (
                  'startLine' => 202,
                  'endLine' => 202,
                  'startTokenPos' => 494,
                  'startFilePos' => 6833,
                  'endTokenPos' => 494,
                  'endFilePos' => 6837,
                ),
              ),
              'scope' => 
              array (
                'code' => '\'create\'',
                'attributes' => 
                array (
                  'startLine' => 203,
                  'endLine' => 203,
                  'startTokenPos' => 500,
                  'startFilePos' => 6849,
                  'endTokenPos' => 500,
                  'endFilePos' => 6856,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * Generate a single correspondence document
 *
 * Fetches the template, resolves recipient data, applies huisstijl,
 * renders the template, produces the output, and logs to the register.
 *
 * @param string $templateId The UUID of the template to use
 * @param array $dataRefs Array of data references with register/schema/id
 * @param array $options Options: format (pdf|docx|html|email),
 *                       huisstijlId, caseReference, recipientType, userId
 *
 * @return array{content: string, format: string, warnings: array, registerEntry: array}
 *
 * @throws Exception If generation fails
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-correspondence-generation-api
 * @spec openspec/changes/filinq-mcp-adoption/tasks.md#task-2-1
 */',
        'startLine' => 189,
        'endLine' => 264,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'aliasName' => NULL,
      ),
      'generateBatch' => 
      array (
        'name' => 'generateBatch',
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
            'startLine' => 282,
            'endLine' => 282,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'recipientIds' => 
          array (
            'name' => 'recipientIds',
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
            'startLine' => 283,
            'endLine' => 283,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 1,
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
                'startLine' => 284,
                'endLine' => 284,
                'startTokenPos' => 958,
                'startFilePos' => 9311,
                'endTokenPos' => 959,
                'endFilePos' => 9312,
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
            'startLine' => 284,
            'endLine' => 284,
            'startColumn' => 3,
            'endColumn' => 21,
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
 * Generate correspondence for a batch of recipients
 *
 * For batches of 10 or fewer, generation is synchronous.
 * For larger batches, a background job is dispatched.
 *
 * @param string $templateId The UUID of the template to use
 * @param array $recipientIds Array of recipient object UUIDs
 * @param array $options Options: format, register, schema, huisstijlId,
 *                       caseReference, recipientType, userId
 *
 * @return array Synchronous results or job info
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
 */',
        'startLine' => 281,
        'endLine' => 302,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'aliasName' => NULL,
      ),
      'generateBatchSync' => 
      array (
        'name' => 'generateBatchSync',
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
            'startLine' => 316,
            'endLine' => 316,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'recipientIds' => 
          array (
            'name' => 'recipientIds',
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
            'startLine' => 317,
            'endLine' => 317,
            'startColumn' => 3,
            'endColumn' => 21,
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
            'startLine' => 318,
            'endLine' => 318,
            'startColumn' => 3,
            'endColumn' => 16,
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
 * Process a batch synchronously
 *
 * @param string $templateId The template UUID
 * @param array $recipientIds Array of recipient UUIDs
 * @param array $options Generation options
 *
 * @return array{results: array, total: int, completed: int, errors: int}
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
 */',
        'startLine' => 315,
        'endLine' => 380,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
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
            'startLine' => 394,
            'endLine' => 394,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'recipientIds' => 
          array (
            'name' => 'recipientIds',
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
            'startLine' => 395,
            'endLine' => 395,
            'startColumn' => 3,
            'endColumn' => 21,
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
            'startLine' => 396,
            'endLine' => 396,
            'startColumn' => 3,
            'endColumn' => 16,
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
 * Dispatch a batch generation as a background job
 *
 * @param string $templateId The template UUID
 * @param array $recipientIds Array of recipient UUIDs
 * @param array $options Generation options
 *
 * @return array{jobId: string, status: string, totalRecipients: int}
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
 */',
        'startLine' => 393,
        'endLine' => 432,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'aliasName' => NULL,
      ),
      'getJobStatus' => 
      array (
        'name' => 'getJobStatus',
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
            'startLine' => 443,
            'endLine' => 443,
            'startColumn' => 31,
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
 * Get the status of a batch job
 *
 * @param string $jobId The job UUID
 *
 * @return array|null The job status or null if not found
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-rest-endpoints
 */',
        'startLine' => 443,
        'endLine' => 445,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
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
            'startLine' => 457,
            'endLine' => 457,
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
            'startLine' => 457,
            'endLine' => 457,
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
 * Update the status of a batch job
 *
 * @param string $jobId The job UUID
 * @param array $data The status data to store
 *
 * @return void
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
 */',
        'startLine' => 457,
        'endLine' => 473,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
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
            'startLine' => 484,
            'endLine' => 484,
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
 * Load the status of a batch job from app config
 *
 * @param string $jobId The job UUID
 *
 * @return array|null The job status or null if not found
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-rest-endpoints
 */',
        'startLine' => 484,
        'endLine' => 507,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'aliasName' => NULL,
      ),
      'validateFormat' => 
      array (
        'name' => 'validateFormat',
        'parameters' => 
        array (
          'format' => 
          array (
            'name' => 'format',
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
            'startLine' => 520,
            'endLine' => 520,
            'startColumn' => 34,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Validate the requested output format
 *
 * @param string $format The format to validate
 *
 * @return void
 *
 * @throws Exception If the format is not valid
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-output-format-selection
 */',
        'startLine' => 520,
        'endLine' => 529,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'aliasName' => NULL,
      ),
      'loadHuisstijl' => 
      array (
        'name' => 'loadHuisstijl',
        'parameters' => 
        array (
          'huisstijlId' => 
          array (
            'name' => 'huisstijlId',
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
            'startLine' => 540,
            'endLine' => 540,
            'startColumn' => 33,
            'endColumn' => 52,
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
 * Load huisstijl configuration from OpenRegister
 *
 * @param string|null $huisstijlId Optional specific huisstijl UUID
 *
 * @return array|null The huisstijl configuration or null if not found
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-huisstijl-default-configuration
 */',
        'startLine' => 540,
        'endLine' => 572,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'aliasName' => NULL,
      ),
      'buildPdfOptions' => 
      array (
        'name' => 'buildPdfOptions',
        'parameters' => 
        array (
          'template' => 
          array (
            'name' => 'template',
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
            'startLine' => 585,
            'endLine' => 585,
            'startColumn' => 35,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'huisstijl' => 
          array (
            'name' => 'huisstijl',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 585,
            'endLine' => 585,
            'startColumn' => 52,
            'endColumn' => 68,
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
            'startLine' => 585,
            'endLine' => 585,
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
 * Build PDF options from template, huisstijl, and request options
 *
 * @param array $template The template object
 * @param array|null $huisstijl The huisstijl configuration
 * @param array $options The request options
 *
 * @return array The merged PDF options
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-huisstijl-default-configuration
 */',
        'startLine' => 585,
        'endLine' => 602,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'aliasName' => NULL,
      ),
      'renderWithHuisstijl' => 
      array (
        'name' => 'renderWithHuisstijl',
        'parameters' => 
        array (
          'templateContent' => 
          array (
            'name' => 'templateContent',
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
            'startLine' => 618,
            'endLine' => 618,
            'startColumn' => 3,
            'endColumn' => 25,
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
            'startLine' => 619,
            'endLine' => 619,
            'startColumn' => 3,
            'endColumn' => 13,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'huisstijl' => 
          array (
            'name' => 'huisstijl',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 620,
            'endLine' => 620,
            'startColumn' => 3,
            'endColumn' => 19,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Render template content with huisstijl header and footer
 *
 * @param string $templateContent The Twig template content
 * @param array $data The data context
 * @param array|null $huisstijl The huisstijl configuration
 *
 * @return string The rendered HTML
 *
 * @throws Exception If rendering fails
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-huisstijl-default-configuration
 */',
        'startLine' => 617,
        'endLine' => 651,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'aliasName' => NULL,
      ),
      'produceOutput' => 
      array (
        'name' => 'produceOutput',
        'parameters' => 
        array (
          'htmlContent' => 
          array (
            'name' => 'htmlContent',
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
            'startLine' => 666,
            'endLine' => 666,
            'startColumn' => 33,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'format' => 
          array (
            'name' => 'format',
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
            'startLine' => 666,
            'endLine' => 666,
            'startColumn' => 54,
            'endColumn' => 67,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'pdfOptions' => 
          array (
            'name' => 'pdfOptions',
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
            'startLine' => 666,
            'endLine' => 666,
            'startColumn' => 70,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Produce output in the requested format
 *
 * @param string $htmlContent The rendered HTML content
 * @param string $format The output format (pdf, docx, html, email)
 * @param array $pdfOptions The PDF generation options
 *
 * @return string The generated content
 *
 * @throws Exception If output generation fails
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-output-format-selection
 */',
        'startLine' => 666,
        'endLine' => 687,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'aliasName' => NULL,
      ),
      'stripPageStyling' => 
      array (
        'name' => 'stripPageStyling',
        'parameters' => 
        array (
          'html' => 
          array (
            'name' => 'html',
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
            'startLine' => 701,
            'endLine' => 701,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Strip page-specific CSS for email output
 *
 * Removes @page rules, fixed dimensions, and other print-specific CSS
 * to produce clean HTML suitable for email clients.
 *
 * @param string $html The rendered HTML content
 *
 * @return string Clean HTML for email use
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-email-body-generation
 */',
        'startLine' => 701,
        'endLine' => 709,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'aliasName' => NULL,
      ),
      'convertToDocx' => 
      array (
        'name' => 'convertToDocx',
        'parameters' => 
        array (
          'htmlContent' => 
          array (
            'name' => 'htmlContent',
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
            'startLine' => 728,
            'endLine' => 728,
            'startColumn' => 33,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'pdfOptions' => 
          array (
            'name' => 'pdfOptions',
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
            'startLine' => 728,
            'endLine' => 728,
            'startColumn' => 54,
            'endColumn' => 70,
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
 * Convert HTML to DOCX using LibreOffice headless
 *
 * @param string $htmlContent The HTML content to convert
 * @param array $pdfOptions The page configuration options
 *
 * @return string The DOCX binary content
 *
 * @throws Exception If LibreOffice is not available or conversion fails
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter) $pdfOptions reserved for future page config
 *
 * @psalm-suppress UnusedParam $pdfOptions reserved for future page config
 * @psalm-suppress ForbiddenCode shell_exec is required to locate the LibreOffice binary
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-output-format-selection
 */',
        'startLine' => 728,
        'endLine' => 781,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'aliasName' => NULL,
      ),
      'logCorrespondence' => 
      array (
        'name' => 'logCorrespondence',
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
            'startLine' => 799,
            'endLine' => 799,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'templateName' => 
          array (
            'name' => 'templateName',
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
            'startLine' => 800,
            'endLine' => 800,
            'startColumn' => 3,
            'endColumn' => 22,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'dataRefs' => 
          array (
            'name' => 'dataRefs',
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
            'startLine' => 801,
            'endLine' => 801,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'format' => 
          array (
            'name' => 'format',
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
            'startLine' => 802,
            'endLine' => 802,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'status' => 
          array (
            'name' => 'status',
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
            'startLine' => 803,
            'endLine' => 803,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'errorMessage' => 
          array (
            'name' => 'errorMessage',
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
            'startLine' => 804,
            'endLine' => 804,
            'startColumn' => 3,
            'endColumn' => 23,
            'parameterIndex' => 5,
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
            'startLine' => 805,
            'endLine' => 805,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 6,
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
 * Log correspondence generation to the document register
 *
 * @param string $templateId The template UUID
 * @param string $templateName The template name
 * @param array $dataRefs The data references used
 * @param string $format The output format
 * @param string $status The generation status (generated|failed)
 * @param string|null $errorMessage Error message if status is failed
 * @param array $options The request options
 *
 * @return array The created correspondence register entry
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-correspondence-register-logging
 */',
        'startLine' => 798,
        'endLine' => 849,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
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
 * Generate a unique job ID using a cryptographically secure source
 *
 * Uses random_bytes() (CSPRNG) rather than mt_rand() so job IDs cannot
 * be predicted by an attacker who knows the approximate creation time
 * (C3 security fix).
 *
 * @return string A RFC-4122 v4 UUID job identifier
 */',
        'startLine' => 860,
        'endLine' => 865,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CorrespondenceService',
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