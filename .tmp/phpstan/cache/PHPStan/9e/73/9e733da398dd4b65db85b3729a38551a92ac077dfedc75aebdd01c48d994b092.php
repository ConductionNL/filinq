<?php declare(strict_types = 1);

// phpinternal-PHPStan\BetterReflection\Reflection\ReflectionFunction-proc_get_status
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-dev-master@709e512-8.3',
   'data' => 
  array (
    'name' => 'proc_get_status',
    'parameters' => 
    array (
      'process' => 
      array (
        'name' => 'process',
        'default' => NULL,
        'type' => NULL,
        'isVariadic' => false,
        'byRef' => false,
        'isPromoted' => false,
        'attributes' => 
        array (
        ),
        'startLine' => 81,
        'endLine' => 81,
        'startColumn' => 30,
        'endColumn' => 37,
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
      0 => 
      array (
        'name' => 'JetBrains\\PhpStorm\\ArrayShape',
        'isRepeated' => false,
        'arguments' => 
        array (
          0 => 
          array (
            'code' => '["command" => "string", "pid" => "int", "running" => "bool", "signaled" => "bool", "stopped" => "bool", "exitcode" => "int", "termsig" => "int", "stopsig" => "int"]',
            'attributes' => 
            array (
              'startLine' => 79,
              'endLine' => 79,
              'startTokenPos' => 11,
              'startFilePos' => 2238,
              'endTokenPos' => 66,
              'endFilePos' => 2401,
            ),
          ),
        ),
      ),
      1 => 
      array (
        'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
        'isRepeated' => false,
        'arguments' => 
        array (
          0 => 
          array (
            'code' => '["8.0" => "array"]',
            'attributes' => 
            array (
              'startLine' => 80,
              'endLine' => 80,
              'startTokenPos' => 73,
              'startFilePos' => 2463,
              'endTokenPos' => 79,
              'endFilePos' => 2480,
            ),
          ),
          'default' => 
          array (
            'code' => '"array|false"',
            'attributes' => 
            array (
              'startLine' => 80,
              'endLine' => 80,
              'startTokenPos' => 85,
              'startFilePos' => 2492,
              'endTokenPos' => 85,
              'endFilePos' => 2504,
            ),
          ),
        ),
      ),
    ),
    'docComment' => '/**
 * Get information about a process opened by {@see proc_open}
 * @link https://php.net/manual/en/function.proc-get-status.php
 * @param resource $process <p>
 * The proc_open resource that will
 * be evaluated.
 * </p>
 * @return array|false An array of collected information on success, and false
 * on failure. The returned array contains the following elements:
 * </p>
 * <p>
 * <tr valign="top"><td>element</td><td>type</td><td>description</td></tr>
 * <tr valign="top">
 * <td>command</td>
 * <td>string</td>
 * <td>
 * The command string that was passed to proc_open.
 * </td>
 * </tr>
 * <tr valign="top">
 * <td>pid</td>
 * <td>int</td>
 * <td>process id</td>
 * </tr>
 * <tr valign="top">
 * <td>running</td>
 * <td>bool</td>
 * <td>
 * true if the process is still running, false if it has
 * terminated.
 * </td>
 * </tr>
 * <tr valign="top">
 * <td>signaled</td>
 * <td>bool</td>
 * <td>
 * true if the child process has been terminated by
 * an uncaught signal. Always set to false on Windows.
 * </td>
 * </tr>
 * <tr valign="top">
 * <td>stopped</td>
 * <td>bool</td>
 * <td>
 * true if the child process has been stopped by a
 * signal. Always set to false on Windows.
 * </td>
 * </tr>
 * <tr valign="top">
 * <td>exitcode</td>
 * <td>int</td>
 * <td>
 * The exit code returned by the process (which is only
 * meaningful if running is false).
 * Only first call of this function return real value, next calls return
 * -1.
 * </td>
 * </tr>
 * <tr valign="top">
 * <td>termsig</td>
 * <td>int</td>
 * <td>
 * The number of the signal that caused the child process to terminate
 * its execution (only meaningful if signaled is true).
 * </td>
 * </tr>
 * <tr valign="top">
 * <td>stopsig</td>
 * <td>int</td>
 * <td>
 * The number of the signal that caused the child process to stop its
 * execution (only meaningful if stopped is true).
 * </td>
 * </tr>
 */',
    'startLine' => 79,
    'endLine' => 83,
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
        'name' => 'proc_get_status',
        'filename' => 'phpstorm-stubs:standard/standard_2.stub',
        'extensionName' => 'standard',
        'aliasName' => NULL,
      ),
    ),
  ),
));