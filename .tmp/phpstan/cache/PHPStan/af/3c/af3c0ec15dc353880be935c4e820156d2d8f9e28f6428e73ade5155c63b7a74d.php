<?php declare(strict_types = 1);

// osfsl-/home/rubenlinde/memcap-work/lq-lanes/fq/vendor/composer/../phpoffice/phpword/src/PhpWord/Reader/ODText.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PhpOffice\PhpWord\Reader\ODText
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-374e50d6835c27c6ae47ce2ed70a24efe25111c246d30a9fa75b34499cccc62c-8.3-6.70.0.6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PhpOffice\\PhpWord\\Reader\\ODText',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/vendor/composer/../phpoffice/phpword/src/PhpWord/Reader/ODText.php',
      ),
    ),
    'namespace' => 'PhpOffice\\PhpWord\\Reader',
    'name' => 'PhpOffice\\PhpWord\\Reader\\ODText',
    'shortName' => 'ODText',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Reader for ODText.
 *
 * @since 0.10.0
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 29,
    'endLine' => 87,
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
            'startLine' => 38,
            'endLine' => 38,
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
        'startLine' => 38,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Reader',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Reader\\ODText',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Reader\\ODText',
        'currentClassName' => 'PhpOffice\\PhpWord\\Reader\\ODText',
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
            'startLine' => 58,
            'endLine' => 58,
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
            'startLine' => 58,
            'endLine' => 58,
            'startColumn' => 49,
            'endColumn' => 68,
            'parameterIndex' => 1,
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
            'startLine' => 58,
            'endLine' => 58,
            'startColumn' => 71,
            'endColumn' => 86,
            'parameterIndex' => 2,
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
            'startLine' => 58,
            'endLine' => 58,
            'startColumn' => 89,
            'endColumn' => 103,
            'parameterIndex' => 3,
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
            'startLine' => 58,
            'endLine' => 58,
            'startColumn' => 106,
            'endColumn' => 120,
            'parameterIndex' => 4,
            'isOptional' => false,
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
 * Read document part.
 */',
        'startLine' => 58,
        'endLine' => 67,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PhpOffice\\PhpWord\\Reader',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Reader\\ODText',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Reader\\ODText',
        'currentClassName' => 'PhpOffice\\PhpWord\\Reader\\ODText',
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
            'startLine' => 72,
            'endLine' => 72,
            'startColumn' => 40,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Read all relationship files.
 */',
        'startLine' => 72,
        'endLine' => 86,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PhpOffice\\PhpWord\\Reader',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Reader\\ODText',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Reader\\ODText',
        'currentClassName' => 'PhpOffice\\PhpWord\\Reader\\ODText',
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