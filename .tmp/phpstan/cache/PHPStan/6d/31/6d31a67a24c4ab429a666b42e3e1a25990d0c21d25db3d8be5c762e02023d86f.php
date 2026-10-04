<?php declare(strict_types = 1);

// phpinternal-PHPStan\BetterReflection\Reflection\ReflectionFunction-mt_rand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-dev-master@709e512-8.3',
   'data' => 
  array (
    'name' => 'mt_rand',
    'parameters' => 
    array (
      'min' => 
      array (
        'name' => 'min',
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
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 22,
        'endColumn' => 29,
        'parameterIndex' => 0,
        'isOptional' => false,
      ),
      'max' => 
      array (
        'name' => 'max',
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
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 32,
        'endColumn' => 39,
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
        'name' => 'int',
        'isIdentifier' => true,
      ),
    ),
    'attributes' => 
    array (
    ),
    'docComment' => '/**
 * Generate a random value via the Mersenne Twister Random Number Generator
 * @link https://php.net/manual/en/function.mt-rand.php
 * @param int $min [optional] <p>
 * Optional lowest value to be returned (default: 0)
 * </p>
 * @param int $max [optional] <p>
 * Optional highest value to be returned (default: mt_getrandmax())
 * </p>
 * @return int A random integer value between min (or 0)
 * and max (or mt_getrandmax, inclusive)
 */',
    'startLine' => 20,
    'endLine' => 22,
    'startColumn' => 5,
    'endColumn' => 5,
    'couldThrow' => false,
    'isClosure' => false,
    'isGenerator' => false,
    'isVariadic' => false,
    'isStatic' => false,
    'namespace' => NULL,
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\InternalLocatedSource',
      'data' => 
      array (
        'name' => 'mt_rand',
        'filename' => 'phpstorm-stubs:random/random.stub',
        'extensionName' => 'random',
        'aliasName' => NULL,
      ),
    ),
  ),
));