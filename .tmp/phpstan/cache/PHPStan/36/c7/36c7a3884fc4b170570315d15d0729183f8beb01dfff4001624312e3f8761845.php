<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Charts/TableDataNormalizer.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Charts\TableDataNormalizer
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-6dccf4e99517bc7071606016f9deac9eafa3d2a40808c37a7acb28d25edbdd57',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Charts\\TableDataNormalizer',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Charts/TableDataNormalizer.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Charts',
    'name' => 'OCA\\Filinq\\Service\\Charts\\TableDataNormalizer',
    'shortName' => 'TableDataNormalizer',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Normalizes table collections and column definitions.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/template-charts/tasks.md#task-1.2
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 42,
    'endLine' => 188,
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
      'VALID_FORMATS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\TableDataNormalizer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\TableDataNormalizer',
        'name' => 'VALID_FORMATS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'text\', \'number\', \'date\', \'currency\']',
          'attributes' => 
          array (
            'startLine' => 49,
            'endLine' => 49,
            'startTokenPos' => 45,
            'startFilePos' => 1342,
            'endTokenPos' => 56,
            'endFilePos' => 1379,
          ),
        ),
        'docComment' => '/**
 * Column formats a column definition may declare.
 *
 * @var string[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 49,
        'endLine' => 49,
        'startColumn' => 2,
        'endColumn' => 70,
      ),
      'VALID_ALIGNS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\TableDataNormalizer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\TableDataNormalizer',
        'name' => 'VALID_ALIGNS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'left\', \'right\', \'center\']',
          'attributes' => 
          array (
            'startLine' => 56,
            'endLine' => 56,
            'startTokenPos' => 69,
            'startFilePos' => 1498,
            'endTokenPos' => 77,
            'endFilePos' => 1524,
          ),
        ),
        'docComment' => '/**
 * Cell alignments a column definition may declare.
 *
 * @var string[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 56,
        'endLine' => 56,
        'startColumn' => 2,
        'endColumn' => 58,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'normalizeCollection' => 
      array (
        'name' => 'normalizeCollection',
        'parameters' => 
        array (
          'collection' => 
          array (
            'name' => 'collection',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 68,
            'endLine' => 68,
            'startColumn' => 38,
            'endColumn' => 48,
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
 * Normalize the incoming collection into a plain array of associative
 * rows, tolerating iterables and array-accessible objects.
 *
 * @param mixed $collection Raw collection value.
 *
 * @return array<int, array<string, mixed>>
 *
 * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-004
 */',
        'startLine' => 68,
        'endLine' => 94,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Charts',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\TableDataNormalizer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\TableDataNormalizer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Charts\\TableDataNormalizer',
        'aliasName' => NULL,
      ),
      'deriveColumns' => 
      array (
        'name' => 'deriveColumns',
        'parameters' => 
        array (
          'firstRow' => 
          array (
            'name' => 'firstRow',
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
            'startLine' => 106,
            'endLine' => 106,
            'startColumn' => 32,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Derive a default column list from the keys of the first row when the
 * caller supplies no explicit `columns` definition.
 *
 * @param array $firstRow The first normalized row.
 *
 * @return array<int, array{key: string, label: string}>
 *
 * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-004
 */',
        'startLine' => 106,
        'endLine' => 120,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Charts',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\TableDataNormalizer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\TableDataNormalizer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Charts\\TableDataNormalizer',
        'aliasName' => NULL,
      ),
      'normalizeColumns' => 
      array (
        'name' => 'normalizeColumns',
        'parameters' => 
        array (
          'columns' => 
          array (
            'name' => 'columns',
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
            'startLine' => 131,
            'endLine' => 131,
            'startColumn' => 35,
            'endColumn' => 48,
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
 * Normalize/validate the column definitions, skipping malformed entries.
 *
 * @param array $columns Raw column definitions.
 *
 * @return array<int, array{key: string, label: string, align: string, format: string}>
 *
 * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-004
 */',
        'startLine' => 131,
        'endLine' => 149,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Charts',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\TableDataNormalizer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\TableDataNormalizer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Charts\\TableDataNormalizer',
        'aliasName' => NULL,
      ),
      'resolveFormat' => 
      array (
        'name' => 'resolveFormat',
        'parameters' => 
        array (
          'raw' => 
          array (
            'name' => 'raw',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 158,
            'endLine' => 158,
            'startColumn' => 33,
            'endColumn' => 36,
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
 * Resolve a column\'s declared format, falling back to \'text\'.
 *
 * @param mixed $raw The declared format value.
 *
 * @return string A valid format name.
 */',
        'startLine' => 158,
        'endLine' => 165,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Charts',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\TableDataNormalizer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\TableDataNormalizer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Charts\\TableDataNormalizer',
        'aliasName' => NULL,
      ),
      'resolveAlign' => 
      array (
        'name' => 'resolveAlign',
        'parameters' => 
        array (
          'raw' => 
          array (
            'name' => 'raw',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 175,
            'endLine' => 175,
            'startColumn' => 32,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'format' => 
          array (
            'name' => 'format',
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
            'startLine' => 175,
            'endLine' => 175,
            'startColumn' => 38,
            'endColumn' => 51,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolve a column\'s alignment, defaulting from its format.
 *
 * @param mixed $raw The declared alignment value, or null when absent.
 * @param string $format The already-resolved column format.
 *
 * @return string A valid alignment name.
 */',
        'startLine' => 175,
        'endLine' => 187,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Charts',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\TableDataNormalizer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\TableDataNormalizer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Charts\\TableDataNormalizer',
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