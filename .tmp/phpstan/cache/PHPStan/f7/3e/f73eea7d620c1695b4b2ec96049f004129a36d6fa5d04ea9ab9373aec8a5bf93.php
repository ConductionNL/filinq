<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/PdfStreamReaderFactory.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\PdfStreamReaderFactory
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-c48733100ae44b73a8ab1acee989d5856d8e560f54e0c63852c0c30c3ad1dd19',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\PdfStreamReaderFactory',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/PdfStreamReaderFactory.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\PdfStreamReaderFactory',
    'shortName' => 'PdfStreamReaderFactory',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Builds FPDI stream readers over in-memory PDF byte strings.
 *
 * @category  Service
 * @package   OCA\\Filinq\\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 45,
    'endLine' => 75,
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
      'DEFAULT_MAX_MEMORY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PdfStreamReaderFactory',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PdfStreamReaderFactory',
        'name' => 'DEFAULT_MAX_MEMORY',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '2097152',
          'attributes' => 
          array (
            'startLine' => 51,
            'endLine' => 51,
            'startTokenPos' => 45,
            'startFilePos' => 1586,
            'endTokenPos' => 45,
            'endFilePos' => 1592,
          ),
        ),
        'docComment' => '/**
 * Bytes FPDI keeps in memory before spilling the temp stream to disk.
 * Matches FPDI\'s own default for createByString().
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 51,
        'endLine' => 51,
        'startColumn' => 2,
        'endColumn' => 44,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'fromString' => 
      array (
        'name' => 'fromString',
        'parameters' => 
        array (
          'content' => 
          array (
            'name' => 'content',
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
            'startLine' => 64,
            'endLine' => 64,
            'startColumn' => 29,
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
            'name' => 'setasign\\Fpdi\\PdfParser\\StreamReader',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Build a stream reader over a raw PDF byte string.
 *
 * @param string $content Raw PDF bytes.
 *
 * @return StreamReader Reader positioned at the start of $content.
 *
 * @throws InvalidArgumentException When the temp stream cannot be opened.
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 */',
        'startLine' => 64,
        'endLine' => 74,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PdfStreamReaderFactory',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PdfStreamReaderFactory',
        'currentClassName' => 'OCA\\Filinq\\Service\\PdfStreamReaderFactory',
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