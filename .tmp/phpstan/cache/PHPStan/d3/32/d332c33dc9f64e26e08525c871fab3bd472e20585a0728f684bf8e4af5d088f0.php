<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Charts/TableHtmlRenderer.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Charts\TableHtmlRenderer
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-5fa579af7abba3ef1fb028348190d4f2321b54789437fc57d8e1f8d4df9d31a3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Charts\\TableHtmlRenderer',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Charts/TableHtmlRenderer.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Charts',
    'name' => 'OCA\\Filinq\\Service\\Charts\\TableHtmlRenderer',
    'shortName' => 'TableHtmlRenderer',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Renders a collection + column definition to a styled HTML table.
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
    'endLine' => 213,
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
      'DEFAULT_MAX_ROWS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\TableHtmlRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\TableHtmlRenderer',
        'name' => 'DEFAULT_MAX_ROWS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '500',
          'attributes' => 
          array (
            'startLine' => 50,
            'endLine' => 50,
            'startTokenPos' => 35,
            'startFilePos' => 1571,
            'endTokenPos' => 35,
            'endFilePos' => 1573,
          ),
        ),
        'docComment' => '/**
 * Default cap on the number of rows rendered (guardrail against
 * unbounded collections blowing up document size).
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 50,
        'endLine' => 50,
        'startColumn' => 2,
        'endColumn' => 38,
      ),
    ),
    'immediateProperties' => 
    array (
      'normalizer' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\TableHtmlRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\TableHtmlRenderer',
        'name' => 'normalizer',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Charts\\TableDataNormalizer',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Row and column shape normalizer.
 *
 * @var TableDataNormalizer
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 2,
        'endColumn' => 50,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'cells' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\TableHtmlRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\TableHtmlRenderer',
        'name' => 'cells',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Charts\\TableCellFormatter',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Cell value formatter.
 *
 * @var TableCellFormatter
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 64,
        'endLine' => 64,
        'startColumn' => 2,
        'endColumn' => 44,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor.
 *
 * Collaborators are pure, stateless helpers with no I/O, so they are
 * composed here rather than injected — this keeps the public constructor
 * argument-free for both the DI container and direct instantiation.
 *
 * @return void
 */',
        'startLine' => 75,
        'endLine' => 79,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Charts',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\TableHtmlRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\TableHtmlRenderer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Charts\\TableHtmlRenderer',
        'aliasName' => NULL,
      ),
      'render' => 
      array (
        'name' => 'render',
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
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 25,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 38,
            'endColumn' => 51,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 99,
                'endLine' => 99,
                'startTokenPos' => 123,
                'startFilePos' => 3107,
                'endTokenPos' => 124,
                'endFilePos' => 3108,
              ),
            ),
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
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 54,
            'endColumn' => 72,
            'parameterIndex' => 2,
            'isOptional' => true,
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
 * Render a collection as an HTML table.
 *
 * Never throws: malformed columns are skipped individually, and an
 * empty/invalid collection renders a localised empty-state row.
 *
 * @param mixed $collection Iterable collection of associative rows
 *                          (array of arrays/array-accessible objects).
 * @param array $columns `[{key, label, align?, format?}, ...]`.
 *                       When empty, columns are derived from the
 *                       keys of the first row.
 * @param array $options Rendering options: maxRows (int), emptyText
 *                       (string override for the empty-state row).
 *
 * @return string HTML `<table>` markup.
 *
 * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-004
 */',
        'startLine' => 99,
        'endLine' => 133,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Charts',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\TableHtmlRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\TableHtmlRenderer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Charts\\TableHtmlRenderer',
        'aliasName' => NULL,
      ),
      'renderBody' => 
      array (
        'name' => 'renderBody',
        'parameters' => 
        array (
          'rows' => 
          array (
            'name' => 'rows',
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
            'startLine' => 145,
            'endLine' => 145,
            'startColumn' => 30,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 145,
            'endLine' => 145,
            'startColumn' => 43,
            'endColumn' => 56,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'emptyText' => 
          array (
            'name' => 'emptyText',
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
            'startLine' => 145,
            'endLine' => 145,
            'startColumn' => 59,
            'endColumn' => 75,
            'parameterIndex' => 2,
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
 * Render the `<tbody>` row content: the empty-state row, or one `<tr>`
 * per data row.
 *
 * @param array $rows Normalized rows (already row-limited).
 * @param array $columns Normalized columns.
 * @param string $emptyText Empty-state message.
 *
 * @return string HTML markup fragment.
 */',
        'startLine' => 145,
        'endLine' => 159,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Charts',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\TableHtmlRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\TableHtmlRenderer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Charts\\TableHtmlRenderer',
        'aliasName' => NULL,
      ),
      'renderHead' => 
      array (
        'name' => 'renderHead',
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
            'startLine' => 168,
            'endLine' => 168,
            'startColumn' => 30,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Render the `<thead>` row.
 *
 * @param array $columns Normalized columns.
 *
 * @return string HTML markup.
 */',
        'startLine' => 168,
        'endLine' => 179,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Charts',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\TableHtmlRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\TableHtmlRenderer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Charts\\TableHtmlRenderer',
        'aliasName' => NULL,
      ),
      'renderRow' => 
      array (
        'name' => 'renderRow',
        'parameters' => 
        array (
          'row' => 
          array (
            'name' => 'row',
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
            'startLine' => 189,
            'endLine' => 189,
            'startColumn' => 29,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 189,
            'endLine' => 189,
            'startColumn' => 41,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Render a single `<tr>` for a data row.
 *
 * @param array $row A single normalized row.
 * @param array $columns Normalized columns.
 *
 * @return string HTML markup.
 */',
        'startLine' => 189,
        'endLine' => 201,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Charts',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\TableHtmlRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\TableHtmlRenderer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Charts\\TableHtmlRenderer',
        'aliasName' => NULL,
      ),
      'escape' => 
      array (
        'name' => 'escape',
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
            'startLine' => 210,
            'endLine' => 210,
            'startColumn' => 26,
            'endColumn' => 38,
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
 * Escape a data-derived value for safe embedding as HTML text content.
 *
 * @param string $value Raw text.
 *
 * @return string Escaped text.
 */',
        'startLine' => 210,
        'endLine' => 212,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Charts',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\TableHtmlRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\TableHtmlRenderer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Charts\\TableHtmlRenderer',
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