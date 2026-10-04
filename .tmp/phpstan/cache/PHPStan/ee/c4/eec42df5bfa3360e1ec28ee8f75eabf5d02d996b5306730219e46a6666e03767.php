<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/PackageCodec.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Editing\PackageCodec
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-dba926d93f656b1f931136bb82d2aa3594eb7adb0725724629ec9174a3f40fb8',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/PackageCodec.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Editing',
    'name' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
    'shortName' => 'PackageCodec',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Byte-surgical reader/editor for ODF and OOXML word-processing packages.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-untouched-parts-of-a-document-package-survive-an-edit-unchanged
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 52,
    'endLine' => 681,
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
      'FORMAT_OOXML' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'name' => 'FORMAT_OOXML',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'ooxml\'',
          'attributes' => 
          array (
            'startLine' => 59,
            'endLine' => 59,
            'startTokenPos' => 40,
            'startFilePos' => 2004,
            'endTokenPos' => 40,
            'endFilePos' => 2010,
          ),
        ),
        'docComment' => '/**
 * OOXML word-processing package (`.docx`).
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 59,
        'endLine' => 59,
        'startColumn' => 2,
        'endColumn' => 37,
      ),
      'FORMAT_ODF' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'name' => 'FORMAT_ODF',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'odf\'',
          'attributes' => 
          array (
            'startLine' => 66,
            'endLine' => 66,
            'startTokenPos' => 53,
            'startFilePos' => 2102,
            'endTokenPos' => 53,
            'endFilePos' => 2106,
          ),
        ),
        'docComment' => '/**
 * ODF text package (`.odt`).
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 66,
        'endLine' => 66,
        'startColumn' => 2,
        'endColumn' => 33,
      ),
      'PACKAGES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'name' => 'PACKAGES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'docx\' => [\'format\' => self::FORMAT_OOXML, \'part\' => \'word/document.xml\', \'tag\' => \'w:p\', \'blockTags\' => [\'w:p\']], \'odt\' => [
    \'format\' => self::FORMAT_ODF,
    \'part\' => \'content.xml\',
    \'tag\' => \'text:p\',
    // 🔴 `text:h` too. ODF writes a heading as its OWN element, not as
    // a styled paragraph, so scanning `text:p` alone made every heading
    // in an .odt invisible to readDocument — and an anchor an agent
    // could never resolve for text plainly on the page.
    \'blockTags\' => [\'text:p\', \'text:h\'],
]]',
          'attributes' => 
          array (
            'startLine' => 79,
            'endLine' => 96,
            'startTokenPos' => 66,
            'startFilePos' => 2626,
            'endTokenPos' => 161,
            'endFilePos' => 3175,
          ),
        ),
        'docComment' => '/**
 * Supported extensions mapped to their package family, body part and
 * paragraph element name.
 *
 * Spreadsheets and presentations are deliberately absent: their block model
 * is a cell/slide, not a paragraph, and giving them a paragraph anchor would
 * produce anchors that resolve to nothing. They are specified separately in
 * `multi-format-editing-tools`.
 *
 * @var array<string, array{format: string, part: string, tag: string, blockTags: array<int, string>}>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 79,
        'endLine' => 96,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
      'ACTION_REPLACE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'name' => 'ACTION_REPLACE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'replace\'',
          'attributes' => 
          array (
            'startLine' => 103,
            'endLine' => 103,
            'startTokenPos' => 174,
            'startFilePos' => 3304,
            'endTokenPos' => 174,
            'endFilePos' => 3312,
          ),
        ),
        'docComment' => '/**
 * Edit action: replace the anchored paragraph\'s visible text.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 103,
        'endLine' => 103,
        'startColumn' => 2,
        'endColumn' => 41,
      ),
      'ACTION_INSERT_AFTER' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'name' => 'ACTION_INSERT_AFTER',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'insertAfter\'',
          'attributes' => 
          array (
            'startLine' => 110,
            'endLine' => 110,
            'startTokenPos' => 187,
            'startFilePos' => 3469,
            'endTokenPos' => 187,
            'endFilePos' => 3481,
          ),
        ),
        'docComment' => '/**
 * Edit action: insert a new paragraph after the anchored one, inheriting its markup.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 110,
        'endLine' => 110,
        'startColumn' => 2,
        'endColumn' => 50,
      ),
      'ACTION_DELETE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'name' => 'ACTION_DELETE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'delete\'',
          'attributes' => 
          array (
            'startLine' => 117,
            'endLine' => 117,
            'startTokenPos' => 200,
            'startFilePos' => 3593,
            'endTokenPos' => 200,
            'endFilePos' => 3600,
          ),
        ),
        'docComment' => '/**
 * Edit action: remove the anchored paragraph.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 117,
        'endLine' => 117,
        'startColumn' => 2,
        'endColumn' => 39,
      ),
      'ACTION_STYLE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'name' => 'ACTION_STYLE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'style\'',
          'attributes' => 
          array (
            'startLine' => 129,
            'endLine' => 129,
            'startTokenPos' => 213,
            'startFilePos' => 4057,
            'endTokenPos' => 213,
            'endFilePos' => 4063,
          ),
        ),
        'docComment' => '/**
 * Edit action: change the anchored paragraph\'s style or layout, not its text.
 *
 * Separate from `replace` on purpose. Restyling and rewording are different
 * intentions, and folding them together would mean a caller that wanted bold
 * had to resend the text — which, when the caller is a language model, is an
 * invitation to paraphrase the paragraph while "only" making it bold.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 129,
        'endLine' => 129,
        'startColumn' => 2,
        'endColumn' => 37,
      ),
      'ACTIONS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'name' => 'ACTIONS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[self::ACTION_REPLACE, self::ACTION_INSERT_AFTER, self::ACTION_DELETE, self::ACTION_STYLE]',
          'attributes' => 
          array (
            'startLine' => 136,
            'endLine' => 141,
            'startTokenPos' => 226,
            'startFilePos' => 4174,
            'endTokenPos' => 248,
            'endFilePos' => 4275,
          ),
        ),
        'docComment' => '/**
 * Every action this codec understands.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 136,
        'endLine' => 141,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'scanner' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'name' => 'scanner',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Editing\\XmlBlockScanner',
            'isIdentifier' => false,
          ),
        ),
        'default' => 
        array (
          'code' => 'new \\OCA\\Filinq\\Service\\Editing\\XmlBlockScanner()',
          'attributes' => 
          array (
            'startLine' => 153,
            'endLine' => 153,
            'startTokenPos' => 270,
            'startFilePos' => 4599,
            'endTokenPos' => 274,
            'endFilePos' => 4619,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 153,
        'endLine' => 153,
        'startColumn' => 3,
        'endColumn' => 67,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'styles' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'name' => 'styles',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleCodec',
            'isIdentifier' => false,
          ),
        ),
        'default' => 
        array (
          'code' => 'new \\OCA\\Filinq\\Service\\Editing\\BlockStyleCodec()',
          'attributes' => 
          array (
            'startLine' => 154,
            'endLine' => 154,
            'startTokenPos' => 287,
            'startFilePos' => 4667,
            'endTokenPos' => 291,
            'endFilePos' => 4687,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 154,
        'endLine' => 154,
        'startColumn' => 3,
        'endColumn' => 66,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'io' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'name' => 'io',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Editing\\PackagePartIo',
            'isIdentifier' => false,
          ),
        ),
        'default' => 
        array (
          'code' => 'new \\OCA\\Filinq\\Service\\Editing\\PackagePartIo()',
          'attributes' => 
          array (
            'startLine' => 155,
            'endLine' => 155,
            'startTokenPos' => 304,
            'startFilePos' => 4729,
            'endTokenPos' => 308,
            'endFilePos' => 4747,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 155,
        'endLine' => 155,
        'startColumn' => 3,
        'endColumn' => 58,
        'isPromoted' => true,
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
          'scanner' => 
          array (
            'name' => 'scanner',
            'default' => 
            array (
              'code' => 'new \\OCA\\Filinq\\Service\\Editing\\XmlBlockScanner()',
              'attributes' => 
              array (
                'startLine' => 153,
                'endLine' => 153,
                'startTokenPos' => 270,
                'startFilePos' => 4599,
                'endTokenPos' => 274,
                'endFilePos' => 4619,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Editing\\XmlBlockScanner',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 153,
            'endLine' => 153,
            'startColumn' => 3,
            'endColumn' => 67,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'styles' => 
          array (
            'name' => 'styles',
            'default' => 
            array (
              'code' => 'new \\OCA\\Filinq\\Service\\Editing\\BlockStyleCodec()',
              'attributes' => 
              array (
                'startLine' => 154,
                'endLine' => 154,
                'startTokenPos' => 287,
                'startFilePos' => 4667,
                'endTokenPos' => 291,
                'endFilePos' => 4687,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Editing\\BlockStyleCodec',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 154,
            'endLine' => 154,
            'startColumn' => 3,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'io' => 
          array (
            'name' => 'io',
            'default' => 
            array (
              'code' => 'new \\OCA\\Filinq\\Service\\Editing\\PackagePartIo()',
              'attributes' => 
              array (
                'startLine' => 155,
                'endLine' => 155,
                'startTokenPos' => 304,
                'startFilePos' => 4729,
                'endTokenPos' => 308,
                'endFilePos' => 4747,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Editing\\PackagePartIo',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 155,
            'endLine' => 155,
            'startColumn' => 3,
            'endColumn' => 58,
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
 * Constructor.
 *
 * @param XmlBlockScanner $scanner The element-span scanner.
 * @param BlockStyleCodec $styles The paragraph style/layout codec.
 * @param PackagePartIo $io The package part reader/writer.
 *
 * @return void
 */',
        'startLine' => 152,
        'endLine' => 158,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'aliasName' => NULL,
      ),
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
            'startLine' => 169,
            'endLine' => 169,
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
 * Whether this codec can address the given file extension.
 *
 * @param string $extension The file extension, without a leading dot, any case.
 *
 * @return bool True when the extension names a supported package.
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-editing-session-availability-is-probed-never-inferred
 */',
        'startLine' => 169,
        'endLine' => 171,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'aliasName' => NULL,
      ),
      'supportedExtensions' => 
      array (
        'name' => 'supportedExtensions',
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
 * Every extension this codec can address.
 *
 * @return array<int, string> The supported extensions.
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-editing-session-availability-is-probed-never-inferred
 */',
        'startLine' => 180,
        'endLine' => 182,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'aliasName' => NULL,
      ),
      'readBlocks' => 
      array (
        'name' => 'readBlocks',
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
            'startLine' => 202,
            'endLine' => 202,
            'startColumn' => 29,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 202,
            'endLine' => 202,
            'startColumn' => 51,
            'endColumn' => 67,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Read the anchored blocks of a package.
 *
 * Anchors are derived from block CONTENT, not from position: a human
 * inserting a paragraph shifts every index, and an index-addressed edit
 * would then land in the wrong place with no error. They are recomputed on
 * every read, so an anchor is only valid for as long as the block\'s text is
 * unchanged -- which is the property that makes a stale edit fail loudly.
 *
 * @param string $packageBytes The raw package bytes.
 * @param string $extension The file extension, without a leading dot.
 *
 * @return array{format: string, blocks: array<int, array{anchor: string, text: string}>}
 *
 * @throws RuntimeException When the extension is unsupported or the package is unreadable.
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-edits-address-stable-anchors-never-positional-indexes
 */',
        'startLine' => 202,
        'endLine' => 217,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'aliasName' => NULL,
      ),
      'applyEdits' => 
      array (
        'name' => 'applyEdits',
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
            'startLine' => 239,
            'endLine' => 239,
            'startColumn' => 29,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 239,
            'endLine' => 239,
            'startColumn' => 51,
            'endColumn' => 67,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'edits' => 
          array (
            'name' => 'edits',
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
            'startLine' => 239,
            'endLine' => 239,
            'startColumn' => 70,
            'endColumn' => 81,
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
 * Apply anchored edits and return the new package bytes.
 *
 * Edits are applied from the LAST matched span backwards, so the byte
 * offsets of the spans still to be edited are never invalidated by an
 * earlier rewrite.
 *
 * @param string $packageBytes The raw package bytes.
 * @param string $extension The file extension, without a leading dot.
 * @param array<int, array<string, mixed>> $edits The edits to apply, each
 *                                                `{anchor, action?, text?}`. Loosely typed because they arrive as
 *                                                decoded JSON from a model; `resolveEdits()` validates them.
 *
 * @return array{bytes: string, applied: array<int, string>}
 *
 * @throws RuntimeException When an anchor does not resolve, an action is unknown,
 *                          or the package cannot be rewritten.
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-untouched-parts-of-a-document-package-survive-an-edit-unchanged
 */',
        'startLine' => 239,
        'endLine' => 280,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'aliasName' => NULL,
      ),
      'resolveEdits' => 
      array (
        'name' => 'resolveEdits',
        'parameters' => 
        array (
          'edits' => 
          array (
            'name' => 'edits',
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
            'startLine' => 301,
            'endLine' => 301,
            'startColumn' => 32,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'anchors' => 
          array (
            'name' => 'anchors',
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
            'startLine' => 301,
            'endLine' => 301,
            'startColumn' => 46,
            'endColumn' => 59,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolve every edit\'s anchor to a block index, failing on the first miss.
 *
 * Failing the WHOLE edit set on one unresolvable anchor is deliberate: a
 * partially applied edit set leaves a document in a state neither the user
 * nor the agent asked for, and no caller can tell which half landed.
 *
 * The declared shape is deliberately loose: these arrive as decoded JSON from
 * a language model, so `anchor` and `action` may be absent, and this method
 * is the thing that finds out. Typing them as present would make the checks
 * below look redundant to a static analyser and invite their removal.
 *
 * @param array<int, array<string, mixed>> $edits The requested edits.
 * @param array<int, string> $anchors The anchors of the current blocks, in document order.
 *
 * @return array<int, array{index: int, anchor: string, action: string, text: string, style: array<string, mixed>}>
 *
 * @throws RuntimeException When an anchor is unknown or an action is not supported.
 */',
        'startLine' => 301,
        'endLine' => 358,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'aliasName' => NULL,
      ),
      'rewriteStyledSpan' => 
      array (
        'name' => 'rewriteStyledSpan',
        'parameters' => 
        array (
          'xml' => 
          array (
            'name' => 'xml',
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
            'startLine' => 388,
            'endLine' => 388,
            'startColumn' => 37,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'span' => 
          array (
            'name' => 'span',
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
            'startLine' => 388,
            'endLine' => 388,
            'startColumn' => 50,
            'endColumn' => 60,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
            'startLine' => 388,
            'endLine' => 388,
            'startColumn' => 63,
            'endColumn' => 76,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'edit' => 
          array (
            'name' => 'edit',
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
            'startLine' => 388,
            'endLine' => 388,
            'startColumn' => 79,
            'endColumn' => 89,
            'parameterIndex' => 3,
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
            'startLine' => 388,
            'endLine' => 388,
            'startColumn' => 92,
            'endColumn' => 105,
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
 * Apply a style edit, injecting any ODF automatic style it mints.
 *
 * ⚠️ A style edit produces TWO things: the rewritten block, and — for ODF —
 * a document-level automatic style the block only POINTS at. Both are
 * handled here, together, because they are only correct together: a
 * definition that never lands leaves the block referencing a style that
 * does not exist, and the restyle silently does nothing.
 *
 * They used to be split, with the definition handed back through a mutable
 * `$pendingOdfStyle` property that the caller read after the fact. That is
 * action at a distance in the one place it is least affordable, and it also
 * defeated static analysis: phpstan could not see the property ever being
 * assigned across the `match` arm, so it read the caller\'s `!== null` check
 * as comparing null with null and reported the injection as unreachable.
 * Returning the finished XML removes the property and the false reading at
 * once.
 *
 * @param string $xml The part XML.
 * @param array{0: int, 1: int} $span The span offset and length.
 * @param string $markup The block markup.
 * @param array $edit The edit.
 * @param string $format The package family.
 *
 * @return string The rewritten part XML, style definition included.
 *
 * @spec openspec/specs/document-rich-editing/spec.md
 */',
        'startLine' => 388,
        'endLine' => 407,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'aliasName' => NULL,
      ),
      'injectAutomaticStyle' => 
      array (
        'name' => 'injectAutomaticStyle',
        'parameters' => 
        array (
          'xml' => 
          array (
            'name' => 'xml',
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
            'startLine' => 417,
            'endLine' => 417,
            'startColumn' => 40,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'definition' => 
          array (
            'name' => 'definition',
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
            'startLine' => 417,
            'endLine' => 417,
            'startColumn' => 53,
            'endColumn' => 70,
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
 * Insert an automatic style definition into `content.xml`.
 *
 * @param string $xml The part XML.
 * @param string $definition The `<style:style>` element.
 *
 * @return string The part XML with the definition in place.
 */',
        'startLine' => 417,
        'endLine' => 433,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'aliasName' => NULL,
      ),
      'rewriteSpan' => 
      array (
        'name' => 'rewriteSpan',
        'parameters' => 
        array (
          'xml' => 
          array (
            'name' => 'xml',
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
            'startLine' => 445,
            'endLine' => 445,
            'startColumn' => 31,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'span' => 
          array (
            'name' => 'span',
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
            'startLine' => 445,
            'endLine' => 445,
            'startColumn' => 44,
            'endColumn' => 54,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'edit' => 
          array (
            'name' => 'edit',
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
            'startLine' => 445,
            'endLine' => 445,
            'startColumn' => 57,
            'endColumn' => 67,
            'parameterIndex' => 2,
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
            'startLine' => 445,
            'endLine' => 445,
            'startColumn' => 70,
            'endColumn' => 83,
            'parameterIndex' => 3,
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
 * Rewrite one span according to one edit.
 *
 * @param string $xml The part XML.
 * @param array{0: int, 1: int} $span The span offset and length.
 * @param array{action: string, text: string, style: array<string, mixed>} $edit The edit to apply.
 * @param string $format The package family.
 *
 * @return string The rewritten part XML.
 */',
        'startLine' => 445,
        'endLine' => 471,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'aliasName' => NULL,
      ),
      'packageFor' => 
      array (
        'name' => 'packageFor',
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
            'startLine' => 482,
            'endLine' => 482,
            'startColumn' => 30,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolve the package descriptor for an extension.
 *
 * @param string $extension The file extension, without a leading dot.
 *
 * @return array{format: string, part: string, tag: string, blockTags: array<int, string>}
 *
 * @throws RuntimeException When the extension names no supported package.
 */',
        'startLine' => 482,
        'endLine' => 495,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'aliasName' => NULL,
      ),
      'extractText' => 
      array (
        'name' => 'extractText',
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
            'startLine' => 514,
            'endLine' => 514,
            'startColumn' => 31,
            'endColumn' => 44,
            'parameterIndex' => 0,
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
            'startLine' => 514,
            'endLine' => 514,
            'startColumn' => 47,
            'endColumn' => 60,
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
 * Extract a block\'s visible text from its markup.
 *
 * @param string $markup The block markup.
 * @param string $format The package family.
 *
 * @return string The visible text.
 */',
        'startLine' => 514,
        'endLine' => 536,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'aliasName' => NULL,
      ),
      'setText' => 
      array (
        'name' => 'setText',
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
            'startLine' => 552,
            'endLine' => 552,
            'startColumn' => 27,
            'endColumn' => 40,
            'parameterIndex' => 0,
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
            'startLine' => 552,
            'endLine' => 552,
            'startColumn' => 43,
            'endColumn' => 54,
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
            'startLine' => 552,
            'endLine' => 552,
            'startColumn' => 57,
            'endColumn' => 70,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Replace a block\'s visible text, keeping the block\'s own markup.
 *
 * OOXML: the first `w:t` carries the new text and every later one is
 * emptied, so the paragraph keeps its properties and its first run\'s
 * formatting. ODF: the paragraph\'s children are replaced wholesale, so
 * paragraph style survives but intra-paragraph spans do not.
 *
 * @param string $markup The block markup.
 * @param string $text The new visible text.
 * @param string $format The package family.
 *
 * @return string The rewritten block markup.
 */',
        'startLine' => 552,
        'endLine' => 590,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'aliasName' => NULL,
      ),
      'rewriteTextRun' => 
      array (
        'name' => 'rewriteTextRun',
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
            'startLine' => 600,
            'endLine' => 600,
            'startColumn' => 34,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'escaped' => 
          array (
            'name' => 'escaped',
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
            'startLine' => 600,
            'endLine' => 600,
            'startColumn' => 47,
            'endColumn' => 61,
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
 * Set the contents of a single `w:t` element, preserving significant whitespace.
 *
 * @param string $run The `w:t` element markup.
 * @param string $escaped The already XML-escaped replacement text.
 *
 * @return string The rewritten element.
 */',
        'startLine' => 600,
        'endLine' => 616,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'aliasName' => NULL,
      ),
      'injectRun' => 
      array (
        'name' => 'injectRun',
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
            'startLine' => 629,
            'endLine' => 629,
            'startColumn' => 29,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'escaped' => 
          array (
            'name' => 'escaped',
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
            'startLine' => 629,
            'endLine' => 629,
            'startColumn' => 45,
            'endColumn' => 59,
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
 * Give a run-less paragraph a run to carry text.
 *
 * An empty paragraph has no `w:t` to rewrite, so replacing its text means
 * adding the run that holds it.
 *
 * @param string $markup The paragraph markup.
 * @param string $escaped The already XML-escaped text.
 *
 * @return string The rewritten paragraph.
 */',
        'startLine' => 629,
        'endLine' => 642,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'aliasName' => NULL,
      ),
      'decode' => 
      array (
        'name' => 'decode',
        'parameters' => 
        array (
          'raw' => 
          array (
            'name' => 'raw',
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
            'startLine' => 651,
            'endLine' => 651,
            'startColumn' => 26,
            'endColumn' => 36,
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
 * Decode XML entities and collapse whitespace to a comparable form.
 *
 * @param string $raw The raw text.
 *
 * @return string The decoded text.
 */',
        'startLine' => 651,
        'endLine' => 653,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'aliasName' => NULL,
      ),
      'anchor' => 
      array (
        'name' => 'anchor',
        'parameters' => 
        array (
          'texts' => 
          array (
            'name' => 'texts',
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
            'startLine' => 666,
            'endLine' => 666,
            'startColumn' => 26,
            'endColumn' => 37,
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
 * Give each block a content-derived anchor, disambiguated by occurrence.
 *
 * Two paragraphs with identical text are common (empty ones especially), so
 * the hash alone is not an address. The occurrence ordinal makes it one, and
 * keeps it stable as long as the identical blocks stay in the same order.
 *
 * @param array<int, string> $texts The block texts, in document order.
 *
 * @return array<int, array{anchor: string, text: string}>
 */',
        'startLine' => 666,
        'endLine' => 680,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageCodec',
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