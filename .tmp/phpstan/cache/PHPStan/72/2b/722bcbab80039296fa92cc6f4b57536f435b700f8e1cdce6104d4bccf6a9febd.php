<?php declare(strict_types = 1);

// phpinternal-PHPStan\BetterReflection\Reflection\ReflectionFunction-interface_exists
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-dev-master@709e512-8.3',
   'data' => 
  array (
    'name' => 'interface_exists',
    'parameters' => 
    array (
      'interface' => 
      array (
        'name' => 'interface',
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
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 31,
        'endColumn' => 47,
        'parameterIndex' => 0,
        'isOptional' => false,
      ),
      'autoload' => 
      array (
        'name' => 'autoload',
        'default' => 
        array (
          'code' => '\\true',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 17,
            'startTokenPos' => 23,
            'startFilePos' => 532,
            'endTokenPos' => 23,
            'endFilePos' => 535,
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
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 50,
        'endColumn' => 70,
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
        'name' => 'bool',
        'isIdentifier' => true,
      ),
    ),
    'attributes' => 
    array (
    ),
    'docComment' => '/**
 * Checks if the interface has been defined
 * @link https://php.net/manual/en/function.interface-exists.php
 * @param string $interface <p>
 * The interface name
 * </p>
 * @param bool $autoload [optional] <p>
 * Whether to call autoload or not by default.
 * </p>
 * @return bool true if the interface given by
 * <i>interface_name</i> has been defined, false otherwise.
 * @since 5.0
 */',
    'startLine' => 17,
    'endLine' => 19,
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
        'name' => 'interface_exists',
        'filename' => 'phpstorm-stubs:Core/Core.stub',
        'extensionName' => 'Core',
        'aliasName' => NULL,
      ),
    ),
  ),
));