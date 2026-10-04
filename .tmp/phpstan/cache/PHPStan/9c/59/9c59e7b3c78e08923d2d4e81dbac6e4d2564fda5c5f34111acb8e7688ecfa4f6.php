<?php declare(strict_types = 1);

// osfsl-/home/rubenlinde/memcap-work/lq-lanes/fq/vendor/composer/../twig/twig/src/Sandbox/SecurityPolicyInterface.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Twig\Sandbox\SecurityPolicyInterface
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-4269593f7bc80815a2426efde8a837199de346df5adf9fe1a4eb1ca3558d1714-8.3-6.70.0.6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Twig\\Sandbox\\SecurityPolicyInterface',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/vendor/composer/../twig/twig/src/Sandbox/SecurityPolicyInterface.php',
      ),
    ),
    'namespace' => 'Twig\\Sandbox',
    'name' => 'Twig\\Sandbox\\SecurityPolicyInterface',
    'shortName' => 'SecurityPolicyInterface',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Interface that all security policy classes must implements.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 19,
    'endLine' => 46,
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
      'checkSecurity' => 
      array (
        'name' => 'checkSecurity',
        'parameters' => 
        array (
          'tags' => 
          array (
            'name' => 'tags',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 35,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'filters' => 
          array (
            'name' => 'filters',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 42,
            'endColumn' => 49,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'functions' => 
          array (
            'name' => 'functions',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 52,
            'endColumn' => 61,
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
 * @param string[] $tags
 * @param string[] $filters
 * @param string[] $functions
 * @param string[] $tests
 *
 * @throws SecurityError
 */',
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 89,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Twig\\Sandbox',
        'declaringClassName' => 'Twig\\Sandbox\\SecurityPolicyInterface',
        'implementingClassName' => 'Twig\\Sandbox\\SecurityPolicyInterface',
        'currentClassName' => 'Twig\\Sandbox\\SecurityPolicyInterface',
        'aliasName' => NULL,
      ),
      'checkMethodAllowed' => 
      array (
        'name' => 'checkMethodAllowed',
        'parameters' => 
        array (
          'obj' => 
          array (
            'name' => 'obj',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 37,
            'endLine' => 37,
            'startColumn' => 40,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'method' => 
          array (
            'name' => 'method',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 37,
            'endLine' => 37,
            'startColumn' => 46,
            'endColumn' => 52,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param object $obj
 * @param string $method
 *
 * @throws SecurityNotAllowedMethodError
 */',
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 60,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Twig\\Sandbox',
        'declaringClassName' => 'Twig\\Sandbox\\SecurityPolicyInterface',
        'implementingClassName' => 'Twig\\Sandbox\\SecurityPolicyInterface',
        'currentClassName' => 'Twig\\Sandbox\\SecurityPolicyInterface',
        'aliasName' => NULL,
      ),
      'checkPropertyAllowed' => 
      array (
        'name' => 'checkPropertyAllowed',
        'parameters' => 
        array (
          'obj' => 
          array (
            'name' => 'obj',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 45,
            'endLine' => 45,
            'startColumn' => 42,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'property' => 
          array (
            'name' => 'property',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 45,
            'endLine' => 45,
            'startColumn' => 48,
            'endColumn' => 56,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param object $obj
 * @param string $property
 *
 * @throws SecurityNotAllowedPropertyError
 */',
        'startLine' => 45,
        'endLine' => 45,
        'startColumn' => 5,
        'endColumn' => 64,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Twig\\Sandbox',
        'declaringClassName' => 'Twig\\Sandbox\\SecurityPolicyInterface',
        'implementingClassName' => 'Twig\\Sandbox\\SecurityPolicyInterface',
        'currentClassName' => 'Twig\\Sandbox\\SecurityPolicyInterface',
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