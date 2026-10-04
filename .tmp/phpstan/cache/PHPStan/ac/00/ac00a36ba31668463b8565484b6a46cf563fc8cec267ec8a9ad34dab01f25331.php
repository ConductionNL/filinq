<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/OoxmlBlockStyleCodec.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Editing\OoxmlBlockStyleCodec
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-e2ac373696638111ed48a8052fa9bc8a58b93a825dce13502206a6f740861a52',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/OoxmlBlockStyleCodec.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Editing',
    'name' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
    'shortName' => 'OoxmlBlockStyleCodec',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Applies block style to OOXML packages.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 35,
    'endLine' => 309,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'OCA\\Filinq\\Service\\Editing\\BlockStyleFamilyCodec',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'ALIGNMENTS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'name' => 'ALIGNMENTS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'left\' => \'left\', \'center\' => \'center\', \'right\' => \'right\', \'justify\' => \'both\']',
          'attributes' => 
          array (
            'startLine' => 42,
            'endLine' => 47,
            'startTokenPos' => 39,
            'startFilePos' => 1183,
            'endTokenPos' => 69,
            'endFilePos' => 1275,
          ),
        ),
        'docComment' => '/**
 * Paragraph alignment values, mapped to their OOXML `w:jc` value.
 *
 * @var array<string, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 42,
        'endLine' => 47,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
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
          'format' => 
          array (
            'name' => 'format',
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
            'startColumn' => 27,
            'endColumn' => 40,
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
 * Whether this codec handles the given package family.
 *
 * @param string $format The package family constant.
 *
 * @return bool True for OOXML.
 *
 * @spec openspec/specs/document-rich-editing/spec.md
 */',
        'startLine' => 58,
        'endLine' => 60,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'aliasName' => NULL,
      ),
      'applyStyle' => 
      array (
        'name' => 'applyStyle',
        'parameters' => 
        array (
          'markup' => 
          array (
            'name' => 'markup',
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
            'startLine' => 73,
            'endLine' => 73,
            'startColumn' => 29,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'style' => 
          array (
            'name' => 'style',
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
            'startLine' => 73,
            'endLine' => 73,
            'startColumn' => 45,
            'endColumn' => 56,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'styleName' => 
          array (
            'name' => 'styleName',
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
            'startLine' => 73,
            'endLine' => 73,
            'startColumn' => 59,
            'endColumn' => 75,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Apply style properties to one paragraph\'s markup.
 *
 * @param string $markup The paragraph markup.
 * @param array $style The style properties.
 * @param string $styleName Unused: OOXML style is inline, so nothing is minted.
 *
 * @return array{markup: string, automaticStyle: string|null} The rewritten block; never a definition.
 *
 * @spec openspec/specs/document-rich-editing/spec.md
 */',
        'startLine' => 73,
        'endLine' => 77,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'aliasName' => NULL,
      ),
      'applyParagraphProperties' => 
      array (
        'name' => 'applyParagraphProperties',
        'parameters' => 
        array (
          'markup' => 
          array (
            'name' => 'markup',
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
            'startLine' => 87,
            'endLine' => 87,
            'startColumn' => 44,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'style' => 
          array (
            'name' => 'style',
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
            'startLine' => 87,
            'endLine' => 87,
            'startColumn' => 60,
            'endColumn' => 71,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Apply the paragraph-level properties.
 *
 * @param string $markup The paragraph markup.
 * @param array $style The style properties.
 *
 * @return string The rewritten markup.
 */',
        'startLine' => 87,
        'endLine' => 100,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'aliasName' => NULL,
      ),
      'paragraphAdditions' => 
      array (
        'name' => 'paragraphAdditions',
        'parameters' => 
        array (
          'style' => 
          array (
            'name' => 'style',
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
            'startLine' => 109,
            'endLine' => 109,
            'startColumn' => 38,
            'endColumn' => 49,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The paragraph properties this style adds.
 *
 * @param array $style The style properties.
 *
 * @return string The property markup.
 */',
        'startLine' => 109,
        'endLine' => 130,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'aliasName' => NULL,
      ),
      'paragraphRemovals' => 
      array (
        'name' => 'paragraphRemovals',
        'parameters' => 
        array (
          'style' => 
          array (
            'name' => 'style',
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
            'startLine' => 144,
            'endLine' => 144,
            'startColumn' => 37,
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
 * The paragraph properties this style REMOVES.
 *
 * Switching a property off is a removal, not an absence. Treating it as
 * "nothing to add" leaves the existing element in place and reports the
 * restyle as applied while changing nothing — which is exactly what the first
 * version of this codec did with `heading: 0`.
 *
 * @param array $style The style properties.
 *
 * @return array<int, string> The element names to remove.
 */',
        'startLine' => 144,
        'endLine' => 160,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'aliasName' => NULL,
      ),
      'mergeParagraphProperties' => 
      array (
        'name' => 'mergeParagraphProperties',
        'parameters' => 
        array (
          'markup' => 
          array (
            'name' => 'markup',
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
            'startLine' => 176,
            'endLine' => 176,
            'startColumn' => 44,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'properties' => 
          array (
            'name' => 'properties',
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
            'startLine' => 176,
            'endLine' => 176,
            'startColumn' => 60,
            'endColumn' => 77,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'removals' => 
          array (
            'name' => 'removals',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 176,
                'endLine' => 176,
                'startTokenPos' => 661,
                'startFilePos' => 5374,
                'endTokenPos' => 662,
                'endFilePos' => 5375,
              ),
            ),
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
            'startLine' => 176,
            'endLine' => 176,
            'startColumn' => 80,
            'endColumn' => 99,
            'parameterIndex' => 2,
            'isOptional' => true,
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
 * Merge properties into the paragraph\'s `<w:pPr>`, creating it when absent.
 *
 * Existing properties of the SAME name are replaced; unrelated ones survive.
 * Wholesale replacement of `<w:pPr>` would silently drop spacing, indentation
 * and numbering the user set by hand — the class of loss ADR-087 §2 warns
 * about, and one no test would notice.
 *
 * @param string $markup The paragraph markup.
 * @param string $properties The properties to merge.
 * @param array<string> $removals Element names to remove outright.
 *
 * @return string The rewritten markup.
 */',
        'startLine' => 176,
        'endLine' => 202,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'aliasName' => NULL,
      ),
      'dropSameNamed' => 
      array (
        'name' => 'dropSameNamed',
        'parameters' => 
        array (
          'existing' => 
          array (
            'name' => 'existing',
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
            'startLine' => 212,
            'endLine' => 212,
            'startColumn' => 33,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'incoming' => 
          array (
            'name' => 'incoming',
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
            'startLine' => 212,
            'endLine' => 212,
            'startColumn' => 51,
            'endColumn' => 66,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Remove existing properties whose element name is being set again.
 *
 * @param string $existing The existing property markup.
 * @param string $incoming The incoming property markup.
 *
 * @return string The retained existing markup.
 */',
        'startLine' => 212,
        'endLine' => 216,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'aliasName' => NULL,
      ),
      'dropNamed' => 
      array (
        'name' => 'dropNamed',
        'parameters' => 
        array (
          'existing' => 
          array (
            'name' => 'existing',
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
            'startLine' => 226,
            'endLine' => 226,
            'startColumn' => 29,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'names' => 
          array (
            'name' => 'names',
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
            'startLine' => 226,
            'endLine' => 226,
            'startColumn' => 47,
            'endColumn' => 58,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Remove named elements from a property block.
 *
 * @param string $existing The existing property markup.
 * @param array<string> $names The element names to remove.
 *
 * @return string The retained markup.
 */',
        'startLine' => 226,
        'endLine' => 234,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'aliasName' => NULL,
      ),
      'applyRunProperties' => 
      array (
        'name' => 'applyRunProperties',
        'parameters' => 
        array (
          'markup' => 
          array (
            'name' => 'markup',
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
            'startLine' => 244,
            'endLine' => 244,
            'startColumn' => 38,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'style' => 
          array (
            'name' => 'style',
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
            'startLine' => 244,
            'endLine' => 244,
            'startColumn' => 54,
            'endColumn' => 65,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Apply the run-level properties to every run in the paragraph.
 *
 * @param string $markup The paragraph markup.
 * @param array $style The style properties.
 *
 * @return string The rewritten markup.
 */',
        'startLine' => 244,
        'endLine' => 280,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'aliasName' => NULL,
      ),
      'mergeRunProperties' => 
      array (
        'name' => 'mergeRunProperties',
        'parameters' => 
        array (
          'run' => 
          array (
            'name' => 'run',
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
            'startLine' => 290,
            'endLine' => 290,
            'startColumn' => 38,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'properties' => 
          array (
            'name' => 'properties',
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
            'startLine' => 290,
            'endLine' => 290,
            'startColumn' => 51,
            'endColumn' => 68,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Merge run properties into one `<w:r>`, creating `<w:rPr>` when absent.
 *
 * @param string $run The run markup.
 * @param string $properties The properties to merge.
 *
 * @return string The rewritten run.
 */',
        'startLine' => 290,
        'endLine' => 308,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\OoxmlBlockStyleCodec',
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