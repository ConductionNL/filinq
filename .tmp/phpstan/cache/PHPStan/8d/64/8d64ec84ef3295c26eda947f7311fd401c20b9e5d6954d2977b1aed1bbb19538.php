<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Extraction/AmountExtractor.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Extraction\AmountExtractor
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-21644aec7e4f2dbf3104ab254370a06510230f54a8b2b5600b8615dc0b9ff18f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Extraction/AmountExtractor.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Extraction',
    'name' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
    'shortName' => 'AmountExtractor',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Extracts and parses monetary amounts (Dutch/Anglo grouping) from free text.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Extraction
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/financial-document-field-extraction/tasks.md#2-4
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 39,
    'endLine' => 206,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
        'name' => 'LABELLED_CONFIDENCE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0.75',
          'attributes' => 
          array (
            'startLine' => 46,
            'endLine' => 46,
            'startTokenPos' => 35,
            'startFilePos' => 1367,
            'endTokenPos' => 35,
            'endFilePos' => 1370,
          ),
        ),
        'docComment' => '/**
 * Confidence assigned to an amount found immediately after a matched label.
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
      'AMOUNT_TOKEN' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
        'name' => 'AMOUNT_TOKEN',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'(?:€|EUR)?\\s?[0-9]{1,3}(?:[.,][0-9]{3})*[.,][0-9]{2}\'',
          'attributes' => 
          array (
            'startLine' => 55,
            'endLine' => 55,
            'startTokenPos' => 48,
            'startFilePos' => 1590,
            'endTokenPos' => 48,
            'endFilePos' => 1645,
          ),
        ),
        'docComment' => '/**
 * The raw amount-token regex (without delimiters): an optional currency
 * marker followed by digits, optional thousands groups, and a decimal
 * part.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 55,
        'endLine' => 55,
        'startColumn' => 2,
        'endColumn' => 87,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'parseAmount' => 
      array (
        'name' => 'parseAmount',
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
            'startLine' => 66,
            'endLine' => 66,
            'startColumn' => 30,
            'endColumn' => 40,
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
                  'name' => 'float',
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Parse a single raw amount token (Dutch or Anglo grouping) to a float.
 *
 * @param string $raw The raw amount text (e.g. "€ 1.234,56", "1,234.56").
 *
 * @return float|null The parsed numeric value, or null when unparseable.
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 */',
        'startLine' => 66,
        'endLine' => 78,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Extraction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
        'currentClassName' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
        'aliasName' => NULL,
      ),
      'normaliseGrouping' => 
      array (
        'name' => 'normaliseGrouping',
        'parameters' => 
        array (
          'cleaned' => 
          array (
            'name' => 'cleaned',
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
            'startLine' => 88,
            'endLine' => 88,
            'startColumn' => 37,
            'endColumn' => 51,
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
 * Normalise Dutch/Anglo thousands+decimal grouping to a plain
 * dot-decimal numeric string.
 *
 * @param string $cleaned Amount text with currency markers/spaces stripped.
 *
 * @return string The normalised (dot-decimal) numeric string.
 */',
        'startLine' => 88,
        'endLine' => 119,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Extraction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
        'currentClassName' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
        'aliasName' => NULL,
      ),
      'normaliseMixedGrouping' => 
      array (
        'name' => 'normaliseMixedGrouping',
        'parameters' => 
        array (
          'cleaned' => 
          array (
            'name' => 'cleaned',
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
            'startLine' => 129,
            'endLine' => 129,
            'startColumn' => 42,
            'endColumn' => 56,
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
 * Normalise grouping when both `,` and `.` are present: whichever
 * separator appears last is the decimal separator.
 *
 * @param string $cleaned Amount text containing both `,` and `.`.
 *
 * @return string The normalised (dot-decimal) numeric string.
 */',
        'startLine' => 129,
        'endLine' => 138,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Extraction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
        'currentClassName' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
        'aliasName' => NULL,
      ),
      'extractAll' => 
      array (
        'name' => 'extractAll',
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
            'startLine' => 149,
            'endLine' => 149,
            'startColumn' => 29,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Extract every amount token in the text, in document order.
 *
 * @param string $text The text to search.
 *
 * @return array<int, array{raw: string, value: float}> Amounts found.
 *
 * @spec openspec/changes/financial-document-field-extraction/tasks.md#2-4
 */',
        'startLine' => 149,
        'endLine' => 166,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Extraction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
        'currentClassName' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
        'aliasName' => NULL,
      ),
      'extractLabelled' => 
      array (
        'name' => 'extractLabelled',
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
            'startLine' => 179,
            'endLine' => 179,
            'startColumn' => 34,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'labels' => 
          array (
            'name' => 'labels',
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
            'startLine' => 179,
            'endLine' => 179,
            'startColumn' => 48,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Extract the first amount immediately following one of the given labels.
 *
 * @param string $text The text to search.
 * @param array<string> $labels Case-insensitive labels (e.g. "totaal").
 *
 * @return array{value: float|null, confidence: float} The amount and its
 *                                                     confidence, or a null value with confidence 0.
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 */',
        'startLine' => 179,
        'endLine' => 205,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Extraction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
        'currentClassName' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
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