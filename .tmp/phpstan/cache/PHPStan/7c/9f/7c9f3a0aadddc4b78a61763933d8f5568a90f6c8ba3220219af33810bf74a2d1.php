<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/TemplateVersionService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\TemplateVersionService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-5067aa89515f10869f67b2a75ac681aa631f7fc0f88068e4db4014daf3c43166',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\TemplateVersionService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/TemplateVersionService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\TemplateVersionService',
    'shortName' => 'TemplateVersionService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service for CRUD operations on template versions via OpenRegister
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 40,
    'endLine' => 289,
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
      'container' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
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
        'startLine' => 51,
        'endLine' => 51,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
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
        'startLine' => 52,
        'endLine' => 52,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'registerResolver' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
        'name' => 'registerResolver',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 53,
        'endLine' => 53,
        'startColumn' => 3,
        'endColumn' => 57,
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
            'startLine' => 51,
            'endLine' => 51,
            'startColumn' => 3,
            'endColumn' => 48,
            'parameterIndex' => 0,
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
            'startLine' => 52,
            'endLine' => 52,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'registerResolver' => 
          array (
            'name' => 'registerResolver',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 53,
            'endLine' => 53,
            'startColumn' => 3,
            'endColumn' => 57,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor for TemplateVersionService
 *
 * @param ContainerInterface $container Container for dependency injection
 * @param IAppManager $appManager App manager interface
 * @param OpenRegisterResolver $registerResolver Resolver for register/schema config
 *
 * @return void
 */',
        'startLine' => 50,
        'endLine' => 56,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
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
 * @throws \\RuntimeException If OpenRegister is not available
 */',
        'startLine' => 65,
        'endLine' => 76,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
        'aliasName' => NULL,
      ),
      'createVersion' => 
      array (
        'name' => 'createVersion',
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
            'startLine' => 93,
            'endLine' => 93,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'templateState' => 
          array (
            'name' => 'templateState',
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
            'startLine' => 94,
            'endLine' => 94,
            'startColumn' => 3,
            'endColumn' => 22,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'editor' => 
          array (
            'name' => 'editor',
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
            'startLine' => 95,
            'endLine' => 95,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'changelog' => 
          array (
            'name' => 'changelog',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 96,
                'endLine' => 96,
                'startTokenPos' => 203,
                'startFilePos' => 2891,
                'endTokenPos' => 203,
                'endFilePos' => 2894,
              ),
            ),
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
            'startLine' => 96,
            'endLine' => 96,
            'startColumn' => 3,
            'endColumn' => 27,
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
 * Create a version snapshot of a template\'s current state
 *
 * @param string $templateId The UUID of the parent template
 * @param array $templateState The current template data to capture
 * @param string $editor The Nextcloud user ID who made the edit
 * @param string|null $changelog Optional note describing the change
 *
 * @return array The created version object
 *
 * @throws Exception If version creation fails
 *
 * @spec openspec/specs/template-management/spec.md
 */',
        'startLine' => 92,
        'endLine' => 127,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
        'aliasName' => NULL,
      ),
      'getVersions' => 
      array (
        'name' => 'getVersions',
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
            'startLine' => 142,
            'endLine' => 142,
            'startColumn' => 30,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'limit' => 
          array (
            'name' => 'limit',
            'default' => 
            array (
              'code' => '20',
              'attributes' => 
              array (
                'startLine' => 142,
                'endLine' => 142,
                'startTokenPos' => 472,
                'startFilePos' => 4352,
                'endTokenPos' => 472,
                'endFilePos' => 4353,
              ),
            ),
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
            'startLine' => 142,
            'endLine' => 142,
            'startColumn' => 50,
            'endColumn' => 64,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'offset' => 
          array (
            'name' => 'offset',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 142,
                'endLine' => 142,
                'startTokenPos' => 481,
                'startFilePos' => 4370,
                'endTokenPos' => 481,
                'endFilePos' => 4370,
              ),
            ),
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
            'startLine' => 142,
            'endLine' => 142,
            'startColumn' => 67,
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
 * List versions for a template, ordered by version number descending
 *
 * @param string $templateId The UUID of the parent template
 * @param int $limit Maximum number of results (default: 20)
 * @param int $offset Result offset for pagination (default: 0)
 *
 * @return array{results: array, total: int} Paginated version results
 *
 * @throws Exception If listing fails
 *
 * @spec openspec/specs/template-management/spec.md
 */',
        'startLine' => 142,
        'endLine' => 160,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
        'aliasName' => NULL,
      ),
      'getVersion' => 
      array (
        'name' => 'getVersion',
        'parameters' => 
        array (
          'versionId' => 
          array (
            'name' => 'versionId',
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
            'startLine' => 173,
            'endLine' => 173,
            'startColumn' => 29,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get a single version by UUID
 *
 * @param string $versionId The version UUID
 *
 * @return array The version object
 *
 * @throws Exception If the version is not found
 *
 * @spec openspec/specs/template-management/spec.md
 */',
        'startLine' => 173,
        'endLine' => 194,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
        'aliasName' => NULL,
      ),
      'getNextVersionNumber' => 
      array (
        'name' => 'getNextVersionNumber',
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
            'startLine' => 207,
            'endLine' => 207,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the next version number for a template
 *
 * @param string $templateId The UUID of the parent template
 *
 * @return int The next version number (existing count + 1)
 *
 * @throws Exception If counting fails
 *
 * @spec openspec/specs/template-management/spec.md
 */',
        'startLine' => 207,
        'endLine' => 215,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
        'aliasName' => NULL,
      ),
      'restoreVersion' => 
      array (
        'name' => 'restoreVersion',
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
            'startLine' => 235,
            'endLine' => 235,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'versionId' => 
          array (
            'name' => 'versionId',
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
            'startLine' => 236,
            'endLine' => 236,
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'editor' => 
          array (
            'name' => 'editor',
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
            'startLine' => 237,
            'endLine' => 237,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'service' => 
          array (
            'name' => 'service',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 238,
            'endLine' => 238,
            'startColumn' => 3,
            'endColumn' => 26,
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
 * Restore a template to a previous version
 *
 * Saves the current template state as a new version, then updates the
 * template with the content from the target version.
 *
 * @param string $templateId The UUID of the template to restore
 * @param string $versionId The UUID of the version to restore to
 * @param string $editor The Nextcloud user ID performing the restore
 * @param TemplateService $service The template service for updating the template
 *
 * @return array The restored template object
 *
 * @throws Exception If restore fails
 *
 * @spec openspec/specs/template-management/spec.md
 */',
        'startLine' => 234,
        'endLine' => 265,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
        'aliasName' => NULL,
      ),
      'getDiff' => 
      array (
        'name' => 'getDiff',
        'parameters' => 
        array (
          'versionIdFrom' => 
          array (
            'name' => 'versionIdFrom',
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
            'startLine' => 279,
            'endLine' => 279,
            'startColumn' => 26,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'versionIdTo' => 
          array (
            'name' => 'versionIdTo',
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
            'startLine' => 279,
            'endLine' => 279,
            'startColumn' => 49,
            'endColumn' => 67,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get two versions for client-side diff comparison
 *
 * @param string $versionIdFrom The UUID of the source version
 * @param string $versionIdTo The UUID of the target version
 *
 * @return array{from: array, to: array} Both version objects
 *
 * @throws Exception If either version is not found
 *
 * @spec openspec/specs/template-management/spec.md
 */',
        'startLine' => 279,
        'endLine' => 288,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\TemplateVersionService',
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