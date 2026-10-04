<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Extraction/DateExtractor.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Extraction\DateExtractor
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-430307eb8e84aca551bac28cdad8f15da92717ef0c1d94482dc80ef575a64de6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Extraction/DateExtractor.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Extraction',
    'name' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
    'shortName' => 'DateExtractor',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Extracts and normalises ISO/Dutch-format dates from free text.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Extraction
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/financial-document-field-extraction/tasks.md#2-3
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 43,
    'endLine' => 225,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
        'name' => 'LABELLED_CONFIDENCE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0.8',
          'attributes' => 
          array (
            'startLine' => 50,
            'endLine' => 50,
            'startTokenPos' => 45,
            'startFilePos' => 1435,
            'endTokenPos' => 45,
            'endFilePos' => 1437,
          ),
        ),
        'docComment' => '/**
 * Confidence assigned to a date found immediately after a matched label.
 *
 * @var float
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 50,
        'endLine' => 50,
        'startColumn' => 2,
        'endColumn' => 41,
      ),
      'UNLABELLED_CONFIDENCE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
        'name' => 'UNLABELLED_CONFIDENCE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0.6',
          'attributes' => 
          array (
            'startLine' => 57,
            'endLine' => 57,
            'startTokenPos' => 58,
            'startFilePos' => 1576,
            'endTokenPos' => 58,
            'endFilePos' => 1578,
          ),
        ),
        'docComment' => '/**
 * Confidence assigned to a date found without an adjacent label.
 *
 * @var float
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 2,
        'endColumn' => 43,
      ),
      'DUTCH_MONTHS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
        'name' => 'DUTCH_MONTHS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'januari\' => 1, \'februari\' => 2, \'maart\' => 3, \'april\' => 4, \'mei\' => 5, \'juni\' => 6, \'juli\' => 7, \'augustus\' => 8, \'september\' => 9, \'oktober\' => 10, \'november\' => 11, \'december\' => 12]',
          'attributes' => 
          array (
            'startLine' => 64,
            'endLine' => 77,
            'startTokenPos' => 71,
            'startFilePos' => 1731,
            'endTokenPos' => 157,
            'endFilePos' => 1945,
          ),
        ),
        'docComment' => '/**
 * Dutch month names, in genitive/nominative form, mapped to month numbers.
 *
 * @var array<string, int>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 64,
        'endLine' => 77,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
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
            'startLine' => 90,
            'endLine' => 90,
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
            'startLine' => 90,
            'endLine' => 90,
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
 * Extract the first date immediately following one of the given labels.
 *
 * @param string $text The text to search.
 * @param array<string> $labels Case-insensitive labels (e.g. "factuurdatum").
 *
 * @return array{value: string|null, confidence: float} The ISO 8601 date
 *                                                      and its confidence, or a null value with confidence 0.
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 */',
        'startLine' => 90,
        'endLine' => 115,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Extraction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
        'currentClassName' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
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
            'startLine' => 127,
            'endLine' => 127,
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
 * Extract every parseable date in the text, in document order, normalised
 * to ISO 8601 and de-duplicated.
 *
 * @param string $text The text to search.
 *
 * @return array<int, array{value: string, confidence: float}> Dates found.
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 */',
        'startLine' => 127,
        'endLine' => 149,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Extraction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
        'currentClassName' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
        'aliasName' => NULL,
      ),
      'datePattern' => 
      array (
        'name' => 'datePattern',
        'parameters' => 
        array (
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
 * Build the combined regex alternation for ISO / Dutch numeric / Dutch
 * long-form dates.
 *
 * @return string The regex alternation (without delimiters).
 */',
        'startLine' => 157,
        'endLine' => 166,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Extraction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
        'currentClassName' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
        'aliasName' => NULL,
      ),
      'normalise' => 
      array (
        'name' => 'normalise',
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
            'startLine' => 176,
            'endLine' => 176,
            'startColumn' => 29,
            'endColumn' => 39,
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
                  'name' => 'string',
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
 * Normalise a matched raw date string to ISO 8601 (`YYYY-MM-DD`).
 *
 * @param string $raw The raw matched date text.
 *
 * @return string|null The normalised date, or null when it does not
 *                     represent a valid calendar date.
 */',
        'startLine' => 176,
        'endLine' => 200,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Extraction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
        'currentClassName' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
        'aliasName' => NULL,
      ),
      'buildIsoDate' => 
      array (
        'name' => 'buildIsoDate',
        'parameters' => 
        array (
          'year' => 
          array (
            'name' => 'year',
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
            'startLine' => 212,
            'endLine' => 212,
            'startColumn' => 32,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'month' => 
          array (
            'name' => 'month',
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
            'startLine' => 212,
            'endLine' => 212,
            'startColumn' => 43,
            'endColumn' => 52,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'day' => 
          array (
            'name' => 'day',
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
            'startLine' => 212,
            'endLine' => 212,
            'startColumn' => 55,
            'endColumn' => 62,
            'parameterIndex' => 2,
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
                  'name' => 'string',
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
 * Build a validated ISO 8601 date string, or null when the parts do not
 * form a real calendar date.
 *
 * @param int $year Four-digit year.
 * @param int $month Month (1-12).
 * @param int $day Day of month.
 *
 * @return string|null The `YYYY-MM-DD` string, or null when invalid.
 */',
        'startLine' => 212,
        'endLine' => 224,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Extraction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
        'currentClassName' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
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