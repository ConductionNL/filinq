<?php declare(strict_types = 1);

// phpinternal-PHPStan\BetterReflection\Reflection\ReflectionFunction-set_error_handler
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-dev-master@709e512-8.3',
   'data' => 
  array (
    'name' => 'set_error_handler',
    'parameters' => 
    array (
      'callback' => 
      array (
        'name' => 'callback',
        'default' => NULL,
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
                  'name' => 'callable',
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
        'startLine' => 53,
        'endLine' => 53,
        'startColumn' => 32,
        'endColumn' => 50,
        'parameterIndex' => 0,
        'isOptional' => false,
      ),
      'error_levels' => 
      array (
        'name' => 'error_levels',
        'default' => 
        array (
          'code' => '\\E_ALL',
          'attributes' => 
          array (
            'startLine' => 53,
            'endLine' => 53,
            'startTokenPos' => 24,
            'startFilePos' => 2619,
            'endTokenPos' => 24,
            'endFilePos' => 2623,
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
        'startLine' => 53,
        'endLine' => 53,
        'startColumn' => 53,
        'endColumn' => 77,
        'parameterIndex' => 1,
        'isOptional' => true,
      ),
    ),
    'returnsReference' => false,
    'returnType' => NULL,
    'attributes' => 
    array (
    ),
    'docComment' => '/**
 * Sets a user-defined error handler function
 * @link https://php.net/manual/en/function.set-error-handler.php
 * @param callable|null $callback <p>
 * The user function needs to accept two parameters: the error code, and a
 * string describing the error. Then there are three optional parameters
 * that may be supplied: the filename in which the error occurred, the
 * line number in which the error occurred, and the context in which the
 * error occurred (an array that points to the active symbol table at the
 * point the error occurred). The function can be shown as:
 * </p>
 * <p>
 * <b>handler</b>
 * <b>int<i>errno</i></b>
 * <b>string<i>errstr</i></b>
 * <b>string<i>errfile</i></b>
 * <b>int<i>errline</i></b>
 * <b>array<i>errcontext</i></b>
 * <i>errno</i>
 * The first parameter, <i>errno</i>, contains the
 * level of the error raised, as an integer.</p>
 * The user function should stop execution if necessary by calling `exit()`.
 * If the function returns a value other than false, script execution will
 * continue with the next statement after the one that caused an error.
 * If the function returns false, the standard PHP error handler is called.
 * @param int $error_levels [optional] <p>
 * Can be used to mask the triggering of the
 * <i>error_handler</i> function just like the error_reporting ini setting
 * controls which errors are shown. Without this mask set the
 * <i>error_handler</i> will be called for every error
 * regardless to the setting of the error_reporting setting.
 * </p>
 * @return callable|null a string containing the previously defined error handler (if any). If
 * the built-in error handler is used null is returned. null is also returned
 * in case of an error such as an invalid callback. If the previous error handler
 * was a class method, this function will return an indexed array with the class
 * and the method name.
 *
 * Note that error_handler chaining is possible by passing the output value as a reference:
 * ```
 * $previousErrorHandler = set_error_handler(
 *     static function (int $errNo, string $errstr, string $errFile, int $errLine) use (&$previousErrorHandler): bool {
 *         // Handle specific scenarios
 *
 *         return $previousErrorHandler !== null ? (bool) $previousErrorHandler(...func_get_args()) : false;
 *     }
 * );
 * ```
 */',
    'startLine' => 53,
    'endLine' => 55,
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
        'name' => 'set_error_handler',
        'filename' => 'phpstorm-stubs:Core/Core.stub',
        'extensionName' => 'Core',
        'aliasName' => NULL,
      ),
    ),
  ),
));