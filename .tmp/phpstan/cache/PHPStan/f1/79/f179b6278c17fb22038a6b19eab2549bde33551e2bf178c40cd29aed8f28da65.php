<?php declare(strict_types = 1);

// phpinternal-PHPStan\BetterReflection\Reflection\ReflectionClass-domchildnode
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-dev-master@709e512-8.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\InternalLocatedSource',
      'data' => 
      array (
        'name' => 'DOMChildNode',
        'filename' => 'phpstorm-stubs:dom/dom_c.stub',
        'extensionName' => 'dom',
        'aliasName' => NULL,
      ),
    ),
    'namespace' => NULL,
    'name' => 'DOMChildNode',
    'shortName' => 'DOMChildNode',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @property-read DOMElement|null $previousElementSibling
 * @property-read DOMElement|null $nextElementSibling
 *
 * @since 8.0
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 10,
    'endLine' => 44,
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
 * Acts as a simpler version of {@see DOMNode::removeChild()}.
 *
 * @return void
 * @since 8.0
 */',
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 9,
        'endColumn' => 39,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMChildNode',
        'implementingClassName' => 'DOMChildNode',
        'currentClassName' => 'DOMChildNode',
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
            'startLine' => 26,
            'endLine' => 26,
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
 * Add passed node(s) before the current node
 *
 * @param DOMNode|string|null ...$nodes
 * @return void
 * @since 8.0
 */',
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 9,
        'endColumn' => 48,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMChildNode',
        'implementingClassName' => 'DOMChildNode',
        'currentClassName' => 'DOMChildNode',
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
            'startLine' => 34,
            'endLine' => 34,
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
 * Add passed node(s) after  the current node
 *
 * @param DOMNode|string|null ...$nodes
 * @return void
 * @since 8.0
 */',
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 9,
        'endColumn' => 47,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMChildNode',
        'implementingClassName' => 'DOMChildNode',
        'currentClassName' => 'DOMChildNode',
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
            'startLine' => 43,
            'endLine' => 43,
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
 * Replace current node with new node(s), a combination
 * of {@see DOMChildNode::remove()} + {@see DOMChildNode::append()}.
 *
 * @param DOMNode|string|null ...$nodes
 * @return void
 * @since 8.0
 */',
        'startLine' => 43,
        'endLine' => 43,
        'startColumn' => 9,
        'endColumn' => 53,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'DOMChildNode',
        'implementingClassName' => 'DOMChildNode',
        'currentClassName' => 'DOMChildNode',
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