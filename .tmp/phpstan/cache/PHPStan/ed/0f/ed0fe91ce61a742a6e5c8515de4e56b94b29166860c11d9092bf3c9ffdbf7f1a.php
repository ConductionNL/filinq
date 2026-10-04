<?php declare(strict_types = 1);

// osfsl-/home/rubenlinde/memcap-work/lq-lanes/fq/vendor/composer/../phpoffice/phpword/src/PhpWord/Style/Numbering.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PhpOffice\PhpWord\Style\Numbering
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-220410f03bcda9e80a00c22e05ecd761556af407519aaba02b3dbf08b818974c-8.3-6.70.0.6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PhpOffice\\PhpWord\\Style\\Numbering',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/vendor/composer/../phpoffice/phpword/src/PhpWord/Style/Numbering.php',
      ),
    ),
    'namespace' => 'PhpOffice\\PhpWord\\Style',
    'name' => 'PhpOffice\\PhpWord\\Style\\Numbering',
    'shortName' => 'Numbering',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Numbering style.
 *
 * @see  http://www.schemacentral.com/sc/ooxml/e-w_numbering.html
 * @see  http://www.schemacentral.com/sc/ooxml/e-w_abstractNum-1.html
 * @see  http://www.schemacentral.com/sc/ooxml/e-w_num-1.html
 * @since 0.10.0
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 29,
    'endLine' => 121,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'PhpOffice\\PhpWord\\Style\\AbstractStyle',
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
      'numId' => 
      array (
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Numbering',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Numbering',
        'name' => 'numId',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * Numbering definition instance ID.
 *
 * @var int
 *
 * @see  http://www.schemacentral.com/sc/ooxml/e-w_num-1.html
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 38,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 19,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'type' => 
      array (
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Numbering',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Numbering',
        'name' => 'type',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * Multilevel type singleLevel|multilevel|hybridMultilevel.
 *
 * @var string
 *
 * @see  http://www.schemacentral.com/sc/ooxml/a-w_val-67.html
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 47,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 18,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'levels' => 
      array (
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Numbering',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Numbering',
        'name' => 'levels',
        'modifiers' => 4,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 54,
            'endLine' => 54,
            'startTokenPos' => 43,
            'startFilePos' => 1429,
            'endTokenPos' => 44,
            'endFilePos' => 1430,
          ),
        ),
        'docComment' => '/**
 * Numbering levels.
 *
 * @var NumberingLevel[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 54,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 25,
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
      'getNumId' => 
      array (
        'name' => 'getNumId',
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get Id.
 */',
        'startLine' => 59,
        'endLine' => 62,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Numbering',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Numbering',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Numbering',
        'aliasName' => NULL,
      ),
      'setNumId' => 
      array (
        'name' => 'setNumId',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
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
            ),
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 30,
            'endColumn' => 39,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set Id.
 */',
        'startLine' => 67,
        'endLine' => 72,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Numbering',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Numbering',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Numbering',
        'aliasName' => NULL,
      ),
      'getType' => 
      array (
        'name' => 'getType',
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
        ),
        'docComment' => '/**
 * Get multilevel type.
 */',
        'startLine' => 77,
        'endLine' => 80,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Numbering',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Numbering',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Numbering',
        'aliasName' => NULL,
      ),
      'setType' => 
      array (
        'name' => 'setType',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
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
            'startLine' => 85,
            'endLine' => 85,
            'startColumn' => 29,
            'endColumn' => 41,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set multilevel type.
 */',
        'startLine' => 85,
        'endLine' => 91,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Numbering',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Numbering',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Numbering',
        'aliasName' => NULL,
      ),
      'getLevels' => 
      array (
        'name' => 'getLevels',
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
 * Get levels.
 *
 * @return NumberingLevel[]
 */',
        'startLine' => 98,
        'endLine' => 101,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Numbering',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Numbering',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Numbering',
        'aliasName' => NULL,
      ),
      'setLevels' => 
      array (
        'name' => 'setLevels',
        'parameters' => 
        array (
          'values' => 
          array (
            'name' => 'values',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 106,
            'endLine' => 106,
            'startColumn' => 31,
            'endColumn' => 43,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set multilevel type.
 */',
        'startLine' => 106,
        'endLine' => 120,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Numbering',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Numbering',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Numbering',
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