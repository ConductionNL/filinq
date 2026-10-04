<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/OpenRegisterAvailabilityService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\OpenRegisterAvailabilityService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-1705cc7103cebf9f5ccc1fdb72dce902aebf056f598e928cb29fa482fe6e444b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\OpenRegisterAvailabilityService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/OpenRegisterAvailabilityService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\OpenRegisterAvailabilityService',
    'shortName' => 'OpenRegisterAvailabilityService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Resolves OpenRegister availability, minimum version and ObjectService
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/admin-settings/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 44,
    'endLine' => 160,
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
      'OPENREGISTER_APP_ID' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\OpenRegisterAvailabilityService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OpenRegisterAvailabilityService',
        'name' => 'OPENREGISTER_APP_ID',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'openregister\'',
          'attributes' => 
          array (
            'startLine' => 51,
            'endLine' => 51,
            'startTokenPos' => 50,
            'startFilePos' => 1466,
            'endTokenPos' => 50,
            'endFilePos' => 1479,
          ),
        ),
        'docComment' => '/**
 * The unique identifier for the OpenRegister application
 *
 * @var string The ID of the OpenRegister app
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 51,
        'endLine' => 51,
        'startColumn' => 2,
        'endColumn' => 51,
      ),
      'FALLBACK_MIN_OPENREGISTER_VERSION' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\OpenRegisterAvailabilityService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OpenRegisterAvailabilityService',
        'name' => 'FALLBACK_MIN_OPENREGISTER_VERSION',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'0.2.10\'',
          'attributes' => 
          array (
            'startLine' => 64,
            'endLine' => 64,
            'startTokenPos' => 63,
            'startFilePos' => 2005,
            'endTokenPos' => 63,
            'endFilePos' => 2012,
          ),
        ),
        'docComment' => '/**
 * Fallback minimum OpenRegister version if the manifest cannot be read.
 *
 * The canonical source of truth is `openspec/manifest.yaml`
 * (`dependencies.openregister.minVersion`) per
 * docudesk-adopt-or-abstractions task 1. This constant is only used when
 * the manifest is missing/unreadable so the runtime still has a defensive
 * floor; the manifest validator enforces parity.
 *
 * @var string Fallback minimum required version of OpenRegister.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 64,
        'endLine' => 64,
        'startColumn' => 2,
        'endColumn' => 59,
      ),
    ),
    'immediateProperties' => 
    array (
      'cachedMinVersion' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\OpenRegisterAvailabilityService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OpenRegisterAvailabilityService',
        'name' => 'cachedMinVersion',
        'modifiers' => 4,
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
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 71,
            'endLine' => 71,
            'startTokenPos' => 77,
            'startFilePos' => 2156,
            'endTokenPos' => 77,
            'endFilePos' => 2159,
          ),
        ),
        'docComment' => '/**
 * Cached minimum OpenRegister version resolved from the manifest.
 *
 * @var string|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 71,
        'endLine' => 71,
        'startColumn' => 2,
        'endColumn' => 42,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'appManager' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\OpenRegisterAvailabilityService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OpenRegisterAvailabilityService',
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
      'container' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\OpenRegisterAvailabilityService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OpenRegisterAvailabilityService',
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
        'startLine' => 83,
        'endLine' => 83,
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
            'startLine' => 82,
            'endLine' => 82,
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
            'startLine' => 83,
            'endLine' => 83,
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
 * OpenRegisterAvailabilityService constructor
 *
 * @param IAppManager $appManager App manager interface
 * @param ContainerInterface $container Container for DI
 *
 * @return void
 */',
        'startLine' => 81,
        'endLine' => 86,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OpenRegisterAvailabilityService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OpenRegisterAvailabilityService',
        'currentClassName' => 'OCA\\Filinq\\Service\\OpenRegisterAvailabilityService',
        'aliasName' => NULL,
      ),
      'isInstalled' => 
      array (
        'name' => 'isInstalled',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Checks if OpenRegister is installed and meets version requirements
 *
 * @return bool True if OpenRegister is installed and meets version requirements
 *
 * @spec openspec/specs/admin-settings/spec.md
 */',
        'startLine' => 95,
        'endLine' => 102,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OpenRegisterAvailabilityService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OpenRegisterAvailabilityService',
        'currentClassName' => 'OCA\\Filinq\\Service\\OpenRegisterAvailabilityService',
        'aliasName' => NULL,
      ),
      'getMinVersion' => 
      array (
        'name' => 'getMinVersion',
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
 * Resolve the minimum supported OpenRegister version.
 *
 * Reads `dependencies.openregister.minVersion` from the project\'s
 * `openspec/manifest.yaml`. Falls back to FALLBACK_MIN_OPENREGISTER_VERSION
 * when the manifest is missing, unreadable, or shaped unexpectedly so the
 * boot path stays defensive. The result is memoised per-instance.
 *
 * @return string Semantic version of the minimum supported OpenRegister.
 *
 * @spec openspec/specs/admin-settings/spec.md
 */',
        'startLine' => 116,
        'endLine' => 137,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OpenRegisterAvailabilityService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OpenRegisterAvailabilityService',
        'currentClassName' => 'OCA\\Filinq\\Service\\OpenRegisterAvailabilityService',
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
                  'name' => 'OCA\\OpenRegister\\Service\\ObjectService',
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
 * Attempts to retrieve the OpenRegister service from the container
 *
 * @return \\OCA\\OpenRegister\\Service\\ObjectService|null The OpenRegister service
 *
 * @throws \\RuntimeException If the service is not available
 *
 * @spec openspec/specs/admin-settings/spec.md
 */',
        'startLine' => 148,
        'endLine' => 159,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OpenRegisterAvailabilityService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OpenRegisterAvailabilityService',
        'currentClassName' => 'OCA\\Filinq\\Service\\OpenRegisterAvailabilityService',
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