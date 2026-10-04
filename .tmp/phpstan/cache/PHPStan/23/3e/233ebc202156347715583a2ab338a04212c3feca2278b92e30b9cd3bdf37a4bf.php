<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/BlockStyleCodec.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Editing\BlockStyleCodec
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-70dd3fdeb025420d5b91c32c46b5eb36fa214666352f4770355d9e4f3c5aa2be',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleCodec',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/BlockStyleCodec.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Editing',
    'name' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleCodec',
    'shortName' => 'BlockStyleCodec',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Style and layout for one block, dispatched to its package family.
 *
 * This class owns the parts that must NOT differ between families: the style
 * vocabulary, and the validation of a caller\'s request against it. Two copies
 * of "which keys are legal" is how two codecs drift into accepting different
 * things, and the caller here is a language model, which will misspell keys.
 *
 * The parts that genuinely do differ live behind
 * {@see BlockStyleFamilyCodec}. OOXML carries direct formatting INSIDE the
 * paragraph, so rewriting the span is sufficient. ODF has no direct formatting
 * at all: a block points at an automatic style defined elsewhere in
 * `content.xml`, and a heading is a different ELEMENT (`text:h`) rather than a
 * styled paragraph. Holding both in one class measured at a complexity of 65
 * against a threshold of 50 — the number saying these were two implementations
 * sharing a name.
 *
 * It knows nothing about packages, anchors or which block is being styled;
 * {@see PackageCodec} owns all of that. That ignorance is what lets every
 * family codec be tested against a plain string.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://filinq.app
 *
 * @spec openspec/specs/document-rich-editing/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 63,
    'endLine' => 239,
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
      'ALIGNMENTS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleCodec',
        'name' => 'ALIGNMENTS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'left\', \'center\', \'right\', \'justify\']',
          'attributes' => 
          array (
            'startLine' => 80,
            'endLine' => 85,
            'startTokenPos' => 42,
            'startFilePos' => 2662,
            'endTokenPos' => 56,
            'endFilePos' => 2711,
          ),
        ),
        'docComment' => '/**
 * The alignment vocabulary callers may use.
 *
 * The NAMES are shared; the spelling each family emits is not. OOXML writes
 * `both` for justify and ODF writes `start`/`end` for left/right, so the
 * mapping belongs to each codec while the accepted vocabulary — the thing a
 * caller is validated against — belongs here. One list, validated once.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 80,
        'endLine' => 85,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
      'STYLE_KEYS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleCodec',
        'name' => 'STYLE_KEYS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'bold\', \'italic\', \'underline\', \'alignment\', \'heading\', \'list\', \'pageBreakBefore\']',
          'attributes' => 
          array (
            'startLine' => 87,
            'endLine' => 95,
            'startTokenPos' => 67,
            'startFilePos' => 2742,
            'endTokenPos' => 90,
            'endFilePos' => 2841,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 87,
        'endLine' => 95,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'families' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleCodec',
        'name' => 'families',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * The per-family codecs, tried in order.
 *
 * @var array<int, BlockStyleFamilyCodec>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 102,
        'endLine' => 102,
        'startColumn' => 2,
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
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'families' => 
          array (
            'name' => 'families',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 109,
                'endLine' => 109,
                'startTokenPos' => 117,
                'startFilePos' => 3141,
                'endTokenPos' => 117,
                'endFilePos' => 3144,
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
            'startLine' => 109,
            'endLine' => 109,
            'startColumn' => 30,
            'endColumn' => 52,
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
 * Constructor.
 *
 * @param array<int, BlockStyleFamilyCodec>|null $families Optional override, for tests.
 */',
        'startLine' => 109,
        'endLine' => 111,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleCodec',
        'aliasName' => NULL,
      ),
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
            'startLine' => 122,
            'endLine' => 122,
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
 * Whether style can be applied to this package family.
 *
 * @param string $format The package family constant.
 *
 * @return bool True when a codec handles it.
 *
 * @spec openspec/specs/document-rich-editing/spec.md
 */',
        'startLine' => 122,
        'endLine' => 124,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleCodec',
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
            'startLine' => 144,
            'endLine' => 144,
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
            'startLine' => 144,
            'endLine' => 144,
            'startColumn' => 45,
            'endColumn' => 56,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
            'startLine' => 144,
            'endLine' => 144,
            'startColumn' => 59,
            'endColumn' => 72,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'styleName' => 
          array (
            'name' => 'styleName',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 144,
                'endLine' => 144,
                'startTokenPos' => 222,
                'startFilePos' => 4454,
                'endTokenPos' => 222,
                'endFilePos' => 4455,
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
            ),
            'startLine' => 144,
            'endLine' => 144,
            'startColumn' => 75,
            'endColumn' => 96,
            'parameterIndex' => 3,
            'isOptional' => true,
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
 * Apply style properties to one block\'s markup.
 *
 * Validation lives HERE rather than in each family: both share one style
 * vocabulary, and two copies of "which keys are legal" is how they drift
 * into accepting different things.
 *
 * @param string $markup The block markup.
 * @param array $style The style properties.
 * @param string $format The package family constant.
 * @param string $styleName A unique name a family may mint a style under.
 *
 * @return array{markup: string, automaticStyle: string|null} The rewritten block and any style to inject.
 *
 * @throws RuntimeException When the format is unsupported or a property is unknown.
 *
 * @spec openspec/specs/document-rich-editing/spec.md
 */',
        'startLine' => 144,
        'endLine' => 155,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleCodec',
        'aliasName' => NULL,
      ),
      'codecFor' => 
      array (
        'name' => 'codecFor',
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
            'startLine' => 164,
            'endLine' => 164,
            'startColumn' => 28,
            'endColumn' => 41,
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
                  'name' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleFamilyCodec',
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
        ),
        'docComment' => '/**
 * The codec handling a package family, or null.
 *
 * @param string $format The package family constant.
 *
 * @return BlockStyleFamilyCodec|null The codec, or null when unhandled.
 */',
        'startLine' => 164,
        'endLine' => 172,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleCodec',
        'aliasName' => NULL,
      ),
      'assertKnownKeys' => 
      array (
        'name' => 'assertKnownKeys',
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
            'startLine' => 186,
            'endLine' => 186,
            'startColumn' => 35,
            'endColumn' => 46,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Reject unknown style keys.
 *
 * A misspelled key that was silently ignored would report success and change
 * nothing — and the caller here is a language model, which will misspell keys.
 *
 * @param array $style The style properties.
 *
 * @return void
 *
 * @throws RuntimeException When a key is not understood.
 */',
        'startLine' => 186,
        'endLine' => 204,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleCodec',
        'aliasName' => NULL,
      ),
      'assertKnownValues' => 
      array (
        'name' => 'assertKnownValues',
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
            'startLine' => 218,
            'endLine' => 218,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Reject values that name something the codec cannot express.
 *
 * Split from {@see assertKnownKeys()} to keep both under phpmd\'s complexity
 * threshold; the behaviour is unchanged.
 *
 * @param array $style The style properties.
 *
 * @return void
 *
 * @throws RuntimeException When a value is not understood.
 */',
        'startLine' => 218,
        'endLine' => 238,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleCodec',
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