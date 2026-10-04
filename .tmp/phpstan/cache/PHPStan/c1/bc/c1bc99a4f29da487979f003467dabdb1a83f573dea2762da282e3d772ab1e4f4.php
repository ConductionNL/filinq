<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/LanguageClassifier.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\LanguageClassifier
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-946d707caf58bc59a0b9d3e9a21aa77d552b53384600621f663b66f82ae2e59b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\LanguageClassifier',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/LanguageClassifier.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\LanguageClassifier',
    'shortName' => 'LanguageClassifier',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service for language detection and topic classification
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 35,
    'endLine' => 159,
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
      'DUTCH_WORDS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\LanguageClassifier',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LanguageClassifier',
        'name' => 'DUTCH_WORDS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'de\', \'het\', \'een\', \'en\', \'van\', \'is\', \'zijn\', \'op\', \'voor\', \'met\']',
          'attributes' => 
          array (
            'startLine' => 42,
            'endLine' => 53,
            'startTokenPos' => 35,
            'startFilePos' => 1148,
            'endTokenPos' => 67,
            'endFilePos' => 1239,
          ),
        ),
        'docComment' => '/**
 * Common Dutch words for language detection
 *
 * @var string[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 42,
        'endLine' => 53,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
      'ENGLISH_WORDS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\LanguageClassifier',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LanguageClassifier',
        'name' => 'ENGLISH_WORDS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'the\', \'be\', \'to\', \'of\', \'and\', \'a\', \'in\', \'that\', \'have\', \'it\']',
          'attributes' => 
          array (
            'startLine' => 60,
            'endLine' => 71,
            'startTokenPos' => 80,
            'startFilePos' => 1354,
            'endTokenPos' => 112,
            'endFilePos' => 1442,
          ),
        ),
        'docComment' => '/**
 * Common English words for language detection
 *
 * @var string[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 60,
        'endLine' => 71,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
      'TOPIC_KEYWORDS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\LanguageClassifier',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LanguageClassifier',
        'name' => 'TOPIC_KEYWORDS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'legal\' => [\'contract\', \'agreement\', \'law\', \'legal\', \'court\', \'judge\'], \'financial\' => [\'invoice\', \'payment\', \'budget\', \'financial\', \'account\', \'money\'], \'medical\' => [\'patient\', \'diagnosis\', \'treatment\', \'medical\', \'health\', \'doctor\'], \'technical\' => [\'system\', \'software\', \'technical\', \'code\', \'development\', \'api\']]',
          'attributes' => 
          array (
            'startLine' => 78,
            'endLine' => 83,
            'startTokenPos' => 125,
            'startFilePos' => 1571,
            'endTokenPos' => 223,
            'endFilePos' => 1901,
          ),
        ),
        'docComment' => '/**
 * Topic keyword mappings for classification
 *
 * @var array<string, string[]>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 78,
        'endLine' => 83,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'countWordOccurrences' => 
      array (
        'name' => 'countWordOccurrences',
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
            'startLine' => 95,
            'endLine' => 95,
            'startColumn' => 40,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'words' => 
          array (
            'name' => 'words',
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
            'startLine' => 95,
            'endLine' => 95,
            'startColumn' => 54,
            'endColumn' => 65,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Count word occurrences for a list of target words in text
 *
 * @param string $text The text to search in
 * @param array<string> $words The words to count
 *
 * @return int Total occurrence count
 *
 * @spec openspec/specs/metadata-enrichment/spec.md
 */',
        'startLine' => 95,
        'endLine' => 102,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LanguageClassifier',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LanguageClassifier',
        'currentClassName' => 'OCA\\Filinq\\Service\\LanguageClassifier',
        'aliasName' => NULL,
      ),
      'detectLanguage' => 
      array (
        'name' => 'detectLanguage',
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
            'startLine' => 113,
            'endLine' => 113,
            'startColumn' => 33,
            'endColumn' => 44,
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
 * Detect language from text content
 *
 * @param string $text Text content to analyze
 *
 * @return string|null Detected language code or null if detection fails
 *
 * @spec openspec/specs/metadata-enrichment/spec.md
 */',
        'startLine' => 113,
        'endLine' => 128,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LanguageClassifier',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LanguageClassifier',
        'currentClassName' => 'OCA\\Filinq\\Service\\LanguageClassifier',
        'aliasName' => NULL,
      ),
      'classifyTopic' => 
      array (
        'name' => 'classifyTopic',
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
            'startLine' => 139,
            'endLine' => 139,
            'startColumn' => 32,
            'endColumn' => 43,
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
 * Classify document topic based on text content
 *
 * @param string $text Text content to analyze
 *
 * @return string|null Classified topic or null if classification fails
 *
 * @spec openspec/specs/metadata-enrichment/spec.md
 */',
        'startLine' => 139,
        'endLine' => 158,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LanguageClassifier',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LanguageClassifier',
        'currentClassName' => 'OCA\\Filinq\\Service\\LanguageClassifier',
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