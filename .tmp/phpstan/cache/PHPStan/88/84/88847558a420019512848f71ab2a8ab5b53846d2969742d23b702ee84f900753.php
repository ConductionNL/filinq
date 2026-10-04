<?php declare(strict_types = 1);

// osfsl-/home/rubenlinde/memcap-work/lq-lanes/fq/vendor/composer/../phpoffice/phpword/src/PhpWord/Reader/Word2007.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PhpOffice\PhpWord\Reader\Word2007
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-dfa716818010931ca363d428ede85bae286b58c8e09061c20ac9e32737614221-8.3-6.70.0.6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PhpOffice\\PhpWord\\Reader\\Word2007',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/vendor/composer/../phpoffice/phpword/src/PhpWord/Reader/Word2007.php',
      ),
    ),
    'namespace' => 'PhpOffice\\PhpWord\\Reader',
    'name' => 'PhpOffice\\PhpWord\\Reader\\Word2007',
    'shortName' => 'Word2007',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Reader for Word2007.
 *
 * @since 0.8.0
 *
 * @todo watermark, checkbox, toc
 * @todo Partly done: image, object
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 36,
    'endLine' => 194,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'PhpOffice\\PhpWord\\Reader\\AbstractReader',
    'implementsClassNames' => 
    array (
      0 => 'PhpOffice\\PhpWord\\Reader\\ReaderInterface',
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
      'load' => 
      array (
        'name' => 'load',
        'parameters' => 
        array (
          'docFile' => 
          array (
            'name' => 'docFile',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 45,
            'endLine' => 45,
            'startColumn' => 26,
            'endColumn' => 33,
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
 * Loads PhpWord from file.
 *
 * @param string $docFile
 *
 * @return PhpWord
 */',
        'startLine' => 45,
        'endLine' => 97,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Reader',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Reader\\Word2007',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Reader\\Word2007',
        'currentClassName' => 'PhpOffice\\PhpWord\\Reader\\Word2007',
        'aliasName' => NULL,
      ),
      'readPart' => 
      array (
        'name' => 'readPart',
        'parameters' => 
        array (
          'phpWord' => 
          array (
            'name' => 'phpWord',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpOffice\\PhpWord\\PhpWord',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 31,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'relationships' => 
          array (
            'name' => 'relationships',
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
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 49,
            'endColumn' => 68,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'commentRefs' => 
          array (
            'name' => 'commentRefs',
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
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 71,
            'endColumn' => 88,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'partName' => 
          array (
            'name' => 'partName',
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
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 91,
            'endColumn' => 106,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'docFile' => 
          array (
            'name' => 'docFile',
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
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 109,
            'endColumn' => 123,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'xmlFile' => 
          array (
            'name' => 'xmlFile',
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
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 126,
            'endColumn' => 140,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PhpOffice\\PhpWord\\Reader\\Word2007\\AbstractPart',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Read document part.
 *
 * @param array<string, array<string, null|AbstractElement>> $commentRefs
 */',
        'startLine' => 104,
        'endLine' => 119,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PhpOffice\\PhpWord\\Reader',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Reader\\Word2007',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Reader\\Word2007',
        'currentClassName' => 'PhpOffice\\PhpWord\\Reader\\Word2007',
        'aliasName' => NULL,
      ),
      'readRelationships' => 
      array (
        'name' => 'readRelationships',
        'parameters' => 
        array (
          'docFile' => 
          array (
            'name' => 'docFile',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 128,
            'endLine' => 128,
            'startColumn' => 40,
            'endColumn' => 47,
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
 * Read all relationship files.
 *
 * @param string $docFile
 *
 * @return array
 */',
        'startLine' => 128,
        'endLine' => 150,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PhpOffice\\PhpWord\\Reader',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Reader\\Word2007',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Reader\\Word2007',
        'currentClassName' => 'PhpOffice\\PhpWord\\Reader\\Word2007',
        'aliasName' => NULL,
      ),
      'getRels' => 
      array (
        'name' => 'getRels',
        'parameters' => 
        array (
          'docFile' => 
          array (
            'name' => 'docFile',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 161,
            'endLine' => 161,
            'startColumn' => 30,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'xmlFile' => 
          array (
            'name' => 'xmlFile',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 161,
            'endLine' => 161,
            'startColumn' => 40,
            'endColumn' => 47,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'targetPrefix' => 
          array (
            'name' => 'targetPrefix',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 161,
                'endLine' => 161,
                'startTokenPos' => 790,
                'startFilePos' => 5038,
                'endTokenPos' => 790,
                'endFilePos' => 5039,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 161,
            'endLine' => 161,
            'startColumn' => 50,
            'endColumn' => 67,
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
 * Get relationship array.
 *
 * @param string $docFile
 * @param string $xmlFile
 * @param string $targetPrefix
 *
 * @return array
 */',
        'startLine' => 161,
        'endLine' => 193,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PhpOffice\\PhpWord\\Reader',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Reader\\Word2007',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Reader\\Word2007',
        'currentClassName' => 'PhpOffice\\PhpWord\\Reader\\Word2007',
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