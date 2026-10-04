<?php declare(strict_types = 1);

// phpinternal-PHPStan\BetterReflection\Reflection\ReflectionFunction-exec
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-dev-master@709e512-8.3',
   'data' => 
  array (
    'name' => 'exec',
    'parameters' => 
    array (
      'command' => 
      array (
        'name' => 'command',
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
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 19,
        'endColumn' => 33,
        'parameterIndex' => 0,
        'isOptional' => false,
      ),
      'output' => 
      array (
        'name' => 'output',
        'default' => 
        array (
          'code' => '\\null',
          'attributes' => 
          array (
            'startLine' => 34,
            'endLine' => 34,
            'startTokenPos' => 22,
            'startFilePos' => 1354,
            'endTokenPos' => 22,
            'endFilePos' => 1357,
          ),
        ),
        'type' => NULL,
        'isVariadic' => false,
        'byRef' => true,
        'isPromoted' => false,
        'attributes' => 
        array (
        ),
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 36,
        'endColumn' => 50,
        'parameterIndex' => 1,
        'isOptional' => true,
      ),
      'result_code' => 
      array (
        'name' => 'result_code',
        'default' => 
        array (
          'code' => '\\null',
          'attributes' => 
          array (
            'startLine' => 34,
            'endLine' => 34,
            'startTokenPos' => 30,
            'startFilePos' => 1376,
            'endTokenPos' => 30,
            'endFilePos' => 1379,
          ),
        ),
        'type' => NULL,
        'isVariadic' => false,
        'byRef' => true,
        'isPromoted' => false,
        'attributes' => 
        array (
        ),
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 53,
        'endColumn' => 72,
        'parameterIndex' => 2,
        'isOptional' => true,
      ),
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
              'name' => 'string',
              'isIdentifier' => true,
            ),
          ),
          1 => 
          array (
            'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
            'data' => 
            array (
              'name' => 'false',
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
 * Execute an external program
 * @link https://php.net/manual/en/function.exec.php
 * @param string $command <p>
 * The command that will be executed.
 * </p>
 * @param array &$output [optional] <p>
 * If the output argument is present, then the
 * specified array will be filled with every line of output from the
 * command. Trailing whitespace, such as \\n, is not
 * included in this array. Note that if the array already contains some
 * elements, exec will append to the end of the array.
 * If you do not want the function to append elements, call
 * unset on the array before passing it to
 * exec.
 * </p>
 * @param int &$result_code [optional] <p>
 * If the result_code argument is present
 * along with the output argument, then the
 * return status of the executed command will be written to this
 * variable.
 * </p>
 * @return string|false The last line from the result of the command. If you need to execute a
 * command and have all the data from the command passed directly back without
 * any interference, use the passthru function.
 * </p>
 * <p>
 * To get the output of the executed command, be sure to set and use the
 * output parameter.
 */',
    'startLine' => 34,
    'endLine' => 36,
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
        'name' => 'exec',
        'filename' => 'phpstorm-stubs:standard/standard_2.stub',
        'extensionName' => 'standard',
        'aliasName' => NULL,
      ),
    ),
  ),
));