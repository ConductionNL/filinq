<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/MergedPdfDocument.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\MergedPdfDocument
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-4efb9d00ca9180731968c30eb163abb048f35436a1accf06d5afffaf355582fa',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\MergedPdfDocument',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/MergedPdfDocument.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\MergedPdfDocument',
    'shortName' => 'MergedPdfDocument',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * An FPDI document that can carry bookmarks.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 46,
    'endLine' => 196,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'setasign\\Fpdi\\Fpdi',
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
      'outlines' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\MergedPdfDocument',
        'implementingClassName' => 'OCA\\Filinq\\Service\\MergedPdfDocument',
        'name' => 'outlines',
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
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 72,
            'endLine' => 72,
            'startTokenPos' => 80,
            'startFilePos' => 2699,
            'endTokenPos' => 81,
            'endFilePos' => 2700,
          ),
        ),
        'docComment' => '/**
 * The outline entries, in document order.
 *
 * @var array<int, array<string, mixed>>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 72,
        'endLine' => 72,
        'startColumn' => 2,
        'endColumn' => 30,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'outlineRoot' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\MergedPdfDocument',
        'implementingClassName' => 'OCA\\Filinq\\Service\\MergedPdfDocument',
        'name' => 'outlineRoot',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '0',
          'attributes' => 
          array (
            'startLine' => 79,
            'endLine' => 79,
            'startTokenPos' => 94,
            'startFilePos' => 2823,
            'endTokenPos' => 94,
            'endFilePos' => 2823,
          ),
        ),
        'docComment' => '/**
 * The object number the outline root got, once it is written.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 79,
        'endLine' => 79,
        'startColumn' => 2,
        'endColumn' => 30,
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
      'addBookmark' => 
      array (
        'name' => 'addBookmark',
        'parameters' => 
        array (
          'label' => 
          array (
            'name' => 'label',
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
            'startLine' => 91,
            'endLine' => 91,
            'startColumn' => 30,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'page' => 
          array (
            'name' => 'page',
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
            'startLine' => 91,
            'endLine' => 91,
            'startColumn' => 45,
            'endColumn' => 53,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Add one bookmark, pointing at a page.
 *
 * @param string $label What the entry says.
 * @param int $page The page it points at, from 1.
 *
 * @return void
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */',
        'startLine' => 91,
        'endLine' => 94,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\MergedPdfDocument',
        'implementingClassName' => 'OCA\\Filinq\\Service\\MergedPdfDocument',
        'currentClassName' => 'OCA\\Filinq\\Service\\MergedPdfDocument',
        'aliasName' => NULL,
      ),
      'bookmarks' => 
      array (
        'name' => 'bookmarks',
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
 * The bookmarks this document carries.
 *
 * @return array<int, array<string, mixed>> The entries.
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */',
        'startLine' => 103,
        'endLine' => 106,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\MergedPdfDocument',
        'implementingClassName' => 'OCA\\Filinq\\Service\\MergedPdfDocument',
        'currentClassName' => 'OCA\\Filinq\\Service\\MergedPdfDocument',
        'aliasName' => NULL,
      ),
      'putOutlines' => 
      array (
        'name' => 'putOutlines',
        'parameters' => 
        array (
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
 * Write the outline objects.
 *
 * One flat level: every entry is a child of the root, in order, with the
 * previous and next links FPDF\'s own bookmark implementation writes.
 *
 * @return void
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */',
        'startLine' => 118,
        'endLine' => 154,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\MergedPdfDocument',
        'implementingClassName' => 'OCA\\Filinq\\Service\\MergedPdfDocument',
        'currentClassName' => 'OCA\\Filinq\\Service\\MergedPdfDocument',
        'aliasName' => NULL,
      ),
      '_putresources' => 
      array (
        'name' => '_putresources',
        'parameters' => 
        array (
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
 * Hook the outline objects into the document\'s resource pass.
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName) The name is FPDF\'s, not ours.
 * `_putresources` is the method FPDF calls during output, and this class
 * exists to override it. Renaming it to `putResources` does not rename the
 * call inside the parent: FPDF would go on calling its own `_putresources`,
 * this body would never run, and every merged bundle would come out with no
 * bookmarks and no error. The lowercase name IS the contract.
 *
 * @return void
 */',
        'startLine' => 168,
        'endLine' => 172,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\MergedPdfDocument',
        'implementingClassName' => 'OCA\\Filinq\\Service\\MergedPdfDocument',
        'currentClassName' => 'OCA\\Filinq\\Service\\MergedPdfDocument',
        'aliasName' => NULL,
      ),
      '_putcatalog' => 
      array (
        'name' => '_putcatalog',
        'parameters' => 
        array (
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
 * Name the outline root in the catalog, so a reader opens the bookmarks.
 *
 * Writing the outline objects without this line produces a file that HAS
 * bookmarks and shows none: the objects exist and nothing points at them.
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName) The name is FPDF\'s, not ours.
 * See `_putresources()` above: renaming an override FPDF calls by name
 * silently stops it being called.
 *
 * @return void
 */',
        'startLine' => 186,
        'endLine' => 195,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\MergedPdfDocument',
        'implementingClassName' => 'OCA\\Filinq\\Service\\MergedPdfDocument',
        'currentClassName' => 'OCA\\Filinq\\Service\\MergedPdfDocument',
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