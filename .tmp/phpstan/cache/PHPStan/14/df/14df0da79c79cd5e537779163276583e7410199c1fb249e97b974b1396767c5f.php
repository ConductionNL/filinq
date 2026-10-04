<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Controller/BatchAnonymizationController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Controller\BatchAnonymizationController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-3216efbfb5e94055132ecb75ac8d4b3281bcda94e1e0bd56e1676421395afd30',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Controller/BatchAnonymizationController.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Controller',
    'name' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
    'shortName' => 'BatchAnonymizationController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Controller that wires the batch-anonymization routes to their service layer.
 *
 * @category Controller
 * @package  OCA\\Filinq\\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-1
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 73,
    'endLine' => 639,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'OCP\\AppFramework\\Controller',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'DEFAULT_OUTPUT_FORMAT_KEY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'name' => 'DEFAULT_OUTPUT_FORMAT_KEY',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq.anonymisation.default_output_format\'',
          'attributes' => 
          array (
            'startLine' => 132,
            'endLine' => 132,
            'startTokenPos' => 320,
            'startFilePos' => 5710,
            'endTokenPos' => 320,
            'endFilePos' => 5753,
          ),
        ),
        'docComment' => '/**
 * Tenant config key for default outputFormat. Mirrors the constant
 * used by AnonymizationController; defined here so both controllers
 * stay aligned on the lookup key.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 132,
        'endLine' => 132,
        'startColumn' => 2,
        'endColumn' => 88,
      ),
      'VALID_OUTPUT_FORMATS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'name' => 'VALID_OUTPUT_FORMATS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'pdf-only\', \'pdf\', \'preserve\']',
          'attributes' => 
          array (
            'startLine' => 142,
            'endLine' => 142,
            'startTokenPos' => 333,
            'startFilePos' => 6123,
            'endTokenPos' => 341,
            'endFilePos' => 6153,
          ),
        ),
        'docComment' => '/**
 * Supported values for the `outputFormat` request param.
 *
 * - `pdf-only` (default): convert to PDF and delete the native
 *   anonymised intermediate so only the PDF remains.
 * - `pdf`: convert to PDF but keep the native intermediate too.
 * - `preserve`: skip conversion; native format is the only output.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 142,
        'endLine' => 142,
        'startColumn' => 2,
        'endColumn' => 70,
      ),
    ),
    'immediateProperties' => 
    array (
      'batchRequest' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'name' => 'batchRequest',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\BatchRequestService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Request/response shaping helpers for the batch endpoints: body
 * validation, folder-parameter coercion, progress aggregation and the
 * multi-status mapping.
 *
 * @var BatchRequestService
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 82,
        'endLine' => 82,
        'startColumn' => 2,
        'endColumn' => 52,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
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
        'startLine' => 108,
        'endLine' => 108,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'stateService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
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
        'startLine' => 109,
        'endLine' => 109,
        'startColumn' => 3,
        'endColumn' => 50,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'uploadService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'name' => 'uploadService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\BatchUploadService',
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
        'endColumn' => 52,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'extractService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'name' => 'extractService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\BatchExtractionService',
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
        'endColumn' => 57,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'anonService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'name' => 'anonService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
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
        'endColumn' => 53,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'reportService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'name' => 'reportService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\BatchReportService',
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
        'endColumn' => 52,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'entityService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'name' => 'entityService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
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
        'endColumn' => 60,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'profileService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'name' => 'profileService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\WooProfileService',
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
        'endColumn' => 52,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'folderBatchService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'name' => 'folderBatchService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\FolderBatchService',
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
        'endColumn' => 57,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'l10n' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
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
        'startLine' => 117,
        'endLine' => 117,
        'startColumn' => 3,
        'endColumn' => 30,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'appConfig' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
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
        'startLine' => 118,
        'endLine' => 118,
        'startColumn' => 3,
        'endColumn' => 40,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'userSession' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
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
        'startLine' => 119,
        'endLine' => 119,
        'startColumn' => 3,
        'endColumn' => 44,
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
          'appName' => 
          array (
            'name' => 'appName',
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
            'startLine' => 106,
            'endLine' => 106,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IRequest',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 107,
            'endLine' => 107,
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 1,
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
            'startLine' => 108,
            'endLine' => 108,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 2,
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
            'startLine' => 109,
            'endLine' => 109,
            'startColumn' => 3,
            'endColumn' => 50,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'uploadService' => 
          array (
            'name' => 'uploadService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\BatchUploadService',
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
            'endColumn' => 52,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'extractService' => 
          array (
            'name' => 'extractService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\BatchExtractionService',
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
            'endColumn' => 57,
            'parameterIndex' => 5,
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
                'name' => 'OCA\\Filinq\\Service\\BatchAnonymizeService',
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
            'endColumn' => 53,
            'parameterIndex' => 6,
            'isOptional' => false,
          ),
          'reportService' => 
          array (
            'name' => 'reportService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\BatchReportService',
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
            'endColumn' => 52,
            'parameterIndex' => 7,
            'isOptional' => false,
          ),
          'entityService' => 
          array (
            'name' => 'entityService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
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
            'endColumn' => 60,
            'parameterIndex' => 8,
            'isOptional' => false,
          ),
          'profileService' => 
          array (
            'name' => 'profileService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\WooProfileService',
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
            'endColumn' => 52,
            'parameterIndex' => 9,
            'isOptional' => false,
          ),
          'folderBatchService' => 
          array (
            'name' => 'folderBatchService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\FolderBatchService',
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
            'endColumn' => 57,
            'parameterIndex' => 10,
            'isOptional' => false,
          ),
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
            'startLine' => 117,
            'endLine' => 117,
            'startColumn' => 3,
            'endColumn' => 30,
            'parameterIndex' => 11,
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
            'startLine' => 118,
            'endLine' => 118,
            'startColumn' => 3,
            'endColumn' => 40,
            'parameterIndex' => 12,
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
            'startLine' => 119,
            'endLine' => 119,
            'startColumn' => 3,
            'endColumn' => 44,
            'parameterIndex' => 13,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor for BatchAnonymizationController
 *
 * @param string $appName App name passed through to the base Controller.
 * @param IRequest $request Current HTTP request.
 * @param LoggerInterface $logger Logger used by the err() helper for failure reporting.
 * @param BatchStateService $stateService Service that stores and loads batch records.
 * @param BatchUploadService $uploadService Service that persists uploaded files into a new batch.
 * @param BatchExtractionService $extractService Service that drives per-file entity extraction.
 * @param BatchAnonymizeService $anonService Service that applies approved entities across a batch.
 * @param BatchReportService $reportService Service that produces the per-batch CSV report.
 * @param EntityConsolidationService $entityService Service that merges per-file entity detections into one list.
 * @param WooProfileService $profileService Service that stores the WOO entity profile.
 * @param FolderBatchService $folderBatchService Service that turns an existing folder into a batch.
 * @param IL10N $l10n Translator for user-facing error messages.
 * @param IAppConfig $appConfig Tenant configuration provider (reads
 *                              filinq.anonymisation.default_output_format).
 * @param IUserSession $userSession User session for authentication.
 *
 * @return void
 */',
        'startLine' => 105,
        'endLine' => 125,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'aliasName' => NULL,
      ),
      'batchUpload' => 
      array (
        'name' => 'batchUpload',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Accept a multipart upload and create a new anonymization batch.
 *
 * @return JSONResponse Batch metadata (id, file count, per-file entries) or an error payload.
 *
 * @NoAdminRequired
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
 */',
        'startLine' => 153,
        'endLine' => 180,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'aliasName' => NULL,
      ),
      'folderBatch' => 
      array (
        'name' => 'folderBatch',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a folder-based batch from either folderId or folderPath.
 *
 * @return JSONResponse Batch metadata or an error payload.
 *
 * @NoAdminRequired
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
 *
 * @no-admin-idor-exempt ownership is enforced one layer down.
 * Every path here loads the batch through BatchStateService::getBatch(),
 * which compares the record\'s userId against the session user and returns
 * null for a mismatch (admins excepted for support). It returns null
 * rather than throwing precisely so a denied read is indistinguishable
 * from a missing one — both answer 404 — so a caller cannot even confirm
 * another user\'s batch exists.
 */',
        'startLine' => 199,
        'endLine' => 231,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'aliasName' => NULL,
      ),
      'batchExtract' => 
      array (
        'name' => 'batchExtract',
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
            'startLine' => 252,
            'endLine' => 252,
            'startColumn' => 31,
            'endColumn' => 45,
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
            'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Extract entities from the next pending file in a batch.
 *
 * @param string $batchId Identifier of the batch to advance.
 *
 * @return JSONResponse Per-file extraction result, or an error payload.
 *
 * @NoAdminRequired
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-sequential-batch-extraction
 *
 * @no-admin-idor-exempt ownership is enforced one layer down.
 * Every path here loads the batch through BatchStateService::getBatch(),
 * which compares the record\'s userId against the session user and returns
 * null for a mismatch (admins excepted for support). It returns null
 * rather than throwing precisely so a denied read is indistinguishable
 * from a missing one — both answer 404 — so a caller cannot even confirm
 * another user\'s batch exists.
 */',
        'startLine' => 252,
        'endLine' => 263,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'aliasName' => NULL,
      ),
      'batchStatus' => 
      array (
        'name' => 'batchStatus',
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
            'startLine' => 277,
            'endLine' => 277,
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
            'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Return progress, per-file status, and total entity count for a batch.
 *
 * @param string $batchId Identifier of the batch to inspect.
 *
 * @return JSONResponse Batch status snapshot, or 404 when the batch is unknown.
 *
 * @NoAdminRequired
 * @NoCSRFRequired
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-status-endpoint
 */',
        'startLine' => 277,
        'endLine' => 300,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'aliasName' => NULL,
      ),
      'batchEntities' => 
      array (
        'name' => 'batchEntities',
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
            'startLine' => 318,
            'endLine' => 318,
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
            'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Return the consolidated entity list for a batch once extraction has started.
 *
 * Accepts an optional `minConfidence` query parameter; entities below the
 * threshold are returned but flagged as not-included so the UI can still
 * surface them for manual review.
 *
 * @param string $batchId Identifier of the batch whose entities should be returned.
 *
 * @return JSONResponse Consolidated entity list plus progress metadata, or an error payload.
 *
 * @NoAdminRequired
 * @NoCSRFRequired
 *
 * @spec openspec/specs/anonymization-entity-review/spec.md#requirement-consolidated-entity-list-endpoint
 */',
        'startLine' => 318,
        'endLine' => 348,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'aliasName' => NULL,
      ),
      'batchAnonymize' => 
      array (
        'name' => 'batchAnonymize',
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
            'startLine' => 378,
            'endLine' => 378,
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
            'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Apply the user-approved entity list to every extracted file in a batch.
 *
 * Stray `bases[]` fields on entity entries are silently ignored (per 2026-05-12
 * explore-mode rework); bases are set via OR\'s PATCH /api/entity-relations/{id}.
 * Accepts an optional `appendBasisSummary` boolean flag (default false).
 * When true, invokes the grondslagen summary service after each file\'s
 * anonymization. Per-file summary failures surface as per-file warnings
 * in the response; the overall batch still completes as HTTP 200.
 *
 * @param string $batchId Identifier of the batch to anonymize.
 *
 * @return JSONResponse Summary of the run, or an error payload when the request body is malformed.
 *
 * @NoAdminRequired
 *
 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-1
 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-1
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
 *
 * @no-admin-idor-exempt ownership is enforced one layer down.
 * Every path here loads the batch through BatchStateService::getBatch(),
 * which compares the record\'s userId against the session user and returns
 * null for a mismatch (admins excepted for support). It returns null
 * rather than throwing precisely so a denied read is indistinguishable
 * from a missing one — both answer 404 — so a caller cannot even confirm
 * another user\'s batch exists.
 */',
        'startLine' => 378,
        'endLine' => 429,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'aliasName' => NULL,
      ),
      'runBatchAnonymize' => 
      array (
        'name' => 'runBatchAnonymize',
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
            'startLine' => 448,
            'endLine' => 448,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'request' => 
          array (
            'name' => 'request',
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
            'startLine' => 449,
            'endLine' => 449,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'outputFormat' => 
          array (
            'name' => 'outputFormat',
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
            'startLine' => 450,
            'endLine' => 450,
            'startColumn' => 3,
            'endColumn' => 22,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'scope' => 
          array (
            'name' => 'scope',
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
            'startLine' => 451,
            'endLine' => 451,
            'startColumn' => 3,
            'endColumn' => 15,
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
 * Dispatch to the batch entry point this request asked for.
 *
 * `appendBasisSummary` selects between the plain and the summary-producing
 * BatchAnonymizeService entry point; every other value is forwarded verbatim.
 *
 * @param string $batchId Identifier of the batch to anonymize.
 * @param array<string, mixed> $request The validated request body.
 * @param string $outputFormat Resolved per-batch output format.
 * @param string $scope Resolved placeholder-numbering scope.
 *
 * @return array<string, mixed> The batch run summary.
 *
 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-7
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
 */',
        'startLine' => 447,
        'endLine' => 471,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'aliasName' => NULL,
      ),
      'resolveOutputFormat' => 
      array (
        'name' => 'resolveOutputFormat',
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
            'startLine' => 486,
            'endLine' => 486,
            'startColumn' => 39,
            'endColumn' => 51,
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
 * Resolve the effective `outputFormat` for this batch call.
 *
 * Per-batch value overrides tenant default; tenant default defaults
 * to `"pdf-only"`. Returns null when an invalid per-call value was
 * supplied; the caller maps that to HTTP 400.
 *
 * @param array<string,mixed> $params Request params.
 *
 * @return string|null Resolved outputFormat or null on invalid input.
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
 */',
        'startLine' => 486,
        'endLine' => 509,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'aliasName' => NULL,
      ),
      'batchReport' => 
      array (
        'name' => 'batchReport',
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
            'startLine' => 531,
            'endLine' => 531,
            'startColumn' => 30,
            'endColumn' => 44,
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
                  'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
                  'isIdentifier' => false,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'OCP\\AppFramework\\Http\\DataDownloadResponse',
                  'isIdentifier' => false,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Produce the CSV anonymization report for a batch as a file download.
 *
 * @param string $batchId Identifier of the batch to report on.
 *
 * @return JSONResponse|DataDownloadResponse CSV download on success, JSON error payload on failure.
 *
 * @NoAdminRequired
 * @NoCSRFRequired
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-completion-report
 *
 * @no-admin-idor-exempt ownership is enforced one layer down.
 * Every path here loads the batch through BatchStateService::getBatch(),
 * which compares the record\'s userId against the session user and returns
 * null for a mismatch (admins excepted for support). It returns null
 * rather than throwing precisely so a denied read is indistinguishable
 * from a missing one — both answer 404 — so a caller cannot even confirm
 * another user\'s batch exists.
 */',
        'startLine' => 531,
        'endLine' => 543,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'aliasName' => NULL,
      ),
      'getProfiles' => 
      array (
        'name' => 'getProfiles',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Return the active WOO anonymization profile.
 *
 * @return JSONResponse Profile with `anonymize` and `keep` entity-type arrays.
 *
 * @NoAdminRequired
 * @NoCSRFRequired
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-woo-entity-category-profiles
 */',
        'startLine' => 555,
        'endLine' => 561,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'aliasName' => NULL,
      ),
      'updateProfiles' => 
      array (
        'name' => 'updateProfiles',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\AuthorizedAdminSetting',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\\OCA\\Filinq\\Settings\\FilinqAdmin::class',
                'attributes' => 
                array (
                  'startLine' => 594,
                  'endLine' => 594,
                  'startTokenPos' => 2519,
                  'startFilePos' => 22603,
                  'endTokenPos' => 2521,
                  'endFilePos' => 22620,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * Persist a new WOO anonymization profile from the request body.
 *
 * ADMIN-ONLY, DELIBERATELY. The profile this writes is INSTANCE-WIDE: it
 * lands in `IAppConfig` under `filinq_woo_entity_profiles`
 * ({@see WooProfileService::saveProfile()}), and
 * {@see EntityConsolidationService} reads it to decide which entity types
 * are redacted for EVERY user. Moving `PERSON` / `BSN` / `EMAIL` out of the
 * `anonymize` set therefore leaves other people\'s PII in place on every
 * subsequent run.
 *
 * ⚠️ The obvious way to satisfy a "missing auth attribute" finding here —
 * copying the sibling `getProfiles()`\'s user-level annotation onto this
 * method — WOULD INTRODUCE EXACTLY THAT VULNERABILITY. The two are not
 * symmetric: reading the profile is a user-level concern, writing it is
 * instance policy. Until this attribute existed the endpoint was admin-only
 * only by ACCIDENT (Nextcloud defaults an unannotated method to admin), and
 * the body\'s lone `getUser() === null` check reads as though user-level
 * access were intended, which is what makes the wrong fix look right.
 *
 * `#[AuthorizedAdminSetting]` matches how every other instance-wide write in
 * this app declares itself ({@see SettingsController},
 * {@see AnonymiserWarningController}).
 *
 * The null-session check below is retained as defence in depth, not as the
 * authorization boundary — the attribute is the boundary.
 *
 * @return JSONResponse Success message, or an error payload when the body is malformed.
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-woo-entity-category-profiles
 */',
        'startLine' => 594,
        'endLine' => 612,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'aliasName' => NULL,
      ),
      'err' => 
      array (
        'name' => 'err',
        'parameters' => 
        array (
          'msg' => 
          array (
            'name' => 'msg',
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
            'startLine' => 629,
            'endLine' => 629,
            'startColumn' => 23,
            'endColumn' => 33,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'e' => 
          array (
            'name' => 'e',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Throwable',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 629,
            'endLine' => 629,
            'startColumn' => 36,
            'endColumn' => 48,
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
            'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Build a JSON error response, logging the underlying exception.
 *
 * Exception codes outside the HTTP error range (400..599) are normalized
 * to 500 so the client always receives a valid status.
 *
 * @param string $msg Human-readable description of what failed.
 * @param \\Throwable $e Throwable captured at the controller boundary.
 *
 * @return JSONResponse Error payload with an appropriate HTTP status.
 *
 * @psalm-suppress InvalidArgument $code is clamped to int<400, 599>; Psalm wants the literal HTTP status union.
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
 */',
        'startLine' => 629,
        'endLine' => 638,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\BatchAnonymizationController',
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