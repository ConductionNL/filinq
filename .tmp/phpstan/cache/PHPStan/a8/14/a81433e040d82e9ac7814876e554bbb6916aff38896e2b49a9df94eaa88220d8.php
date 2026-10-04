<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Controller/AnonymizationController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Controller\AnonymizationController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-c40c4914d18df5ed654eb0eac53d595f3c2e082effb398097f5ad19dfc6834f7',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Controller/AnonymizationController.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Controller',
    'name' => 'OCA\\Filinq\\Controller\\AnonymizationController',
    'shortName' => 'AnonymizationController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Controller for anonymization pipeline endpoints
 *
 * @category Controller
 * @package  OCA\\Filinq\\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-1
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 57,
    'endLine' => 471,
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
        'declaringClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'name' => 'DEFAULT_OUTPUT_FORMAT_KEY',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq.anonymisation.default_output_format\'',
          'attributes' => 
          array (
            'startLine' => 111,
            'endLine' => 111,
            'startTokenPos' => 269,
            'startFilePos' => 3773,
            'endTokenPos' => 269,
            'endFilePos' => 3816,
          ),
        ),
        'docComment' => '/**
 * Default-output-format tenant config key. The per-call
 * `outputFormat` request param overrides this when supplied.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 111,
        'endLine' => 111,
        'startColumn' => 2,
        'endColumn' => 88,
      ),
      'VALID_OUTPUT_FORMATS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'name' => 'VALID_OUTPUT_FORMATS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'pdf-only\', \'pdf\', \'preserve\']',
          'attributes' => 
          array (
            'startLine' => 122,
            'endLine' => 122,
            'startTokenPos' => 282,
            'startFilePos' => 4258,
            'endTokenPos' => 290,
            'endFilePos' => 4288,
          ),
        ),
        'docComment' => '/**
 * Supported values for the `outputFormat` request param + tenant
 * config. Anything else from the request results in HTTP 400.
 *
 * - `pdf-only` (default): convert to PDF and delete the native
 *   anonymised intermediate so only the PDF remains.
 * - `pdf`: convert to PDF but keep the native intermediate too.
 * - `preserve`: skip conversion; native format is the only output.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 122,
        'endLine' => 122,
        'startColumn' => 2,
        'endColumn' => 70,
      ),
    ),
    'immediateProperties' => 
    array (
      'anonymizeRequest' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'name' => 'anonymizeRequest',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\AnonymizeRequestService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Workflow behind the anonymize endpoints: authentication, per-user file
 * access verification, body validation, the prohibition guards and the
 * anonymisation call itself.
 *
 * @var AnonymizeRequestService
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 66,
        'endLine' => 66,
        'startColumn' => 2,
        'endColumn' => 60,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
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
        'startLine' => 87,
        'endLine' => 87,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'anonymizationService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'name' => 'anonymizationService',
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
        'startLine' => 88,
        'endLine' => 88,
        'startColumn' => 3,
        'endColumn' => 61,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'fileListingService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'name' => 'fileListingService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\FileListingService',
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
        'declaringClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
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
        'startLine' => 90,
        'endLine' => 90,
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
        'declaringClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
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
        'startLine' => 91,
        'endLine' => 91,
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
        'declaringClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
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
        'startLine' => 92,
        'endLine' => 92,
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
        'declaringClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
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
        'startLine' => 93,
        'endLine' => 93,
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
            'startLine' => 85,
            'endLine' => 85,
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
            'startLine' => 86,
            'endLine' => 86,
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
            'startLine' => 87,
            'endLine' => 87,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'anonymizationService' => 
          array (
            'name' => 'anonymizationService',
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
            'startLine' => 88,
            'endLine' => 88,
            'startColumn' => 3,
            'endColumn' => 61,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'fileListingService' => 
          array (
            'name' => 'fileListingService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\FileListingService',
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
            'endColumn' => 57,
            'parameterIndex' => 4,
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
            'startLine' => 90,
            'endLine' => 90,
            'startColumn' => 3,
            'endColumn' => 30,
            'parameterIndex' => 5,
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
            'startLine' => 91,
            'endLine' => 91,
            'startColumn' => 3,
            'endColumn' => 40,
            'parameterIndex' => 6,
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
            'startLine' => 92,
            'endLine' => 92,
            'startColumn' => 3,
            'endColumn' => 44,
            'parameterIndex' => 7,
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
            'startLine' => 93,
            'endLine' => 93,
            'startColumn' => 3,
            'endColumn' => 42,
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
 * Constructor for AnonymizationController
 *
 * @param string $appName The application name
 * @param IRequest $request The request object
 * @param LoggerInterface $logger Logger for error reporting
 * @param AnonymizationService $anonymizationService Service for anonymization operations
 * @param FileListingService $fileListingService Service for file listing operations
 * @param IL10N $l10n The localization service
 * @param IAppConfig $appConfig Tenant configuration provider (reads
 *                              filinq.anonymisation.default_output_format)
 * @param IUserSession $userSession User session for authentication
 * @param IRootFolder $rootFolder Root folder for file access checks
 *
 * @return void
 */',
        'startLine' => 84,
        'endLine' => 105,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'aliasName' => NULL,
      ),
      'files' => 
      array (
        'name' => 'files',
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
 * List all processed files with entity counts and status
 *
 * Returns files from the user\'s Filinq folder with their
 * entity detection counts and anonymization status.
 *
 * @return JSONResponse JSON response with array of file data
 *
 * @NoAdminRequired
 * @NoCSRFRequired
 *
 * @spec openspec/specs/anonymization/spec.md
 */',
        'startLine' => 137,
        'endLine' => 163,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'aliasName' => NULL,
      ),
      'upload' => 
      array (
        'name' => 'upload',
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
 * Upload a file to the user\'s Filinq folder
 *
 * Reads the uploaded file from the request and saves it
 * to the user\'s Filinq folder.
 *
 * @return JSONResponse JSON response with upload result
 *
 * @NoAdminRequired
 *
 * @spec openspec/specs/anonymization/spec.md
 */',
        'startLine' => 177,
        'endLine' => 229,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'aliasName' => NULL,
      ),
      'extract' => 
      array (
        'name' => 'extract',
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
            'startLine' => 244,
            'endLine' => 244,
            'startColumn' => 26,
            'endColumn' => 36,
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
 * Extract text and detect entities in a file
 *
 * Runs text extraction and entity recognition on the specified file.
 *
 * @param int $fileId The Nextcloud file ID
 *
 * @return JSONResponse JSON response with extraction and detection results
 *
 * @NoAdminRequired
 *
 * @spec openspec/specs/anonymization/spec.md
 */',
        'startLine' => 244,
        'endLine' => 275,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'aliasName' => NULL,
      ),
      'anonymize' => 
      array (
        'name' => 'anonymize',
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
            'startLine' => 299,
            'endLine' => 299,
            'startColumn' => 28,
            'endColumn' => 38,
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
 * Anonymize entities in a document
 *
 * Replaces detected entities in the document with anonymized placeholders.
 * Supports optional excludeTypes, minConfidence, appendBasisSummary, and
 * outputFormat parameters. Stray `bases[]` fields on entity entries are
 * silently ignored (per 2026-05-12 explore-mode rework); bases are set via
 * OR\'s PATCH /api/entity-relations/{id}.
 *
 * @param int $fileId The Nextcloud file ID
 *
 * @return JSONResponse JSON response with anonymization result
 *
 * @NoAdminRequired
 *
 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-1
 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-1
 * @spec openspec/specs/anonymization/spec.md
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-4
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-11
 */',
        'startLine' => 299,
        'endLine' => 388,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'aliasName' => NULL,
      ),
      'updateRelation' => 
      array (
        'name' => 'updateRelation',
        'parameters' => 
        array (
          'id' => 
          array (
            'name' => 'id',
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
            'startLine' => 415,
            'endLine' => 415,
            'startColumn' => 33,
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
            'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Record a per-entity skip/include decision, guarded by the prohibition policy.
 *
 * Called by the review UI on skip-toggle in place of PATCHing OpenRegister\'s
 * relation endpoint directly. Skipping a prohibition-matched entity is
 * rejected with HTTP 422 (absolute at/above the threshold; below it only
 * unless `force`). Include / non-skip decisions are always allowed and
 * forwarded to OpenRegister.
 *
 * `$id` is a caller-supplied primary key into a table that is NOT scoped to
 * the caller, so the decision path is authorised twice: authentication here,
 * and per-document ownership inside RelationSkipDecisionService (which is
 * where the relation — and therefore its Nextcloud file id — is loaded). A
 * relation on a document the caller cannot reach yields the same 404 as one
 * that does not exist.
 *
 * @param int $id The EntityRelation id.
 *
 * @return JSONResponse Success, or 401 / 404 / 422 with `{threshold, prohibitionMatch}`.
 *
 * @NoAdminRequired
 * @NoCSRFRequired
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
 */',
        'startLine' => 415,
        'endLine' => 427,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
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
            'startLine' => 445,
            'endLine' => 445,
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
 * Resolve the effective `outputFormat` for this request.
 *
 * Order: per-call value (when supplied and valid) → tenant default
 * from IAppConfig → hard-coded `"pdf-only"` fallback.
 *
 * Returns `null` when the per-call value is supplied but invalid;
 * the caller maps that to HTTP 400.
 *
 * @param array<string,mixed> $params Request params.
 *
 * @return string|null Resolved outputFormat (\'pdf-only\'|\'pdf\'|\'preserve\'),
 *                     or null when an invalid value was supplied.
 *
 * @spec openspec/specs/anonymization/spec.md
 */',
        'startLine' => 445,
        'endLine' => 470,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\AnonymizationController',
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