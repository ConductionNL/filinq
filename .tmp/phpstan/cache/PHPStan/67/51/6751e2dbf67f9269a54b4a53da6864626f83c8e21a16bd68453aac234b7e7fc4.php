<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/CustomDictionaryPayloadNormaliser.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\CustomDictionaryPayloadNormaliser
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-f44178c31c0f3aa8d350d80f578b95ff30e54ea25c96c23b4bcff6c6bd43aa9c',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\CustomDictionaryPayloadNormaliser',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/CustomDictionaryPayloadNormaliser.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\CustomDictionaryPayloadNormaliser',
    'shortName' => 'CustomDictionaryPayloadNormaliser',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Pure input-shaping helpers for custom dictionary payloads.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 43,
    'endLine' => 183,
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
      'VALID_MATCH_MODES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryPayloadNormaliser',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryPayloadNormaliser',
        'name' => 'VALID_MATCH_MODES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'exact\', \'caseInsensitive\', \'wordBoundary\']',
          'attributes' => 
          array (
            'startLine' => 50,
            'endLine' => 50,
            'startTokenPos' => 35,
            'startFilePos' => 1649,
            'endTokenPos' => 43,
            'endFilePos' => 1692,
          ),
        ),
        'docComment' => '/**
 * Valid `matchMode` values (mirrors the schema enum).
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 50,
        'endLine' => 50,
        'startColumn' => 2,
        'endColumn' => 79,
      ),
      'DEFAULT_MATCH_MODE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryPayloadNormaliser',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryPayloadNormaliser',
        'name' => 'DEFAULT_MATCH_MODE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'caseInsensitive\'',
          'attributes' => 
          array (
            'startLine' => 57,
            'endLine' => 57,
            'startTokenPos' => 56,
            'startFilePos' => 1804,
            'endTokenPos' => 56,
            'endFilePos' => 1820,
          ),
        ),
        'docComment' => '/**
 * Default match mode when unset/invalid.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 2,
        'endColumn' => 53,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'sanitizeMatchMode' => 
      array (
        'name' => 'sanitizeMatchMode',
        'parameters' => 
        array (
          'mode' => 
          array (
            'name' => 'mode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 68,
            'endLine' => 68,
            'startColumn' => 36,
            'endColumn' => 46,
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
 * Sanitise a `matchMode` value against the schema enum.
 *
 * @param mixed $mode Raw value.
 *
 * @return string A value from {@see VALID_MATCH_MODES}.
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */',
        'startLine' => 68,
        'endLine' => 74,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryPayloadNormaliser',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryPayloadNormaliser',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryPayloadNormaliser',
        'aliasName' => NULL,
      ),
      'parseCsv' => 
      array (
        'name' => 'parseCsv',
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
            'startLine' => 85,
            'endLine' => 85,
            'startColumn' => 27,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Parse CSV import content into `{value, label}` rows.
 *
 * @param string $content Raw CSV content.
 *
 * @return array<int, array{value: string, label: string|null}>
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */',
        'startLine' => 85,
        'endLine' => 99,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryPayloadNormaliser',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryPayloadNormaliser',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryPayloadNormaliser',
        'aliasName' => NULL,
      ),
      'parseList' => 
      array (
        'name' => 'parseList',
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
            'startLine' => 111,
            'endLine' => 111,
            'startColumn' => 28,
            'endColumn' => 42,
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
 * Parse newline-separated plain-text import content into
 * `{value, label}` rows.
 *
 * @param string $content Raw plain-text content.
 *
 * @return array<int, array{value: string, label: string|null}>
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */',
        'startLine' => 111,
        'endLine' => 121,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryPayloadNormaliser',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryPayloadNormaliser',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryPayloadNormaliser',
        'aliasName' => NULL,
      ),
      'stripFrameworkParams' => 
      array (
        'name' => 'stripFrameworkParams',
        'parameters' => 
        array (
          'data' => 
          array (
            'name' => 'data',
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
            'startLine' => 132,
            'endLine' => 132,
            'startColumn' => 39,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Strip framework-injected request params before persistence.
 *
 * @param array<string, mixed> $data Raw incoming data.
 *
 * @return array<string, mixed>
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */',
        'startLine' => 132,
        'endLine' => 135,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryPayloadNormaliser',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryPayloadNormaliser',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryPayloadNormaliser',
        'aliasName' => NULL,
      ),
      'stringOrNull' => 
      array (
        'name' => 'stringOrNull',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 146,
            'endLine' => 146,
            'startColumn' => 31,
            'endColumn' => 42,
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
 * Coerce a value to a trimmed string, or null when blank/absent.
 *
 * @param mixed $value Raw value.
 *
 * @return string|null
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */',
        'startLine' => 146,
        'endLine' => 157,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryPayloadNormaliser',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryPayloadNormaliser',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryPayloadNormaliser',
        'aliasName' => NULL,
      ),
      'splitLines' => 
      array (
        'name' => 'splitLines',
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
            'startLine' => 174,
            'endLine' => 174,
            'startColumn' => 30,
            'endColumn' => 44,
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
 * Split raw import content into lines.
 *
 * Normalises line endings so a Windows-authored CSV/list parses the same
 * as a Unix one, then drops exactly one trailing newline artifact (a
 * pasted textarea value or an uploaded file almost always ends with one)
 * so it is not counted as an extra blank line. Every OTHER
 * blank/whitespace-only line is preserved as a row — importTerms() counts
 * it toward `skipped` per REQ-DDCDR-005\'s scenario numbers (blank lines
 * are part of the reported total, not silently dropped pre-count).
 *
 * @param string $content Raw content.
 *
 * @return array<int, string>
 */',
        'startLine' => 174,
        'endLine' => 182,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryPayloadNormaliser',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryPayloadNormaliser',
        'currentClassName' => 'OCA\\Filinq\\Service\\CustomDictionaryPayloadNormaliser',
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