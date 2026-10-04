<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Controller/IntakeController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Controller\IntakeController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-4d7a7aa12b2562e0210efd444188b50ce93f582c50d3682384530269b493686f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Controller\\IntakeController',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Controller/IntakeController.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Controller',
    'name' => 'OCA\\Filinq\\Controller\\IntakeController',
    'shortName' => 'IntakeController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Controller for the documents waiting for a record.
 *
 * @category Controller
 * @package  OCA\\Filinq\\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 53,
    'endLine' => 388,
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
      'intake' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'name' => 'intake',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\IntakeService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 71,
        'endLine' => 71,
        'startColumn' => 3,
        'endColumn' => 40,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'detachment' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'name' => 'detachment',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\IntakeDetachmentService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 72,
        'endLine' => 72,
        'startColumn' => 3,
        'endColumn' => 54,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'routing' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'name' => 'routing',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\IntakeRoutingService',
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
        'endColumn' => 48,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'parties' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'name' => 'parties',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\PartySuggestionService',
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
        'endColumn' => 50,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'userSession' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
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
        'startLine' => 75,
        'endLine' => 75,
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
            'startLine' => 69,
            'endLine' => 69,
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
            'startLine' => 70,
            'endLine' => 70,
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'intake' => 
          array (
            'name' => 'intake',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\IntakeService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 71,
            'endLine' => 71,
            'startColumn' => 3,
            'endColumn' => 40,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'detachment' => 
          array (
            'name' => 'detachment',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\IntakeDetachmentService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 72,
            'endLine' => 72,
            'startColumn' => 3,
            'endColumn' => 54,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'routing' => 
          array (
            'name' => 'routing',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\IntakeRoutingService',
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
            'endColumn' => 48,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'parties' => 
          array (
            'name' => 'parties',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\PartySuggestionService',
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
            'endColumn' => 50,
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
            'startLine' => 75,
            'endLine' => 75,
            'startColumn' => 3,
            'endColumn' => 44,
            'parameterIndex' => 6,
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
 * @param string $appName The app name.
 * @param IRequest $request The request.
 * @param IntakeService $intake The intake inbox.
 * @param IntakeDetachmentService $detachment Returns a filed document to the worklist.
 * @param IntakeRoutingService $routing What a consuming app declared per record type.
 * @param PartySuggestionService $parties Party suggestions and the corrections corpus.
 * @param IUserSession $userSession The current session.
 *
 * @return void
 */',
        'startLine' => 68,
        'endLine' => 79,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'aliasName' => NULL,
      ),
      'index' => 
      array (
        'name' => 'index',
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
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\NoAdminRequired',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * List the documents waiting for a clerk.
 *
 * @return JSONResponse The waiting documents.
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */',
        'startLine' => 88,
        'endLine' => 106,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'aliasName' => NULL,
      ),
      'assign' => 
      array (
        'name' => 'assign',
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
            'startLine' => 132,
            'endLine' => 132,
            'startColumn' => 3,
            'endColumn' => 14,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'register' => 
          array (
            'name' => 'register',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 133,
                'endLine' => 133,
                'startTokenPos' => 336,
                'startFilePos' => 4464,
                'endTokenPos' => 336,
                'endFilePos' => 4465,
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
            'endColumn' => 23,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'schema' => 
          array (
            'name' => 'schema',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 134,
                'endLine' => 134,
                'startTokenPos' => 345,
                'startFilePos' => 4487,
                'endTokenPos' => 345,
                'endFilePos' => 4488,
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
            'startLine' => 134,
            'endLine' => 134,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'id' => 
          array (
            'name' => 'id',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 135,
                'endLine' => 135,
                'startTokenPos' => 354,
                'startFilePos' => 4506,
                'endTokenPos' => 354,
                'endFilePos' => 4507,
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
            'startLine' => 135,
            'endLine' => 135,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'declaringApp' => 
          array (
            'name' => 'declaringApp',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 136,
                'endLine' => 136,
                'startTokenPos' => 363,
                'startFilePos' => 4535,
                'endTokenPos' => 363,
                'endFilePos' => 4536,
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
            'startLine' => 136,
            'endLine' => 136,
            'startColumn' => 3,
            'endColumn' => 27,
            'parameterIndex' => 4,
            'isOptional' => true,
          ),
          'typeReference' => 
          array (
            'name' => 'typeReference',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 137,
                'endLine' => 137,
                'startTokenPos' => 372,
                'startFilePos' => 4565,
                'endTokenPos' => 372,
                'endFilePos' => 4566,
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
            'startLine' => 137,
            'endLine' => 137,
            'startColumn' => 3,
            'endColumn' => 28,
            'parameterIndex' => 5,
            'isOptional' => true,
          ),
          'withAttachments' => 
          array (
            'name' => 'withAttachments',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 138,
                'endLine' => 138,
                'startTokenPos' => 381,
                'startFilePos' => 4595,
                'endTokenPos' => 381,
                'endFilePos' => 4599,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 138,
            'endLine' => 138,
            'startColumn' => 3,
            'endColumn' => 31,
            'parameterIndex' => 6,
            'isOptional' => true,
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
          0 => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\NoAdminRequired',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Assign one waiting document to a record.
 *
 * @param string $uuid The intake document.
 * @param string $register The target register slug.
 * @param string $schema The target schema slug.
 * @param string $id The target object id.
 * @param string $declaringApp App id of the app that owns the record type.
 * @param string $typeReference That app\'s reference for the record type.
 * @param bool $withAttachments Whether everything that arrived with the message goes along.
 *
 * @return JSONResponse The assigned document, or the refusal.
 *
 * @SuppressWarnings(PHPMD.BooleanArgumentFlag) `$withAttachments` is a field
 * of the request body, not a mode this code chose. Nextcloud binds a
 * controller parameter to the JSON key of the same name, so the SRP split
 * phpmd asks for is a split into two ROUTES for one thing a clerk does:
 * "file this letter, and the bijlagen that came with it". The service
 * behind it carries no flag, see IntakeService::assignWithAttachments().
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */',
        'startLine' => 130,
        'endLine' => 169,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'aliasName' => NULL,
      ),
      'reject' => 
      array (
        'name' => 'reject',
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
            'startLine' => 182,
            'endLine' => 182,
            'startColumn' => 25,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'reason' => 
          array (
            'name' => 'reason',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 182,
                'endLine' => 182,
                'startTokenPos' => 614,
                'startFilePos' => 5687,
                'endTokenPos' => 614,
                'endFilePos' => 5688,
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
            'startLine' => 182,
            'endLine' => 182,
            'startColumn' => 39,
            'endColumn' => 57,
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
            'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\NoAdminRequired',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Reject one waiting document.
 *
 * @param string $uuid The intake document.
 * @param string $reason Why it does not belong here.
 *
 * @return JSONResponse The rejected document, or the refusal.
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */',
        'startLine' => 181,
        'endLine' => 196,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'aliasName' => NULL,
      ),
      'detached' => 
      array (
        'name' => 'detached',
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
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\NoAdminRequired',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * List the documents taken back off a record.
 *
 * @return JSONResponse The worklist.
 *
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 */',
        'startLine' => 205,
        'endLine' => 223,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'aliasName' => NULL,
      ),
      'detach' => 
      array (
        'name' => 'detach',
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
            'startLine' => 237,
            'endLine' => 237,
            'startColumn' => 25,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'reason' => 
          array (
            'name' => 'reason',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 237,
                'endLine' => 237,
                'startTokenPos' => 888,
                'startFilePos' => 7234,
                'endTokenPos' => 888,
                'endFilePos' => 7235,
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
            'startLine' => 237,
            'endLine' => 237,
            'startColumn' => 38,
            'endColumn' => 56,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'documentName' => 
          array (
            'name' => 'documentName',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 237,
                'endLine' => 237,
                'startTokenPos' => 897,
                'startFilePos' => 7261,
                'endTokenPos' => 897,
                'endFilePos' => 7262,
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
            'startLine' => 237,
            'endLine' => 237,
            'startColumn' => 59,
            'endColumn' => 83,
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
            'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\NoAdminRequired',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Take one document off the record it is filed on.
 *
 * @param int $fileId The Nextcloud file id.
 * @param string $reason Why it does not belong there.
 * @param string $documentName The document\'s name, for a file that never had an intake record.
 *
 * @return JSONResponse The intake document, now on the worklist.
 *
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 */',
        'startLine' => 236,
        'endLine' => 255,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'aliasName' => NULL,
      ),
      'decideParty' => 
      array (
        'name' => 'decideParty',
        'parameters' => 
        array (
          'sender' => 
          array (
            'name' => 'sender',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 272,
                'endLine' => 272,
                'startTokenPos' => 1040,
                'startFilePos' => 8328,
                'endTokenPos' => 1040,
                'endFilePos' => 8329,
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
            'startLine' => 272,
            'endLine' => 272,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'decision' => 
          array (
            'name' => 'decision',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 273,
                'endLine' => 273,
                'startTokenPos' => 1049,
                'startFilePos' => 8353,
                'endTokenPos' => 1049,
                'endFilePos' => 8354,
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
            'startLine' => 273,
            'endLine' => 273,
            'startColumn' => 3,
            'endColumn' => 23,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'suggested' => 
          array (
            'name' => 'suggested',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 274,
                'endLine' => 274,
                'startTokenPos' => 1058,
                'startFilePos' => 8378,
                'endTokenPos' => 1059,
                'endFilePos' => 8379,
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
            'startLine' => 274,
            'endLine' => 274,
            'startColumn' => 3,
            'endColumn' => 23,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'accepted' => 
          array (
            'name' => 'accepted',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 275,
                'endLine' => 275,
                'startTokenPos' => 1068,
                'startFilePos' => 8402,
                'endTokenPos' => 1069,
                'endFilePos' => 8403,
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
            'startLine' => 275,
            'endLine' => 275,
            'startColumn' => 3,
            'endColumn' => 22,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'intakeDocument' => 
          array (
            'name' => 'intakeDocument',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 276,
                'endLine' => 276,
                'startTokenPos' => 1078,
                'startFilePos' => 8433,
                'endTokenPos' => 1078,
                'endFilePos' => 8434,
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
            'startLine' => 276,
            'endLine' => 276,
            'startColumn' => 3,
            'endColumn' => 29,
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
            'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\NoAdminRequired',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Record what a clerk did with a party suggestion.
 *
 * @param string $sender The sender the suggestion was made for.
 * @param string $decision One of `accepted`, `edited`, `rejected`.
 * @param array<string, mixed> $suggested What was proposed.
 * @param array<string, mixed> $accepted What was filed, empty on a rejection.
 * @param string $intakeDocument The intake document it was shown on.
 *
 * @return JSONResponse The stored correction.
 *
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 */',
        'startLine' => 270,
        'endLine' => 304,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'aliasName' => NULL,
      ),
      'declareRouting' => 
      array (
        'name' => 'declareRouting',
        'parameters' => 
        array (
          'declaringApp' => 
          array (
            'name' => 'declaringApp',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 326,
                'endLine' => 326,
                'startTokenPos' => 1295,
                'startFilePos' => 10099,
                'endTokenPos' => 1295,
                'endFilePos' => 10100,
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
            'startLine' => 326,
            'endLine' => 326,
            'startColumn' => 3,
            'endColumn' => 27,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'typeReference' => 
          array (
            'name' => 'typeReference',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 327,
                'endLine' => 327,
                'startTokenPos' => 1304,
                'startFilePos' => 10129,
                'endTokenPos' => 1304,
                'endFilePos' => 10130,
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
            'startLine' => 327,
            'endLine' => 327,
            'startColumn' => 3,
            'endColumn' => 28,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'routeTo' => 
          array (
            'name' => 'routeTo',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 328,
                'endLine' => 328,
                'startTokenPos' => 1313,
                'startFilePos' => 10153,
                'endTokenPos' => 1313,
                'endFilePos' => 10154,
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
            'startLine' => 328,
            'endLine' => 328,
            'startColumn' => 3,
            'endColumn' => 22,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'requiresAcceptance' => 
          array (
            'name' => 'requiresAcceptance',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 329,
                'endLine' => 329,
                'startTokenPos' => 1322,
                'startFilePos' => 10186,
                'endTokenPos' => 1322,
                'endFilePos' => 10190,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 329,
            'endLine' => 329,
            'startColumn' => 3,
            'endColumn' => 34,
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
            'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\NoAdminRequired',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Store what a consuming app declares about one of its record types.
 *
 * @param string $declaringApp The app making the declaration.
 * @param string $typeReference The record type, in that app\'s vocabulary.
 * @param string $routeTo The group inbound documents go to.
 * @param bool $requiresAcceptance Whether that group has to accept them.
 *
 * @return JSONResponse The stored declaration.
 *
 * @SuppressWarnings(PHPMD.BooleanArgumentFlag) `$requiresAcceptance` is a
 * field of the declaration the consuming app posts, not a mode this code
 * chose. It is stored on the routing rule and read back from there; it does
 * not branch this method at all, so there is no second responsibility here
 * to split off.
 *
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 */',
        'startLine' => 324,
        'endLine' => 349,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'aliasName' => NULL,
      ),
      'requireUser' => 
      array (
        'name' => 'requireUser',
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
 * Refuse an anonymous caller.
 *
 * @return JSONResponse|null The refusal, or null when somebody is logged in.
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */',
        'startLine' => 358,
        'endLine' => 368,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'aliasName' => NULL,
      ),
      'failure' => 
      array (
        'name' => 'failure',
        'parameters' => 
        array (
          'error' => 
          array (
            'name' => 'error',
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
            'startLine' => 379,
            'endLine' => 379,
            'startColumn' => 27,
            'endColumn' => 42,
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
 * Answer one failure with the status the service gave it.
 *
 * @param Throwable $error The failure.
 *
 * @return JSONResponse The refusal.
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */',
        'startLine' => 379,
        'endLine' => 387,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\IntakeController',
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