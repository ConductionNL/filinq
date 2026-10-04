<?php declare(strict_types = 1);

// phpinternal-PHPStan\BetterReflection\Reflection\ReflectionFunction-fastcgi_finish_request
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-dev-master@709e512-8.3',
   'data' => 
  array (
    'name' => 'fastcgi_finish_request',
    'parameters' => 
    array (
    ),
    'returnsReference' => false,
    'returnType' => NULL,
    'attributes' => 
    array (
    ),
    'docComment' => '/**
 * This function flushes all response data to the client and finishes the request.
 * This allows for time consuming tasks to be performed without leaving the connection to the client open.
 * @return bool Returns TRUE on success or FALSE on failure.
 * @link https://php.net/manual/en/install.fpm.php
 * @since 5.3
 */',
    'startLine' => 11,
    'endLine' => 13,
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
        'name' => 'fastcgi_finish_request',
        'filename' => 'phpstorm-stubs:fpm/fpm.stub',
        'extensionName' => 'fpm',
        'aliasName' => NULL,
      ),
    ),
  ),
));