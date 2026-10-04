<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/OdfBlockStyleCodec.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Editing\OdfBlockStyleCodec
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-2728f8e02d04f794d331db745c5f186ace16f985e2f3ecc6b6d606b464efa0f3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Editing\\OdfBlockStyleCodec',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/OdfBlockStyleCodec.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Editing',
    'name' => 'OCA\\Filinq\\Service\\Editing\\OdfBlockStyleCodec',
    'shortName' => 'OdfBlockStyleCodec',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Applies block style to ODF packages.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 38,
    'endLine' => 250,
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
      'ODF_ALIGNMENTS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\OdfBlockStyleCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\OdfBlockStyleCodec',
        'name' => 'ODF_ALIGNMENTS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'left\' => \'start\', \'center\' => \'center\', \'right\' => \'end\', \'justify\' => \'justify\']',
          'attributes' => 
          array (
            'startLine' => 50,
            'endLine' => 55,
            'startTokenPos' => 44,
            'startFilePos' => 1494,
            'endTokenPos' => 74,
            'endFilePos' => 1588,
          ),
        ),
        'docComment' => '/**
 * ODF\'s spelling of the same alignment vocabulary.
 *
 * ODF uses XSL-FO names: `start`/`end` rather than `left`/`right`, and
 * `justify` rather than OOXML\'s `both`. Reusing the OOXML map here would
 * emit values a reader silently ignores — a restyle that reports success
 * and changes nothing.
 *
 * @var array<string, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 50,
        'endLine' => 55,
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
            'startLine' => 66,
            'endLine' => 66,
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
 * @return bool True for ODF.
 *
 * @spec openspec/specs/document-rich-editing/spec.md
 */',
        'startLine' => 66,
        'endLine' => 68,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\OdfBlockStyleCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\OdfBlockStyleCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\OdfBlockStyleCodec',
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
            'startLine' => 94,
            'endLine' => 94,
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
            'startLine' => 94,
            'endLine' => 94,
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
            'startLine' => 94,
            'endLine' => 94,
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
 * Apply style to an ODF block.
 *
 * ⚠️ ODF has no direct formatting on a paragraph. A property is expressed by
 * pointing the block at an AUTOMATIC STYLE defined in `content.xml`, so this
 * returns the definition alongside the rewritten markup and the caller
 * injects it. That indirection is the reason ODF styling was refused
 * outright for so long, and it is the whole of the difficulty.
 *
 * 🔴 A heading is `text:h`, not a styled `text:p`, so `heading` REWRITES THE
 * ELEMENT. That is only safe because the block scanner spans both names —
 * before it did, converting a paragraph to a heading would have made the
 * block vanish from the next read.
 *
 * @param string $markup The block markup.
 * @param array $style The style properties.
 * @param string $styleName The automatic style name to mint.
 *
 * @return array{markup: string, automaticStyle: string|null} The rewritten block and its style.
 *
 * @throws RuntimeException When a property has no ODF expression here.
 *
 * @spec openspec/specs/document-rich-editing/spec.md
 */',
        'startLine' => 94,
        'endLine' => 127,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\OdfBlockStyleCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\OdfBlockStyleCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\OdfBlockStyleCodec',
        'aliasName' => NULL,
      ),
      'odfParagraphProperties' => 
      array (
        'name' => 'odfParagraphProperties',
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
            'startLine' => 136,
            'endLine' => 136,
            'startColumn' => 42,
            'endColumn' => 53,
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
 * The ODF paragraph-level properties for a style.
 *
 * @param array $style The style properties.
 *
 * @return string The attribute string, empty when none apply.
 */',
        'startLine' => 136,
        'endLine' => 148,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\OdfBlockStyleCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\OdfBlockStyleCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\OdfBlockStyleCodec',
        'aliasName' => NULL,
      ),
      'odfTextProperties' => 
      array (
        'name' => 'odfTextProperties',
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
            'startLine' => 162,
            'endLine' => 162,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The ODF text-level properties for a style.
 *
 * Each property is written whenever the key is PRESENT, including when it
 * is false: switching bold off is `fo:font-weight="normal"`, not the
 * absence of the attribute. Omitting it would leave the inherited value in
 * place and report the restyle as applied while changing nothing.
 *
 * @param array $style The style properties.
 *
 * @return string The attribute string, empty when none apply.
 */',
        'startLine' => 162,
        'endLine' => 193,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\OdfBlockStyleCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\OdfBlockStyleCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\OdfBlockStyleCodec',
        'aliasName' => NULL,
      ),
      'applyOdfHeading' => 
      array (
        'name' => 'applyOdfHeading',
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
            'startLine' => 203,
            'endLine' => 203,
            'startColumn' => 35,
            'endColumn' => 48,
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
            'startLine' => 203,
            'endLine' => 203,
            'startColumn' => 51,
            'endColumn' => 62,
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
 * Convert between `text:p` and `text:h` for the `heading` property.
 *
 * @param string $markup The block markup.
 * @param array $style The style properties.
 *
 * @return string The rewritten markup.
 */',
        'startLine' => 203,
        'endLine' => 230,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\OdfBlockStyleCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\OdfBlockStyleCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\OdfBlockStyleCodec',
        'aliasName' => NULL,
      ),
      'pointAtStyle' => 
      array (
        'name' => 'pointAtStyle',
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
            'startLine' => 240,
            'endLine' => 240,
            'startColumn' => 32,
            'endColumn' => 45,
            'parameterIndex' => 0,
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
            'startLine' => 240,
            'endLine' => 240,
            'startColumn' => 48,
            'endColumn' => 64,
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
 * Point a block at an automatic style, replacing any existing reference.
 *
 * @param string $markup The block markup.
 * @param string $styleName The automatic style name.
 *
 * @return string The rewritten markup.
 */',
        'startLine' => 240,
        'endLine' => 249,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\OdfBlockStyleCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\OdfBlockStyleCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\OdfBlockStyleCodec',
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