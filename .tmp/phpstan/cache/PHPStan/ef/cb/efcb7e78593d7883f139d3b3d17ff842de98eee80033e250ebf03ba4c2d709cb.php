<?php declare(strict_types = 1);

// phpinternal-PHPStan\BetterReflection\Reflection\ReflectionFunction-ltrim
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-dev-master@709e512-8.3',
   'data' => 
  array (
    'name' => 'ltrim',
    'parameters' => 
    array (
      'string' => 
      array (
        'name' => 'string',
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
        'startColumn' => 20,
        'endColumn' => 33,
        'parameterIndex' => 0,
        'isOptional' => false,
      ),
      'characters' => 
      array (
        'name' => 'characters',
        'default' => 
        array (
          'code' => '" \\n\\r\\t\\v\\x00"',
          'attributes' => 
          array (
            'startLine' => 34,
            'endLine' => 34,
            'startTokenPos' => 27,
            'startFilePos' => 1128,
            'endTokenPos' => 27,
            'endFilePos' => 1142,
          ),
        ),
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
        'startColumn' => 36,
        'endColumn' => 71,
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
        'name' => 'string',
        'isIdentifier' => true,
      ),
    ),
    'attributes' => 
    array (
      0 => 
      array (
        'name' => 'JetBrains\\PhpStorm\\Pure',
        'isRepeated' => false,
        'arguments' => 
        array (
        ),
      ),
    ),
    'docComment' => '/**
 * Strip whitespace (or other characters) from the beginning of a string
 * @link https://php.net/manual/en/function.ltrim.php
 * @param string $string <p>
 * The input string.
 * </p>
 * @param string $characters [optional] <p>
 * You can also specify the characters you want to strip, by means of the
 * charlist parameter.
 * Simply list all characters that you want to be stripped. With
 * .. you can specify a range of characters.
 * </p>
 * @return string This function returns a string with whitespace stripped from the
 * beginning of str.
 * Without the second parameter,
 * ltrim will strip these characters:
 * " " (ASCII 32
 * (0x20)), an ordinary space.
 * "\\t" (ASCII 9
 * (0x09)), a tab.
 * "\\n" (ASCII 10
 * (0x0A)), a new line (line feed).
 * "\\r" (ASCII 13
 * (0x0D)), a carriage return.
 * "\\0" (ASCII 0
 * (0x00)), the NUL-byte.
 * "\\x0B" (ASCII 11
 * (0x0B)), a vertical tab.
 */',
    'startLine' => 33,
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
        'name' => 'ltrim',
        'filename' => 'phpstorm-stubs:standard/standard_1.stub',
        'extensionName' => 'standard',
        'aliasName' => NULL,
      ),
    ),
  ),
));