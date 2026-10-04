<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Conversion/PhpWordIo.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Conversion\PhpWordIo
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-192cf05923ba57539cbd97fd74020d6db9210a36f69088fee269f42ad48f755d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordIo',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Conversion/PhpWordIo.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Conversion',
    'name' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordIo',
    'shortName' => 'PhpWordIo',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Injectable seam over PhpWord\'s reader/writer construction.
 *
 * The reader names accepted here are the same short names PhpWord\'s own
 * IOFactory uses (`Word2007`, `MsDoc`, `ODText`, `RTF`, `HTML`), so the
 * backend\'s extension→reader map and its diagnostic messages are unchanged.
 *
 * @category  Service
 * @package   OCA\\Filinq\\Service\\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/pdf-conversion/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 54,
    'endLine' => 109,
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
      'load' => 
      array (
        'name' => 'load',
        'parameters' => 
        array (
          'path' => 
          array (
            'name' => 'path',
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
            'startColumn' => 23,
            'endColumn' => 34,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'readerName' => 
          array (
            'name' => 'readerName',
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
            'startColumn' => 37,
            'endColumn' => 54,
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
            'name' => 'PhpOffice\\PhpWord\\PhpWord',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Read a document from disk with the named PhpWord reader.
 *
 * @param string $path Absolute path to the source document.
 * @param string $readerName PhpWord reader short name (e.g. `Word2007`).
 *
 * @return PhpWord The parsed document model.
 *
 * @throws InvalidArgumentException When $readerName is not a known reader.
 *
 * @spec openspec/specs/pdf-conversion/spec.md
 */',
        'startLine' => 67,
        'endLine' => 69,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordIo',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordIo',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordIo',
        'aliasName' => NULL,
      ),
      'toHtml' => 
      array (
        'name' => 'toHtml',
        'parameters' => 
        array (
          'document' => 
          array (
            'name' => 'document',
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
            'startLine' => 80,
            'endLine' => 80,
            'startColumn' => 25,
            'endColumn' => 41,
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
 * Render a parsed document to the HTML intermediate representation.
 *
 * @param PhpWord $document Parsed document model.
 *
 * @return string HTML produced by PhpWord\'s HTML writer.
 *
 * @spec openspec/specs/pdf-conversion/spec.md
 */',
        'startLine' => 80,
        'endLine' => 82,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordIo',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordIo',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordIo',
        'aliasName' => NULL,
      ),
      'createReader' => 
      array (
        'name' => 'createReader',
        'parameters' => 
        array (
          'readerName' => 
          array (
            'name' => 'readerName',
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
            'startLine' => 93,
            'endLine' => 93,
            'startColumn' => 32,
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
            'name' => 'PhpOffice\\PhpWord\\Reader\\ReaderInterface',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Instantiate the concrete reader for a PhpWord reader short name.
 *
 * @param string $readerName PhpWord reader short name.
 *
 * @return ReaderInterface The matching reader instance.
 *
 * @throws InvalidArgumentException When $readerName is not a known reader.
 */',
        'startLine' => 93,
        'endLine' => 108,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordIo',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordIo',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordIo',
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