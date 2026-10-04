<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Controller/DocumentController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Controller\DocumentController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-a5dc2161405740e433b828940ecf3f3fcd5242ccf84d8e768390dd4380e47809',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Controller\\DocumentController',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Controller/DocumentController.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Controller',
    'name' => 'OCA\\Filinq\\Controller\\DocumentController',
    'shortName' => 'DocumentController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Controller for document generation endpoints
 *
 * @category Controller
 * @package  OCA\\Filinq\\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 51,
    'endLine' => 505,
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
    ),
    'immediateProperties' => 
    array (
      'documentSvc' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'name' => 'documentSvc',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\DocumentService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 67,
        'endLine' => 67,
        'startColumn' => 3,
        'endColumn' => 47,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'userSession' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
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
        'startLine' => 68,
        'endLine' => 68,
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
        'declaringClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
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
        'startLine' => 69,
        'endLine' => 69,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'l10n' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
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
        'startLine' => 70,
        'endLine' => 70,
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
            'startLine' => 65,
            'endLine' => 65,
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
            'startLine' => 66,
            'endLine' => 66,
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'documentSvc' => 
          array (
            'name' => 'documentSvc',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\DocumentService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 3,
            'endColumn' => 47,
            'parameterIndex' => 2,
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
            'startLine' => 68,
            'endLine' => 68,
            'startColumn' => 3,
            'endColumn' => 44,
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
            'startLine' => 69,
            'endLine' => 69,
            'startColumn' => 3,
            'endColumn' => 42,
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
            'startLine' => 70,
            'endLine' => 70,
            'startColumn' => 3,
            'endColumn' => 30,
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
 * Constructor for DocumentController.
 *
 * @param string $appName The application name
 * @param IRequest $request The request object
 * @param DocumentService $documentSvc Document generation service
 * @param IUserSession $userSession User session for authentication
 * @param LoggerInterface $logger Logger for error reporting
 * @param IL10N $l10n The localization service
 *
 * @return void
 */',
        'startLine' => 64,
        'endLine' => 74,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'aliasName' => NULL,
      ),
      'generate' => 
      array (
        'name' => 'generate',
        'parameters' => 
        array (
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
                  'name' => 'OCP\\AppFramework\\Http\\DataDownloadResponse',
                  'isIdentifier' => false,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
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
 * Generate a single document from a template.
 *
 * POST /api/documents/generate
 *
 * Request body:
 * - templateId (string, required): UUID of the template
 * - dataRefs (array, required): [{register, schema, id}, ...]
 * - options (object, optional): format, huisstijlId, zaakId, adHocData,
 *   listRefs, pdfOptions, output
 *   - listRefs (array, optional): [{register, schema, filter?, limit?,
 *     order?, as?}, ...] — each resolves to an array of objects in the
 *     Twig context under key \'as\' (default: schema + \'_list\')
 *   - output (object, optional): {mode?: \'return\'|\'files\'|\'both\', targetPath?}
 *     — defaults to mode \'return\' (byte-identical to omitting it). \'files\'
 *     stores the document in the requesting user\'s Files and returns JSON
 *     refs instead of a binary; \'both\' stores AND returns the binary, with
 *     the stored file identified via X-Docudesk-File-Id/X-Docudesk-File-Path
 *     response headers.
 * - filename (string, optional): Download filename (also used as the
 *   stored filename\'s basename when output.mode is \'files\'/\'both\')
 *
 * @return DataDownloadResponse|JSONResponse Generated document binary,
 *                                           stored-file JSON refs, or error
 *
 * @NoAdminRequired
 *
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
 * @spec openspec/changes/document-generation-list-refs/specs/document-creatie-sjablonen/spec.md
 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md
 */',
        'startLine' => 107,
        'endLine' => 139,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'aliasName' => NULL,
      ),
      'preview' => 
      array (
        'name' => 'preview',
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
 * Generate an HTML preview of a template without final output.
 *
 * POST /api/documents/generate/preview
 *
 * Request body:
 * - templateId (string, required): UUID of the template
 * - dataRefs (array, optional): [{register, schema, id}, ...]
 * - options (object, optional): huisstijlId, adHocData, listRefs
 *   - listRefs (array, optional): [{register, schema, filter?, limit?,
 *     order?, as?}, ...] — each resolves to an array of objects in the
 *     Twig context under key \'as\' (default: schema + \'_list\')
 *
 * @return JSONResponse Rendered HTML or error
 *
 * @NoAdminRequired
 *
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
 * @spec openspec/changes/document-generation-list-refs/specs/document-creatie-sjablonen/spec.md
 *
 * @no-admin-idor-exempt object access runs under OpenRegister\'s RBAC,
 * which is ON by default. This method passes no `_rbac: false`, and none
 * of the services it reaches does either — the 22 real opt-outs in this
 * app are in the dossier, policy, consent-validator and custom-dictionary
 * paths, none of which this endpoint touches. The data layer is the guard,
 * so an id belonging to another tenant returns nothing.
 */',
        'startLine' => 168,
        'endLine' => 213,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'aliasName' => NULL,
      ),
      'generateBulk' => 
      array (
        'name' => 'generateBulk',
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
 * Generate documents for multiple objects in bulk.
 *
 * POST /api/documents/generate/bulk
 *
 * Request body:
 * - templateId (string, required): UUID of the template
 * - objectIds (array, required): Array of object UUIDs
 * - options (object, optional): register, schema, format, huisstijlId
 *   - Note: options.listRefs is NOT supported here — see
 *     DocumentService::generateBulk() docblock for why.
 *
 * @return JSONResponse Synchronous results or async job info (202 Accepted)
 *
 * @NoAdminRequired
 *
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
 *
 * @no-admin-idor-exempt object access runs under OpenRegister\'s RBAC,
 * which is ON by default. This method passes no `_rbac: false`, and none
 * of the services it reaches does either — the 22 real opt-outs in this
 * app are in the dossier, policy, consent-validator and custom-dictionary
 * paths, none of which this endpoint touches. The data layer is the guard,
 * so an id belonging to another tenant returns nothing.
 */',
        'startLine' => 240,
        'endLine' => 289,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'aliasName' => NULL,
      ),
      'jobStatus' => 
      array (
        'name' => 'jobStatus',
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
            'startLine' => 305,
            'endLine' => 305,
            'startColumn' => 28,
            'endColumn' => 40,
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
 * Get the status of an async bulk document generation job.
 *
 * GET /api/documents/jobs/{jobId}
 *
 * @param string $jobId The job UUID
 *
 * @return JSONResponse Job status or 404 if not found
 *
 * @NoAdminRequired
 * @NoCSRFRequired
 *
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
 */',
        'startLine' => 305,
        'endLine' => 339,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'aliasName' => NULL,
      ),
      'parseGenerateParams' => 
      array (
        'name' => 'parseGenerateParams',
        'parameters' => 
        array (
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
                  'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
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
 * Parse and validate the generate request parameters.
 *
 * @return array|JSONResponse Parsed params array or an error JSONResponse
 */',
        'startLine' => 346,
        'endLine' => 374,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'aliasName' => NULL,
      ),
      'buildDocumentResponse' => 
      array (
        'name' => 'buildDocumentResponse',
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
            'startLine' => 395,
            'endLine' => 395,
            'startColumn' => 3,
            'endColumn' => 15,
            'parameterIndex' => 0,
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
            'startLine' => 396,
            'endLine' => 396,
            'startColumn' => 3,
            'endColumn' => 18,
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
                  'name' => 'OCP\\AppFramework\\Http\\DataDownloadResponse',
                  'isIdentifier' => false,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
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
 * Build a download response for the generated document.
 *
 * Returns a binary download for pdf/odf, or JSON with HTML content, when
 * the resolved output mode is \'return\' (the default — byte-identical to
 * this method\'s behaviour before options.output existed) or \'both\'.
 * Returns JSON stored-file refs instead, with no binary, when the mode
 * is \'files\'. When the mode is \'both\', the stored file is additionally
 * identified via X-Docudesk-File-Id/X-Docudesk-File-Path response
 * headers.
 *
 * @param array $result The generation result
 * @param string $filename The requested filename (without extension)
 *
 * @return DataDownloadResponse|JSONResponse The formatted response
 *
 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md#req-ddob-001
 */',
        'startLine' => 394,
        'endLine' => 449,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'aliasName' => NULL,
      ),
      'addStoredFileHeaders' => 
      array (
        'name' => 'addStoredFileHeaders',
        'parameters' => 
        array (
          'response' => 
          array (
            'name' => 'response',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\AppFramework\\Http\\Response',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 464,
            'endLine' => 464,
            'startColumn' => 40,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'mode' => 
          array (
            'name' => 'mode',
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
            'startLine' => 464,
            'endLine' => 464,
            'startColumn' => 60,
            'endColumn' => 71,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'output' => 
          array (
            'name' => 'output',
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
            'startLine' => 464,
            'endLine' => 464,
            'startColumn' => 74,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Attach X-Docudesk-File-Id/X-Docudesk-File-Path headers when the
 * output mode is \'both\' and the document was actually stored (a
 * fail-open storage failure leaves no fileId, so no headers are added).
 *
 * @param Response $response The response to annotate
 * @param string $mode The resolved output mode
 * @param array $output The generation result\'s output sub-array
 *
 * @return void
 *
 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md#req-ddob-001
 */',
        'startLine' => 464,
        'endLine' => 476,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'aliasName' => NULL,
      ),
      'handleException' => 
      array (
        'name' => 'handleException',
        'parameters' => 
        array (
          'exception' => 
          array (
            'name' => 'exception',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Exception',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 487,
            'endLine' => 487,
            'startColumn' => 35,
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
            'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Handle exceptions and return appropriate JSON error responses.
 *
 * @param Exception $exception The exception to handle
 *
 * @return JSONResponse The error response
 *
 * @psalm-suppress InvalidArgument $statusCode is clamped to int<400, 599>
 */',
        'startLine' => 487,
        'endLine' => 504,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\DocumentController',
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