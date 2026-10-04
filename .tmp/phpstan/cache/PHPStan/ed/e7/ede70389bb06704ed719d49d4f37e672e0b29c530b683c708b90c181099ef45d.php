<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Extraction/KvkExtractor.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Extraction\KvkExtractor
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-55b673f896e9469e3474234f7e4f2e0199b407c308d6121ac39230ce10d863d0',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Extraction/KvkExtractor.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Extraction',
    'name' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
    'shortName' => 'KvkExtractor',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Extracts an 8-digit KvK number from free text.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Extraction
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/financial-document-field-extraction/tasks.md#2-2
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 39,
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
      'LABELLED_CONFIDENCE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
        'name' => 'LABELLED_CONFIDENCE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0.85',
          'attributes' => 
          array (
            'startLine' => 46,
            'endLine' => 46,
            'startTokenPos' => 35,
            'startFilePos' => 1298,
            'endTokenPos' => 35,
            'endFilePos' => 1301,
          ),
        ),
        'docComment' => '/**
 * Confidence assigned to a labelled KvK-number match.
 *
 * @var float
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 46,
        'endLine' => 46,
        'startColumn' => 2,
        'endColumn' => 42,
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
            'startLine' => 59,
            'endLine' => 59,
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
 * Extract a KvK number labelled by "KvK" (or "Kamer van Koophandel") from text.
 *
 * @param string $text The text to search.
 *
 * @return array{value: string|null, confidence: float} The extracted
 *                                                      KvK number and its confidence, or a null value with
 *                                                      confidence 0 when no labelled candidate is found.
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 */',
        'startLine' => 59,
        'endLine' => 74,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Extraction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
        'currentClassName' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
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