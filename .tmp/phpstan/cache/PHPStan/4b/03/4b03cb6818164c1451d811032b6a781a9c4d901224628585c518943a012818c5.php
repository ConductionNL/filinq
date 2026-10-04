<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/PackageMetadataCodec.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Editing\PackageMetadataCodec
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-cace1f1d14e947b3f942ea7f5d75fa0141df4190ad07f16853206a8de1475a36',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/PackageMetadataCodec.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Editing',
    'name' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
    'shortName' => 'PackageMetadataCodec',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Document metadata, addressed by a format-neutral field name.
 *
 * Both families store the same handful of Dublin Core fields, in different parts
 * under different element names:
 *
 *   OOXML  docProps/core.xml   dc:title, dc:subject, dc:creator,
 *                              cp:keywords, dc:description
 *   ODF    meta.xml            dc:title, dc:subject, dc:creator,
 *                              meta:keyword, dc:description
 *
 * Callers name a field once (`title`, `subject`, …) and the codec resolves the
 * element. A caller that had to know `cp:keywords` for one format and
 * `meta:keyword` for the other would be writing per-format code, which is the
 * thing ADR-087 §2 exists to prevent — the formats differ, but the CAPABILITY does
 * not.
 *
 * Only these five fields are writable. `dcterms:created` / `dcterms:modified` and
 * ODF\'s `meta:editing-cycles` are deliberately excluded: they are a record of what
 * happened to the document, and letting an agent set them would make that record
 * a claim rather than a fact.
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
    'startLine' => 65,
    'endLine' => 355,
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
      'PACKAGES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
        'name' => 'PACKAGES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'docx\' => [\'part\' => \'docProps/core.xml\', \'root\' => \'cp:coreProperties\', \'ns\' => \'xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" \' . \'xmlns:dc="http://purl.org/dc/elements/1.1/" \' . \'xmlns:dcterms="http://purl.org/dc/terms/" \' . \'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"\', \'fields\' => [\'title\' => \'dc:title\', \'subject\' => \'dc:subject\', \'creator\' => \'dc:creator\', \'keywords\' => \'cp:keywords\', \'description\' => \'dc:description\']], \'odt\' => [\'part\' => \'meta.xml\', \'root\' => \'office:document-meta\', \'ns\' => \'xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" \' . \'xmlns:dc="http://purl.org/dc/elements/1.1/" \' . \'xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0"\', \'fields\' => [\'title\' => \'dc:title\', \'subject\' => \'dc:subject\', \'creator\' => \'dc:creator\', \'keywords\' => \'meta:keyword\', \'description\' => \'dc:description\']]]',
          'attributes' => 
          array (
            'startLine' => 72,
            'endLine' => 102,
            'startTokenPos' => 40,
            'startFilePos' => 2331,
            'endTokenPos' => 210,
            'endFilePos' => 3334,
          ),
        ),
        'docComment' => '/**
 * Per-extension metadata part and element mapping.
 *
 * @var array<string, array{part: string, root: string, ns: string, fields: array<string, string>}>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 72,
        'endLine' => 102,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'io' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
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
            'startLine' => 112,
            'endLine' => 112,
            'startTokenPos' => 232,
            'startFilePos' => 3520,
            'endTokenPos' => 236,
            'endFilePos' => 3538,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 112,
        'endLine' => 112,
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
          'io' => 
          array (
            'name' => 'io',
            'default' => 
            array (
              'code' => 'new \\OCA\\Filinq\\Service\\Editing\\PackagePartIo()',
              'attributes' => 
              array (
                'startLine' => 112,
                'endLine' => 112,
                'startTokenPos' => 232,
                'startFilePos' => 3520,
                'endTokenPos' => 236,
                'endFilePos' => 3538,
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
            'startLine' => 112,
            'endLine' => 112,
            'startColumn' => 3,
            'endColumn' => 58,
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
 * @param PackagePartIo $io The package part reader/writer.
 *
 * @return void
 */',
        'startLine' => 111,
        'endLine' => 114,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
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
            'startLine' => 125,
            'endLine' => 125,
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
 * Whether metadata can be addressed for this extension.
 *
 * @param string $extension The file extension, without a leading dot.
 *
 * @return bool True when supported.
 *
 * @spec openspec/specs/document-rich-editing/spec.md
 */',
        'startLine' => 125,
        'endLine' => 127,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
        'aliasName' => NULL,
      ),
      'fields' => 
      array (
        'name' => 'fields',
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
 * The field names this codec understands.
 *
 * @return array<int, string> The field names.
 *
 * @spec openspec/specs/document-rich-editing/spec.md
 */',
        'startLine' => 136,
        'endLine' => 138,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
        'aliasName' => NULL,
      ),
      'readMetadata' => 
      array (
        'name' => 'readMetadata',
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
            'startLine' => 156,
            'endLine' => 156,
            'startColumn' => 31,
            'endColumn' => 50,
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
            'startLine' => 156,
            'endLine' => 156,
            'startColumn' => 53,
            'endColumn' => 69,
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
 * Read a document\'s metadata.
 *
 * A field the document does not carry comes back as an empty string rather
 * than being omitted, so a caller can tell "this document has no subject" from
 * "I forgot to ask for the subject".
 *
 * @param string $packageBytes The raw package bytes.
 * @param string $extension The file extension.
 *
 * @return array<string, string> Field name => value.
 *
 * @throws RuntimeException When the extension is unsupported.
 *
 * @spec openspec/specs/document-rich-editing/spec.md
 */',
        'startLine' => 156,
        'endLine' => 170,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
        'aliasName' => NULL,
      ),
      'writeMetadata' => 
      array (
        'name' => 'writeMetadata',
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
            'startLine' => 185,
            'endLine' => 185,
            'startColumn' => 32,
            'endColumn' => 51,
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
            'startLine' => 185,
            'endLine' => 185,
            'startColumn' => 54,
            'endColumn' => 70,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
            'startLine' => 185,
            'endLine' => 185,
            'startColumn' => 73,
            'endColumn' => 85,
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
 * Write metadata fields, leaving unnamed fields and every other part untouched.
 *
 * @param string $packageBytes The raw package bytes.
 * @param string $extension The file extension.
 * @param array<string, string> $values Field name => new value.
 *
 * @return array{bytes: string, written: array<int, string>}
 *
 * @throws RuntimeException When the extension or a field name is unsupported.
 *
 * @spec openspec/specs/document-rich-editing/spec.md
 */',
        'startLine' => 185,
        'endLine' => 224,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
        'aliasName' => NULL,
      ),
      'existingOrEmpty' => 
      array (
        'name' => 'existingOrEmpty',
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
            'startColumn' => 35,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'package' => 
          array (
            'name' => 'package',
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
            'startColumn' => 57,
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
 * Return the existing metadata part, or a minimal well-formed one.
 *
 * A document with no metadata part is ordinary — PhpWord-generated ODF often
 * has none — so this creates one rather than refusing. The namespace
 * declarations must be present on the root or the written elements resolve to
 * nothing and the suite silently ignores them.
 *
 * @param string $packageBytes The raw package bytes.
 * @param array $package The package mapping.
 *
 * @return string The metadata XML.
 */',
        'startLine' => 239,
        'endLine' => 250,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
        'aliasName' => NULL,
      ),
      'readElement' => 
      array (
        'name' => 'readElement',
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
            'startLine' => 260,
            'endLine' => 260,
            'startColumn' => 31,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'element' => 
          array (
            'name' => 'element',
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
            'startLine' => 260,
            'endLine' => 260,
            'startColumn' => 44,
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
 * Read one element\'s text.
 *
 * @param string $xml The metadata XML.
 * @param string $element The element name.
 *
 * @return string The text, or an empty string.
 */',
        'startLine' => 260,
        'endLine' => 267,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
        'aliasName' => NULL,
      ),
      'writeElement' => 
      array (
        'name' => 'writeElement',
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
            'startLine' => 280,
            'endLine' => 280,
            'startColumn' => 32,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'element' => 
          array (
            'name' => 'element',
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
            'startLine' => 280,
            'endLine' => 280,
            'startColumn' => 45,
            'endColumn' => 59,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
            'startLine' => 280,
            'endLine' => 280,
            'startColumn' => 62,
            'endColumn' => 74,
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
 * Set one element\'s text, adding the element when it is absent.
 *
 * @param string $xml The metadata XML.
 * @param string $element The element name.
 * @param string $value The new text.
 *
 * @return string The rewritten XML.
 *
 * @throws RuntimeException When the root element cannot be found.
 */',
        'startLine' => 280,
        'endLine' => 330,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
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
            'startLine' => 341,
            'endLine' => 341,
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
 * Resolve the package mapping for an extension.
 *
 * @param string $extension The file extension.
 *
 * @return array{part: string, root: string, ns: string, fields: array<string, string>}
 *
 * @throws RuntimeException When the extension is unsupported.
 */',
        'startLine' => 341,
        'endLine' => 354,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
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