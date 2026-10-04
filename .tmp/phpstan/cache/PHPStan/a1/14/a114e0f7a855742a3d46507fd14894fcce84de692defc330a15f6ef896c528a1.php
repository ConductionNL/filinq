<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Controller/PostRegisterController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Controller\PostRegisterController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-8f0abbdea997c9125e05d8fcb2f279ccaa9944a85e37a17c3402c2b4e3151a0d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Controller\\PostRegisterController',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Controller/PostRegisterController.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Controller',
    'name' => 'OCA\\Filinq\\Controller\\PostRegisterController',
    'shortName' => 'PostRegisterController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Reads the post register over HTTP.
 *
 * 🔴 A FAILED READ ANSWERS AN ERROR, NEVER AN EMPTY LIST. The reader raises
 * rather than reporting "no answers" or "nothing open", and this controller
 * keeps that: an empty list on this endpoint means the unit has nothing
 * outstanding, and a handler acts on it. Turning a register outage into `[]`
 * here would empty somebody\'s work list and they would go home.
 *
 * 🔑 THE LEAF IS NOT THIS. ADR-066\'s leaf for the open post list is blocked on
 * filinq shipping no `leaves` webpack entry, so what the post register offers
 * today is these endpoints. A consuming app can already read them; when the
 * leaf lands it renders what is here rather than a second query.
 *
 * @category Controller
 * @package  OCA\\Filinq\\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 59,
    'endLine' => 165,
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
      'reader' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PostRegisterController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PostRegisterController',
        'name' => 'reader',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\PostRegisterReader',
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
        'endColumn' => 45,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'userSession' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PostRegisterController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PostRegisterController',
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
            'startLine' => 72,
            'endLine' => 72,
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
            'startLine' => 73,
            'endLine' => 73,
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'reader' => 
          array (
            'name' => 'reader',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\PostRegisterReader',
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
            'endColumn' => 45,
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
            'startLine' => 75,
            'endLine' => 75,
            'startColumn' => 3,
            'endColumn' => 44,
            'parameterIndex' => 3,
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
 * @param string             $appName     The app name.
 * @param IRequest           $request     The request.
 * @param PostRegisterReader $reader      The post register\'s derived reads.
 * @param IUserSession       $userSession The current session.
 *
 * @return void
 */',
        'startLine' => 71,
        'endLine' => 79,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PostRegisterController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PostRegisterController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\PostRegisterController',
        'aliasName' => NULL,
      ),
      'openPost' => 
      array (
        'name' => 'openPost',
        'parameters' => 
        array (
          'unit' => 
          array (
            'name' => 'unit',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 91,
                'endLine' => 91,
                'startTokenPos' => 148,
                'startFilePos' => 3056,
                'endTokenPos' => 148,
                'endFilePos' => 3057,
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
            'startLine' => 91,
            'endLine' => 91,
            'startColumn' => 27,
            'endColumn' => 43,
            'parameterIndex' => 0,
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
 * What a unit still has to answer, oldest first.
 *
 * @param string $unit The organisational unit.
 *
 * @return JSONResponse The undischarged inbound entries.
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
 */',
        'startLine' => 90,
        'endLine' => 94,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PostRegisterController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PostRegisterController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\PostRegisterController',
        'aliasName' => NULL,
      ),
      'answers' => 
      array (
        'name' => 'answers',
        'parameters' => 
        array (
          'uuid' => 
          array (
            'name' => 'uuid',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 106,
                'endLine' => 106,
                'startTokenPos' => 213,
                'startFilePos' => 3549,
                'endTokenPos' => 213,
                'endFilePos' => 3550,
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
            'startLine' => 106,
            'endLine' => 106,
            'startColumn' => 26,
            'endColumn' => 42,
            'parameterIndex' => 0,
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
 * The outbound registrations that answer one inbound entry.
 *
 * @param string $uuid The inbound registration.
 *
 * @return JSONResponse The answers, and whether there are any.
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
 */',
        'startLine' => 105,
        'endLine' => 119,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PostRegisterController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PostRegisterController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\PostRegisterController',
        'aliasName' => NULL,
      ),
      'series' => 
      array (
        'name' => 'series',
        'parameters' => 
        array (
          'unit' => 
          array (
            'name' => 'unit',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 131,
                'endLine' => 131,
                'startTokenPos' => 320,
                'startFilePos' => 4389,
                'endTokenPos' => 320,
                'endFilePos' => 4390,
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
            'startLine' => 131,
            'endLine' => 131,
            'startColumn' => 25,
            'endColumn' => 41,
            'parameterIndex' => 0,
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
 * A unit\'s series, in number order, with every gap accounted for.
 *
 * @param string $unit The organisational unit.
 *
 * @return JSONResponse The series.
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
 */',
        'startLine' => 130,
        'endLine' => 134,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PostRegisterController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PostRegisterController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\PostRegisterController',
        'aliasName' => NULL,
      ),
      'read' => 
      array (
        'name' => 'read',
        'parameters' => 
        array (
          'handler' => 
          array (
            'name' => 'handler',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 145,
            'endLine' => 145,
            'startColumn' => 24,
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
 * Run one read behind the session guard, turning a failure into an error.
 *
 * @param callable():array<string, mixed> $handler The read.
 *
 * @return JSONResponse The answer.
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
 */',
        'startLine' => 145,
        'endLine' => 164,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PostRegisterController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PostRegisterController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\PostRegisterController',
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