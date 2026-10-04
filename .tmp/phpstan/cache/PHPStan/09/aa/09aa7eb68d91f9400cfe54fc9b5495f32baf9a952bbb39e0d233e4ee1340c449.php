<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Charts/ChartSvgRenderer.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Charts\ChartSvgRenderer
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-ada635745680c9194be76218478a970e3fc4da934949400ff55d5292ec06ddf6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Charts/ChartSvgRenderer.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Charts',
    'name' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
    'shortName' => 'ChartSvgRenderer',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Renders bar, line, and pie charts as self-contained, deterministic SVG.
 *
 * Data shape: `{labels: string[], series: [{name: string, values: (int|float|null)[]}]}`.
 * Pie charts use only the first series. Horizontal bar orientation and donut
 * rendering are options on the `bar` and `pie` types respectively (not
 * separate chart types), keeping the supported type set at exactly
 * bar/line/pie per the template-charts spec (REQ-DDTCH-001).
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/template-charts/tasks.md#task-1.1
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 54,
    'endLine' => 320,
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
      'SUPPORTED_TYPES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'name' => 'SUPPORTED_TYPES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'bar\', \'line\', \'pie\']',
          'attributes' => 
          array (
            'startLine' => 61,
            'endLine' => 61,
            'startTokenPos' => 35,
            'startFilePos' => 2250,
            'endTokenPos' => 43,
            'endFilePos' => 2271,
          ),
        ),
        'docComment' => '/**
 * Chart types supported by this renderer.
 *
 * @var string[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 61,
        'endLine' => 61,
        'startColumn' => 2,
        'endColumn' => 55,
      ),
      'DEFAULT_WIDTH' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'name' => 'DEFAULT_WIDTH',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '600',
          'attributes' => 
          array (
            'startLine' => 68,
            'endLine' => 68,
            'startTokenPos' => 56,
            'startFilePos' => 2390,
            'endTokenPos' => 56,
            'endFilePos' => 2392,
          ),
        ),
        'docComment' => '/**
 * Default canvas width in SVG user units (roughly px).
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 68,
        'endLine' => 68,
        'startColumn' => 2,
        'endColumn' => 35,
      ),
      'DEFAULT_HEIGHT' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'name' => 'DEFAULT_HEIGHT',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '300',
          'attributes' => 
          array (
            'startLine' => 75,
            'endLine' => 75,
            'startTokenPos' => 69,
            'startFilePos' => 2513,
            'endTokenPos' => 69,
            'endFilePos' => 2515,
          ),
        ),
        'docComment' => '/**
 * Default canvas height in SVG user units (roughly px).
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 75,
        'endLine' => 75,
        'startColumn' => 2,
        'endColumn' => 36,
      ),
      'DEFAULT_MAX_POINTS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'name' => 'DEFAULT_MAX_POINTS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '1000',
          'attributes' => 
          array (
            'startLine' => 83,
            'endLine' => 83,
            'startTokenPos' => 82,
            'startFilePos' => 2716,
            'endTokenPos' => 82,
            'endFilePos' => 2719,
          ),
        ),
        'docComment' => '/**
 * Default cap on the number of data points (labels) per chart.
 * Overridable via the `filinq.charts.max_points` app config value.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 83,
        'endLine' => 83,
        'startColumn' => 2,
        'endColumn' => 41,
      ),
    ),
    'immediateProperties' => 
    array (
      'lastWarning' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'name' => 'lastWarning',
        'modifiers' => 4,
        'type' => 
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
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 93,
            'endLine' => 93,
            'startTokenPos' => 96,
            'startFilePos' => 3067,
            'endTokenPos' => 96,
            'endFilePos' => 3070,
          ),
        ),
        'docComment' => '/**
 * The failure reason recorded by the most recent {@see render()} call
 * that fell back to a placeholder, or null when the last call rendered
 * a real chart. Callers (the Twig `chart()` function) read this
 * immediately after `render()` to surface a generation warning.
 *
 * @var string|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 93,
        'endLine' => 93,
        'startColumn' => 2,
        'endColumn' => 37,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'normalizer' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'name' => 'normalizer',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Charts\\ChartDataNormalizer',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Chart data validator/normalizer.
 *
 * @var ChartDataNormalizer
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 100,
        'endLine' => 100,
        'startColumn' => 2,
        'endColumn' => 50,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'palette' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'name' => 'palette',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Charts\\ChartPalette',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Color palette resolver.
 *
 * @var ChartPalette
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 107,
        'endLine' => 107,
        'startColumn' => 2,
        'endColumn' => 40,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'svg' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'name' => 'svg',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Charts\\SvgPrimitives',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * SVG element builders (used for the placeholder box).
 *
 * @var SvgPrimitives
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 114,
        'endLine' => 114,
        'startColumn' => 2,
        'endColumn' => 37,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'bar' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'name' => 'bar',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Charts\\BarChartRenderer',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Bar chart renderer.
 *
 * @var BarChartRenderer
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 121,
        'endLine' => 121,
        'startColumn' => 2,
        'endColumn' => 40,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'line' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'name' => 'line',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Charts\\LineChartRenderer',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Line chart renderer.
 *
 * @var LineChartRenderer
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 128,
        'endLine' => 128,
        'startColumn' => 2,
        'endColumn' => 42,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'pie' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'name' => 'pie',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Charts\\PieChartRenderer',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Pie/donut chart renderer.
 *
 * @var PieChartRenderer
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 135,
        'endLine' => 135,
        'startColumn' => 2,
        'endColumn' => 40,
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
        'startLine' => 146,
        'endLine' => 154,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Charts',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'aliasName' => NULL,
      ),
      'render' => 
      array (
        'name' => 'render',
        'parameters' => 
        array (
          'type' => 
          array (
            'name' => 'type',
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
            'startLine' => 177,
            'endLine' => 177,
            'startColumn' => 25,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 177,
            'endLine' => 177,
            'startColumn' => 39,
            'endColumn' => 49,
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
                'startLine' => 177,
                'endLine' => 177,
                'startTokenPos' => 282,
                'startFilePos' => 5425,
                'endTokenPos' => 283,
                'endFilePos' => 5426,
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
            'startLine' => 177,
            'endLine' => 177,
            'startColumn' => 52,
            'endColumn' => 70,
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
 * Render a chart as self-contained SVG.
 *
 * Never throws: invalid type/shape or empty data degrade to a visible
 * placeholder box inside the returned SVG rather than an exception, so
 * callers (the Twig `chart()` function) can always embed the result.
 * Check {@see getLastWarning()} immediately afterwards to detect a
 * placeholder fallback.
 *
 * @param string $type Chart type: \'bar\', \'line\', or \'pie\'.
 * @param array $data `{labels: string[], series: [{name, values}]}`.
 * @param array $options Rendering options: title, width, height, palette
 *                       (hex[] override), showLegend (bool), valueFormat
 *                       (\'integer\'|\'decimal:N\'|\'currency\'|\'percent\'),
 *                       orientation (\'vertical\'|\'horizontal\', bar only),
 *                       donut (bool, pie only), maxPoints (int).
 *
 * @return string SVG markup (starts with `<svg`).
 *
 * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-001
 */',
        'startLine' => 177,
        'endLine' => 214,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Charts',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'aliasName' => NULL,
      ),
      'getLastWarning' => 
      array (
        'name' => 'getLastWarning',
        'parameters' => 
        array (
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
 * The failure reason from the most recent {@see render()} call, or null
 * when it rendered a real chart (no placeholder fallback).
 *
 * @return string|null
 *
 * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-002
 */',
        'startLine' => 224,
        'endLine' => 226,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Charts',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'aliasName' => NULL,
      ),
      'delegate' => 
      array (
        'name' => 'delegate',
        'parameters' => 
        array (
          'type' => 
          array (
            'name' => 'type',
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
            'startLine' => 242,
            'endLine' => 242,
            'startColumn' => 3,
            'endColumn' => 14,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'normalized' => 
          array (
            'name' => 'normalized',
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
            'startLine' => 243,
            'endLine' => 243,
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'palette' => 
          array (
            'name' => 'palette',
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
            'startLine' => 244,
            'endLine' => 244,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'width' => 
          array (
            'name' => 'width',
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
            'startLine' => 245,
            'endLine' => 245,
            'startColumn' => 3,
            'endColumn' => 12,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'height' => 
          array (
            'name' => 'height',
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
            'startLine' => 246,
            'endLine' => 246,
            'startColumn' => 3,
            'endColumn' => 13,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
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
            'startLine' => 247,
            'endLine' => 247,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 5,
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
                  'name' => 'OCA\\Filinq\\Service\\Charts\\ChartRenderError',
                  'isIdentifier' => false,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Hand a normalized payload to the renderer for its chart type.
 *
 * @param string $type Chart type (already validated as supported).
 * @param array $normalized Normalized `{labels, series}` data.
 * @param array $palette Ordered hex colors.
 * @param int $width Canvas width.
 * @param int $height Canvas height.
 * @param array $options Rendering options.
 *
 * @return string|ChartRenderError SVG markup, or the reason no chart could
 *                                 be drawn.
 */',
        'startLine' => 241,
        'endLine' => 277,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Charts',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'aliasName' => NULL,
      ),
      'renderPlaceholder' => 
      array (
        'name' => 'renderPlaceholder',
        'parameters' => 
        array (
          'width' => 
          array (
            'name' => 'width',
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
            'startLine' => 290,
            'endLine' => 290,
            'startColumn' => 37,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'height' => 
          array (
            'name' => 'height',
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
            'startLine' => 290,
            'endLine' => 290,
            'startColumn' => 49,
            'endColumn' => 59,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
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
            'startLine' => 290,
            'endLine' => 290,
            'startColumn' => 62,
            'endColumn' => 76,
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
 * Render an accessible placeholder box with a centered message — used
 * for chart errors and empty-data states. Always valid SVG so callers
 * can embed the result unconditionally.
 *
 * @param int $width Canvas width.
 * @param int $height Canvas height.
 * @param string $message Message to display (escaped).
 *
 * @return string SVG markup.
 */',
        'startLine' => 290,
        'endLine' => 301,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Charts',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'aliasName' => NULL,
      ),
      'intOption' => 
      array (
        'name' => 'intOption',
        'parameters' => 
        array (
          'options' => 
          array (
            'name' => 'options',
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
            'startLine' => 312,
            'endLine' => 312,
            'startColumn' => 29,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'key' => 
          array (
            'name' => 'key',
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
            'startLine' => 312,
            'endLine' => 312,
            'startColumn' => 45,
            'endColumn' => 55,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'default' => 
          array (
            'name' => 'default',
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
            'startLine' => 312,
            'endLine' => 312,
            'startColumn' => 58,
            'endColumn' => 69,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Read an integer option with a default and a sane positive floor.
 *
 * @param array $options Options array.
 * @param string $key Option key.
 * @param int $default Default value.
 *
 * @return int Resolved value (always >= 1).
 */',
        'startLine' => 312,
        'endLine' => 319,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Charts',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
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