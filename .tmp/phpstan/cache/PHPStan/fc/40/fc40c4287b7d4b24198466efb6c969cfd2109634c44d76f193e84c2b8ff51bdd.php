<?php declare(strict_types = 1);

// phpinternal-PHPStan\BetterReflection\Reflection\ReflectionClass-domtext
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-dev-master@709e512-8.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\InternalLocatedSource',
      'data' => 
      array (
        'name' => 'DOMText',
        'filename' => 'phpstorm-stubs:dom/dom_c.stub',
        'extensionName' => 'dom',
        'aliasName' => NULL,
      ),
    ),
    'namespace' => NULL,
    'name' => 'DOMText',
    'shortName' => 'DOMText',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * The DOMText class inherits from <classname>DOMCharacterData</classname> and represents the textual content of
 * a <classname>DOMElement</classname> or <classname>DOMAttr</classname>.
 * @link https://php.net/manual/en/class.domtext.php
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 9,
    'endLine' => 64,
    'startColumn' => 5,
    'endColumn' => 5,
    'parentClassName' => 'DOMCharacterData',
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
      'wholeText' => 
      array (
        'declaringClassName' => 'DOMText',
        'implementingClassName' => 'DOMText',
        'name' => 'wholeText',
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
 * Holds all the text of logically-adjacent (not separated by Element, Comment or Processing Instruction) Text nodes.
 * @link https://php.net/manual/en/class.domtext.php#domtext.props.wholeText
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
                  'startLine' => 15,
                  'endLine' => 15,
                  'startTokenPos' => 23,
                  'startFilePos' => 634,
                  'endTokenPos' => 29,
                  'endFilePos' => 652,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 15,
                  'endLine' => 15,
                  'startTokenPos' => 35,
                  'startFilePos' => 664,
                  'endTokenPos' => 35,
                  'endFilePos' => 665,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 15,
        'endLine' => 16,
        'startColumn' => 9,
        'endColumn' => 33,
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
      'splitText' => 
      array (
        'name' => 'splitText',
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
                      'startLine' => 27,
                      'endLine' => 27,
                      'startTokenPos' => 58,
                      'startFilePos' => 1193,
                      'endTokenPos' => 64,
                      'endFilePos' => 1208,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 27,
                      'endLine' => 27,
                      'startTokenPos' => 70,
                      'startFilePos' => 1220,
                      'endTokenPos' => 70,
                      'endFilePos' => 1221,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 27,
            'endLine' => 28,
            'startColumn' => 13,
            'endColumn' => 23,
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
 * Breaks this node into two nodes at the specified offset
 * @link https://php.net/manual/en/domtext.splittext.php
 * @param int $offset <p>
 * The offset at which to split, starting from 0.
 * </p>
 * @return DOMText The new node of the same type, which contains all the content at and after the
 * offset.
 */',
        'startLine' => 26,
        'endLine' => 31,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMText',
        'implementingClassName' => 'DOMText',
        'currentClassName' => 'DOMText',
        'aliasName' => NULL,
      ),
      'isWhitespaceInElementContent' => 
      array (
        'name' => 'isWhitespaceInElementContent',
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
 * Indicates whether this text node contains whitespace
 * @link https://php.net/manual/en/domtext.iswhitespaceinelementcontent.php
 * @return bool true on success or false on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 38,
        'endLine' => 41,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMText',
        'implementingClassName' => 'DOMText',
        'currentClassName' => 'DOMText',
        'aliasName' => NULL,
      ),
      'isElementContentWhitespace' => 
      array (
        'name' => 'isElementContentWhitespace',
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
        'docComment' => '/** @betterReflectionTentativeReturnType */',
        'startLine' => 43,
        'endLine' => 46,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMText',
        'implementingClassName' => 'DOMText',
        'currentClassName' => 'DOMText',
        'aliasName' => NULL,
      ),
      'replaceWholeText' => 
      array (
        'name' => 'replaceWholeText',
        'parameters' => 
        array (
          'content' => 
          array (
            'name' => 'content',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 50,
            'endLine' => 50,
            'startColumn' => 42,
            'endColumn' => 49,
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
 * @param $content
 */',
        'startLine' => 50,
        'endLine' => 52,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMText',
        'implementingClassName' => 'DOMText',
        'currentClassName' => 'DOMText',
        'aliasName' => NULL,
      ),
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'data' => 
          array (
            'name' => 'data',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 60,
                'endLine' => 60,
                'startTokenPos' => 175,
                'startFilePos' => 2436,
                'endTokenPos' => 175,
                'endFilePos' => 2437,
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
                      'startLine' => 59,
                      'endLine' => 59,
                      'startTokenPos' => 153,
                      'startFilePos' => 2374,
                      'endTokenPos' => 159,
                      'endFilePos' => 2392,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 59,
                      'endLine' => 59,
                      'startTokenPos' => 165,
                      'startFilePos' => 2404,
                      'endTokenPos' => 165,
                      'endFilePos' => 2405,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 59,
            'endLine' => 60,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Creates a new <classname>DOMText</classname> object
 * @link https://php.net/manual/en/domtext.construct.php
 * @param string $data [optional] The value of the text node. If not supplied an empty text node is created.
 */',
        'startLine' => 58,
        'endLine' => 63,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMText',
        'implementingClassName' => 'DOMText',
        'currentClassName' => 'DOMText',
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