<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/AnonymiserBackendStateClient.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\AnonymiserBackendStateClient
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-a9a62a7947f8cf05355351b33a5950eb426896cdd0dcd608f80b1611b0ed15d8',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\AnonymiserBackendStateClient',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/AnonymiserBackendStateClient.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\AnonymiserBackendStateClient',
    'shortName' => 'AnonymiserBackendStateClient',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Wraps the OpenRegister AnonymisationBackendService::getState() call.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @spec openspec/changes/anonymiser-backend-warning/tasks.md#task-1
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 42,
    'endLine' => 97,
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
      'OR_SERVICE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymiserBackendStateClient',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymiserBackendStateClient',
        'name' => 'OR_SERVICE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'OCA\\OpenRegister\\Service\\AnonymisationBackendService\'',
          'attributes' => 
          array (
            'startLine' => 49,
            'endLine' => 49,
            'startTokenPos' => 45,
            'startFilePos' => 1446,
            'endTokenPos' => 45,
            'endFilePos' => 1499,
          ),
        ),
        'docComment' => '/**
 * Fully-qualified class name of the OpenRegister backend service.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 49,
        'endLine' => 49,
        'startColumn' => 2,
        'endColumn' => 83,
      ),
    ),
    'immediateProperties' => 
    array (
      'container' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymiserBackendStateClient',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymiserBackendStateClient',
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
        'startLine' => 58,
        'endLine' => 58,
        'startColumn' => 3,
        'endColumn' => 48,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymiserBackendStateClient',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymiserBackendStateClient',
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
        'startLine' => 59,
        'endLine' => 59,
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
            'startLine' => 58,
            'endLine' => 58,
            'startColumn' => 3,
            'endColumn' => 48,
            'parameterIndex' => 0,
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
            'startLine' => 59,
            'endLine' => 59,
            'startColumn' => 3,
            'endColumn' => 42,
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
 * Constructor.
 *
 * @param ContainerInterface $container DI container used for lazy service resolution.
 * @param LoggerInterface $logger Logger for debug/warning output.
 */',
        'startLine' => 57,
        'endLine' => 61,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymiserBackendStateClient',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymiserBackendStateClient',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymiserBackendStateClient',
        'aliasName' => NULL,
      ),
      'getState' => 
      array (
        'name' => 'getState',
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
 * Retrieve the current anonymisation backend state.
 *
 * Delegates to `OCA\\OpenRegister\\Service\\AnonymisationBackendService::getState()`.
 * Falls back to `[\'method\' => \'regex\', \'appApiInstalled\' => false]` when the
 * companion service is not yet deployed, so the admin warning is shown rather
 * than silently suppressed.
 *
 * The returned array contains at least:
 * - `method`         (string) — one of \'regex\', \'openanonymiser\', \'presidio\', \'llm\', or a URL.
 * - `appApiInstalled` (bool)  — whether the app_api ExApp host is installed on this instance.
 *
 * @return array<string, mixed> State array from OpenRegister, or safe defaults.
 *
 * @spec openspec/changes/anonymiser-backend-warning/tasks.md#task-1
 */',
        'startLine' => 79,
        'endLine' => 96,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymiserBackendStateClient',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymiserBackendStateClient',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymiserBackendStateClient',
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