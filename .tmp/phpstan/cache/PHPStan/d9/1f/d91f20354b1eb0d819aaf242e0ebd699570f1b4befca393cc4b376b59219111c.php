<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/AppInfo/MetricsEngineFactory.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\AppInfo\MetricsEngineFactory
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-b3b30048f3348be9eb19ee645b3b414ffa35370cfa9d5c9cee0f13b0ce8599d0',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\AppInfo\\MetricsEngineFactory',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/AppInfo/MetricsEngineFactory.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\AppInfo',
    'name' => 'OCA\\Filinq\\AppInfo\\MetricsEngineFactory',
    'shortName' => 'MetricsEngineFactory',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Constructs the AppHost MetricsEngine from the server container.
 *
 * @category AppInfo
 * @package  OCA\\Filinq\\AppInfo
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/adopt-apphost/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 55,
    'endLine' => 84,
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
    ),
    'immediateMethods' => 
    array (
      'build' => 
      array (
        'name' => 'build',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 24,
            'endColumn' => 52,
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
            'name' => 'object',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Build the metrics engine.
 *
 * Returns `object`, not the engine\'s own type — see the file docblock.
 *
 * @param ContainerInterface $container The server container.
 *
 * @return object The constructed OpenRegister AppHost MetricsEngine.
 *
 * @spec openspec/specs/adopt-apphost/spec.md
 */',
        'startLine' => 67,
        'endLine' => 83,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\AppInfo',
        'declaringClassName' => 'OCA\\Filinq\\AppInfo\\MetricsEngineFactory',
        'implementingClassName' => 'OCA\\Filinq\\AppInfo\\MetricsEngineFactory',
        'currentClassName' => 'OCA\\Filinq\\AppInfo\\MetricsEngineFactory',
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