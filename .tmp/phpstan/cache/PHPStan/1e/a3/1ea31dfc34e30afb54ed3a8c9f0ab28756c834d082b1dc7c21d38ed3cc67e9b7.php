<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Controller/ReportRenderController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Controller\ReportRenderController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-31aa16ee04b2d1f14c27c66777188049f96e5b6c406a5eb1fd836608b665ac44',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Controller\\ReportRenderController',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Controller/ReportRenderController.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Controller',
    'name' => 'OCA\\Filinq\\Controller\\ReportRenderController',
    'shortName' => 'ReportRenderController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Controller for the external documents/render and documents/render/batch
 * endpoints.
 *
 * @category Controller
 * @package  OCA\\Filinq\\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 57,
    'endLine' => 277,
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
      'CFG_API_TOKEN' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
        'name' => 'CFG_API_TOKEN',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'report_render_api_token\'',
          'attributes' => 
          array (
            'startLine' => 65,
            'endLine' => 65,
            'startTokenPos' => 94,
            'startFilePos' => 2173,
            'endTokenPos' => 94,
            'endFilePos' => 2197,
          ),
        ),
        'docComment' => '/**
 * App-config key holding the shared-secret bearer token external
 * callers must present.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 65,
        'endLine' => 65,
        'startColumn' => 2,
        'endColumn' => 57,
      ),
    ),
    'immediateProperties' => 
    array (
      'renderService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
        'name' => 'renderService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\ReportRenderService',
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
        'endColumn' => 53,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'appConfig' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
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
        'startLine' => 82,
        'endLine' => 82,
        'startColumn' => 3,
        'endColumn' => 40,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
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
        'startLine' => 83,
        'endLine' => 83,
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
            'startLine' => 79,
            'endLine' => 79,
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
            'startLine' => 80,
            'endLine' => 80,
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'renderService' => 
          array (
            'name' => 'renderService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\ReportRenderService',
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
            'endColumn' => 53,
            'parameterIndex' => 2,
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
            'startLine' => 82,
            'endLine' => 82,
            'startColumn' => 3,
            'endColumn' => 40,
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
            'startLine' => 83,
            'endLine' => 83,
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
 * Constructor for ReportRenderController.
 *
 * @param string $appName The application name.
 * @param IRequest $request The request object.
 * @param ReportRenderService $renderService Slug resolution + render + store.
 * @param IAppConfig $appConfig App-config reader for the shared-secret token.
 * @param LoggerInterface $logger Logger for rejected/failed calls.
 *
 * @return void
 */',
        'startLine' => 78,
        'endLine' => 87,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
        'aliasName' => NULL,
      ),
      'render' => 
      array (
        'name' => 'render',
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
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\PublicPage',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
          1 => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\NoCSRFRequired',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
          2 => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\AnonRateLimit',
            'isRepeated' => false,
            'arguments' => 
            array (
              'limit' => 
              array (
                'code' => '60',
                'attributes' => 
                array (
                  'startLine' => 98,
                  'endLine' => 98,
                  'startTokenPos' => 183,
                  'startFilePos' => 3202,
                  'endTokenPos' => 183,
                  'endFilePos' => 3203,
                ),
              ),
              'period' => 
              array (
                'code' => '60',
                'attributes' => 
                array (
                  'startLine' => 98,
                  'endLine' => 98,
                  'startTokenPos' => 189,
                  'startFilePos' => 3214,
                  'endTokenPos' => 189,
                  'endFilePos' => 3215,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * POST /api/v1/documents/render
 *
 * @return JSONResponse
 *
 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md#requirement-single-document-render-by-template-slug-req-rra-01
 */',
        'startLine' => 96,
        'endLine' => 131,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
        'aliasName' => NULL,
      ),
      'renderBatch' => 
      array (
        'name' => 'renderBatch',
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
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\PublicPage',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
          1 => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\NoCSRFRequired',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
          2 => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\AnonRateLimit',
            'isRepeated' => false,
            'arguments' => 
            array (
              'limit' => 
              array (
                'code' => '20',
                'attributes' => 
                array (
                  'startLine' => 142,
                  'endLine' => 142,
                  'startTokenPos' => 457,
                  'startFilePos' => 4368,
                  'endTokenPos' => 457,
                  'endFilePos' => 4369,
                ),
              ),
              'period' => 
              array (
                'code' => '60',
                'attributes' => 
                array (
                  'startLine' => 142,
                  'endLine' => 142,
                  'startTokenPos' => 463,
                  'startFilePos' => 4380,
                  'endTokenPos' => 463,
                  'endFilePos' => 4381,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * POST /api/v1/documents/render/batch
 *
 * @return JSONResponse
 *
 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md#requirement-batch-render-and-zip-req-rra-03
 */',
        'startLine' => 140,
        'endLine' => 178,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
        'aliasName' => NULL,
      ),
      'resolveTenantId' => 
      array (
        'name' => 'resolveTenantId',
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
 * Parse the optional `tenantId` request parameter.
 *
 * @return string|null The tenant id, or null when absent/empty/non-string.
 */',
        'startLine' => 185,
        'endLine' => 192,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
        'aliasName' => NULL,
      ),
      'resolveOptions' => 
      array (
        'name' => 'resolveOptions',
        'parameters' => 
        array (
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
 * Parse the optional `options` request parameter, folding in `userId`
 * when the caller supplied one at the top level.
 *
 * @return array<string, mixed> The options array.
 */',
        'startLine' => 200,
        'endLine' => 212,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
        'aliasName' => NULL,
      ),
      'requireValidToken' => 
      array (
        'name' => 'requireValidToken',
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
                  'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
                  'isIdentifier' => false,
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
 * Verify the caller\'s bearer token against the configured shared secret.
 *
 * Fails closed: an unconfigured token rejects every request rather than
 * silently accepting all callers.
 *
 * @return JSONResponse|null A 401 response when the token is missing,
 *                           mismatched, or unconfigured; null when valid.
 *
 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md#requirement-shared-secret-authentication-req-rra-00
 */',
        'startLine' => 225,
        'endLine' => 248,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
        'aliasName' => NULL,
      ),
      'exceptionResponse' => 
      array (
        'name' => 'exceptionResponse',
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
            'startLine' => 258,
            'endLine' => 258,
            'startColumn' => 37,
            'endColumn' => 56,
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
 * Map a service-layer Exception to a JSON error response, using the
 * exception\'s own code when it is a valid HTTP status.
 *
 * @param Exception $exception The caught exception.
 *
 * @return JSONResponse
 */',
        'startLine' => 258,
        'endLine' => 276,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\ReportRenderController',
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