<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Suggestion/CategoryKeywordMapper.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Suggestion\CategoryKeywordMapper
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-3e312f447c59a8fea735cedde7f6e319031670b7e785a995bbeed86c5337f39a',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Suggestion\\CategoryKeywordMapper',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Suggestion/CategoryKeywordMapper.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Suggestion',
    'name' => 'OCA\\Filinq\\Service\\Suggestion\\CategoryKeywordMapper',
    'shortName' => 'CategoryKeywordMapper',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Matches admin-edited keyword/category rules against free text.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Suggestion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 41,
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
      'COLD_START_CONFIDENCE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Suggestion\\CategoryKeywordMapper',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Suggestion\\CategoryKeywordMapper',
        'name' => 'COLD_START_CONFIDENCE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0.4',
          'attributes' => 
          array (
            'startLine' => 49,
            'endLine' => 49,
            'startTokenPos' => 35,
            'startFilePos' => 1536,
            'endTokenPos' => 35,
            'endFilePos' => 1538,
          ),
        ),
        'docComment' => '/**
 * Fixed confidence assigned to a cold-start keyword-rule match — always
 * lower than a meaningfully history-backed suggestion.
 *
 * @var float
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 49,
        'endLine' => 49,
        'startColumn' => 2,
        'endColumn' => 42,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'match' => 
      array (
        'name' => 'match',
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
            'startLine' => 66,
            'endLine' => 66,
            'startColumn' => 24,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'rules' => 
          array (
            'name' => 'rules',
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
            'startLine' => 66,
            'endLine' => 66,
            'startColumn' => 38,
            'endColumn' => 49,
            'parameterIndex' => 1,
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
                  'name' => 'array',
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
 * Match the highest-priority enabled rule whose keyword substring-matches
 * the given text.
 *
 * @param string $text Supplier name and/or document text to match
 *                     against (case-insensitive).
 * @param array<int, array<string, mixed>> $rules Admin-authored mapping rules (each `{keywords[],
 *                                                accountCode, accountLabel?, priority?,
 *                                                enabled?}`), in any order.
 *
 * @return array<string, mixed>|null The matched suggestion (`{code, label, confidence, rationale}`),
 *                                   or null when no enabled rule matches.
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 */',
        'startLine' => 66,
        'endLine' => 86,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Suggestion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Suggestion\\CategoryKeywordMapper',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Suggestion\\CategoryKeywordMapper',
        'currentClassName' => 'OCA\\Filinq\\Service\\Suggestion\\CategoryKeywordMapper',
        'aliasName' => NULL,
      ),
      'matchRule' => 
      array (
        'name' => 'matchRule',
        'parameters' => 
        array (
          'haystack' => 
          array (
            'name' => 'haystack',
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
            'startLine' => 97,
            'endLine' => 97,
            'startColumn' => 29,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'rule' => 
          array (
            'name' => 'rule',
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
            'startLine' => 97,
            'endLine' => 97,
            'startColumn' => 47,
            'endColumn' => 57,
            'parameterIndex' => 1,
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
                  'name' => 'array',
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
 * Attempt to match a single rule\'s keywords against the haystack.
 *
 * @param string $haystack Lower-cased search text.
 * @param array<string, mixed> $rule Candidate rule (`{keywords[], accountCode, accountLabel?,
 *                                   priority?, enabled?}`).
 *
 * @return array<string, mixed>|null The matched suggestion, or null when this rule does not match.
 */',
        'startLine' => 97,
        'endLine' => 119,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Suggestion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Suggestion\\CategoryKeywordMapper',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Suggestion\\CategoryKeywordMapper',
        'currentClassName' => 'OCA\\Filinq\\Service\\Suggestion\\CategoryKeywordMapper',
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