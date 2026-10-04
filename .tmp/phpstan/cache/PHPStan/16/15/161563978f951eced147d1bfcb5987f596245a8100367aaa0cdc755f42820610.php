<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Charts/ChartLegendRenderer.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Charts\ChartLegendRenderer
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-29789682ce6a0e76e459c702731c047e39c22567a7bfd57e67dc914f1e24d167',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Charts\\ChartLegendRenderer',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Charts/ChartLegendRenderer.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Charts',
    'name' => 'OCA\\Filinq\\Service\\Charts\\ChartLegendRenderer',
    'shortName' => 'ChartLegendRenderer',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Renders the series legend of a cartesian chart.
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
    'startLine' => 38,
    'endLine' => 100,
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
    ),
    'immediateProperties' => 
    array (
      'svg' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartLegendRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartLegendRenderer',
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
 * SVG element builders.
 *
 * @var SvgPrimitives
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 45,
        'endLine' => 45,
        'startColumn' => 2,
        'endColumn' => 37,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'labels' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartLegendRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartLegendRenderer',
        'name' => 'labels',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Charts\\ChartLabelFormatter',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Label text formatter.
 *
 * @var ChartLabelFormatter
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 52,
        'endLine' => 52,
        'startColumn' => 2,
        'endColumn' => 46,
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
        'startLine' => 63,
        'endLine' => 67,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Charts',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartLegendRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartLegendRenderer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartLegendRenderer',
        'aliasName' => NULL,
      ),
      'render' => 
      array (
        'name' => 'render',
        'parameters' => 
        array (
          'series' => 
          array (
            'name' => 'series',
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
            'startLine' => 82,
            'endLine' => 82,
            'startColumn' => 25,
            'endColumn' => 37,
            'parameterIndex' => 0,
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
            'startLine' => 82,
            'endLine' => 82,
            'startColumn' => 40,
            'endColumn' => 53,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'x' => 
          array (
            'name' => 'x',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'float',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 82,
            'endLine' => 82,
            'startColumn' => 56,
            'endColumn' => 63,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'y' => 
          array (
            'name' => 'y',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'float',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 82,
            'endLine' => 82,
            'startColumn' => 66,
            'endColumn' => 73,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'maxEntries' => 
          array (
            'name' => 'maxEntries',
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
            'startLine' => 82,
            'endLine' => 82,
            'startColumn' => 76,
            'endColumn' => 90,
            'parameterIndex' => 4,
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
 * Render a legend row of color-swatch + series name entries.
 *
 * @param array $series Normalized series list.
 * @param array $palette Ordered hex colors.
 * @param float $x Left x position.
 * @param float $y Baseline y position.
 * @param int $maxEntries Maximum number of entries rendered.
 *
 * @return string SVG markup fragment.
 *
 * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-001
 */',
        'startLine' => 82,
        'endLine' => 99,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Charts',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartLegendRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartLegendRenderer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Charts\\ChartLegendRenderer',
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