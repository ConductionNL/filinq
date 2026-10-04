<?php declare(strict_types = 1);

// phpinternal-PHPStan\BetterReflection\Reflection\ReflectionClass-domnode
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-dev-master@709e512-8.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\InternalLocatedSource',
      'data' => 
      array (
        'name' => 'DOMNode',
        'filename' => 'phpstorm-stubs:dom/dom_c.stub',
        'extensionName' => 'dom',
        'aliasName' => NULL,
      ),
    ),
    'namespace' => NULL,
    'name' => 'DOMNode',
    'shortName' => 'DOMNode',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * The DOMNode class
 * @link https://php.net/manual/en/class.domnode.php
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 8,
    'endLine' => 431,
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
      'nodeName' => 
      array (
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'name' => 'nodeName',
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
 * Returns the most accurate name for the current node type
 * @link https://php.net/manual/en/class.domnode.php#domnode.props.nodename
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
                  'startTokenPos' => 19,
                  'startFilePos' => 402,
                  'endTokenPos' => 25,
                  'endFilePos' => 420,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 15,
                  'endLine' => 15,
                  'startTokenPos' => 31,
                  'startFilePos' => 432,
                  'endTokenPos' => 31,
                  'endFilePos' => 433,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 15,
        'endLine' => 16,
        'startColumn' => 9,
        'endColumn' => 32,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'nodeValue' => 
      array (
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'name' => 'nodeValue',
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
 * The value of this node, depending on its type
 * @link https://php.net/manual/en/class.domnode.php#domnode.props.nodevalue
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
                  'startLine' => 22,
                  'endLine' => 22,
                  'startTokenPos' => 47,
                  'startFilePos' => 726,
                  'endTokenPos' => 53,
                  'endFilePos' => 749,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 22,
                  'endLine' => 22,
                  'startTokenPos' => 59,
                  'startFilePos' => 761,
                  'endTokenPos' => 59,
                  'endFilePos' => 762,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 22,
        'endLine' => 23,
        'startColumn' => 9,
        'endColumn' => 38,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'nodeType' => 
      array (
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'name' => 'nodeType',
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
 * Gets the type of the node. One of the predefined
 * <a href="https://secure.php.net/manual/en/dom.constants.php">XML_xxx_NODE</a> constants
 * @link https://php.net/manual/en/class.domnode.php#domnode.props.nodetype
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
                  'startLine' => 30,
                  'endLine' => 30,
                  'startTokenPos' => 77,
                  'startFilePos' => 1154,
                  'endTokenPos' => 83,
                  'endFilePos' => 1169,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 30,
                  'endLine' => 30,
                  'startTokenPos' => 89,
                  'startFilePos' => 1181,
                  'endTokenPos' => 89,
                  'endFilePos' => 1182,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 30,
        'endLine' => 31,
        'startColumn' => 9,
        'endColumn' => 29,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'parentNode' => 
      array (
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'name' => 'parentNode',
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
        'default' => NULL,
        'docComment' => '/**
 * @var DOMNode|null
 * The parent of this node. If there is no such node, this returns NULL.
 * @link https://php.net/manual/en/class.domnode.php#domnode.props.parentnode
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
                'code' => '[\'8.1\' => \'DOMNode|null\']',
                'attributes' => 
                array (
                  'startLine' => 37,
                  'endLine' => 37,
                  'startTokenPos' => 105,
                  'startFilePos' => 1498,
                  'endTokenPos' => 111,
                  'endFilePos' => 1522,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 37,
                  'endLine' => 37,
                  'startTokenPos' => 117,
                  'startFilePos' => 1534,
                  'endTokenPos' => 117,
                  'endFilePos' => 1535,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 37,
        'endLine' => 38,
        'startColumn' => 9,
        'endColumn' => 40,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'childNodes' => 
      array (
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'name' => 'childNodes',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'DOMNodeList',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * @var DOMNodeList<DOMNode>
 * A <classname>DOMNodeList</classname> that contains all children of this node. If there are no children, this is an empty <classname>DOMNodeList</classname>.
 * @link https://php.net/manual/en/class.domnode.php#domnode.props.childnodes
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
                'code' => '[\'8.1\' => \'DOMNodeList\']',
                'attributes' => 
                array (
                  'startLine' => 44,
                  'endLine' => 44,
                  'startTokenPos' => 135,
                  'startFilePos' => 1957,
                  'endTokenPos' => 141,
                  'endFilePos' => 1980,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 44,
                  'endLine' => 44,
                  'startTokenPos' => 147,
                  'startFilePos' => 1992,
                  'endTokenPos' => 147,
                  'endFilePos' => 1993,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 44,
        'endLine' => 45,
        'startColumn' => 9,
        'endColumn' => 39,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'firstChild' => 
      array (
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'name' => 'firstChild',
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
        'default' => NULL,
        'docComment' => '/**
 * @var DOMNode|null
 * The first child of this node. If there is no such node, this returns NULL.
 * @link https://php.net/manual/en/class.domnode.php#domnode.props.firstchild
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
                'code' => '[\'8.1\' => \'DOMNode|null\']',
                'attributes' => 
                array (
                  'startLine' => 51,
                  'endLine' => 51,
                  'startTokenPos' => 163,
                  'startFilePos' => 2324,
                  'endTokenPos' => 169,
                  'endFilePos' => 2348,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 51,
                  'endLine' => 51,
                  'startTokenPos' => 175,
                  'startFilePos' => 2360,
                  'endTokenPos' => 175,
                  'endFilePos' => 2361,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 51,
        'endLine' => 52,
        'startColumn' => 9,
        'endColumn' => 40,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'lastChild' => 
      array (
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'name' => 'lastChild',
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
        'default' => NULL,
        'docComment' => '/**
 * @var DOMNode|null
 * The last child of this node. If there is no such node, this returns NULL.
 * @link https://php.net/manual/en/class.domnode.php#domnode.props.lastchild
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
                'code' => '[\'8.1\' => \'DOMNode|null\']',
                'attributes' => 
                array (
                  'startLine' => 58,
                  'endLine' => 58,
                  'startTokenPos' => 193,
                  'startFilePos' => 2691,
                  'endTokenPos' => 199,
                  'endFilePos' => 2715,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 58,
                  'endLine' => 58,
                  'startTokenPos' => 205,
                  'startFilePos' => 2727,
                  'endTokenPos' => 205,
                  'endFilePos' => 2728,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 58,
        'endLine' => 59,
        'startColumn' => 9,
        'endColumn' => 39,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'previousSibling' => 
      array (
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'name' => 'previousSibling',
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
        'default' => NULL,
        'docComment' => '/**
 * @var DOMNode|null
 * The node immediately preceding this node. If there is no such node, this returns NULL.
 * @link https://php.net/manual/en/class.domnode.php#domnode.props.previoussibling
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
                'code' => '[\'8.1\' => \'DOMNode|null\']',
                'attributes' => 
                array (
                  'startLine' => 65,
                  'endLine' => 65,
                  'startTokenPos' => 223,
                  'startFilePos' => 3076,
                  'endTokenPos' => 229,
                  'endFilePos' => 3100,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 65,
                  'endLine' => 65,
                  'startTokenPos' => 235,
                  'startFilePos' => 3112,
                  'endTokenPos' => 235,
                  'endFilePos' => 3113,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 65,
        'endLine' => 66,
        'startColumn' => 9,
        'endColumn' => 45,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'nextSibling' => 
      array (
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'name' => 'nextSibling',
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
        'default' => NULL,
        'docComment' => '/**
 * @var DOMNode|null
 * The node immediately following this node. If there is no such node, this returns NULL.
 * @link https://php.net/manual/en/class.domnode.php#domnode.props.nextsibling
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
                'code' => '[\'8.1\' => \'DOMNode|null\']',
                'attributes' => 
                array (
                  'startLine' => 72,
                  'endLine' => 72,
                  'startTokenPos' => 253,
                  'startFilePos' => 3463,
                  'endTokenPos' => 259,
                  'endFilePos' => 3487,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 72,
                  'endLine' => 72,
                  'startTokenPos' => 265,
                  'startFilePos' => 3499,
                  'endTokenPos' => 265,
                  'endFilePos' => 3500,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 72,
        'endLine' => 73,
        'startColumn' => 9,
        'endColumn' => 41,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'attributes' => 
      array (
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'name' => 'attributes',
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
                  'name' => 'DOMNamedNodeMap',
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
 * @var DOMNamedNodeMap<DOMAttr>|null
 * A <classname>DOMNamedNodeMap</classname> containing the attributes of this node (if it is a <classname>DOMElement</classname>) or NULL otherwise.
 * @link https://php.net/manual/en/class.domnode.php#domnode.props.attributes
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
                'code' => '[\'8.1\' => \'DOMNamedNodeMap|null\']',
                'attributes' => 
                array (
                  'startLine' => 79,
                  'endLine' => 79,
                  'startTokenPos' => 283,
                  'startFilePos' => 3921,
                  'endTokenPos' => 289,
                  'endFilePos' => 3953,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 79,
                  'endLine' => 79,
                  'startTokenPos' => 295,
                  'startFilePos' => 3965,
                  'endTokenPos' => 295,
                  'endFilePos' => 3966,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 79,
        'endLine' => 80,
        'startColumn' => 9,
        'endColumn' => 48,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'ownerDocument' => 
      array (
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
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
 * @var DOMDocument|null
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
                  'startLine' => 86,
                  'endLine' => 86,
                  'startTokenPos' => 313,
                  'startFilePos' => 4373,
                  'endTokenPos' => 319,
                  'endFilePos' => 4401,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 86,
                  'endLine' => 86,
                  'startTokenPos' => 325,
                  'startFilePos' => 4413,
                  'endTokenPos' => 325,
                  'endFilePos' => 4414,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 86,
        'endLine' => 87,
        'startColumn' => 9,
        'endColumn' => 47,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'namespaceURI' => 
      array (
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'name' => 'namespaceURI',
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
 * The namespace URI of this node, or NULL if it is unspecified.
 * @link https://php.net/manual/en/class.domnode.php#domnode.props.namespaceuri
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
                  'startLine' => 93,
                  'endLine' => 93,
                  'startTokenPos' => 343,
                  'startFilePos' => 4741,
                  'endTokenPos' => 349,
                  'endFilePos' => 4764,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 93,
                  'endLine' => 93,
                  'startTokenPos' => 355,
                  'startFilePos' => 4776,
                  'endTokenPos' => 355,
                  'endFilePos' => 4777,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 93,
        'endLine' => 94,
        'startColumn' => 9,
        'endColumn' => 41,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'prefix' => 
      array (
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'name' => 'prefix',
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
 * @var string|null
 * The namespace prefix of this node, or NULL if it is unspecified.
 * @link https://php.net/manual/en/class.domnode.php#domnode.props.prefix
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
                  'startLine' => 100,
                  'endLine' => 100,
                  'startTokenPos' => 373,
                  'startFilePos' => 5095,
                  'endTokenPos' => 379,
                  'endFilePos' => 5113,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 100,
                  'endLine' => 100,
                  'startTokenPos' => 385,
                  'startFilePos' => 5125,
                  'endTokenPos' => 385,
                  'endFilePos' => 5126,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 100,
        'endLine' => 101,
        'startColumn' => 9,
        'endColumn' => 30,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'localName' => 
      array (
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'name' => 'localName',
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
 * Returns the local part of the qualified name of this node.
 * @link https://php.net/manual/en/class.domnode.php#domnode.props.localname
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
                  'startLine' => 107,
                  'endLine' => 107,
                  'startTokenPos' => 401,
                  'startFilePos' => 5430,
                  'endTokenPos' => 407,
                  'endFilePos' => 5453,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 107,
                  'endLine' => 107,
                  'startTokenPos' => 413,
                  'startFilePos' => 5465,
                  'endTokenPos' => 413,
                  'endFilePos' => 5466,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 107,
        'endLine' => 108,
        'startColumn' => 9,
        'endColumn' => 38,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'baseURI' => 
      array (
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'name' => 'baseURI',
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
 * The absolute base URI of this node or NULL if the implementation wasn\'t able to obtain an absolute URI.
 * @link https://php.net/manual/en/class.domnode.php#domnode.props.baseuri
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
                  'startLine' => 114,
                  'endLine' => 114,
                  'startTokenPos' => 431,
                  'startFilePos' => 5821,
                  'endTokenPos' => 437,
                  'endFilePos' => 5844,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 114,
                  'endLine' => 114,
                  'startTokenPos' => 443,
                  'startFilePos' => 5856,
                  'endTokenPos' => 443,
                  'endFilePos' => 5857,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 114,
        'endLine' => 115,
        'startColumn' => 9,
        'endColumn' => 36,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'textContent' => 
      array (
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'name' => 'textContent',
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
 * This attribute returns the text content of this node and its descendants.
 * @link https://php.net/manual/en/class.domnode.php#domnode.props.textcontent
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
                  'startLine' => 121,
                  'endLine' => 121,
                  'startTokenPos' => 461,
                  'startFilePos' => 6179,
                  'endTokenPos' => 467,
                  'endFilePos' => 6197,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 121,
                  'endLine' => 121,
                  'startTokenPos' => 473,
                  'startFilePos' => 6209,
                  'endTokenPos' => 473,
                  'endFilePos' => 6210,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 121,
        'endLine' => 122,
        'startColumn' => 9,
        'endColumn' => 35,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'isConnected' => 
      array (
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'name' => 'isConnected',
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
        'docComment' => NULL,
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
                'code' => '\'8.3\'',
                'attributes' => 
                array (
                  'startLine' => 123,
                  'endLine' => 123,
                  'startTokenPos' => 490,
                  'startFilePos' => 6325,
                  'endTokenPos' => 490,
                  'endFilePos' => 6329,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 123,
        'endLine' => 124,
        'startColumn' => 9,
        'endColumn' => 33,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'parentElement' => 
      array (
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'name' => 'parentElement',
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
            'name' => 'JetBrains\\PhpStorm\\Internal\\PhpStormStubsElementAvailable',
            'isRepeated' => false,
            'arguments' => 
            array (
              'from' => 
              array (
                'code' => '\'8.3\'',
                'attributes' => 
                array (
                  'startLine' => 125,
                  'endLine' => 125,
                  'startTokenPos' => 507,
                  'startFilePos' => 6442,
                  'endTokenPos' => 507,
                  'endFilePos' => 6446,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 125,
        'endLine' => 126,
        'startColumn' => 9,
        'endColumn' => 43,
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
      'insertBefore' => 
      array (
        'name' => 'insertBefore',
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
            'startLine' => 142,
            'endLine' => 142,
            'startColumn' => 13,
            'endColumn' => 26,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'child' => 
          array (
            'name' => 'child',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 144,
                'endLine' => 144,
                'startTokenPos' => 560,
                'startFilePos' => 7197,
                'endTokenPos' => 560,
                'endFilePos' => 7200,
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
                      'startLine' => 143,
                      'endLine' => 143,
                      'startTokenPos' => 536,
                      'startFilePos' => 7115,
                      'endTokenPos' => 542,
                      'endFilePos' => 7139,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'DOMNode\'',
                    'attributes' => 
                    array (
                      'startLine' => 143,
                      'endLine' => 143,
                      'startTokenPos' => 548,
                      'startFilePos' => 7151,
                      'endTokenPos' => 548,
                      'endFilePos' => 7159,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 143,
            'endLine' => 144,
            'startColumn' => 13,
            'endColumn' => 38,
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
 * Adds a new child before a reference node
 * @link https://php.net/manual/en/domnode.insertbefore.php
 * @template TNode of DOMNode
 * @param TNode $node <p>
 * The new node.
 * </p>
 * @param null|DOMNode $child [optional] <p>
 * The reference node. If not supplied, newnode is
 * appended to the children.
 * </p>
 * @return TNode|false The inserted node.
 * @meta
 */',
        'startLine' => 141,
        'endLine' => 147,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'currentClassName' => 'DOMNode',
        'aliasName' => NULL,
      ),
      'replaceChild' => 
      array (
        'name' => 'replaceChild',
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
            'startLine' => 163,
            'endLine' => 163,
            'startColumn' => 38,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'child' => 
          array (
            'name' => 'child',
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
            'startLine' => 163,
            'endLine' => 163,
            'startColumn' => 54,
            'endColumn' => 68,
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
 * Replaces a child
 * @link https://php.net/manual/en/domnode.replacechild.php
 * @template TNode of DOMNode
 * @param DOMNode $node <p>
 * The new node. It must be a member of the target document, i.e.
 * created by one of the DOMDocument->createXXX() methods or imported in
 * the document by .
 * </p>
 * @param TNode $child <p>
 * The old node.
 * </p>
 * @return TNode|false The old node or false if an error occur.
 * @meta
 */',
        'startLine' => 163,
        'endLine' => 165,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'currentClassName' => 'DOMNode',
        'aliasName' => NULL,
      ),
      'removeChild' => 
      array (
        'name' => 'removeChild',
        'parameters' => 
        array (
          'child' => 
          array (
            'name' => 'child',
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
            'startLine' => 176,
            'endLine' => 176,
            'startColumn' => 37,
            'endColumn' => 51,
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
 * Removes child from list of children
 * @link https://php.net/manual/en/domnode.removechild.php
 * @template TNode of DOMNode
 * @param TNode $child <p>
 * The removed child.
 * </p>
 * @return TNode|false If the child could be removed the functions returns the old child.
 * @meta
 */',
        'startLine' => 176,
        'endLine' => 178,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'currentClassName' => 'DOMNode',
        'aliasName' => NULL,
      ),
      'appendChild' => 
      array (
        'name' => 'appendChild',
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
            'startLine' => 189,
            'endLine' => 189,
            'startColumn' => 37,
            'endColumn' => 50,
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
 * Adds new child at the end of the children
 * @link https://php.net/manual/en/domnode.appendchild.php
 * @template TNode of DOMNode
 * @param TNode $node <p>
 * The appended child.
 * </p>
 * @return TNode|false The node added.
 * @meta
 */',
        'startLine' => 189,
        'endLine' => 191,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'currentClassName' => 'DOMNode',
        'aliasName' => NULL,
      ),
      'hasChildNodes' => 
      array (
        'name' => 'hasChildNodes',
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
 * Checks if node has children
 * @link https://php.net/manual/en/domnode.haschildnodes.php
 * @return bool true on success or false on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 198,
        'endLine' => 201,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'currentClassName' => 'DOMNode',
        'aliasName' => NULL,
      ),
      'cloneNode' => 
      array (
        'name' => 'cloneNode',
        'parameters' => 
        array (
          'deep' => 
          array (
            'name' => 'deep',
            'default' => 
            array (
              'code' => '\\false',
              'attributes' => 
              array (
                'startLine' => 214,
                'endLine' => 214,
                'startTokenPos' => 689,
                'startFilePos' => 9652,
                'endTokenPos' => 689,
                'endFilePos' => 9656,
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
                    'code' => '\'7.0\'',
                    'attributes' => 
                    array (
                      'startLine' => 212,
                      'endLine' => 212,
                      'startTokenPos' => 660,
                      'startFilePos' => 9520,
                      'endTokenPos' => 660,
                      'endFilePos' => 9524,
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
                      'startLine' => 213,
                      'endLine' => 213,
                      'startTokenPos' => 667,
                      'startFilePos' => 9594,
                      'endTokenPos' => 673,
                      'endFilePos' => 9610,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 213,
                      'endLine' => 213,
                      'startTokenPos' => 679,
                      'startFilePos' => 9622,
                      'endTokenPos' => 679,
                      'endFilePos' => 9623,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 212,
            'endLine' => 214,
            'startColumn' => 13,
            'endColumn' => 30,
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
 * Clones a node
 * @link https://php.net/manual/en/domnode.clonenode.php
 * @param bool $deep <p>
 * Indicates whether to copy all descendant nodes. This parameter is
 * defaulted to false.
 * </p>
 * @return static|false The cloned node.
 */',
        'startLine' => 211,
        'endLine' => 217,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'currentClassName' => 'DOMNode',
        'aliasName' => NULL,
      ),
      'normalize' => 
      array (
        'name' => 'normalize',
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
 * Normalizes the node
 * @link https://php.net/manual/en/domnode.normalize.php
 * @return void
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 224,
        'endLine' => 227,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'currentClassName' => 'DOMNode',
        'aliasName' => NULL,
      ),
      'isSupported' => 
      array (
        'name' => 'isSupported',
        'parameters' => 
        array (
          'feature' => 
          array (
            'name' => 'feature',
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
                      'startLine' => 244,
                      'endLine' => 244,
                      'startTokenPos' => 734,
                      'startFilePos' => 10706,
                      'endTokenPos' => 740,
                      'endFilePos' => 10724,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 244,
                      'endLine' => 244,
                      'startTokenPos' => 746,
                      'startFilePos' => 10736,
                      'endTokenPos' => 746,
                      'endFilePos' => 10737,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 244,
            'endLine' => 245,
            'startColumn' => 13,
            'endColumn' => 27,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'version' => 
          array (
            'name' => 'version',
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
                      'startLine' => 246,
                      'endLine' => 246,
                      'startTokenPos' => 758,
                      'startFilePos' => 10836,
                      'endTokenPos' => 764,
                      'endFilePos' => 10854,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 246,
                      'endLine' => 246,
                      'startTokenPos' => 770,
                      'startFilePos' => 10866,
                      'endTokenPos' => 770,
                      'endFilePos' => 10867,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 246,
            'endLine' => 247,
            'startColumn' => 13,
            'endColumn' => 27,
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
 * Checks if feature is supported for specified version
 * @link https://php.net/manual/en/domnode.issupported.php
 * @param string $feature <p>
 * The feature to test. See the example of
 * DOMImplementation::hasFeature for a
 * list of features.
 * </p>
 * @param string $version <p>
 * The version number of the feature to test.
 * </p>
 * @return bool true on success or false on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 242,
        'endLine' => 250,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'currentClassName' => 'DOMNode',
        'aliasName' => NULL,
      ),
      'hasAttributes' => 
      array (
        'name' => 'hasAttributes',
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
 * Checks if node has attributes
 * @link https://php.net/manual/en/domnode.hasattributes.php
 * @return bool true on success or false on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 257,
        'endLine' => 260,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'currentClassName' => 'DOMNode',
        'aliasName' => NULL,
      ),
      'compareDocumentPosition' => 
      array (
        'name' => 'compareDocumentPosition',
        'parameters' => 
        array (
          'other' => 
          array (
            'name' => 'other',
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
            'startLine' => 265,
            'endLine' => 265,
            'startColumn' => 49,
            'endColumn' => 63,
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
                'code' => '[\'8.4\' => \'int\']',
                'attributes' => 
                array (
                  'startLine' => 264,
                  'endLine' => 264,
                  'startTokenPos' => 813,
                  'startFilePos' => 11407,
                  'endTokenPos' => 819,
                  'endFilePos' => 11422,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 264,
                  'endLine' => 264,
                  'startTokenPos' => 825,
                  'startFilePos' => 11434,
                  'endTokenPos' => 825,
                  'endFilePos' => 11435,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * @return int
 */',
        'startLine' => 264,
        'endLine' => 267,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'currentClassName' => 'DOMNode',
        'aliasName' => NULL,
      ),
      'isSameNode' => 
      array (
        'name' => 'isSameNode',
        'parameters' => 
        array (
          'otherNode' => 
          array (
            'name' => 'otherNode',
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
            'startLine' => 278,
            'endLine' => 278,
            'startColumn' => 36,
            'endColumn' => 54,
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
 * Indicates if two nodes are the same node
 * @link https://php.net/manual/en/domnode.issamenode.php
 * @param DOMNode $otherNode <p>
 * The compared node.
 * </p>
 * @return bool true on success or false on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 277,
        'endLine' => 280,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'currentClassName' => 'DOMNode',
        'aliasName' => NULL,
      ),
      'lookupPrefix' => 
      array (
        'name' => 'lookupPrefix',
        'parameters' => 
        array (
          'namespace' => 
          array (
            'name' => 'namespace',
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
                      'startLine' => 292,
                      'endLine' => 292,
                      'startTokenPos' => 884,
                      'startFilePos' => 12513,
                      'endTokenPos' => 890,
                      'endFilePos' => 12531,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 292,
                      'endLine' => 292,
                      'startTokenPos' => 896,
                      'startFilePos' => 12543,
                      'endTokenPos' => 896,
                      'endFilePos' => 12544,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 292,
            'endLine' => 293,
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
 * Gets the namespace prefix of the node based on the namespace URI
 * @link https://php.net/manual/en/domnode.lookupprefix.php
 * @param string $namespace <p>
 * The namespace URI.
 * </p>
 * @return string The prefix of the namespace.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 290,
        'endLine' => 296,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'currentClassName' => 'DOMNode',
        'aliasName' => NULL,
      ),
      'isDefaultNamespace' => 
      array (
        'name' => 'isDefaultNamespace',
        'parameters' => 
        array (
          'namespace' => 
          array (
            'name' => 'namespace',
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
                      'startLine' => 309,
                      'endLine' => 309,
                      'startTokenPos' => 930,
                      'startFilePos' => 13211,
                      'endTokenPos' => 936,
                      'endFilePos' => 13229,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 309,
                      'endLine' => 309,
                      'startTokenPos' => 942,
                      'startFilePos' => 13241,
                      'endTokenPos' => 942,
                      'endFilePos' => 13242,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 309,
            'endLine' => 310,
            'startColumn' => 13,
            'endColumn' => 29,
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
 * Checks if the specified namespaceURI is the default namespace or not
 * @link https://php.net/manual/en/domnode.isdefaultnamespace.php
 * @param string $namespace <p>
 * The namespace URI to look for.
 * </p>
 * @return bool Return true if namespaceURI is the default
 * namespace, false otherwise.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 307,
        'endLine' => 313,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'currentClassName' => 'DOMNode',
        'aliasName' => NULL,
      ),
      'lookupNamespaceURI' => 
      array (
        'name' => 'lookupNamespaceURI',
        'parameters' => 
        array (
          'prefix' => 
          array (
            'name' => 'prefix',
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
            ),
            'startLine' => 325,
            'endLine' => 325,
            'startColumn' => 44,
            'endColumn' => 58,
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
                'code' => '\'8.0\'',
                'attributes' => 
                array (
                  'startLine' => 323,
                  'endLine' => 323,
                  'startTokenPos' => 967,
                  'startFilePos' => 13759,
                  'endTokenPos' => 967,
                  'endFilePos' => 13763,
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
 * Gets the namespace URI of the node based on the prefix
 * @link https://php.net/manual/en/domnode.lookupnamespaceuri.php
 * @param string|null $prefix <p>
 * The prefix of the namespace.
 * </p>
 * @return string|null The namespace URI of the node.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 323,
        'endLine' => 327,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'currentClassName' => 'DOMNode',
        'aliasName' => NULL,
      ),
      'isEqualNode' => 
      array (
        'name' => 'isEqualNode',
        'parameters' => 
        array (
          'otherNode' => 
          array (
            'name' => 'otherNode',
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
                    'code' => '[\'8.3\' => \'DOMNode|null\']',
                    'attributes' => 
                    array (
                      'startLine' => 334,
                      'endLine' => 334,
                      'startTokenPos' => 1026,
                      'startFilePos' => 14192,
                      'endTokenPos' => 1032,
                      'endFilePos' => 14216,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'DOMNode\'',
                    'attributes' => 
                    array (
                      'startLine' => 334,
                      'endLine' => 334,
                      'startTokenPos' => 1038,
                      'startFilePos' => 14228,
                      'endTokenPos' => 1038,
                      'endFilePos' => 14236,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 334,
            'endLine' => 335,
            'startColumn' => 13,
            'endColumn' => 35,
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
            'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '[\'8.3\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 332,
                  'endLine' => 332,
                  'startTokenPos' => 1000,
                  'startFilePos' => 14056,
                  'endTokenPos' => 1006,
                  'endFilePos' => 14072,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 332,
                  'endLine' => 332,
                  'startTokenPos' => 1012,
                  'startFilePos' => 14084,
                  'endTokenPos' => 1012,
                  'endFilePos' => 14085,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * @param DOMNode|null $arg
 * @return bool
 */',
        'startLine' => 332,
        'endLine' => 338,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'currentClassName' => 'DOMNode',
        'aliasName' => NULL,
      ),
      'getNodePath' => 
      array (
        'name' => 'getNodePath',
        'parameters' => 
        array (
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
 * Gets an XPath location path for the node
 * @return string|null the XPath, or NULL in case of an error.
 * @link https://secure.php.net/manual/en/domnode.getnodepath.php
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 345,
        'endLine' => 348,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'currentClassName' => 'DOMNode',
        'aliasName' => NULL,
      ),
      'getLineNo' => 
      array (
        'name' => 'getLineNo',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
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
 * Get line number for a node
 * @link https://php.net/manual/en/domnode.getlineno.php
 * @return int Always returns the line number where the node was defined in.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 355,
        'endLine' => 358,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'currentClassName' => 'DOMNode',
        'aliasName' => NULL,
      ),
      'C14N' => 
      array (
        'name' => 'C14N',
        'parameters' => 
        array (
          'exclusive' => 
          array (
            'name' => 'exclusive',
            'default' => 
            array (
              'code' => '\\false',
              'attributes' => 
              array (
                'startLine' => 371,
                'endLine' => 371,
                'startTokenPos' => 1138,
                'startFilePos' => 15901,
                'endTokenPos' => 1138,
                'endFilePos' => 15905,
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
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'bool\']',
                    'attributes' => 
                    array (
                      'startLine' => 370,
                      'endLine' => 370,
                      'startTokenPos' => 1116,
                      'startFilePos' => 15838,
                      'endTokenPos' => 1122,
                      'endFilePos' => 15854,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 370,
                      'endLine' => 370,
                      'startTokenPos' => 1128,
                      'startFilePos' => 15866,
                      'endTokenPos' => 1128,
                      'endFilePos' => 15867,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 370,
            'endLine' => 371,
            'startColumn' => 13,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'withComments' => 
          array (
            'name' => 'withComments',
            'default' => 
            array (
              'code' => '\\false',
              'attributes' => 
              array (
                'startLine' => 373,
                'endLine' => 373,
                'startTokenPos' => 1166,
                'startFilePos' => 16040,
                'endTokenPos' => 1166,
                'endFilePos' => 16044,
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
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'bool\']',
                    'attributes' => 
                    array (
                      'startLine' => 372,
                      'endLine' => 372,
                      'startTokenPos' => 1144,
                      'startFilePos' => 15974,
                      'endTokenPos' => 1150,
                      'endFilePos' => 15990,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 372,
                      'endLine' => 372,
                      'startTokenPos' => 1156,
                      'startFilePos' => 16002,
                      'endTokenPos' => 1156,
                      'endFilePos' => 16003,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 372,
            'endLine' => 373,
            'startColumn' => 13,
            'endColumn' => 38,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'xpath' => 
          array (
            'name' => 'xpath',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 375,
                'endLine' => 375,
                'startTokenPos' => 1196,
                'startFilePos' => 16184,
                'endTokenPos' => 1196,
                'endFilePos' => 16187,
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
                      'name' => 'array',
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
                    'code' => '[\'7.1\' => \'array|null\']',
                    'attributes' => 
                    array (
                      'startLine' => 374,
                      'endLine' => 374,
                      'startTokenPos' => 1172,
                      'startFilePos' => 16113,
                      'endTokenPos' => 1178,
                      'endFilePos' => 16135,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 374,
                      'endLine' => 374,
                      'startTokenPos' => 1184,
                      'startFilePos' => 16147,
                      'endTokenPos' => 1184,
                      'endFilePos' => 16148,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 374,
            'endLine' => 375,
            'startColumn' => 13,
            'endColumn' => 36,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'nsPrefixes' => 
          array (
            'name' => 'nsPrefixes',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 377,
                'endLine' => 377,
                'startTokenPos' => 1226,
                'startFilePos' => 16332,
                'endTokenPos' => 1226,
                'endFilePos' => 16335,
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
                      'name' => 'array',
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
                    'code' => '[\'7.1\' => \'array|null\']',
                    'attributes' => 
                    array (
                      'startLine' => 376,
                      'endLine' => 376,
                      'startTokenPos' => 1202,
                      'startFilePos' => 16256,
                      'endTokenPos' => 1208,
                      'endFilePos' => 16278,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 376,
                      'endLine' => 376,
                      'startTokenPos' => 1214,
                      'startFilePos' => 16290,
                      'endTokenPos' => 1214,
                      'endFilePos' => 16291,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 376,
            'endLine' => 377,
            'startColumn' => 13,
            'endColumn' => 41,
            'parameterIndex' => 3,
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
 * Canonicalize nodes to a string
 * @param bool $exclusive [optional] Enable exclusive parsing of only the nodes matched by the provided xpath or namespace prefixes.
 * @param bool $withComments [optional] Retain comments in output.
 * @param null|array $xpath [optional] An array of xpaths to filter the nodes by.
 * @param null|array $nsPrefixes [optional] An array of namespace prefixes to filter the nodes by.
 * @return string|false Canonicalized nodes as a string or FALSE on failure
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 368,
        'endLine' => 380,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'currentClassName' => 'DOMNode',
        'aliasName' => NULL,
      ),
      'C14NFile' => 
      array (
        'name' => 'C14NFile',
        'parameters' => 
        array (
          'uri' => 
          array (
            'name' => 'uri',
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
                      'startLine' => 394,
                      'endLine' => 394,
                      'startTokenPos' => 1255,
                      'startFilePos' => 17273,
                      'endTokenPos' => 1261,
                      'endFilePos' => 17291,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 394,
                      'endLine' => 394,
                      'startTokenPos' => 1267,
                      'startFilePos' => 17303,
                      'endTokenPos' => 1267,
                      'endFilePos' => 17304,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 394,
            'endLine' => 395,
            'startColumn' => 13,
            'endColumn' => 23,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'exclusive' => 
          array (
            'name' => 'exclusive',
            'default' => 
            array (
              'code' => '\\false',
              'attributes' => 
              array (
                'startLine' => 397,
                'endLine' => 397,
                'startTokenPos' => 1301,
                'startFilePos' => 17462,
                'endTokenPos' => 1301,
                'endFilePos' => 17466,
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
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'bool\']',
                    'attributes' => 
                    array (
                      'startLine' => 396,
                      'endLine' => 396,
                      'startTokenPos' => 1279,
                      'startFilePos' => 17399,
                      'endTokenPos' => 1285,
                      'endFilePos' => 17415,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 396,
                      'endLine' => 396,
                      'startTokenPos' => 1291,
                      'startFilePos' => 17427,
                      'endTokenPos' => 1291,
                      'endFilePos' => 17428,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 396,
            'endLine' => 397,
            'startColumn' => 13,
            'endColumn' => 35,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'withComments' => 
          array (
            'name' => 'withComments',
            'default' => 
            array (
              'code' => '\\false',
              'attributes' => 
              array (
                'startLine' => 399,
                'endLine' => 399,
                'startTokenPos' => 1329,
                'startFilePos' => 17601,
                'endTokenPos' => 1329,
                'endFilePos' => 17605,
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
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'bool\']',
                    'attributes' => 
                    array (
                      'startLine' => 398,
                      'endLine' => 398,
                      'startTokenPos' => 1307,
                      'startFilePos' => 17535,
                      'endTokenPos' => 1313,
                      'endFilePos' => 17551,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 398,
                      'endLine' => 398,
                      'startTokenPos' => 1319,
                      'startFilePos' => 17563,
                      'endTokenPos' => 1319,
                      'endFilePos' => 17564,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 398,
            'endLine' => 399,
            'startColumn' => 13,
            'endColumn' => 38,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'xpath' => 
          array (
            'name' => 'xpath',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 401,
                'endLine' => 401,
                'startTokenPos' => 1359,
                'startFilePos' => 17745,
                'endTokenPos' => 1359,
                'endFilePos' => 17748,
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
                      'name' => 'array',
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
                    'code' => '[\'7.1\' => \'array|null\']',
                    'attributes' => 
                    array (
                      'startLine' => 400,
                      'endLine' => 400,
                      'startTokenPos' => 1335,
                      'startFilePos' => 17674,
                      'endTokenPos' => 1341,
                      'endFilePos' => 17696,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 400,
                      'endLine' => 400,
                      'startTokenPos' => 1347,
                      'startFilePos' => 17708,
                      'endTokenPos' => 1347,
                      'endFilePos' => 17709,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 400,
            'endLine' => 401,
            'startColumn' => 13,
            'endColumn' => 36,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'nsPrefixes' => 
          array (
            'name' => 'nsPrefixes',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 403,
                'endLine' => 403,
                'startTokenPos' => 1389,
                'startFilePos' => 17893,
                'endTokenPos' => 1389,
                'endFilePos' => 17896,
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
                      'name' => 'array',
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
                    'code' => '[\'7.1\' => \'array|null\']',
                    'attributes' => 
                    array (
                      'startLine' => 402,
                      'endLine' => 402,
                      'startTokenPos' => 1365,
                      'startFilePos' => 17817,
                      'endTokenPos' => 1371,
                      'endFilePos' => 17839,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 402,
                      'endLine' => 402,
                      'startTokenPos' => 1377,
                      'startFilePos' => 17851,
                      'endTokenPos' => 1377,
                      'endFilePos' => 17852,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 402,
            'endLine' => 403,
            'startColumn' => 13,
            'endColumn' => 41,
            'parameterIndex' => 4,
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
 * Canonicalize nodes to a file.
 * @link https://www.php.net/manual/en/domnode.c14nfile
 * @param string $uri Number of bytes written or FALSE on failure
 * @param bool $exclusive [optional] Enable exclusive parsing of only the nodes matched by the provided xpath or namespace prefixes.
 * @param bool $withComments [optional]  Retain comments in output.
 * @param null|array $xpath [optional] An array of xpaths to filter the nodes by.
 * @param null|array $nsPrefixes [optional] An array of namespace prefixes to filter the nodes by.
 * @return int|false Number of bytes written or FALSE on failure
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 392,
        'endLine' => 406,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'currentClassName' => 'DOMNode',
        'aliasName' => NULL,
      ),
      'contains' => 
      array (
        'name' => 'contains',
        'parameters' => 
        array (
          'other' => 
          array (
            'name' => 'other',
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
                      'name' => 'DOMNode',
                      'isIdentifier' => false,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'DOMNameSpaceNode',
                      'isIdentifier' => false,
                    ),
                  ),
                  2 => 
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
            'startLine' => 410,
            'endLine' => 410,
            'startColumn' => 34,
            'endColumn' => 71,
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
        ),
        'docComment' => '/**
 * @since 8.3
 */',
        'startLine' => 410,
        'endLine' => 412,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'currentClassName' => 'DOMNode',
        'aliasName' => NULL,
      ),
      'getRootNode' => 
      array (
        'name' => 'getRootNode',
        'parameters' => 
        array (
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 416,
                'endLine' => 416,
                'startTokenPos' => 1441,
                'startFilePos' => 18184,
                'endTokenPos' => 1441,
                'endFilePos' => 18187,
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
                      'name' => 'array',
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
            'startLine' => 416,
            'endLine' => 416,
            'startColumn' => 37,
            'endColumn' => 58,
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
            'name' => 'DOMNode',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @since 8.3
 */',
        'startLine' => 416,
        'endLine' => 418,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'currentClassName' => 'DOMNode',
        'aliasName' => NULL,
      ),
      '__sleep' => 
      array (
        'name' => '__sleep',
        'parameters' => 
        array (
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
        ),
        'docComment' => '/**
 * @since 8.1
 */',
        'startLine' => 422,
        'endLine' => 424,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'currentClassName' => 'DOMNode',
        'aliasName' => NULL,
      ),
      '__wakeup' => 
      array (
        'name' => '__wakeup',
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
 * @since 8.1
 */',
        'startLine' => 428,
        'endLine' => 430,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMNode',
        'implementingClassName' => 'DOMNode',
        'currentClassName' => 'DOMNode',
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