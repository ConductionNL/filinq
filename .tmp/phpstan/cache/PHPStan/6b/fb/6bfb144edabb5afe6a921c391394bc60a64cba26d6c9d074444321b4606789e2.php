<?php declare(strict_types = 1);

// phpinternal-PHPStan\BetterReflection\Reflection\ReflectionClass-finfo
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-dev-master@709e512-8.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\InternalLocatedSource',
      'data' => 
      array (
        'name' => 'finfo',
        'filename' => 'phpstorm-stubs:fileinfo/fileinfo.stub',
        'extensionName' => 'fileinfo',
        'aliasName' => NULL,
      ),
    ),
    'namespace' => NULL,
    'name' => 'finfo',
    'shortName' => 'finfo',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 4,
    'endLine' => 93,
    'startColumn' => 5,
    'endColumn' => 5,
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
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 12,
                'endLine' => 12,
                'startTokenPos' => 46,
                'startFilePos' => 317,
                'endTokenPos' => 46,
                'endFilePos' => 317,
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 11,
                      'endLine' => 11,
                      'startTokenPos' => 24,
                      'startFilePos' => 260,
                      'endTokenPos' => 30,
                      'endFilePos' => 275,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 11,
                      'endLine' => 11,
                      'startTokenPos' => 36,
                      'startFilePos' => 287,
                      'endTokenPos' => 36,
                      'endFilePos' => 288,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 11,
            'endLine' => 12,
            'startColumn' => 13,
            'endColumn' => 26,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'magic_database' => 
          array (
            'name' => 'magic_database',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 14,
                'endLine' => 14,
                'startTokenPos' => 76,
                'startFilePos' => 468,
                'endTokenPos' => 76,
                'endFilePos' => 471,
              ),
            ),
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
                      'name' => 'string',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string|null\']',
                    'attributes' => 
                    array (
                      'startLine' => 13,
                      'endLine' => 13,
                      'startTokenPos' => 52,
                      'startFilePos' => 386,
                      'endTokenPos' => 58,
                      'endFilePos' => 409,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 13,
                      'endLine' => 13,
                      'startTokenPos' => 64,
                      'startFilePos' => 421,
                      'endTokenPos' => 64,
                      'endFilePos' => 422,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 13,
            'endLine' => 14,
            'startColumn' => 13,
            'endColumn' => 46,
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
 * @param int $flags [optional]
 * @param string $magic_database [optional]
 */',
        'startLine' => 10,
        'endLine' => 17,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'finfo',
        'implementingClassName' => 'finfo',
        'currentClassName' => 'finfo',
        'aliasName' => NULL,
      ),
      'set_flags' => 
      array (
        'name' => 'set_flags',
        'parameters' => 
        array (
          'flags' => 
          array (
            'name' => 'flags',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 32,
                      'endLine' => 32,
                      'startTokenPos' => 119,
                      'startFilePos' => 1201,
                      'endTokenPos' => 125,
                      'endFilePos' => 1216,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 32,
                      'endLine' => 32,
                      'startTokenPos' => 131,
                      'startFilePos' => 1228,
                      'endTokenPos' => 131,
                      'endFilePos' => 1229,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 32,
            'endLine' => 33,
            'startColumn' => 13,
            'endColumn' => 22,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '[\'8.4\' => \'true\']',
                'attributes' => 
                array (
                  'startLine' => 29,
                  'endLine' => 29,
                  'startTokenPos' => 89,
                  'startFilePos' => 1009,
                  'endTokenPos' => 95,
                  'endFilePos' => 1025,
                ),
              ),
              'default' => 
              array (
                'code' => '\'bool\'',
                'attributes' => 
                array (
                  'startLine' => 29,
                  'endLine' => 29,
                  'startTokenPos' => 101,
                  'startFilePos' => 1037,
                  'endTokenPos' => 101,
                  'endFilePos' => 1042,
                ),
              ),
            ),
          ),
          1 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP &gt;= 5.3.0, PECL fileinfo &gt;= 0.1.0)<br/>
 * Set libmagic configuration options
 * @link https://php.net/manual/en/function.finfo-set-flags.php
 * @param int $flags <p>
 * One or disjunction of more Fileinfo
 * constants.
 * </p>
 * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 29,
        'endLine' => 36,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'finfo',
        'implementingClassName' => 'finfo',
        'currentClassName' => 'finfo',
        'aliasName' => NULL,
      ),
      'file' => 
      array (
        'name' => 'file',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 58,
                      'endLine' => 58,
                      'startTokenPos' => 168,
                      'startFilePos' => 2212,
                      'endTokenPos' => 174,
                      'endFilePos' => 2230,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 58,
                      'endLine' => 58,
                      'startTokenPos' => 180,
                      'startFilePos' => 2242,
                      'endTokenPos' => 180,
                      'endFilePos' => 2243,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 58,
            'endLine' => 59,
            'startColumn' => 13,
            'endColumn' => 28,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '\\FILEINFO_NONE',
              'attributes' => 
              array (
                'startLine' => 61,
                'endLine' => 61,
                'startTokenPos' => 214,
                'startFilePos' => 2400,
                'endTokenPos' => 214,
                'endFilePos' => 2412,
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 60,
                      'endLine' => 60,
                      'startTokenPos' => 192,
                      'startFilePos' => 2343,
                      'endTokenPos' => 198,
                      'endFilePos' => 2358,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 60,
                      'endLine' => 60,
                      'startTokenPos' => 204,
                      'startFilePos' => 2370,
                      'endTokenPos' => 204,
                      'endFilePos' => 2371,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 60,
            'endLine' => 61,
            'startColumn' => 13,
            'endColumn' => 38,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'context' => 
          array (
            'name' => 'context',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 62,
                'endLine' => 62,
                'startTokenPos' => 221,
                'startFilePos' => 2438,
                'endTokenPos' => 221,
                'endFilePos' => 2441,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 62,
            'endLine' => 62,
            'startColumn' => 13,
            'endColumn' => 27,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Pure',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\\true',
                'attributes' => 
                array (
                  'startLine' => 55,
                  'endLine' => 55,
                  'startTokenPos' => 150,
                  'startFilePos' => 2055,
                  'endTokenPos' => 150,
                  'endFilePos' => 2058,
                ),
              ),
            ),
          ),
          1 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP &gt;= 5.3.0, PECL fileinfo &gt;= 0.1.0)<br/>
 * Return information about a file
 * @link https://php.net/manual/en/function.finfo-file.php
 * @param string $filename <p>
 * Name of a file to be checked.
 * </p>
 * @param int $flags [optional] <p>
 * One or disjunction of more Fileinfo
 * constants.
 * </p>
 * @param resource $context [optional] <p>
 * For a description of contexts, refer to .
 * </p>
 * @return string a textual description of the contents of the
 * <i>filename</i> argument, or <b>FALSE</b> if an error occurred.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 55,
        'endLine' => 65,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'finfo',
        'implementingClassName' => 'finfo',
        'currentClassName' => 'finfo',
        'aliasName' => NULL,
      ),
      'buffer' => 
      array (
        'name' => 'buffer',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 85,
                      'endLine' => 85,
                      'startTokenPos' => 257,
                      'startFilePos' => 3338,
                      'endTokenPos' => 263,
                      'endFilePos' => 3356,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 85,
                      'endLine' => 85,
                      'startTokenPos' => 269,
                      'startFilePos' => 3368,
                      'endTokenPos' => 269,
                      'endFilePos' => 3369,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 85,
            'endLine' => 86,
            'startColumn' => 13,
            'endColumn' => 26,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '\\FILEINFO_NONE',
              'attributes' => 
              array (
                'startLine' => 88,
                'endLine' => 88,
                'startTokenPos' => 303,
                'startFilePos' => 3524,
                'endTokenPos' => 303,
                'endFilePos' => 3536,
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 87,
                      'endLine' => 87,
                      'startTokenPos' => 281,
                      'startFilePos' => 3467,
                      'endTokenPos' => 287,
                      'endFilePos' => 3482,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 87,
                      'endLine' => 87,
                      'startTokenPos' => 293,
                      'startFilePos' => 3494,
                      'endTokenPos' => 293,
                      'endFilePos' => 3495,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 87,
            'endLine' => 88,
            'startColumn' => 13,
            'endColumn' => 38,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'context' => 
          array (
            'name' => 'context',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 89,
                'endLine' => 89,
                'startTokenPos' => 310,
                'startFilePos' => 3562,
                'endTokenPos' => 310,
                'endFilePos' => 3565,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 89,
            'endLine' => 89,
            'startColumn' => 13,
            'endColumn' => 27,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Pure',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\\true',
                'attributes' => 
                array (
                  'startLine' => 82,
                  'endLine' => 82,
                  'startTokenPos' => 239,
                  'startFilePos' => 3179,
                  'endTokenPos' => 239,
                  'endFilePos' => 3182,
                ),
              ),
            ),
          ),
          1 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.3.0, PECL fileinfo &gt;= 0.1.0)<br/>
 * Return information about a string buffer
 * @link https://php.net/manual/en/function.finfo-buffer.php
 * @param string $string <p>
 * Content of a file to be checked.
 * </p>
 * @param int $flags [optional] <p>
 * One or disjunction of more Fileinfo
 * constants.
 * </p>
 * @param resource $context [optional]
 * @return string a textual description of the <i>string</i>
 * argument, or <b>FALSE</b> if an error occurred.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 82,
        'endLine' => 92,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'finfo',
        'implementingClassName' => 'finfo',
        'currentClassName' => 'finfo',
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