<?php declare(strict_types = 1);

// phpinternal-PHPStan\BetterReflection\Reflection\ReflectionClass-domdocument
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-dev-master@709e512-8.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\InternalLocatedSource',
      'data' => 
      array (
        'name' => 'DOMDocument',
        'filename' => 'phpstorm-stubs:dom/dom_c.stub',
        'extensionName' => 'dom',
        'aliasName' => NULL,
      ),
    ),
    'namespace' => NULL,
    'name' => 'DOMDocument',
    'shortName' => 'DOMDocument',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * The DOMDocument class represents an entire HTML or XML
 * document; serves as the root of the document tree.
 * @link https://php.net/manual/en/class.domdocument.php
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 9,
    'endLine' => 786,
    'startColumn' => 5,
    'endColumn' => 5,
    'parentClassName' => 'DOMNode',
    'implementsClassNames' => 
    array (
      0 => 'DOMParentNode',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'actualEncoding' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'actualEncoding',
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
        'default' => NULL,
        'docComment' => '/**
 * @var string|null
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.actualencoding
 */',
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Deprecated',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '"The property is deprecated"',
                'attributes' => 
                array (
                  'startLine' => 15,
                  'endLine' => 15,
                  'startTokenPos' => 27,
                  'startFilePos' => 478,
                  'endTokenPos' => 27,
                  'endFilePos' => 505,
                ),
              ),
              'since' => 
              array (
                'code' => '"8.4"',
                'attributes' => 
                array (
                  'startLine' => 15,
                  'endLine' => 15,
                  'startTokenPos' => 33,
                  'startFilePos' => 515,
                  'endTokenPos' => 33,
                  'endFilePos' => 519,
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
                'code' => '[\'8.1\' => \'string|null\']',
                'attributes' => 
                array (
                  'startLine' => 16,
                  'endLine' => 16,
                  'startTokenPos' => 40,
                  'startFilePos' => 585,
                  'endTokenPos' => 46,
                  'endFilePos' => 608,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 16,
                  'endLine' => 16,
                  'startTokenPos' => 52,
                  'startFilePos' => 620,
                  'endTokenPos' => 52,
                  'endFilePos' => 621,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 15,
        'endLine' => 17,
        'startColumn' => 9,
        'endColumn' => 43,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'config' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'config',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'mixed',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * @var DOMConfiguration
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.config
 * @see DOMDocument::normalizeDocument()
 */',
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Deprecated',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '"The property is deprecated"',
                'attributes' => 
                array (
                  'startLine' => 23,
                  'endLine' => 23,
                  'startTokenPos' => 70,
                  'startFilePos' => 906,
                  'endTokenPos' => 70,
                  'endFilePos' => 933,
                ),
              ),
              'since' => 
              array (
                'code' => '"8.4"',
                'attributes' => 
                array (
                  'startLine' => 23,
                  'endLine' => 23,
                  'startTokenPos' => 76,
                  'startFilePos' => 943,
                  'endTokenPos' => 76,
                  'endFilePos' => 947,
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
                'code' => '[\'8.1\' => \'mixed\']',
                'attributes' => 
                array (
                  'startLine' => 24,
                  'endLine' => 24,
                  'startTokenPos' => 83,
                  'startFilePos' => 1013,
                  'endTokenPos' => 89,
                  'endFilePos' => 1030,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 24,
                  'endLine' => 24,
                  'startTokenPos' => 95,
                  'startFilePos' => 1042,
                  'endTokenPos' => 95,
                  'endFilePos' => 1043,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 23,
        'endLine' => 25,
        'startColumn' => 9,
        'endColumn' => 29,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'doctype' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'doctype',
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
                  'name' => 'DOMDocumentType',
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
        'docComment' => '/**
 * @var DOMDocumentType
 * The Document Type Declaration associated with this document.
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.doctype
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
                'code' => '[\'8.1\' => \'DOMDocumentType|null\']',
                'attributes' => 
                array (
                  'startLine' => 31,
                  'endLine' => 31,
                  'startTokenPos' => 111,
                  'startFilePos' => 1358,
                  'endTokenPos' => 117,
                  'endFilePos' => 1390,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 31,
                  'endLine' => 31,
                  'startTokenPos' => 123,
                  'startFilePos' => 1402,
                  'endTokenPos' => 123,
                  'endFilePos' => 1403,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 31,
        'endLine' => 32,
        'startColumn' => 9,
        'endColumn' => 45,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'documentElement' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'documentElement',
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
        'docComment' => '/**
 * @var DOMElement
 * This is a convenience attribute that allows direct access to the child node
 * that is the document element of the document.
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.documentelement
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
                'code' => '[\'8.1\' => \'DOMElement|null\']',
                'attributes' => 
                array (
                  'startLine' => 39,
                  'endLine' => 39,
                  'startTokenPos' => 141,
                  'startFilePos' => 1809,
                  'endTokenPos' => 147,
                  'endFilePos' => 1836,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 39,
                  'endLine' => 39,
                  'startTokenPos' => 153,
                  'startFilePos' => 1848,
                  'endTokenPos' => 153,
                  'endFilePos' => 1849,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 39,
        'endLine' => 40,
        'startColumn' => 9,
        'endColumn' => 48,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'documentURI' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'documentURI',
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
        'default' => NULL,
        'docComment' => '/**
 * @var string|null
 * The location of the document or NULL if undefined.
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.documenturi
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
                'code' => '[\'8.1\' => \'string|null\']',
                'attributes' => 
                array (
                  'startLine' => 46,
                  'endLine' => 46,
                  'startTokenPos' => 171,
                  'startFilePos' => 2173,
                  'endTokenPos' => 177,
                  'endFilePos' => 2196,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 46,
                  'endLine' => 46,
                  'startTokenPos' => 183,
                  'startFilePos' => 2208,
                  'endTokenPos' => 183,
                  'endFilePos' => 2209,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 46,
        'endLine' => 47,
        'startColumn' => 9,
        'endColumn' => 40,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'encoding' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'encoding',
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
        'default' => NULL,
        'docComment' => '/**
 * @var string|null
 * Encoding of the document, as specified by the XML declaration. This attribute is not present
 * in the final DOM Level 3 specification, but is the only way of manipulating XML document
 * encoding in this implementation.
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.encoding
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
                'code' => '[\'8.1\' => \'string|null\']',
                'attributes' => 
                array (
                  'startLine' => 55,
                  'endLine' => 55,
                  'startTokenPos' => 201,
                  'startFilePos' => 2708,
                  'endTokenPos' => 207,
                  'endFilePos' => 2731,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 55,
                  'endLine' => 55,
                  'startTokenPos' => 213,
                  'startFilePos' => 2743,
                  'endTokenPos' => 213,
                  'endFilePos' => 2744,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 55,
        'endLine' => 56,
        'startColumn' => 9,
        'endColumn' => 37,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'formatOutput' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'formatOutput',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * @var bool
 * Nicely formats output with indentation and extra space.
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.formatoutput
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
                'code' => '[\'8.1\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 62,
                  'endLine' => 62,
                  'startTokenPos' => 231,
                  'startFilePos' => 3056,
                  'endTokenPos' => 237,
                  'endFilePos' => 3072,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 62,
                  'endLine' => 62,
                  'startTokenPos' => 243,
                  'startFilePos' => 3084,
                  'endTokenPos' => 243,
                  'endFilePos' => 3085,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 62,
        'endLine' => 63,
        'startColumn' => 9,
        'endColumn' => 34,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'implementation' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'implementation',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'DOMImplementation',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * @var DOMImplementation
 * The <classname>DOMImplementation</classname> object that handles this document.
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.implementation
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
                'code' => '[\'8.1\' => \'DOMImplementation\']',
                'attributes' => 
                array (
                  'startLine' => 69,
                  'endLine' => 69,
                  'startTokenPos' => 259,
                  'startFilePos' => 3433,
                  'endTokenPos' => 265,
                  'endFilePos' => 3462,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 69,
                  'endLine' => 69,
                  'startTokenPos' => 271,
                  'startFilePos' => 3474,
                  'endTokenPos' => 271,
                  'endFilePos' => 3475,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 69,
        'endLine' => 70,
        'startColumn' => 9,
        'endColumn' => 49,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'preserveWhiteSpace' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'preserveWhiteSpace',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '\\true',
          'attributes' => 
          array (
            'startLine' => 77,
            'endLine' => 77,
            'startTokenPos' => 311,
            'startFilePos' => 3878,
            'endTokenPos' => 311,
            'endFilePos' => 3882,
          ),
        ),
        'docComment' => '/**
 * @var bool
 * Do not remove redundant white space. Default to TRUE.
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.preservewhitespace
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
                'code' => '[\'8.1\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 76,
                  'endLine' => 76,
                  'startTokenPos' => 287,
                  'startFilePos' => 3803,
                  'endTokenPos' => 293,
                  'endFilePos' => 3819,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 76,
                  'endLine' => 76,
                  'startTokenPos' => 299,
                  'startFilePos' => 3831,
                  'endTokenPos' => 299,
                  'endFilePos' => 3832,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 76,
        'endLine' => 77,
        'startColumn' => 9,
        'endColumn' => 48,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'recover' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'recover',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * @var bool
 * Proprietary. Enables recovery mode, i.e. trying to parse non-well formed documents.
 * This attribute is not part of the DOM specification and is specific to libxml.
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.recover
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
                'code' => '[\'8.1\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 84,
                  'endLine' => 84,
                  'startTokenPos' => 319,
                  'startFilePos' => 4268,
                  'endTokenPos' => 325,
                  'endFilePos' => 4284,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 84,
                  'endLine' => 84,
                  'startTokenPos' => 331,
                  'startFilePos' => 4296,
                  'endTokenPos' => 331,
                  'endFilePos' => 4297,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 84,
        'endLine' => 85,
        'startColumn' => 9,
        'endColumn' => 29,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'resolveExternals' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'resolveExternals',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * @var bool
 * Set it to TRUE to load external entities from a doctype declaration. This is useful for
 * including character entities in your XML document.
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.resolveexternals
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
                'code' => '[\'8.1\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 92,
                  'endLine' => 92,
                  'startTokenPos' => 347,
                  'startFilePos' => 4699,
                  'endTokenPos' => 353,
                  'endFilePos' => 4715,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 92,
                  'endLine' => 92,
                  'startTokenPos' => 359,
                  'startFilePos' => 4727,
                  'endTokenPos' => 359,
                  'endFilePos' => 4728,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 92,
        'endLine' => 93,
        'startColumn' => 9,
        'endColumn' => 38,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'standalone' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'standalone',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * @var bool
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.standalone
 * @deprecated
 */',
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Deprecated',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '"Whether or not the document is standalone, as specified by the XML declaration, corresponds to xmlStandalone."',
                'attributes' => 
                array (
                  'startLine' => 99,
                  'endLine' => 99,
                  'startTokenPos' => 375,
                  'startFilePos' => 4974,
                  'endTokenPos' => 375,
                  'endFilePos' => 5084,
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
                'code' => '[\'8.1\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 100,
                  'endLine' => 100,
                  'startTokenPos' => 382,
                  'startFilePos' => 5150,
                  'endTokenPos' => 388,
                  'endFilePos' => 5166,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 100,
                  'endLine' => 100,
                  'startTokenPos' => 394,
                  'startFilePos' => 5178,
                  'endTokenPos' => 394,
                  'endFilePos' => 5179,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 99,
        'endLine' => 101,
        'startColumn' => 9,
        'endColumn' => 32,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'strictErrorChecking' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'strictErrorChecking',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '\\true',
          'attributes' => 
          array (
            'startLine' => 108,
            'endLine' => 108,
            'startTokenPos' => 434,
            'startFilePos' => 5584,
            'endTokenPos' => 434,
            'endFilePos' => 5588,
          ),
        ),
        'docComment' => '/**
 * @var bool
 * Throws <classname>DOMException</classname> on errors. Default to TRUE.
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.stricterrorchecking
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
                'code' => '[\'8.1\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 107,
                  'endLine' => 107,
                  'startTokenPos' => 410,
                  'startFilePos' => 5508,
                  'endTokenPos' => 416,
                  'endFilePos' => 5524,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 107,
                  'endLine' => 107,
                  'startTokenPos' => 422,
                  'startFilePos' => 5536,
                  'endTokenPos' => 422,
                  'endFilePos' => 5537,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 107,
        'endLine' => 108,
        'startColumn' => 9,
        'endColumn' => 49,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'substituteEntities' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'substituteEntities',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * @var bool
 * Proprietary. Whether or not to substitute entities. This attribute is not part of the DOM
 * specification and is specific to libxml.
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.substituteentities
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
                'code' => '[\'8.1\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 115,
                  'endLine' => 115,
                  'startTokenPos' => 442,
                  'startFilePos' => 5953,
                  'endTokenPos' => 448,
                  'endFilePos' => 5969,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 115,
                  'endLine' => 115,
                  'startTokenPos' => 454,
                  'startFilePos' => 5981,
                  'endTokenPos' => 454,
                  'endFilePos' => 5982,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 115,
        'endLine' => 116,
        'startColumn' => 9,
        'endColumn' => 40,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'validateOnParse' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'validateOnParse',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '\\false',
          'attributes' => 
          array (
            'startLine' => 123,
            'endLine' => 123,
            'startTokenPos' => 494,
            'startFilePos' => 6371,
            'endTokenPos' => 494,
            'endFilePos' => 6376,
          ),
        ),
        'docComment' => '/**
 * @var bool
 * Loads and validates against the DTD. Default to FALSE.
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.validateonparse
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
                'code' => '[\'8.1\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 122,
                  'endLine' => 122,
                  'startTokenPos' => 470,
                  'startFilePos' => 6299,
                  'endTokenPos' => 476,
                  'endFilePos' => 6315,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 122,
                  'endLine' => 122,
                  'startTokenPos' => 482,
                  'startFilePos' => 6327,
                  'endTokenPos' => 482,
                  'endFilePos' => 6328,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 122,
        'endLine' => 123,
        'startColumn' => 9,
        'endColumn' => 46,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'version' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'version',
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
        'default' => NULL,
        'docComment' => '/**
 * @var string
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.version
 * @deprecated
 */',
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Deprecated',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'Version of XML, corresponds to xmlVersion\'',
                'attributes' => 
                array (
                  'startLine' => 129,
                  'endLine' => 129,
                  'startTokenPos' => 502,
                  'startFilePos' => 6581,
                  'endTokenPos' => 502,
                  'endFilePos' => 6623,
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
                'code' => '[\'8.1\' => \'string|null\']',
                'attributes' => 
                array (
                  'startLine' => 130,
                  'endLine' => 130,
                  'startTokenPos' => 509,
                  'startFilePos' => 6689,
                  'endTokenPos' => 515,
                  'endFilePos' => 6712,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 130,
                  'endLine' => 130,
                  'startTokenPos' => 521,
                  'startFilePos' => 6724,
                  'endTokenPos' => 521,
                  'endFilePos' => 6725,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 129,
        'endLine' => 131,
        'startColumn' => 9,
        'endColumn' => 36,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'xmlEncoding' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'xmlEncoding',
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
        'default' => NULL,
        'docComment' => '/**
 * @var string|null
 * An attribute specifying, as part of the XML declaration, the encoding of this document. This is NULL when
 * unspecified or when it is not known, such as when the Document was created in memory.
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.xmlencoding
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
                'code' => '[\'8.1\' => \'string|null\']',
                'attributes' => 
                array (
                  'startLine' => 138,
                  'endLine' => 138,
                  'startTokenPos' => 539,
                  'startFilePos' => 7189,
                  'endTokenPos' => 545,
                  'endFilePos' => 7212,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 138,
                  'endLine' => 138,
                  'startTokenPos' => 551,
                  'startFilePos' => 7224,
                  'endTokenPos' => 551,
                  'endFilePos' => 7225,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 138,
        'endLine' => 139,
        'startColumn' => 9,
        'endColumn' => 40,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'xmlStandalone' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'xmlStandalone',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * @var bool
 * An attribute specifying, as part of the XML declaration, whether this document is standalone.
 * This is FALSE when unspecified.
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.xmlstandalone
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
                'code' => '[\'8.1\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 146,
                  'endLine' => 146,
                  'startTokenPos' => 569,
                  'startFilePos' => 7622,
                  'endTokenPos' => 575,
                  'endFilePos' => 7638,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 146,
                  'endLine' => 146,
                  'startTokenPos' => 581,
                  'startFilePos' => 7650,
                  'endTokenPos' => 581,
                  'endFilePos' => 7651,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 146,
        'endLine' => 147,
        'startColumn' => 9,
        'endColumn' => 35,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'xmlVersion' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'xmlVersion',
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
        'default' => NULL,
        'docComment' => '/**
 * @var string|null
 * An attribute specifying, as part of the XML declaration, the version number of this document. If there is no
 * declaration and if this document supports the "XML" feature, the value is "1.0".
 * @link https://php.net/manual/en/class.domdocument.php#domdocument.props.xmlversion
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
                'code' => '[\'8.1\' => \'string|null\']',
                'attributes' => 
                array (
                  'startLine' => 154,
                  'endLine' => 154,
                  'startTokenPos' => 597,
                  'startFilePos' => 8111,
                  'endTokenPos' => 603,
                  'endFilePos' => 8134,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 154,
                  'endLine' => 154,
                  'startTokenPos' => 609,
                  'startFilePos' => 8146,
                  'endTokenPos' => 609,
                  'endFilePos' => 8147,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 154,
        'endLine' => 155,
        'startColumn' => 9,
        'endColumn' => 39,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'childElementCount' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'childElementCount',
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
                'code' => '[\'8.1\' => \'int\']',
                'attributes' => 
                array (
                  'startLine' => 156,
                  'endLine' => 156,
                  'startTokenPos' => 625,
                  'startFilePos' => 8253,
                  'endTokenPos' => 631,
                  'endFilePos' => 8268,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 156,
                  'endLine' => 156,
                  'startTokenPos' => 637,
                  'startFilePos' => 8280,
                  'endTokenPos' => 637,
                  'endFilePos' => 8281,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 156,
        'endLine' => 157,
        'startColumn' => 9,
        'endColumn' => 38,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'lastElementChild' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'lastElementChild',
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
                  'startLine' => 158,
                  'endLine' => 158,
                  'startTokenPos' => 651,
                  'startFilePos' => 8386,
                  'endTokenPos' => 657,
                  'endFilePos' => 8413,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 158,
                  'endLine' => 158,
                  'startTokenPos' => 663,
                  'startFilePos' => 8425,
                  'endTokenPos' => 663,
                  'endFilePos' => 8426,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 158,
        'endLine' => 159,
        'startColumn' => 9,
        'endColumn' => 49,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'firstElementChild' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'firstElementChild',
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
                  'startLine' => 160,
                  'endLine' => 160,
                  'startTokenPos' => 679,
                  'startFilePos' => 8542,
                  'endTokenPos' => 685,
                  'endFilePos' => 8569,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 160,
                  'endLine' => 160,
                  'startTokenPos' => 691,
                  'startFilePos' => 8581,
                  'endTokenPos' => 691,
                  'endFilePos' => 8582,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 160,
        'endLine' => 161,
        'startColumn' => 9,
        'endColumn' => 50,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'ownerDocument' => 
      array (
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'name' => 'ownerDocument',
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
                  'name' => 'DOMDocument',
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
        'docComment' => '/**
 * @var null
 * The <classname>DOMDocument</classname> object associated with this node, or NULL if this node is a <classname>DOMDocument</classname>.
 * @link https://php.net/manual/en/class.domnode.php#domnode.props.ownerdocument
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
                'code' => '[\'8.1\' => \'DOMDocument|null\']',
                'attributes' => 
                array (
                  'startLine' => 167,
                  'endLine' => 167,
                  'startTokenPos' => 709,
                  'startFilePos' => 8979,
                  'endTokenPos' => 715,
                  'endFilePos' => 9007,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 167,
                  'endLine' => 167,
                  'startTokenPos' => 721,
                  'startFilePos' => 9019,
                  'endTokenPos' => 721,
                  'endFilePos' => 9020,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 167,
        'endLine' => 168,
        'startColumn' => 9,
        'endColumn' => 47,
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
      'createElement' => 
      array (
        'name' => 'createElement',
        'parameters' => 
        array (
          'localName' => 
          array (
            'name' => 'localName',
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
                      'startLine' => 184,
                      'endLine' => 184,
                      'startTokenPos' => 746,
                      'startFilePos' => 9790,
                      'endTokenPos' => 752,
                      'endFilePos' => 9808,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 184,
                      'endLine' => 184,
                      'startTokenPos' => 758,
                      'startFilePos' => 9820,
                      'endTokenPos' => 758,
                      'endFilePos' => 9821,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 184,
            'endLine' => 185,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 187,
                'endLine' => 187,
                'startTokenPos' => 792,
                'startFilePos' => 9985,
                'endTokenPos' => 792,
                'endFilePos' => 9986,
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
                      'startLine' => 186,
                      'endLine' => 186,
                      'startTokenPos' => 770,
                      'startFilePos' => 9922,
                      'endTokenPos' => 776,
                      'endFilePos' => 9940,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 186,
                      'endLine' => 186,
                      'startTokenPos' => 782,
                      'startFilePos' => 9952,
                      'endTokenPos' => 782,
                      'endFilePos' => 9953,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 186,
            'endLine' => 187,
            'startColumn' => 13,
            'endColumn' => 30,
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
 * Create new element node
 * @link https://php.net/manual/en/domdocument.createelement.php
 * @param string $localName <p>
 * The tag name of the element.
 * </p>
 * @param string $value [optional] <p>
 * The value of the element. By default, an empty element will be created.
 * You can also set the value later with DOMElement->nodeValue.
 * </p>
 * @return DOMElement|false A new instance of class DOMElement or false
 * if an error occurred.
 * @throws DOMException If invalid $localName
 */',
        'startLine' => 183,
        'endLine' => 190,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'createDocumentFragment' => 
      array (
        'name' => 'createDocumentFragment',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'DOMDocumentFragment',
            'isIdentifier' => false,
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
 * Create new document fragment
 * @link https://php.net/manual/en/domdocument.createdocumentfragment.php
 * @return DOMDocumentFragment|false The new DOMDocumentFragment or false if an error occurred.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 197,
        'endLine' => 200,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'createTextNode' => 
      array (
        'name' => 'createTextNode',
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
                      'startLine' => 212,
                      'endLine' => 212,
                      'startTokenPos' => 837,
                      'startFilePos' => 10966,
                      'endTokenPos' => 843,
                      'endFilePos' => 10984,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 212,
                      'endLine' => 212,
                      'startTokenPos' => 849,
                      'startFilePos' => 10996,
                      'endTokenPos' => 849,
                      'endFilePos' => 10997,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 212,
            'endLine' => 213,
            'startColumn' => 13,
            'endColumn' => 24,
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
            'name' => 'DOMText',
            'isIdentifier' => false,
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
 * Create new text node
 * @link https://php.net/manual/en/domdocument.createtextnode.php
 * @param string $data <p>
 * The content of the text.
 * </p>
 * @return DOMText|false The new DOMText or false if an error occurred.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 210,
        'endLine' => 216,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'createComment' => 
      array (
        'name' => 'createComment',
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
                      'startLine' => 228,
                      'endLine' => 228,
                      'startTokenPos' => 882,
                      'startFilePos' => 11581,
                      'endTokenPos' => 888,
                      'endFilePos' => 11599,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 228,
                      'endLine' => 228,
                      'startTokenPos' => 894,
                      'startFilePos' => 11611,
                      'endTokenPos' => 894,
                      'endFilePos' => 11612,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 228,
            'endLine' => 229,
            'startColumn' => 13,
            'endColumn' => 24,
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
            'name' => 'DOMComment',
            'isIdentifier' => false,
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
 * Create new comment node
 * @link https://php.net/manual/en/domdocument.createcomment.php
 * @param string $data <p>
 * The content of the comment.
 * </p>
 * @return DOMComment|false The new DOMComment or false if an error occurred.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 226,
        'endLine' => 232,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'createCDATASection' => 
      array (
        'name' => 'createCDATASection',
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
                      'startLine' => 242,
                      'endLine' => 242,
                      'startTokenPos' => 923,
                      'startFilePos' => 12113,
                      'endTokenPos' => 929,
                      'endFilePos' => 12131,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 242,
                      'endLine' => 242,
                      'startTokenPos' => 935,
                      'startFilePos' => 12143,
                      'endTokenPos' => 935,
                      'endFilePos' => 12144,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 242,
            'endLine' => 243,
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
        ),
        'docComment' => '/**
 * Create new cdata node
 * @link https://php.net/manual/en/domdocument.createcdatasection.php
 * @param string $data <p>
 * The content of the cdata.
 * </p>
 * @return DOMCDATASection|false The new DOMCDATASection or false if an error occurred.
 */',
        'startLine' => 241,
        'endLine' => 246,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'createProcessingInstruction' => 
      array (
        'name' => 'createProcessingInstruction',
        'parameters' => 
        array (
          'target' => 
          array (
            'name' => 'target',
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
                      'startLine' => 259,
                      'endLine' => 259,
                      'startTokenPos' => 961,
                      'startFilePos' => 12789,
                      'endTokenPos' => 967,
                      'endFilePos' => 12807,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 259,
                      'endLine' => 259,
                      'startTokenPos' => 973,
                      'startFilePos' => 12819,
                      'endTokenPos' => 973,
                      'endFilePos' => 12820,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 259,
            'endLine' => 260,
            'startColumn' => 13,
            'endColumn' => 26,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'data' => 
          array (
            'name' => 'data',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 263,
                'endLine' => 263,
                'startTokenPos' => 1017,
                'startFilePos' => 13067,
                'endTokenPos' => 1017,
                'endFilePos' => 13068,
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\PhpStormStubsElementAvailable',
                'isRepeated' => false,
                'arguments' => 
                array (
                  'from' => 
                  array (
                    'code' => '\'7.4\'',
                    'attributes' => 
                    array (
                      'startLine' => 261,
                      'endLine' => 261,
                      'startTokenPos' => 988,
                      'startFilePos' => 12931,
                      'endTokenPos' => 988,
                      'endFilePos' => 12935,
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
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 262,
                      'endLine' => 262,
                      'startTokenPos' => 995,
                      'startFilePos' => 13005,
                      'endTokenPos' => 1001,
                      'endFilePos' => 13023,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 262,
                      'endLine' => 262,
                      'startTokenPos' => 1007,
                      'startFilePos' => 13035,
                      'endTokenPos' => 1007,
                      'endFilePos' => 13036,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 261,
            'endLine' => 263,
            'startColumn' => 13,
            'endColumn' => 29,
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
 * Creates new PI node
 * @link https://php.net/manual/en/domdocument.createprocessinginstruction.php
 * @param string $target <p>
 * The target of the processing instruction.
 * </p>
 * @param string $data <p>
 * The content of the processing instruction.
 * </p>
 * @return DOMProcessingInstruction|false The new DOMProcessingInstruction or false if an error occurred.
 */',
        'startLine' => 258,
        'endLine' => 266,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'createAttribute' => 
      array (
        'name' => 'createAttribute',
        'parameters' => 
        array (
          'localName' => 
          array (
            'name' => 'localName',
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
                      'startLine' => 277,
                      'endLine' => 277,
                      'startTokenPos' => 1037,
                      'startFilePos' => 13566,
                      'endTokenPos' => 1043,
                      'endFilePos' => 13584,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 277,
                      'endLine' => 277,
                      'startTokenPos' => 1049,
                      'startFilePos' => 13596,
                      'endTokenPos' => 1049,
                      'endFilePos' => 13597,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 277,
            'endLine' => 278,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create new attribute
 * @link https://php.net/manual/en/domdocument.createattribute.php
 * @param string $localName <p>
 * The name of the attribute.
 * </p>
 * @return DOMAttr|false The new DOMAttr or false if an error occurred.
 * @throws DOMException If invalid $localName
 */',
        'startLine' => 276,
        'endLine' => 281,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'createEntityReference' => 
      array (
        'name' => 'createEntityReference',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
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
                      'startLine' => 294,
                      'endLine' => 294,
                      'startTokenPos' => 1075,
                      'startFilePos' => 14238,
                      'endTokenPos' => 1081,
                      'endFilePos' => 14256,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 294,
                      'endLine' => 294,
                      'startTokenPos' => 1087,
                      'startFilePos' => 14268,
                      'endTokenPos' => 1087,
                      'endFilePos' => 14269,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 294,
            'endLine' => 295,
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
        ),
        'docComment' => '/**
 * Create new entity reference node
 * @link https://php.net/manual/en/domdocument.createentityreference.php
 * @param string $name <p>
 * The content of the entity reference, e.g. the entity reference minus
 * the leading &amp; and the trailing
 * ; characters.
 * </p>
 * @return DOMEntityReference|false The new DOMEntityReference or false if an error
 * occurred.
 */',
        'startLine' => 293,
        'endLine' => 298,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'getElementsByTagName' => 
      array (
        'name' => 'getElementsByTagName',
        'parameters' => 
        array (
          'qualifiedName' => 
          array (
            'name' => 'qualifiedName',
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
                      'startLine' => 312,
                      'endLine' => 312,
                      'startTokenPos' => 1117,
                      'startFilePos' => 14972,
                      'endTokenPos' => 1123,
                      'endFilePos' => 14990,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 312,
                      'endLine' => 312,
                      'startTokenPos' => 1129,
                      'startFilePos' => 15002,
                      'endTokenPos' => 1129,
                      'endFilePos' => 15003,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 312,
            'endLine' => 313,
            'startColumn' => 13,
            'endColumn' => 33,
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
            'name' => 'DOMNodeList',
            'isIdentifier' => false,
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
 * Searches for all elements with given tag name
 * @link https://php.net/manual/en/domdocument.getelementsbytagname.php
 * @param string $qualifiedName <p>
 * The name of the tag to match on. The special value *
 * matches all tags.
 * </p>
 * @return DOMNodeList<DOMElement> A new DOMNodeList object containing all the matched
 * elements.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 310,
        'endLine' => 316,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'importNode' => 
      array (
        'name' => 'importNode',
        'parameters' => 
        array (
          'node' => 
          array (
            'name' => 'node',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'DOMNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 334,
            'endLine' => 334,
            'startColumn' => 13,
            'endColumn' => 26,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'deep' => 
          array (
            'name' => 'deep',
            'default' => 
            array (
              'code' => '\\false',
              'attributes' => 
              array (
                'startLine' => 337,
                'endLine' => 337,
                'startTokenPos' => 1195,
                'startFilePos' => 15941,
                'endTokenPos' => 1195,
                'endFilePos' => 15945,
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\PhpStormStubsElementAvailable',
                'isRepeated' => false,
                'arguments' => 
                array (
                  'from' => 
                  array (
                    'code' => '\'7.4\'',
                    'attributes' => 
                    array (
                      'startLine' => 335,
                      'endLine' => 335,
                      'startTokenPos' => 1166,
                      'startFilePos' => 15809,
                      'endTokenPos' => 1166,
                      'endFilePos' => 15813,
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
                    'code' => '[\'8.0\' => \'bool\']',
                    'attributes' => 
                    array (
                      'startLine' => 336,
                      'endLine' => 336,
                      'startTokenPos' => 1173,
                      'startFilePos' => 15883,
                      'endTokenPos' => 1179,
                      'endFilePos' => 15899,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 336,
                      'endLine' => 336,
                      'startTokenPos' => 1185,
                      'startFilePos' => 15911,
                      'endTokenPos' => 1185,
                      'endFilePos' => 15912,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 335,
            'endLine' => 337,
            'startColumn' => 13,
            'endColumn' => 30,
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
 * Import node into current document
 * @link https://php.net/manual/en/domdocument.importnode.php
 * @param DOMNode $node <p>
 * The node to import.
 * </p>
 * @param bool $deep <p>
 * If set to true, this method will recursively import the subtree under
 * the importedNode.
 * </p>
 * <p>
 * To copy the nodes attributes deep needs to be set to true
 * </p>
 * @return DOMNode|false The copied node or false, if it cannot be copied.
 * @meta
 */',
        'startLine' => 333,
        'endLine' => 340,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'createElementNS' => 
      array (
        'name' => 'createElementNS',
        'parameters' => 
        array (
          'namespace' => 
          array (
            'name' => 'namespace',
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
                      'startLine' => 358,
                      'endLine' => 358,
                      'startTokenPos' => 1215,
                      'startFilePos' => 16846,
                      'endTokenPos' => 1221,
                      'endFilePos' => 16869,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 358,
                      'endLine' => 358,
                      'startTokenPos' => 1227,
                      'startFilePos' => 16881,
                      'endTokenPos' => 1227,
                      'endFilePos' => 16882,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 358,
            'endLine' => 359,
            'startColumn' => 13,
            'endColumn' => 34,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'qualifiedName' => 
          array (
            'name' => 'qualifiedName',
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
                      'startLine' => 360,
                      'endLine' => 360,
                      'startTokenPos' => 1241,
                      'startFilePos' => 16988,
                      'endTokenPos' => 1247,
                      'endFilePos' => 17006,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 360,
                      'endLine' => 360,
                      'startTokenPos' => 1253,
                      'startFilePos' => 17018,
                      'endTokenPos' => 1253,
                      'endFilePos' => 17019,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 360,
            'endLine' => 361,
            'startColumn' => 13,
            'endColumn' => 33,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 363,
                'endLine' => 363,
                'startTokenPos' => 1287,
                'startFilePos' => 17187,
                'endTokenPos' => 1287,
                'endFilePos' => 17188,
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
                      'startLine' => 362,
                      'endLine' => 362,
                      'startTokenPos' => 1265,
                      'startFilePos' => 17124,
                      'endTokenPos' => 1271,
                      'endFilePos' => 17142,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 362,
                      'endLine' => 362,
                      'startTokenPos' => 1277,
                      'startFilePos' => 17154,
                      'endTokenPos' => 1277,
                      'endFilePos' => 17155,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 362,
            'endLine' => 363,
            'startColumn' => 13,
            'endColumn' => 30,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create new element node with an associated namespace
 * @link https://php.net/manual/en/domdocument.createelementns.php
 * @param string|null $namespace <p>
 * The URI of the namespace.
 * </p>
 * @param string $qualifiedName <p>
 * The qualified name of the element, as prefix:tagname.
 * </p>
 * @param string $value [optional] <p>
 * The value of the element. By default, an empty element will be created.
 * You can also set the value later with DOMElement->nodeValue.
 * </p>
 * @return DOMElement|false The new DOMElement or false if an error occurred.
 * @throws DOMException If invalid $namespace or $qualifiedName
 */',
        'startLine' => 357,
        'endLine' => 366,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'createAttributeNS' => 
      array (
        'name' => 'createAttributeNS',
        'parameters' => 
        array (
          'namespace' => 
          array (
            'name' => 'namespace',
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
                      'startLine' => 380,
                      'endLine' => 380,
                      'startTokenPos' => 1307,
                      'startFilePos' => 17878,
                      'endTokenPos' => 1313,
                      'endFilePos' => 17901,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 380,
                      'endLine' => 380,
                      'startTokenPos' => 1319,
                      'startFilePos' => 17913,
                      'endTokenPos' => 1319,
                      'endFilePos' => 17914,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 380,
            'endLine' => 381,
            'startColumn' => 13,
            'endColumn' => 34,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'qualifiedName' => 
          array (
            'name' => 'qualifiedName',
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
                      'startLine' => 382,
                      'endLine' => 382,
                      'startTokenPos' => 1333,
                      'startFilePos' => 18020,
                      'endTokenPos' => 1339,
                      'endFilePos' => 18038,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 382,
                      'endLine' => 382,
                      'startTokenPos' => 1345,
                      'startFilePos' => 18050,
                      'endTokenPos' => 1345,
                      'endFilePos' => 18051,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 382,
            'endLine' => 383,
            'startColumn' => 13,
            'endColumn' => 33,
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
 * Create new attribute node with an associated namespace
 * @link https://php.net/manual/en/domdocument.createattributens.php
 * @param string|null $namespace <p>
 * The URI of the namespace.
 * </p>
 * @param string $qualifiedName <p>
 * The tag name and prefix of the attribute, as prefix:tagname.
 * </p>
 * @return DOMAttr|false The new DOMAttr or false if an error occurred.
 * @throws DOMException If invalid $namespace or $qualifiedName
 */',
        'startLine' => 379,
        'endLine' => 386,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'getElementsByTagNameNS' => 
      array (
        'name' => 'getElementsByTagNameNS',
        'parameters' => 
        array (
          'namespace' => 
          array (
            'name' => 'namespace',
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
                      'startLine' => 404,
                      'endLine' => 404,
                      'startTokenPos' => 1375,
                      'startFilePos' => 18973,
                      'endTokenPos' => 1381,
                      'endFilePos' => 18996,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 404,
                      'endLine' => 404,
                      'startTokenPos' => 1387,
                      'startFilePos' => 19008,
                      'endTokenPos' => 1387,
                      'endFilePos' => 19009,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 404,
            'endLine' => 405,
            'startColumn' => 13,
            'endColumn' => 34,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'localName' => 
          array (
            'name' => 'localName',
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
                      'startLine' => 406,
                      'endLine' => 406,
                      'startTokenPos' => 1401,
                      'startFilePos' => 19115,
                      'endTokenPos' => 1407,
                      'endFilePos' => 19133,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 406,
                      'endLine' => 406,
                      'startTokenPos' => 1413,
                      'startFilePos' => 19145,
                      'endTokenPos' => 1413,
                      'endFilePos' => 19146,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 406,
            'endLine' => 407,
            'startColumn' => 13,
            'endColumn' => 29,
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
            'name' => 'DOMNodeList',
            'isIdentifier' => false,
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
 * Searches for all elements with given tag name in specified namespace
 * @link https://php.net/manual/en/domdocument.getelementsbytagnamens.php
 * @param string $namespace <p>
 * The namespace URI of the elements to match on.
 * The special value * matches all namespaces.
 * </p>
 * @param string $localName <p>
 * The local name of the elements to match on.
 * The special value * matches all local names.
 * </p>
 * @return DOMNodeList<DOMElement> A new DOMNodeList object containing all the matched
 * elements.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 402,
        'endLine' => 410,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'getElementById' => 
      array (
        'name' => 'getElementById',
        'parameters' => 
        array (
          'elementId' => 
          array (
            'name' => 'elementId',
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
                      'startLine' => 423,
                      'endLine' => 423,
                      'startTokenPos' => 1446,
                      'startFilePos' => 19784,
                      'endTokenPos' => 1452,
                      'endFilePos' => 19802,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 423,
                      'endLine' => 423,
                      'startTokenPos' => 1458,
                      'startFilePos' => 19814,
                      'endTokenPos' => 1458,
                      'endFilePos' => 19815,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 423,
            'endLine' => 424,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 0,
            'isOptional' => false,
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
 * Searches for an element with a certain id
 * @link https://php.net/manual/en/domdocument.getelementbyid.php
 * @param string $elementId <p>
 * The unique id value for an element.
 * </p>
 * @return DOMElement|null The DOMElement or null if the element is
 * not found.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 421,
        'endLine' => 427,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'adoptNode' => 
      array (
        'name' => 'adoptNode',
        'parameters' => 
        array (
          'node' => 
          array (
            'name' => 'node',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'DOMNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 431,
            'endLine' => 431,
            'startColumn' => 35,
            'endColumn' => 48,
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
                'code' => '[\'8.3\' => \'DOMNode|false\']',
                'attributes' => 
                array (
                  'startLine' => 430,
                  'endLine' => 430,
                  'startTokenPos' => 1485,
                  'startFilePos' => 20061,
                  'endTokenPos' => 1491,
                  'endFilePos' => 20086,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 430,
                  'endLine' => 430,
                  'startTokenPos' => 1497,
                  'startFilePos' => 20098,
                  'endTokenPos' => 1497,
                  'endFilePos' => 20099,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/** @betterReflectionTentativeReturnType */',
        'startLine' => 429,
        'endLine' => 433,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'append' => 
      array (
        'name' => 'append',
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
            'startLine' => 437,
            'endLine' => 437,
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
        'startLine' => 437,
        'endLine' => 439,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'prepend' => 
      array (
        'name' => 'prepend',
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
            'startLine' => 443,
            'endLine' => 443,
            'startColumn' => 33,
            'endColumn' => 41,
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
        'startLine' => 443,
        'endLine' => 445,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'replaceChildren' => 
      array (
        'name' => 'replaceChildren',
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
            'startLine' => 450,
            'endLine' => 450,
            'startColumn' => 41,
            'endColumn' => 49,
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
 * @since 8.3
 * {@inheritDoc}
 */',
        'startLine' => 450,
        'endLine' => 452,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'normalizeDocument' => 
      array (
        'name' => 'normalizeDocument',
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
 * Normalizes the document
 * @link https://php.net/manual/en/domdocument.normalizedocument.php
 * @return void
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 459,
        'endLine' => 462,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'renameNode' => 
      array (
        'name' => 'renameNode',
        'parameters' => 
        array (
          'node' => 
          array (
            'name' => 'node',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'DOMNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 468,
            'endLine' => 468,
            'startColumn' => 36,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'namespace' => 
          array (
            'name' => 'namespace',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 468,
            'endLine' => 468,
            'startColumn' => 52,
            'endColumn' => 61,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'qualifiedName' => 
          array (
            'name' => 'qualifiedName',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 468,
            'endLine' => 468,
            'startColumn' => 64,
            'endColumn' => 77,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param DOMNode $node
 * @param $namespace
 * @param $qualifiedName
 */',
        'startLine' => 468,
        'endLine' => 470,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'load' => 
      array (
        'name' => 'load',
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
                      'startLine' => 489,
                      'endLine' => 489,
                      'startTokenPos' => 1652,
                      'startFilePos' => 21942,
                      'endTokenPos' => 1658,
                      'endFilePos' => 21960,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 489,
                      'endLine' => 489,
                      'startTokenPos' => 1664,
                      'startFilePos' => 21972,
                      'endTokenPos' => 1664,
                      'endFilePos' => 21973,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 489,
            'endLine' => 490,
            'startColumn' => 13,
            'endColumn' => 28,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 492,
                'endLine' => 492,
                'startTokenPos' => 1698,
                'startFilePos' => 22132,
                'endTokenPos' => 1698,
                'endFilePos' => 22132,
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
                      'startLine' => 491,
                      'endLine' => 491,
                      'startTokenPos' => 1676,
                      'startFilePos' => 22073,
                      'endTokenPos' => 1682,
                      'endFilePos' => 22088,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 491,
                      'endLine' => 491,
                      'startTokenPos' => 1688,
                      'startFilePos' => 22100,
                      'endTokenPos' => 1688,
                      'endFilePos' => 22101,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 491,
            'endLine' => 492,
            'startColumn' => 13,
            'endColumn' => 28,
            'parameterIndex' => 1,
            'isOptional' => true,
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
                'code' => '[\'8.3\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 487,
                  'endLine' => 487,
                  'startTokenPos' => 1626,
                  'startFilePos' => 21797,
                  'endTokenPos' => 1632,
                  'endFilePos' => 21813,
                ),
              ),
              'default' => 
              array (
                'code' => '\'DOMDocument|bool\'',
                'attributes' => 
                array (
                  'startLine' => 487,
                  'endLine' => 487,
                  'startTokenPos' => 1638,
                  'startFilePos' => 21825,
                  'endTokenPos' => 1638,
                  'endFilePos' => 21842,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * Load XML from a file
 * @link https://php.net/manual/en/domdocument.load.php
 * @param string $filename <p>
 * The path to the XML document.
 * </p>
 * @param int $options [optional] <p>
 * Bitwise OR
 * of the libxml option constants.
 * </p>
 * @return DOMDocument|bool true on success or false on failure. Prior to PHP 8.3 if called statically, returns a
 * DOMDocument and issues E_STRICT
 * warning.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 486,
        'endLine' => 495,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'save' => 
      array (
        'name' => 'save',
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
                      'startLine' => 510,
                      'endLine' => 510,
                      'startTokenPos' => 1722,
                      'startFilePos' => 22836,
                      'endTokenPos' => 1728,
                      'endFilePos' => 22854,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 510,
                      'endLine' => 510,
                      'startTokenPos' => 1734,
                      'startFilePos' => 22866,
                      'endTokenPos' => 1734,
                      'endFilePos' => 22867,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 510,
            'endLine' => 511,
            'startColumn' => 13,
            'endColumn' => 28,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 513,
                'endLine' => 513,
                'startTokenPos' => 1768,
                'startFilePos' => 23026,
                'endTokenPos' => 1768,
                'endFilePos' => 23029,
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
                      'name' => 'int',
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
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 512,
                      'endLine' => 512,
                      'startTokenPos' => 1746,
                      'startFilePos' => 22967,
                      'endTokenPos' => 1752,
                      'endFilePos' => 22982,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 512,
                      'endLine' => 512,
                      'startTokenPos' => 1758,
                      'startFilePos' => 22994,
                      'endTokenPos' => 1758,
                      'endFilePos' => 22995,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 512,
            'endLine' => 513,
            'startColumn' => 13,
            'endColumn' => 31,
            'parameterIndex' => 1,
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
                  'name' => 'int',
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
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Dumps the internal XML tree back into a file
 * @link https://php.net/manual/en/domdocument.save.php
 * @param string $filename <p>
 * The path to the saved XML document.
 * </p>
 * @param int $options [optional] <p>
 * Additional Options. Currently only LIBXML_NOEMPTYTAG is supported.
 * </p>
 * @return int|false the number of bytes written or false if an error occurred.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 508,
        'endLine' => 516,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'loadXML' => 
      array (
        'name' => 'loadXML',
        'parameters' => 
        array (
          'source' => 
          array (
            'name' => 'source',
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
                      'startLine' => 535,
                      'endLine' => 535,
                      'startTokenPos' => 1816,
                      'startFilePos' => 23916,
                      'endTokenPos' => 1822,
                      'endFilePos' => 23934,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 535,
                      'endLine' => 535,
                      'startTokenPos' => 1828,
                      'startFilePos' => 23946,
                      'endTokenPos' => 1828,
                      'endFilePos' => 23947,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 535,
            'endLine' => 536,
            'startColumn' => 13,
            'endColumn' => 26,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 538,
                'endLine' => 538,
                'startTokenPos' => 1862,
                'startFilePos' => 24104,
                'endTokenPos' => 1862,
                'endFilePos' => 24104,
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
                      'startLine' => 537,
                      'endLine' => 537,
                      'startTokenPos' => 1840,
                      'startFilePos' => 24045,
                      'endTokenPos' => 1846,
                      'endFilePos' => 24060,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 537,
                      'endLine' => 537,
                      'startTokenPos' => 1852,
                      'startFilePos' => 24072,
                      'endTokenPos' => 1852,
                      'endFilePos' => 24073,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 537,
            'endLine' => 538,
            'startColumn' => 13,
            'endColumn' => 28,
            'parameterIndex' => 1,
            'isOptional' => true,
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
                'code' => '[\'8.3\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 533,
                  'endLine' => 533,
                  'startTokenPos' => 1790,
                  'startFilePos' => 23768,
                  'endTokenPos' => 1796,
                  'endFilePos' => 23784,
                ),
              ),
              'default' => 
              array (
                'code' => '\'DOMDocument|bool\'',
                'attributes' => 
                array (
                  'startLine' => 533,
                  'endLine' => 533,
                  'startTokenPos' => 1802,
                  'startFilePos' => 23796,
                  'endTokenPos' => 1802,
                  'endFilePos' => 23813,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * Load XML from a string
 * @link https://php.net/manual/en/domdocument.loadxml.php
 * @param string $source <p>
 * The string containing the XML.
 * </p>
 * @param int $options [optional] <p>
 * Bitwise OR
 * of the libxml option constants.
 * </p>
 * @return DOMDocument|bool true on success or false on failure. Prior to PHP 8.3 if called statically, returns a
 * DOMDocument and issues E_STRICT
 * warning.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 532,
        'endLine' => 541,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'saveXML' => 
      array (
        'name' => 'saveXML',
        'parameters' => 
        array (
          'node' => 
          array (
            'name' => 'node',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 558,
                'endLine' => 558,
                'startTokenPos' => 1910,
                'startFilePos' => 24969,
                'endTokenPos' => 1910,
                'endFilePos' => 24972,
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
                      'name' => 'DOMNode',
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
                    'code' => '[\'7.1\' => \'DOMNode|null\']',
                    'attributes' => 
                    array (
                      'startLine' => 557,
                      'endLine' => 557,
                      'startTokenPos' => 1886,
                      'startFilePos' => 24895,
                      'endTokenPos' => 1892,
                      'endFilePos' => 24919,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 557,
                      'endLine' => 557,
                      'startTokenPos' => 1898,
                      'startFilePos' => 24931,
                      'endTokenPos' => 1898,
                      'endFilePos' => 24932,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 557,
            'endLine' => 558,
            'startColumn' => 13,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 561,
                'endLine' => 561,
                'startTokenPos' => 1948,
                'startFilePos' => 25187,
                'endTokenPos' => 1948,
                'endFilePos' => 25187,
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
                'name' => 'JetBrains\\PhpStorm\\Internal\\PhpStormStubsElementAvailable',
                'isRepeated' => false,
                'arguments' => 
                array (
                  'from' => 
                  array (
                    'code' => '\'7.0\'',
                    'attributes' => 
                    array (
                      'startLine' => 559,
                      'endLine' => 559,
                      'startTokenPos' => 1919,
                      'startFilePos' => 25054,
                      'endTokenPos' => 1919,
                      'endFilePos' => 25058,
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
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 560,
                      'endLine' => 560,
                      'startTokenPos' => 1926,
                      'startFilePos' => 25128,
                      'endTokenPos' => 1932,
                      'endFilePos' => 25143,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 560,
                      'endLine' => 560,
                      'startTokenPos' => 1938,
                      'startFilePos' => 25155,
                      'endTokenPos' => 1938,
                      'endFilePos' => 25156,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 559,
            'endLine' => 561,
            'startColumn' => 13,
            'endColumn' => 28,
            'parameterIndex' => 1,
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
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Dumps the internal XML tree back into a string
 * @link https://php.net/manual/en/domdocument.savexml.php
 * @param null|DOMNode $node [optional] <p>
 * Use this parameter to output only a specific node without XML declaration
 * rather than the entire document.
 * </p>
 * @param int $options [optional] <p>
 * Additional Options. Currently only LIBXML_NOEMPTYTAG is supported.
 * </p>
 * @return string|false the XML, or false if an error occurred.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 555,
        'endLine' => 564,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'version' => 
          array (
            'name' => 'version',
            'default' => 
            array (
              'code' => '\'1.0\'',
              'attributes' => 
              array (
                'startLine' => 573,
                'endLine' => 573,
                'startTokenPos' => 1995,
                'startFilePos' => 25757,
                'endTokenPos' => 1995,
                'endFilePos' => 25761,
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
                      'startLine' => 572,
                      'endLine' => 572,
                      'startTokenPos' => 1973,
                      'startFilePos' => 25692,
                      'endTokenPos' => 1979,
                      'endFilePos' => 25710,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 572,
                      'endLine' => 572,
                      'startTokenPos' => 1985,
                      'startFilePos' => 25722,
                      'endTokenPos' => 1985,
                      'endFilePos' => 25723,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 572,
            'endLine' => 573,
            'startColumn' => 13,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'encoding' => 
          array (
            'name' => 'encoding',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 575,
                'endLine' => 575,
                'startTokenPos' => 2023,
                'startFilePos' => 25896,
                'endTokenPos' => 2023,
                'endFilePos' => 25897,
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
                      'startLine' => 574,
                      'endLine' => 574,
                      'startTokenPos' => 2001,
                      'startFilePos' => 25830,
                      'endTokenPos' => 2007,
                      'endFilePos' => 25848,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 574,
                      'endLine' => 574,
                      'startTokenPos' => 2013,
                      'startFilePos' => 25860,
                      'endTokenPos' => 2013,
                      'endFilePos' => 25861,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 574,
            'endLine' => 575,
            'startColumn' => 13,
            'endColumn' => 33,
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
 * Creates a new DOMDocument object
 * @link https://php.net/manual/en/domdocument.construct.php
 * @param string $version [optional] The version number of the document as part of the XML declaration.
 * @param string $encoding [optional] The encoding of the document as part of the XML declaration.
 */',
        'startLine' => 571,
        'endLine' => 578,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'validate' => 
      array (
        'name' => 'validate',
        'parameters' => 
        array (
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
 * Validates the document based on its DTD
 * @link https://php.net/manual/en/domdocument.validate.php
 * @return bool true on success or false on failure.
 * If the document have no DTD attached, this method will return false.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 586,
        'endLine' => 589,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'xinclude' => 
      array (
        'name' => 'xinclude',
        'parameters' => 
        array (
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 603,
                'endLine' => 603,
                'startTokenPos' => 2090,
                'startFilePos' => 27003,
                'endTokenPos' => 2090,
                'endFilePos' => 27003,
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
                      'startLine' => 602,
                      'endLine' => 602,
                      'startTokenPos' => 2068,
                      'startFilePos' => 26944,
                      'endTokenPos' => 2074,
                      'endFilePos' => 26959,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 602,
                      'endLine' => 602,
                      'startTokenPos' => 2080,
                      'startFilePos' => 26971,
                      'endTokenPos' => 2080,
                      'endFilePos' => 26972,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 602,
            'endLine' => 603,
            'startColumn' => 13,
            'endColumn' => 28,
            'parameterIndex' => 0,
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
                  'name' => 'int',
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
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Substitutes XIncludes in a DOMDocument Object
 * @link https://php.net/manual/en/domdocument.xinclude.php
 * @param int $options [optional] <p>
 * libxml parameters. Available
 * since PHP 5.1.0 and Libxml 2.6.7.
 * </p>
 * @return int|false the number of XIncludes in the document.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 600,
        'endLine' => 606,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'loadHTML' => 
      array (
        'name' => 'loadHTML',
        'parameters' => 
        array (
          'source' => 
          array (
            'name' => 'source',
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
                      'startLine' => 625,
                      'endLine' => 625,
                      'startTokenPos' => 2138,
                      'startFilePos' => 27950,
                      'endTokenPos' => 2144,
                      'endFilePos' => 27968,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 625,
                      'endLine' => 625,
                      'startTokenPos' => 2150,
                      'startFilePos' => 27980,
                      'endTokenPos' => 2150,
                      'endFilePos' => 27981,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 625,
            'endLine' => 626,
            'startColumn' => 13,
            'endColumn' => 26,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 628,
                'endLine' => 628,
                'startTokenPos' => 2184,
                'startFilePos' => 28138,
                'endTokenPos' => 2184,
                'endFilePos' => 28138,
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
                      'startLine' => 627,
                      'endLine' => 627,
                      'startTokenPos' => 2162,
                      'startFilePos' => 28079,
                      'endTokenPos' => 2168,
                      'endFilePos' => 28094,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 627,
                      'endLine' => 627,
                      'startTokenPos' => 2174,
                      'startFilePos' => 28106,
                      'endTokenPos' => 2174,
                      'endFilePos' => 28107,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 627,
            'endLine' => 628,
            'startColumn' => 13,
            'endColumn' => 28,
            'parameterIndex' => 1,
            'isOptional' => true,
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
                'code' => '[\'8.3\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 623,
                  'endLine' => 623,
                  'startTokenPos' => 2112,
                  'startFilePos' => 27801,
                  'endTokenPos' => 2118,
                  'endFilePos' => 27817,
                ),
              ),
              'default' => 
              array (
                'code' => '\'DOMDocument|bool\'',
                'attributes' => 
                array (
                  'startLine' => 623,
                  'endLine' => 623,
                  'startTokenPos' => 2124,
                  'startFilePos' => 27829,
                  'endTokenPos' => 2124,
                  'endFilePos' => 27846,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * Load HTML from a string
 * @link https://php.net/manual/en/domdocument.loadhtml.php
 * @param string $source <p>
 * The HTML string.
 * </p>
 * @param int $options [optional] <p>
 * Since PHP 5.4.0 and Libxml 2.6.0, you may also
 * use the options parameter to specify additional Libxml parameters.
 * </p>
 * @return DOMDocument|bool true on success or false on failure. Prior to PHP 8.3 if called statically, returns a
 * DOMDocument and issues E_STRICT
 * warning.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 622,
        'endLine' => 631,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'loadHTMLFile' => 
      array (
        'name' => 'loadHTMLFile',
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
                      'startLine' => 650,
                      'endLine' => 650,
                      'startTokenPos' => 2227,
                      'startFilePos' => 29092,
                      'endTokenPos' => 2233,
                      'endFilePos' => 29110,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 650,
                      'endLine' => 650,
                      'startTokenPos' => 2239,
                      'startFilePos' => 29122,
                      'endTokenPos' => 2239,
                      'endFilePos' => 29123,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 650,
            'endLine' => 651,
            'startColumn' => 13,
            'endColumn' => 28,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 653,
                'endLine' => 653,
                'startTokenPos' => 2273,
                'startFilePos' => 29282,
                'endTokenPos' => 2273,
                'endFilePos' => 29282,
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
                      'startLine' => 652,
                      'endLine' => 652,
                      'startTokenPos' => 2251,
                      'startFilePos' => 29223,
                      'endTokenPos' => 2257,
                      'endFilePos' => 29238,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 652,
                      'endLine' => 652,
                      'startTokenPos' => 2263,
                      'startFilePos' => 29250,
                      'endTokenPos' => 2263,
                      'endFilePos' => 29251,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 652,
            'endLine' => 653,
            'startColumn' => 13,
            'endColumn' => 28,
            'parameterIndex' => 1,
            'isOptional' => true,
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
                'code' => '[\'8.3\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 648,
                  'endLine' => 648,
                  'startTokenPos' => 2201,
                  'startFilePos' => 28939,
                  'endTokenPos' => 2207,
                  'endFilePos' => 28955,
                ),
              ),
              'default' => 
              array (
                'code' => '\'DOMDocument|bool\'',
                'attributes' => 
                array (
                  'startLine' => 648,
                  'endLine' => 648,
                  'startTokenPos' => 2213,
                  'startFilePos' => 28967,
                  'endTokenPos' => 2213,
                  'endFilePos' => 28984,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * Load HTML from a file
 * @link https://php.net/manual/en/domdocument.loadhtmlfile.php
 * @param string $filename <p>
 * The path to the HTML file.
 * </p>
 * @param int $options [optional] <p>
 * Since PHP 5.4.0 and Libxml 2.6.0, you may also
 * use the options parameter to specify additional Libxml parameters.
 * </p>
 * @return DOMDocument|bool true on success or false on failure. Prior to PHP 8.3 if called statically, returns a
 * DOMDocument and issues E_STRICT
 * warning.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 647,
        'endLine' => 656,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'saveHTML' => 
      array (
        'name' => 'saveHTML',
        'parameters' => 
        array (
          'node' => 
          array (
            'name' => 'node',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 667,
                'endLine' => 667,
                'startTokenPos' => 2321,
                'startFilePos' => 29924,
                'endTokenPos' => 2321,
                'endFilePos' => 29927,
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
                      'name' => 'DOMNode',
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
                    'code' => '[\'8.0\' => \'DOMNode|null\']',
                    'attributes' => 
                    array (
                      'startLine' => 666,
                      'endLine' => 666,
                      'startTokenPos' => 2297,
                      'startFilePos' => 29850,
                      'endTokenPos' => 2303,
                      'endFilePos' => 29874,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 666,
                      'endLine' => 666,
                      'startTokenPos' => 2309,
                      'startFilePos' => 29886,
                      'endTokenPos' => 2309,
                      'endFilePos' => 29887,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 666,
            'endLine' => 667,
            'startColumn' => 13,
            'endColumn' => 37,
            'parameterIndex' => 0,
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
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Dumps the internal document into a string using HTML formatting
 * @link https://php.net/manual/en/domdocument.savehtml.php
 * @param null|DOMNode $node [optional] parameter to output a subset of the document.
 * @return string|false The HTML, or false if an error occurred.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 664,
        'endLine' => 670,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'saveHTMLFile' => 
      array (
        'name' => 'saveHTMLFile',
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
                      'startLine' => 682,
                      'endLine' => 682,
                      'startTokenPos' => 2350,
                      'startFilePos' => 30539,
                      'endTokenPos' => 2356,
                      'endFilePos' => 30557,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 682,
                      'endLine' => 682,
                      'startTokenPos' => 2362,
                      'startFilePos' => 30569,
                      'endTokenPos' => 2362,
                      'endFilePos' => 30570,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 682,
            'endLine' => 683,
            'startColumn' => 13,
            'endColumn' => 28,
            'parameterIndex' => 0,
            'isOptional' => false,
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
                  'name' => 'int',
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
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Dumps the internal document into a file using HTML formatting
 * @link https://php.net/manual/en/domdocument.savehtmlfile.php
 * @param string $filename <p>
 * The path to the saved HTML document.
 * </p>
 * @return int|false the number of bytes written or false if an error occurred.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 680,
        'endLine' => 686,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'schemaValidate' => 
      array (
        'name' => 'schemaValidate',
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
                      'startLine' => 702,
                      'endLine' => 702,
                      'startTokenPos' => 2397,
                      'startFilePos' => 31278,
                      'endTokenPos' => 2403,
                      'endFilePos' => 31296,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 702,
                      'endLine' => 702,
                      'startTokenPos' => 2409,
                      'startFilePos' => 31308,
                      'endTokenPos' => 2409,
                      'endFilePos' => 31309,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 702,
            'endLine' => 703,
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
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 705,
                'endLine' => 705,
                'startTokenPos' => 2443,
                'startFilePos' => 31466,
                'endTokenPos' => 2443,
                'endFilePos' => 31469,
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
                      'name' => 'int',
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
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 704,
                      'endLine' => 704,
                      'startTokenPos' => 2421,
                      'startFilePos' => 31409,
                      'endTokenPos' => 2427,
                      'endFilePos' => 31424,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 704,
                      'endLine' => 704,
                      'startTokenPos' => 2433,
                      'startFilePos' => 31436,
                      'endTokenPos' => 2433,
                      'endFilePos' => 31437,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 704,
            'endLine' => 705,
            'startColumn' => 13,
            'endColumn' => 29,
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
 * Validates a document based on a schema
 * @link https://php.net/manual/en/domdocument.schemavalidate.php
 * @param string $filename <p>
 * The path to the schema.
 * </p>
 * @param int $options [optional] <p>
 * Bitwise OR
 * of the libxml option constants.
 * </p>
 * @return bool true on success or false on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 700,
        'endLine' => 708,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'schemaValidateSource' => 
      array (
        'name' => 'schemaValidateSource',
        'parameters' => 
        array (
          'source' => 
          array (
            'name' => 'source',
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
                      'startLine' => 722,
                      'endLine' => 722,
                      'startTokenPos' => 2470,
                      'startFilePos' => 32247,
                      'endTokenPos' => 2476,
                      'endFilePos' => 32265,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 722,
                      'endLine' => 722,
                      'startTokenPos' => 2482,
                      'startFilePos' => 32277,
                      'endTokenPos' => 2482,
                      'endFilePos' => 32278,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 722,
            'endLine' => 723,
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
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 725,
                'endLine' => 725,
                'startTokenPos' => 2516,
                'startFilePos' => 32433,
                'endTokenPos' => 2516,
                'endFilePos' => 32433,
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
                      'startLine' => 724,
                      'endLine' => 724,
                      'startTokenPos' => 2494,
                      'startFilePos' => 32376,
                      'endTokenPos' => 2500,
                      'endFilePos' => 32391,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 724,
                      'endLine' => 724,
                      'startTokenPos' => 2506,
                      'startFilePos' => 32403,
                      'endTokenPos' => 2506,
                      'endFilePos' => 32404,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 724,
            'endLine' => 725,
            'startColumn' => 13,
            'endColumn' => 26,
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
 * Validates a document based on a schema
 * @link https://php.net/manual/en/domdocument.schemavalidatesource.php
 * @param string $source <p>
 * A string containing the schema.
 * </p>
 * @param int $flags [optional] <p>A bitmask of Libxml schema validation flags. Currently the only supported value is <b>LIBXML_SCHEMA_CREATE</b>.
 * Available since PHP 5.5.2 and Libxml 2.6.14.</p>
 * @return bool true on success or false on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 720,
        'endLine' => 728,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'relaxNGValidate' => 
      array (
        'name' => 'relaxNGValidate',
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
                      'startLine' => 740,
                      'endLine' => 740,
                      'startTokenPos' => 2543,
                      'startFilePos' => 32975,
                      'endTokenPos' => 2549,
                      'endFilePos' => 32993,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 740,
                      'endLine' => 740,
                      'startTokenPos' => 2555,
                      'startFilePos' => 33005,
                      'endTokenPos' => 2555,
                      'endFilePos' => 33006,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 740,
            'endLine' => 741,
            'startColumn' => 13,
            'endColumn' => 28,
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
 * Performs relaxNG validation on the document
 * @link https://php.net/manual/en/domdocument.relaxngvalidate.php
 * @param string $filename <p>
 * The RNG file.
 * </p>
 * @return bool true on success or false on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 738,
        'endLine' => 744,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'relaxNGValidateSource' => 
      array (
        'name' => 'relaxNGValidateSource',
        'parameters' => 
        array (
          'source' => 
          array (
            'name' => 'source',
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
                      'startLine' => 756,
                      'endLine' => 756,
                      'startTokenPos' => 2588,
                      'startFilePos' => 33611,
                      'endTokenPos' => 2594,
                      'endFilePos' => 33629,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 756,
                      'endLine' => 756,
                      'startTokenPos' => 2600,
                      'startFilePos' => 33641,
                      'endTokenPos' => 2600,
                      'endFilePos' => 33642,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 756,
            'endLine' => 757,
            'startColumn' => 13,
            'endColumn' => 26,
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
 * Performs relaxNG validation on the document
 * @link https://php.net/manual/en/domdocument.relaxngvalidatesource.php
 * @param string $source <p>
 * A string containing the RNG schema.
 * </p>
 * @return bool true on success or false on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 754,
        'endLine' => 760,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
        'aliasName' => NULL,
      ),
      'registerNodeClass' => 
      array (
        'name' => 'registerNodeClass',
        'parameters' => 
        array (
          'baseClass' => 
          array (
            'name' => 'baseClass',
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
                      'startLine' => 779,
                      'endLine' => 779,
                      'startTokenPos' => 2652,
                      'startFilePos' => 34638,
                      'endTokenPos' => 2658,
                      'endFilePos' => 34656,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 779,
                      'endLine' => 779,
                      'startTokenPos' => 2664,
                      'startFilePos' => 34668,
                      'endTokenPos' => 2664,
                      'endFilePos' => 34669,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 779,
            'endLine' => 780,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'extendedClass' => 
          array (
            'name' => 'extendedClass',
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
                      'startLine' => 781,
                      'endLine' => 781,
                      'startTokenPos' => 2676,
                      'startFilePos' => 34770,
                      'endTokenPos' => 2682,
                      'endFilePos' => 34793,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 781,
                      'endLine' => 781,
                      'startTokenPos' => 2688,
                      'startFilePos' => 34805,
                      'endTokenPos' => 2688,
                      'endFilePos' => 34806,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 781,
            'endLine' => 782,
            'startColumn' => 13,
            'endColumn' => 38,
            'parameterIndex' => 1,
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
                'code' => '[\'8.4\' => \'true\']',
                'attributes' => 
                array (
                  'startLine' => 777,
                  'endLine' => 777,
                  'startTokenPos' => 2626,
                  'startFilePos' => 34492,
                  'endTokenPos' => 2632,
                  'endFilePos' => 34508,
                ),
              ),
              'default' => 
              array (
                'code' => '\'bool\'',
                'attributes' => 
                array (
                  'startLine' => 777,
                  'endLine' => 777,
                  'startTokenPos' => 2638,
                  'startFilePos' => 34520,
                  'endTokenPos' => 2638,
                  'endFilePos' => 34525,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * Register extended class used to create base node type
 * @link https://php.net/manual/en/domdocument.registernodeclass.php
 * @param string $baseClass <p>
 * The DOM class that you want to extend. You can find a list of these
 * classes in the chapter introduction.
 * </p>
 * @param string $extendedClass <p>
 * Your extended class name. If null is provided, any previously
 * registered class extending baseclass will
 * be removed.
 * </p>
 * @return bool true on success or false on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 776,
        'endLine' => 785,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMDocument',
        'implementingClassName' => 'DOMDocument',
        'currentClassName' => 'DOMDocument',
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