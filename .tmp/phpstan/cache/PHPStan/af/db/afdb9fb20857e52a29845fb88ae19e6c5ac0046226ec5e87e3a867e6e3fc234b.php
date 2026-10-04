<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Extraction/IbanExtractor.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Extraction\IbanExtractor
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-466814a64f5c4c07c3122911339e8b7bb4cef09e5004d5231de69f05fd466840',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Extraction/IbanExtractor.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Extraction',
    'name' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
    'shortName' => 'IbanExtractor',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Extracts and mod-97-validates IBAN candidates from free text.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Extraction
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/financial-document-field-extraction/tasks.md#2-1
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 40,
    'endLine' => 120,
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
      'VALID_CONFIDENCE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
        'name' => 'VALID_CONFIDENCE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0.99',
          'attributes' => 
          array (
            'startLine' => 47,
            'endLine' => 47,
            'startTokenPos' => 35,
            'startFilePos' => 1383,
            'endTokenPos' => 35,
            'endFilePos' => 1386,
          ),
        ),
        'docComment' => '/**
 * Confidence assigned to a checksum-valid IBAN.
 *
 * @var float
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 47,
        'endLine' => 47,
        'startColumn' => 2,
        'endColumn' => 39,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'extract' => 
      array (
        'name' => 'extract',
        'parameters' => 
        array (
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
            'startLine' => 60,
            'endLine' => 60,
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
 * Extract the first checksum-valid IBAN from text.
 *
 * @param string $text The text to search.
 *
 * @return array{value: string|null, confidence: float} The extracted
 *                                                      IBAN and its confidence, or a null value with confidence 0
 *                                                      when no checksum-valid candidate is found.
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 */',
        'startLine' => 60,
        'endLine' => 80,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Extraction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
        'currentClassName' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
        'aliasName' => NULL,
      ),
      'isValidMod97' => 
      array (
        'name' => 'isValidMod97',
        'parameters' => 
        array (
          'iban' => 
          array (
            'name' => 'iban',
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
            'startLine' => 89,
            'endLine' => 89,
            'startColumn' => 32,
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
 * Validate an IBAN candidate using the ISO 13616 mod-97 checksum.
 *
 * @param string $iban The candidate IBAN (uppercase, no spaces).
 *
 * @return bool True when the checksum is valid.
 */',
        'startLine' => 89,
        'endLine' => 119,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Extraction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
        'currentClassName' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
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