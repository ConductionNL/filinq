<?php declare(strict_types = 1);

// phpinternal-PHPStan\BetterReflection\Reflection\ReflectionClass-domcharacterdata
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-dev-master@709e512-8.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\InternalLocatedSource',
      'data' => 
      array (
        'name' => 'DOMCharacterData',
        'filename' => 'phpstorm-stubs:dom/dom_c.stub',
        'extensionName' => 'dom',
        'aliasName' => NULL,
      ),
    ),
    'namespace' => NULL,
    'name' => 'DOMCharacterData',
    'shortName' => 'DOMCharacterData',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * The DOMCharacterData class represents nodes with character data.
 * No nodes directly correspond to this class, but other nodes do inherit from it.
 * @link https://php.net/manual/en/class.domcharacterdata.php
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 9,
    'endLine' => 162,
    'startColumn' => 5,
    'endColumn' => 5,
    'parentClassName' => 'DOMNode',
    'implementsClassNames' => 
    array (
      0 => 'DOMChildNode',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'data' => 
      array (
        'declaringClassName' => 'DOMCharacterData',
        'implementingClassName' => 'DOMCharacterData',
        'name' => 'data',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * @var string
 * The contents of the node.
 * @link https://php.net/manual/en/class.domcharacterdata.php#domcharacterdata.props.data
 */',
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
                'code' => '[\'8.1\' => \'string\']',
                'attributes' => 
                array (
                  'startLine' => 16,
                  'endLine' => 16,
                  'startTokenPos' => 27,
                  'startFilePos' => 579,
                  'endTokenPos' => 33,
                  'endFilePos' => 597,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 16,
                  'endLine' => 16,
                  'startTokenPos' => 39,
                  'startFilePos' => 609,
                  'endTokenPos' => 39,
                  'endFilePos' => 610,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 16,
        'endLine' => 17,
        'startColumn' => 9,
        'endColumn' => 28,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'length' => 
      array (
        'declaringClassName' => 'DOMCharacterData',
        'implementingClassName' => 'DOMCharacterData',
        'name' => 'length',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * @var int
 * The length of the contents.
 * @link https://php.net/manual/en/class.domcharacterdata.php#domcharacterdata.props.length
 */',
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
                'code' => '[\'8.1\' => \'int\']',
                'attributes' => 
                array (
                  'startLine' => 23,
                  'endLine' => 23,
                  'startTokenPos' => 55,
                  'startFilePos' => 888,
                  'endTokenPos' => 61,
                  'endFilePos' => 903,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 23,
                  'endLine' => 23,
                  'startTokenPos' => 67,
                  'startFilePos' => 915,
                  'endTokenPos' => 67,
                  'endFilePos' => 916,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 23,
        'endLine' => 24,
        'startColumn' => 9,
        'endColumn' => 27,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'nextElementSibling' => 
      array (
        'declaringClassName' => 'DOMCharacterData',
        'implementingClassName' => 'DOMCharacterData',
        'name' => 'nextElementSibling',
        'modifiers' => 1,
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
                  'name' => 'DOMElement',
                  'isIdentifier' => false,
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
        'default' => NULL,
        'docComment' => NULL,
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
                'code' => '[\'8.1\' => \'DOMElement|null\']',
                'attributes' => 
                array (
                  'startLine' => 25,
                  'endLine' => 25,
                  'startTokenPos' => 81,
                  'startFilePos' => 1010,
                  'endTokenPos' => 87,
                  'endFilePos' => 1037,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 25,
                  'endLine' => 25,
                  'startTokenPos' => 93,
                  'startFilePos' => 1049,
                  'endTokenPos' => 93,
                  'endFilePos' => 1050,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 25,
        'endLine' => 26,
        'startColumn' => 9,
        'endColumn' => 51,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'previousElementSibling' => 
      array (
        'declaringClassName' => 'DOMCharacterData',
        'implementingClassName' => 'DOMCharacterData',
        'name' => 'previousElementSibling',
        'modifiers' => 1,
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
                  'name' => 'DOMElement',
                  'isIdentifier' => false,
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
        'default' => NULL,
        'docComment' => NULL,
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
                'code' => '[\'8.1\' => \'DOMElement|null\']',
                'attributes' => 
                array (
                  'startLine' => 27,
                  'endLine' => 27,
                  'startTokenPos' => 109,
                  'startFilePos' => 1168,
                  'endTokenPos' => 115,
                  'endFilePos' => 1195,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 27,
                  'endLine' => 27,
                  'startTokenPos' => 121,
                  'startFilePos' => 1207,
                  'endTokenPos' => 121,
                  'endFilePos' => 1208,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 27,
        'endLine' => 28,
        'startColumn' => 9,
        'endColumn' => 55,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      'substringData' => 
      array (
        'name' => 'substringData',
        'parameters' => 
        array (
          'offset' => 
          array (
            'name' => 'offset',
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
                      'startLine' => 43,
                      'endLine' => 43,
                      'startTokenPos' => 146,
                      'startFilePos' => 1904,
                      'endTokenPos' => 152,
                      'endFilePos' => 1919,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 43,
                      'endLine' => 43,
                      'startTokenPos' => 158,
                      'startFilePos' => 1931,
                      'endTokenPos' => 158,
                      'endFilePos' => 1932,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 43,
            'endLine' => 44,
            'startColumn' => 13,
            'endColumn' => 23,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'count' => 
          array (
            'name' => 'count',
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
                      'startLine' => 45,
                      'endLine' => 45,
                      'startTokenPos' => 170,
                      'startFilePos' => 2027,
                      'endTokenPos' => 176,
                      'endFilePos' => 2042,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 45,
                      'endLine' => 45,
                      'startTokenPos' => 182,
                      'startFilePos' => 2054,
                      'endTokenPos' => 182,
                      'endFilePos' => 2055,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 45,
            'endLine' => 46,
            'startColumn' => 13,
            'endColumn' => 22,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Extracts a range of data from the node
 * @link https://php.net/manual/en/domcharacterdata.substringdata.php
 * @param int $offset <p>
 * Start offset of substring to extract.
 * </p>
 * @param int $count <p>
 * The number of characters to extract.
 * </p>
 * @return string The specified substring. If the sum of offset
 * and count exceeds the length, then all 16-bit units
 * to the end of the data are returned.
 */',
        'startLine' => 42,
        'endLine' => 49,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMCharacterData',
        'implementingClassName' => 'DOMCharacterData',
        'currentClassName' => 'DOMCharacterData',
        'aliasName' => NULL,
      ),
      'appendData' => 
      array (
        'name' => 'appendData',
        'parameters' => 
        array (
          'data' => 
          array (
            'name' => 'data',
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
                      'startLine' => 61,
                      'endLine' => 61,
                      'startTokenPos' => 231,
                      'startFilePos' => 2672,
                      'endTokenPos' => 237,
                      'endFilePos' => 2690,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 61,
                      'endLine' => 61,
                      'startTokenPos' => 243,
                      'startFilePos' => 2702,
                      'endTokenPos' => 243,
                      'endFilePos' => 2703,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 61,
            'endLine' => 62,
            'startColumn' => 13,
            'endColumn' => 24,
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
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
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
                'code' => '[\'8.3\' => \'true\']',
                'attributes' => 
                array (
                  'startLine' => 59,
                  'endLine' => 59,
                  'startTokenPos' => 205,
                  'startFilePos' => 2533,
                  'endTokenPos' => 211,
                  'endFilePos' => 2549,
                ),
              ),
              'default' => 
              array (
                'code' => '\'bool\'',
                'attributes' => 
                array (
                  'startLine' => 59,
                  'endLine' => 59,
                  'startTokenPos' => 217,
                  'startFilePos' => 2561,
                  'endTokenPos' => 217,
                  'endFilePos' => 2566,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * Append the string to the end of the character data of the node
 * @link https://php.net/manual/en/domcharacterdata.appenddata.php
 * @param string $data <p>
 * The string to append.
 * </p>
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 58,
        'endLine' => 65,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMCharacterData',
        'implementingClassName' => 'DOMCharacterData',
        'currentClassName' => 'DOMCharacterData',
        'aliasName' => NULL,
      ),
      'insertData' => 
      array (
        'name' => 'insertData',
        'parameters' => 
        array (
          'offset' => 
          array (
            'name' => 'offset',
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
                      'startLine' => 80,
                      'endLine' => 80,
                      'startTokenPos' => 273,
                      'startFilePos' => 3338,
                      'endTokenPos' => 279,
                      'endFilePos' => 3353,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 80,
                      'endLine' => 80,
                      'startTokenPos' => 285,
                      'startFilePos' => 3365,
                      'endTokenPos' => 285,
                      'endFilePos' => 3366,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 80,
            'endLine' => 81,
            'startColumn' => 13,
            'endColumn' => 23,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'data' => 
          array (
            'name' => 'data',
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
                      'startLine' => 82,
                      'endLine' => 82,
                      'startTokenPos' => 297,
                      'startFilePos' => 3461,
                      'endTokenPos' => 303,
                      'endFilePos' => 3479,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 82,
                      'endLine' => 82,
                      'startTokenPos' => 309,
                      'startFilePos' => 3491,
                      'endTokenPos' => 309,
                      'endFilePos' => 3492,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 82,
            'endLine' => 83,
            'startColumn' => 13,
            'endColumn' => 24,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Insert a string at the specified 16-bit unit offset
 * @link https://php.net/manual/en/domcharacterdata.insertdata.php
 * @param int $offset <p>
 * The character offset at which to insert.
 * </p>
 * @param string $data <p>
 * The string to insert.
 * </p>
 * @return bool
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 78,
        'endLine' => 86,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMCharacterData',
        'implementingClassName' => 'DOMCharacterData',
        'currentClassName' => 'DOMCharacterData',
        'aliasName' => NULL,
      ),
      'deleteData' => 
      array (
        'name' => 'deleteData',
        'parameters' => 
        array (
          'offset' => 
          array (
            'name' => 'offset',
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
                      'startLine' => 103,
                      'endLine' => 103,
                      'startTokenPos' => 342,
                      'startFilePos' => 4265,
                      'endTokenPos' => 348,
                      'endFilePos' => 4280,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 103,
                      'endLine' => 103,
                      'startTokenPos' => 354,
                      'startFilePos' => 4292,
                      'endTokenPos' => 354,
                      'endFilePos' => 4293,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 103,
            'endLine' => 104,
            'startColumn' => 13,
            'endColumn' => 23,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'count' => 
          array (
            'name' => 'count',
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
                      'startLine' => 105,
                      'endLine' => 105,
                      'startTokenPos' => 366,
                      'startFilePos' => 4388,
                      'endTokenPos' => 372,
                      'endFilePos' => 4403,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 105,
                      'endLine' => 105,
                      'startTokenPos' => 378,
                      'startFilePos' => 4415,
                      'endTokenPos' => 378,
                      'endFilePos' => 4416,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 105,
            'endLine' => 106,
            'startColumn' => 13,
            'endColumn' => 22,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Remove a range of characters from the node
 * @link https://php.net/manual/en/domcharacterdata.deletedata.php
 * @param int $offset <p>
 * The offset from which to start removing.
 * </p>
 * @param int $count <p>
 * The number of characters to delete. If the sum of
 * offset and count exceeds
 * the length, then all characters to the end of the data are deleted.
 * </p>
 * @return bool
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 101,
        'endLine' => 109,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMCharacterData',
        'implementingClassName' => 'DOMCharacterData',
        'currentClassName' => 'DOMCharacterData',
        'aliasName' => NULL,
      ),
      'replaceData' => 
      array (
        'name' => 'replaceData',
        'parameters' => 
        array (
          'offset' => 
          array (
            'name' => 'offset',
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
                      'startLine' => 129,
                      'endLine' => 129,
                      'startTokenPos' => 411,
                      'startFilePos' => 5314,
                      'endTokenPos' => 417,
                      'endFilePos' => 5329,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 129,
                      'endLine' => 129,
                      'startTokenPos' => 423,
                      'startFilePos' => 5341,
                      'endTokenPos' => 423,
                      'endFilePos' => 5342,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 129,
            'endLine' => 130,
            'startColumn' => 13,
            'endColumn' => 23,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'count' => 
          array (
            'name' => 'count',
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
                      'startLine' => 131,
                      'endLine' => 131,
                      'startTokenPos' => 435,
                      'startFilePos' => 5437,
                      'endTokenPos' => 441,
                      'endFilePos' => 5452,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 131,
                      'endLine' => 131,
                      'startTokenPos' => 447,
                      'startFilePos' => 5464,
                      'endTokenPos' => 447,
                      'endFilePos' => 5465,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 131,
            'endLine' => 132,
            'startColumn' => 13,
            'endColumn' => 22,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'data' => 
          array (
            'name' => 'data',
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
                      'startLine' => 133,
                      'endLine' => 133,
                      'startTokenPos' => 459,
                      'startFilePos' => 5559,
                      'endTokenPos' => 465,
                      'endFilePos' => 5577,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 133,
                      'endLine' => 133,
                      'startTokenPos' => 471,
                      'startFilePos' => 5589,
                      'endTokenPos' => 471,
                      'endFilePos' => 5590,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 133,
            'endLine' => 134,
            'startColumn' => 13,
            'endColumn' => 24,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Replace a substring within the DOMCharacterData node
 * @link https://php.net/manual/en/domcharacterdata.replacedata.php
 * @param int $offset <p>
 * The offset from which to start replacing.
 * </p>
 * @param int $count <p>
 * The number of characters to replace. If the sum of
 * offset and count exceeds
 * the length, then all characters to the end of the data are replaced.
 * </p>
 * @param string $data <p>
 * The string with which the range must be replaced.
 * </p>
 * @return bool
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 127,
        'endLine' => 137,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMCharacterData',
        'implementingClassName' => 'DOMCharacterData',
        'currentClassName' => 'DOMCharacterData',
        'aliasName' => NULL,
      ),
      'remove' => 
      array (
        'name' => 'remove',
        'parameters' => 
        array (
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
 * {@inheritDoc}
 */',
        'startLine' => 141,
        'endLine' => 143,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMCharacterData',
        'implementingClassName' => 'DOMCharacterData',
        'currentClassName' => 'DOMCharacterData',
        'aliasName' => NULL,
      ),
      'before' => 
      array (
        'name' => 'before',
        'parameters' => 
        array (
          'nodes' => 
          array (
            'name' => 'nodes',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => true,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 147,
            'endLine' => 147,
            'startColumn' => 32,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => true,
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
 * {@inheritDoc}
 */',
        'startLine' => 147,
        'endLine' => 149,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMCharacterData',
        'implementingClassName' => 'DOMCharacterData',
        'currentClassName' => 'DOMCharacterData',
        'aliasName' => NULL,
      ),
      'after' => 
      array (
        'name' => 'after',
        'parameters' => 
        array (
          'nodes' => 
          array (
            'name' => 'nodes',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => true,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 153,
            'endLine' => 153,
            'startColumn' => 31,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => true,
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
 * {@inheritDoc}
 */',
        'startLine' => 153,
        'endLine' => 155,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMCharacterData',
        'implementingClassName' => 'DOMCharacterData',
        'currentClassName' => 'DOMCharacterData',
        'aliasName' => NULL,
      ),
      'replaceWith' => 
      array (
        'name' => 'replaceWith',
        'parameters' => 
        array (
          'nodes' => 
          array (
            'name' => 'nodes',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => true,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 159,
            'endLine' => 159,
            'startColumn' => 37,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => true,
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
 * {@inheritDoc}
 */',
        'startLine' => 159,
        'endLine' => 161,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMCharacterData',
        'implementingClassName' => 'DOMCharacterData',
        'currentClassName' => 'DOMCharacterData',
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