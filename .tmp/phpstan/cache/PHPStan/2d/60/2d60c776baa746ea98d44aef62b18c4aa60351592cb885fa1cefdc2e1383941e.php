<?php declare(strict_types = 1);

// phpinternal-PHPStan\BetterReflection\Reflection\ReflectionFunction-chmod
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-dev-master@709e512-8.3',
   'data' => 
  array (
    'name' => 'chmod',
    'parameters' => 
    array (
      'filename' => 
      array (
        'name' => 'filename',
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
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 20,
        'endColumn' => 35,
        'parameterIndex' => 0,
        'isOptional' => false,
      ),
      'permissions' => 
      array (
        'name' => 'permissions',
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
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 38,
        'endColumn' => 53,
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
        'name' => 'bool',
        'isIdentifier' => true,
      ),
    ),
    'attributes' => 
    array (
    ),
    'docComment' => '/**
 * Changes file mode
 * @link https://php.net/manual/en/function.chmod.php
 * @param string $filename <p>
 * Path to the file.
 * </p>
 * @param int $permissions <p>
 * Note that mode is not automatically
 * assumed to be an octal value, so strings (such as "g+w") will
 * not work properly. To ensure the expected operation,
 * you need to prefix mode with a zero (0):
 * </p>
 * <pre>
 * <?php
 * chmod("/somedir/somefile", 755);   // decimal; probably incorrect
 * chmod("/somedir/somefile", "u+rwx,go+rx"); // string; incorrect
 * chmod("/somedir/somefile", 0755);  // octal; correct value of mode
 * ?>
 * </pre>
 * <p>
 * The mode parameter consists of three octal
 * number components specifying access restrictions for the owner,
 * the user group in which the owner is in, and to everybody else in
 * this order. One component can be computed by adding up the needed
 * permissions for that target user base. Number 1 means that you
 * grant execute rights, number 2 means that you make the file
 * writeable, number 4 means that you make the file readable. Add
 * up these numbers to specify needed rights. You can also read more
 * about modes on Unix systems with \'man 1 chmod\'
 * and \'man 2 chmod\'.
 * </p>
 * @return bool true on success or false on failure.
 */',
    'startLine' => 37,
    'endLine' => 39,
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
        'name' => 'chmod',
        'filename' => 'phpstorm-stubs:standard/standard_7.stub',
        'extensionName' => 'standard',
        'aliasName' => NULL,
      ),
    ),
  ),
));