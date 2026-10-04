<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/TemplateRenderer.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\TemplateRenderer
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-3872274eb55d0f5175d9bb1a3239bd8e944d1805243c0ecc9755349a68372d47',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/TemplateRenderer.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\TemplateRenderer',
    'shortName' => 'TemplateRenderer',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service for rendering Twig templates in a sandboxed environment
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/pdf-generation/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 50,
    'endLine' => 435,
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
      'ALLOWED_FILTERS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'name' => 'ALLOWED_FILTERS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'escape\', \'e\', \'upper\', \'lower\', \'trim\', \'nl2br\', \'date\', \'number_format\', \'join\', \'split\', \'first\', \'last\', \'length\', \'default\', \'raw\', \'sort\', \'reverse\', \'keys\', \'values\', \'merge\', \'slice\', \'batch\', \'column\', \'round\', \'abs\']',
          'attributes' => 
          array (
            'startLine' => 57,
            'endLine' => 83,
            'startTokenPos' => 80,
            'startFilePos' => 1534,
            'endTokenPos' => 157,
            'endFilePos' => 1814,
          ),
        ),
        'docComment' => '/**
 * Allowed Twig filters in the sandbox
 *
 * @var string[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 83,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
      'ALLOWED_FUNCTIONS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'name' => 'ALLOWED_FUNCTIONS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'range\', \'cycle\', \'date\', \'max\', \'min\', \'chart\', \'data_table\']',
          'attributes' => 
          array (
            'startLine' => 90,
            'endLine' => 98,
            'startTokenPos' => 170,
            'startFilePos' => 1927,
            'endTokenPos' => 193,
            'endFilePos' => 2007,
          ),
        ),
        'docComment' => '/**
 * Allowed Twig functions in the sandbox
 *
 * @var string[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 90,
        'endLine' => 98,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
      'MAX_CHARTS_PER_DOCUMENT' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'name' => 'MAX_CHARTS_PER_DOCUMENT',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '20',
          'attributes' => 
          array (
            'startLine' => 107,
            'endLine' => 107,
            'startTokenPos' => 206,
            'startFilePos' => 2289,
            'endTokenPos' => 206,
            'endFilePos' => 2290,
          ),
        ),
        'docComment' => '/**
 * Maximum number of `chart()` calls rendered per document. Beyond this,
 * further chart() calls degrade to a visible placeholder instead of
 * growing the document unboundedly (template-charts guardrail).
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 107,
        'endLine' => 107,
        'startColumn' => 2,
        'endColumn' => 44,
      ),
      'ALLOWED_TAGS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'name' => 'ALLOWED_TAGS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'if\', \'for\', \'set\', \'block\', \'extends\', \'include\', \'macro\', \'spaceless\', \'apply\', \'autoescape\']',
          'attributes' => 
          array (
            'startLine' => 114,
            'endLine' => 125,
            'startTokenPos' => 219,
            'startFilePos' => 2393,
            'endTokenPos' => 251,
            'endFilePos' => 2512,
          ),
        ),
        'docComment' => '/**
 * Allowed Twig tags in the sandbox
 *
 * @var string[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 114,
        'endLine' => 125,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'lastRenderWarnings' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'name' => 'lastRenderWarnings',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 134,
            'endLine' => 134,
            'startTokenPos' => 264,
            'startFilePos' => 2774,
            'endTokenPos' => 265,
            'endFilePos' => 2775,
          ),
        ),
        'docComment' => '/**
 * Generation warnings collected by the visual-content functions
 * (`chart()`, `data_table()`) during the most recent {@see renderTemplate()}
 * call. Reset at the start of every call.
 *
 * @var string[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 134,
        'endLine' => 134,
        'startColumn' => 2,
        'endColumn' => 40,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'name' => 'logger',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Psr\\Log\\LoggerInterface',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 146,
        'endLine' => 146,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'chartRenderer' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'name' => 'chartRenderer',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 147,
        'endLine' => 147,
        'startColumn' => 3,
        'endColumn' => 50,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'tableRenderer' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'name' => 'tableRenderer',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Charts\\TableHtmlRenderer',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 148,
        'endLine' => 148,
        'startColumn' => 3,
        'endColumn' => 51,
        'isPromoted' => true,
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
          'logger' => 
          array (
            'name' => 'logger',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Psr\\Log\\LoggerInterface',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 146,
            'endLine' => 146,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'chartRenderer' => 
          array (
            'name' => 'chartRenderer',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Charts\\ChartSvgRenderer',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 147,
            'endLine' => 147,
            'startColumn' => 3,
            'endColumn' => 50,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'tableRenderer' => 
          array (
            'name' => 'tableRenderer',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Charts\\TableHtmlRenderer',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 148,
            'endLine' => 148,
            'startColumn' => 3,
            'endColumn' => 51,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor for TemplateRenderer
 *
 * @param LoggerInterface $logger Logger for error reporting
 * @param ChartSvgRenderer $chartRenderer Renderer for the `chart()` Twig function
 * @param TableHtmlRenderer $tableRenderer Renderer for the `data_table()` Twig function
 *
 * @return void
 */',
        'startLine' => 145,
        'endLine' => 151,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'currentClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'aliasName' => NULL,
      ),
      'renderTemplate' => 
      array (
        'name' => 'renderTemplate',
        'parameters' => 
        array (
          'templateContent' => 
          array (
            'name' => 'templateContent',
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
            'startColumn' => 33,
            'endColumn' => 55,
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
            'startLine' => 174,
            'endLine' => 174,
            'startColumn' => 58,
            'endColumn' => 68,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'huisstijl' => 
          array (
            'name' => 'huisstijl',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 174,
                'endLine' => 174,
                'startTokenPos' => 336,
                'startFilePos' => 4326,
                'endTokenPos' => 336,
                'endFilePos' => 4329,
              ),
            ),
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 174,
            'endLine' => 174,
            'startColumn' => 71,
            'endColumn' => 94,
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
 * Render a Twig template string with the given data context
 *
 * Uses a sandboxed Twig environment that only allows safe filters,
 * functions, and tags. Objects cannot have methods or properties called.
 * The `chart()` and `data_table()` functions (template-charts) render
 * local, deterministic SVG/HTML — no writes, no network I/O.
 *
 * @param string $templateContent Twig template content
 * @param array $data Data context for rendering
 * @param array|null $huisstijl Optional huisstijl config; when set,
 *                              `huisstijl[\'primaryColor\']` seeds the
 *                              default chart palette (REQ-DDTCH-001)
 *
 * @return string Rendered HTML
 *
 * @throws Exception If Twig rendering fails (syntax error, security violation)
 *
 * @spec openspec/specs/pdf-generation/spec.md
 * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-002
 */',
        'startLine' => 174,
        'endLine' => 206,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'currentClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'aliasName' => NULL,
      ),
      'getLastRenderWarnings' => 
      array (
        'name' => 'getLastRenderWarnings',
        'parameters' => 
        array (
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
 * Generation warnings recorded by `chart()`/`data_table()` during the
 * most recent {@see renderTemplate()} call (e.g. a chart that fell back
 * to a placeholder, or the per-document chart cap being hit).
 *
 * @return string[]
 *
 * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-002
 */',
        'startLine' => 217,
        'endLine' => 219,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'currentClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'aliasName' => NULL,
      ),
      'buildChartFunction' => 
      array (
        'name' => 'buildChartFunction',
        'parameters' => 
        array (
          'huisstijl' => 
          array (
            'name' => 'huisstijl',
            'default' => NULL,
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 233,
            'endLine' => 233,
            'startColumn' => 38,
            'endColumn' => 54,
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
            'name' => 'Twig\\TwigFunction',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Build the sandbox-registered `chart(type, data, options)` Twig
 * function. Pure with respect to the instance: no writes, no network
 * I/O — only local SVG string assembly (REQ-DDTCH-005).
 *
 * @param array|null $huisstijl Optional huisstijl config for the default
 *                              palette seed color.
 *
 * @return TwigFunction
 *
 * @spec openspec/changes/template-charts/specs/pdf-generation/spec.md#REQ-DDTCH-005
 */',
        'startLine' => 233,
        'endLine' => 279,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'currentClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'aliasName' => NULL,
      ),
      'buildDataTableFunction' => 
      array (
        'name' => 'buildDataTableFunction',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Twig\\TwigFunction',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Build the sandbox-registered `data_table(collection, columns, options)`
 * Twig function. Pure with respect to the instance: no writes, no
 * network I/O (REQ-DDTCH-005).
 *
 * @return TwigFunction
 *
 * @spec openspec/changes/template-charts/specs/pdf-generation/spec.md#REQ-DDTCH-005
 */',
        'startLine' => 290,
        'endLine' => 309,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'currentClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'aliasName' => NULL,
      ),
      'convertConditionalSections' => 
      array (
        'name' => 'convertConditionalSections',
        'parameters' => 
        array (
          'html' => 
          array (
            'name' => 'html',
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
            'startLine' => 326,
            'endLine' => 326,
            'startColumn' => 45,
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
 * Convert conditional section data attributes to Twig if blocks.
 *
 * Finds HTML elements with data-condition-field, data-condition-op, and
 * data-condition-value attributes and wraps their inner content in Twig
 * conditional blocks.
 *
 * Supported operators: equals, not_equals, contains, is_empty, is_not_empty.
 *
 * @param string $html HTML content with conditional data attributes
 *
 * @return string HTML with data attributes replaced by Twig if blocks
 *
 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
 */',
        'startLine' => 326,
        'endLine' => 341,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'currentClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'aliasName' => NULL,
      ),
      'replaceConditionalSection' => 
      array (
        'name' => 'replaceConditionalSection',
        'parameters' => 
        array (
          'matches' => 
          array (
            'name' => 'matches',
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
            'startLine' => 356,
            'endLine' => 356,
            'startColumn' => 45,
            'endColumn' => 58,
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
 * Replace a single conditional section match with Twig if block
 *
 * @param array $matches The regex match groups
 *
 * @return string The replacement string with Twig conditional
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateMethod) Called as the callable array
 * `[$this, \'replaceConditionalSection\']` from preg_replace_callback() at
 * line 342. PHPMD resolves only direct `$this->method()` calls, so a
 * callable-array reference reads to it as no caller at all — a false
 * positive, verified by grep.
 */',
        'startLine' => 356,
        'endLine' => 389,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'currentClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'aliasName' => NULL,
      ),
      'buildTwigCondition' => 
      array (
        'name' => 'buildTwigCondition',
        'parameters' => 
        array (
          'field' => 
          array (
            'name' => 'field',
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
            'startLine' => 400,
            'endLine' => 400,
            'startColumn' => 38,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'operator' => 
          array (
            'name' => 'operator',
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
            'startLine' => 400,
            'endLine' => 400,
            'startColumn' => 53,
            'endColumn' => 68,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
            'startLine' => 400,
            'endLine' => 400,
            'startColumn' => 71,
            'endColumn' => 83,
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
 * Build a Twig condition expression from field, operator, and value
 *
 * @param string $field The data field name
 * @param string $operator The condition operator
 * @param string $value The comparison value
 *
 * @return string Twig condition expression
 */',
        'startLine' => 400,
        'endLine' => 418,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'currentClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'aliasName' => NULL,
      ),
      'escapeTwigString' => 
      array (
        'name' => 'escapeTwigString',
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
            'startLine' => 427,
            'endLine' => 427,
            'startColumn' => 36,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Escape a string for safe use inside Twig string literals
 *
 * @param string $value The string value to escape
 *
 * @return string The escaped string
 */',
        'startLine' => 427,
        'endLine' => 434,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
        'currentClassName' => 'OCA\\Filinq\\Service\\TemplateRenderer',
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