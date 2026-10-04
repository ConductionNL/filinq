<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/OpenRegisterServiceLocator.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\OpenRegisterServiceLocator
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-0f00cdf87a14eae6ce3f11d7039fed3fc3cc12c7b8655181b5803846fa5d3de7',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\OpenRegisterServiceLocator',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/OpenRegisterServiceLocator.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\OpenRegisterServiceLocator',
    'shortName' => 'OpenRegisterServiceLocator',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Resolves OpenRegister services/mappers, or fails loudly when OR is absent.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/anonymization/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 44,
    'endLine' => 125,
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
    ),
    'immediateProperties' => 
    array (
      'appManager' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\OpenRegisterServiceLocator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OpenRegisterServiceLocator',
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
        'startLine' => 54,
        'endLine' => 54,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'container' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\OpenRegisterServiceLocator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OpenRegisterServiceLocator',
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
        'startLine' => 55,
        'endLine' => 55,
        'startColumn' => 3,
        'endColumn' => 48,
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
            'startLine' => 54,
            'endLine' => 54,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 0,
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
            'startLine' => 55,
            'endLine' => 55,
            'startColumn' => 3,
            'endColumn' => 48,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor for OpenRegisterServiceLocator
 *
 * @param IAppManager $appManager App manager used to check whether OpenRegister is installed.
 * @param ContainerInterface $container Container the OpenRegister service is resolved from.
 *
 * @return void
 */',
        'startLine' => 53,
        'endLine' => 58,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OpenRegisterServiceLocator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OpenRegisterServiceLocator',
        'currentClassName' => 'OCA\\Filinq\\Service\\OpenRegisterServiceLocator',
        'aliasName' => NULL,
      ),
      'get' => 
      array (
        'name' => 'get',
        'parameters' => 
        array (
          'className' => 
          array (
            'name' => 'className',
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
            'startLine' => 71,
            'endLine' => 71,
            'startColumn' => 22,
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
            'name' => 'mixed',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get an OpenRegister service or mapper by class name.
 *
 * @param string $className The fully qualified class name.
 *
 * @return mixed The service instance.
 *
 * @throws RuntimeException If OpenRegister is not available.
 *
 * @spec openspec/specs/anonymization/spec.md
 */',
        'startLine' => 71,
        'endLine' => 77,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OpenRegisterServiceLocator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OpenRegisterServiceLocator',
        'currentClassName' => 'OCA\\Filinq\\Service\\OpenRegisterServiceLocator',
        'aliasName' => NULL,
      ),
      'lastResidualEntities' => 
      array (
        'name' => 'lastResidualEntities',
        'parameters' => 
        array (
          'fileService' => 
          array (
            'name' => 'fileService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 94,
            'endLine' => 94,
            'startColumn' => 39,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Read OpenRegister\'s best-effort residual-entity list, when it exposes one.
 *
 * OpenRegister produces the anonymised file even when some entity text could
 * not be removed (e.g. the ExApp NER over-captured a span across table cells,
 * so the value is not contiguous in the document). Pulling the residual list
 * lets the operator be warned and iterate. Defensive method_exists() guard
 * for older OpenRegister versions without the best-effort API.
 *
 * @param mixed $fileService OpenRegister FileService (resolved reflectively).
 *
 * @return array<int, mixed> The residual entities, or an empty array.
 *
 * @spec openspec/specs/anonymization/spec.md
 */',
        'startLine' => 94,
        'endLine' => 100,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OpenRegisterServiceLocator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OpenRegisterServiceLocator',
        'currentClassName' => 'OCA\\Filinq\\Service\\OpenRegisterServiceLocator',
        'aliasName' => NULL,
      ),
      'lastPlaceholderMap' => 
      array (
        'name' => 'lastPlaceholderMap',
        'parameters' => 
        array (
          'fileService' => 
          array (
            'name' => 'fileService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 118,
            'endLine' => 118,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Read OpenRegister\'s per-entity placeholder map, when it exposes one.
 *
 * The EXACT placeholder OpenRegister emitted per global entity id (e.g.
 * `"7" => "[PERSOON: 1]"`), so the grondslagen summary renders the same
 * scope-local number + localized label the document carries instead of
 * re-deriving `[<TYPE>: <entity_id>]`. Defensive method_exists() for older
 * OpenRegister versions (the summary then falls back to its own scope-local
 * map, or omits the entity).
 *
 * @param mixed $fileService OpenRegister FileService (resolved reflectively).
 *
 * @return array<string, string> The placeholder map, or an empty array.
 *
 * @spec openspec/specs/anonymization/spec.md
 */',
        'startLine' => 118,
        'endLine' => 124,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OpenRegisterServiceLocator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OpenRegisterServiceLocator',
        'currentClassName' => 'OCA\\Filinq\\Service\\OpenRegisterServiceLocator',
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