<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/PresentationFamilyCodec.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Editing\PresentationFamilyCodec
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-ad1d5c877ff6ddf9c521355bba1e024fa3634fdb03685ad19d8090cc9517b281',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Editing\\PresentationFamilyCodec',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/PresentationFamilyCodec.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Editing',
    'name' => 'OCA\\Filinq\\Service\\Editing\\PresentationFamilyCodec',
    'shortName' => 'PresentationFamilyCodec',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Reads and writes presentation shapes for one family.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 30,
    'endLine' => 68,
    'startColumn' => 1,
    'endColumn' => 1,
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
      'supports' => 
      array (
        'name' => 'supports',
        'parameters' => 
        array (
          'extension' => 
          array (
            'name' => 'extension',
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
            'startLine' => 41,
            'endLine' => 41,
            'startColumn' => 27,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Whether this codec handles an extension.
 *
 * @param string $extension The lower-case file extension.
 *
 * @return bool True when handled.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
 */',
        'startLine' => 41,
        'endLine' => 41,
        'startColumn' => 2,
        'endColumn' => 51,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PresentationFamilyCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PresentationFamilyCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PresentationFamilyCodec',
        'aliasName' => NULL,
      ),
      'readShapes' => 
      array (
        'name' => 'readShapes',
        'parameters' => 
        array (
          'packageBytes' => 
          array (
            'name' => 'packageBytes',
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
            'startLine' => 52,
            'endLine' => 52,
            'startColumn' => 29,
            'endColumn' => 48,
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
 * Read every text-bearing shape.
 *
 * @param string $packageBytes The package.
 *
 * @return array<int, array{slide: string, shape: string, region: string, text: string}> The shapes.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
 */',
        'startLine' => 52,
        'endLine' => 52,
        'startColumn' => 2,
        'endColumn' => 57,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PresentationFamilyCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PresentationFamilyCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PresentationFamilyCodec',
        'aliasName' => NULL,
      ),
      'writeShape' => 
      array (
        'name' => 'writeShape',
        'parameters' => 
        array (
          'packageBytes' => 
          array (
            'name' => 'packageBytes',
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
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 29,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'slide' => 
          array (
            'name' => 'slide',
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
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 51,
            'endColumn' => 63,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'shape' => 
          array (
            'name' => 'shape',
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
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 66,
            'endColumn' => 78,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'region' => 
          array (
            'name' => 'region',
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
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 81,
            'endColumn' => 94,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'text' => 
          array (
            'name' => 'text',
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
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 97,
            'endColumn' => 108,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Replace one shape\'s text.
 *
 * @param string $packageBytes The package.
 * @param string $slide The slide id.
 * @param string $shape The shape id.
 * @param string $region Either `slide` or `notes`.
 * @param string $text The replacement text.
 *
 * @return string The rewritten package.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
 */',
        'startLine' => 67,
        'endLine' => 67,
        'startColumn' => 2,
        'endColumn' => 118,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PresentationFamilyCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PresentationFamilyCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PresentationFamilyCodec',
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