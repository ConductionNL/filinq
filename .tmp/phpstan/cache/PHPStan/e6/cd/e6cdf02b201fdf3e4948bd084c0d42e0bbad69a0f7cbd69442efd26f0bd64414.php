<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/PackagePartIo.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Editing\PackagePartIo
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-66e173e46f3c9ddcd67764198a42437d39e1b76052a93df760afc27b06aeda56',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Editing\\PackagePartIo',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/PackagePartIo.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Editing',
    'name' => 'OCA\\Filinq\\Service\\Editing\\PackagePartIo',
    'shortName' => 'PackagePartIo',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Single-entry package IO, shared by every codec that edits a document in place.
 *
 * Extracted from {@see PackageCodec} when metadata editing arrived: metadata lives
 * in a DIFFERENT part (`docProps/core.xml`, `meta.xml`) from the body, so the
 * choice was one shared reader or two divergent copies of the same ZipArchive
 * dance. Divergence here would be expensive — the reason untouched parts survive
 * an edit is a property of exactly this code.
 *
 * `ZipArchive` copies untouched entries\' raw compressed data rather than
 * recompressing them, which is what keeps the rest of the package — and ODF\'s
 * uncompressed leading `mimetype` entry — intact.
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
 * @spec openspec/specs/document-editing/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 57,
    'endLine' => 228,
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
      'listParts' => 
      array (
        'name' => 'listParts',
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
            'startLine' => 74,
            'endLine' => 74,
            'startColumn' => 28,
            'endColumn' => 47,
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
 * List every entry in a ZIP package.
 *
 * Multi-part formats need this: a `.pptx` keeps one part PER SLIDE and one
 * per notes page, so "which slides exist" is a question about the package\'s
 * contents rather than something a caller can be asked to know.
 *
 * @param string $packageBytes The raw package bytes.
 *
 * @return array<int, string> The entry names.
 *
 * @throws RuntimeException When the package cannot be read.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
 */',
        'startLine' => 74,
        'endLine' => 99,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackagePartIo',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackagePartIo',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackagePartIo',
        'aliasName' => NULL,
      ),
      'readPart' => 
      array (
        'name' => 'readPart',
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
            'startLine' => 113,
            'endLine' => 113,
            'startColumn' => 27,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'part' => 
          array (
            'name' => 'part',
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
            'startLine' => 113,
            'endLine' => 113,
            'startColumn' => 49,
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
 * Read one entry from a ZIP package.
 *
 * @param string $packageBytes The raw package bytes.
 * @param string $part The entry name.
 *
 * @return string The entry contents.
 *
 * @throws RuntimeException When the package or the entry cannot be read.
 *
 * @spec openspec/specs/document-editing/spec.md
 */',
        'startLine' => 113,
        'endLine' => 135,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackagePartIo',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackagePartIo',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackagePartIo',
        'aliasName' => NULL,
      ),
      'hasPart' => 
      array (
        'name' => 'hasPart',
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
            'startLine' => 151,
            'endLine' => 151,
            'startColumn' => 26,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'part' => 
          array (
            'name' => 'part',
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
            'startLine' => 151,
            'endLine' => 151,
            'startColumn' => 48,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Whether a package carries an entry.
 *
 * Distinct from catching {@see readPart()}\'s exception, because "this document
 * has no metadata part yet" is an ordinary state that must be handled by
 * creating one — not an error to be reported to a user.
 *
 * @param string $packageBytes The raw package bytes.
 * @param string $part The entry name.
 *
 * @return bool True when the entry exists.
 *
 * @spec openspec/specs/document-editing/spec.md
 */',
        'startLine' => 151,
        'endLine' => 167,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackagePartIo',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackagePartIo',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackagePartIo',
        'aliasName' => NULL,
      ),
      'writePart' => 
      array (
        'name' => 'writePart',
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
            'startLine' => 182,
            'endLine' => 182,
            'startColumn' => 28,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'part' => 
          array (
            'name' => 'part',
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
            'startLine' => 182,
            'endLine' => 182,
            'startColumn' => 50,
            'endColumn' => 61,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
            'startLine' => 182,
            'endLine' => 182,
            'startColumn' => 64,
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
 * Write one entry back into a ZIP package, leaving every other entry as-is.
 *
 * @param string $packageBytes The raw package bytes.
 * @param string $part The entry name.
 * @param string $xml The new entry contents.
 *
 * @return string The rewritten package bytes.
 *
 * @throws RuntimeException When the package cannot be rewritten.
 *
 * @spec openspec/specs/document-editing/spec.md
 */',
        'startLine' => 182,
        'endLine' => 207,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackagePartIo',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackagePartIo',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackagePartIo',
        'aliasName' => NULL,
      ),
      'spill' => 
      array (
        'name' => 'spill',
        'parameters' => 
        array (
          'bytes' => 
          array (
            'name' => 'bytes',
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
            'startLine' => 218,
            'endLine' => 218,
            'startColumn' => 25,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Spill bytes to a temporary file, because `ZipArchive` has no in-memory mode.
 *
 * @param string $bytes The bytes to spill.
 *
 * @return string The temporary file path.
 *
 * @throws RuntimeException When no temporary file can be created.
 */',
        'startLine' => 218,
        'endLine' => 227,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\PackagePartIo',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\PackagePartIo',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\PackagePartIo',
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