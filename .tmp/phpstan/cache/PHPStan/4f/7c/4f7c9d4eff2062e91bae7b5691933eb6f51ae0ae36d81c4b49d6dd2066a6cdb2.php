<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/ChartCodec.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Editing\ChartCodec
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-60f485672b1f43aa84c2809c1b11cd4c051d81247a262f852a2052923b8dbf57',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/ChartCodec.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Editing',
    'name' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
    'shortName' => 'ChartCodec',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * A real chart, not a picture of one.
 *
 * This is the first thing in the editing surface that ADDS package parts rather
 * than rewriting one. Five things must agree or the document is corrupt, and a
 * corrupt `.docx` does not degrade — the suite refuses to open it at all:
 *
 *   1. `word/charts/chartN.xml`           the chart definition
 *   2. `[Content_Types].xml`              an Override declaring its content type
 *   3. `word/_rels/document.xml.rels`     a Relationship with a fresh rId
 *   4. `word/document.xml`                a `<w:drawing>` referencing that rId
 *   5. the rId in 3 and 4 must be THE SAME and must not collide with an existing one
 *
 * Because of (5) the relationship id is derived by scanning the existing rels for
 * the highest `rId<n>` and taking the next. Hard-coding one would work on a
 * freshly generated document and silently overwrite a real relationship — an
 * image, a hyperlink, a header — on a document that already had six.
 *
 * ## Values are cached, and there is no embedded workbook
 *
 * A chart may reference an embedded `.xlsx` so a user can click it and edit the
 * data. It may also carry its values inline in `<c:numCache>` / `<c:strCache>`,
 * which is what every suite actually renders from. This writes the caches only.
 *
 * The consequence is stated rather than hidden: the chart RENDERS correctly and is
 * selectable, resizable and styleable, but "Edit data" has no worksheet to open.
 * Minting a valid embedded workbook is a second package format inside this one, and
 * a subtly wrong one produces exactly the corrupt-file failure above.
 *
 * ## OOXML only
 *
 * An ODF chart is an embedded OBJECT — its own sub-directory with a `content.xml`,
 * plus a `META-INF/manifest.xml` entry, referenced by a `<draw:frame>`. That is a
 * different construction, not a translation of this one, and it is refused by name
 * rather than silently ignored.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://filinq.app
 *
 * @spec openspec/specs/document-chart-embedding/spec.md
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 80,
    'endLine' => 653,
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
      'TYPES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'name' => 'TYPES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'bar\' => \'c:barChart\', \'line\' => \'c:lineChart\', \'pie\' => \'c:pieChart\']',
          'attributes' => 
          array (
            'startLine' => 87,
            'endLine' => 91,
            'startTokenPos' => 40,
            'startFilePos' => 3141,
            'endTokenPos' => 63,
            'endFilePos' => 3221,
          ),
        ),
        'docComment' => '/**
 * Chart types this codec can build, mapped to their DrawingML element.
 *
 * @var array<string, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 87,
        'endLine' => 91,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
      'AXIAL_TYPES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'name' => 'AXIAL_TYPES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'bar\', \'line\']',
          'attributes' => 
          array (
            'startLine' => 101,
            'endLine' => 101,
            'startTokenPos' => 76,
            'startFilePos' => 3469,
            'endTokenPos' => 81,
            'endFilePos' => 3483,
          ),
        ),
        'docComment' => '/**
 * Types that carry a category and a value axis.
 *
 * A pie chart has neither, and emitting axis references for one produces a
 * file Word opens with a repair prompt.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 101,
        'endLine' => 101,
        'startColumn' => 2,
        'endColumn' => 45,
      ),
      'CAT_AXIS_ID' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'name' => 'CAT_AXIS_ID',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '111111111',
          'attributes' => 
          array (
            'startLine' => 108,
            'endLine' => 108,
            'startTokenPos' => 94,
            'startFilePos' => 3565,
            'endTokenPos' => 94,
            'endFilePos' => 3573,
          ),
        ),
        'docComment' => '/**
 * Category axis id.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 108,
        'endLine' => 108,
        'startColumn' => 2,
        'endColumn' => 39,
      ),
      'VAL_AXIS_ID' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'name' => 'VAL_AXIS_ID',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '222222222',
          'attributes' => 
          array (
            'startLine' => 115,
            'endLine' => 115,
            'startTokenPos' => 107,
            'startFilePos' => 3652,
            'endTokenPos' => 107,
            'endFilePos' => 3660,
          ),
        ),
        'docComment' => '/**
 * Value axis id.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 115,
        'endLine' => 115,
        'startColumn' => 2,
        'endColumn' => 39,
      ),
      'CHART_CONTENT_TYPE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'name' => 'CHART_CONTENT_TYPE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'application/vnd.openxmlformats-officedocument.drawingml.chart+xml\'',
          'attributes' => 
          array (
            'startLine' => 122,
            'endLine' => 122,
            'startTokenPos' => 120,
            'startFilePos' => 3754,
            'endTokenPos' => 120,
            'endFilePos' => 3820,
          ),
        ),
        'docComment' => '/**
 * Chart content type.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 122,
        'endLine' => 122,
        'startColumn' => 2,
        'endColumn' => 104,
      ),
      'CHART_REL_TYPE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'name' => 'CHART_REL_TYPE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'http://schemas.openxmlformats.org/officeDocument/2006/relationships/chart\'',
          'attributes' => 
          array (
            'startLine' => 129,
            'endLine' => 129,
            'startTokenPos' => 133,
            'startFilePos' => 3915,
            'endTokenPos' => 133,
            'endFilePos' => 3989,
          ),
        ),
        'docComment' => '/**
 * Chart relationship type.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 129,
        'endLine' => 129,
        'startColumn' => 2,
        'endColumn' => 108,
      ),
    ),
    'immediateProperties' => 
    array (
      'io' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'name' => 'io',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Editing\\PackagePartIo',
            'isIdentifier' => false,
          ),
        ),
        'default' => 
        array (
          'code' => 'new \\OCA\\Filinq\\Service\\Editing\\PackagePartIo()',
          'attributes' => 
          array (
            'startLine' => 140,
            'endLine' => 140,
            'startTokenPos' => 155,
            'startFilePos' => 4237,
            'endTokenPos' => 159,
            'endFilePos' => 4255,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 140,
        'endLine' => 140,
        'startColumn' => 3,
        'endColumn' => 58,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'scanner' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'name' => 'scanner',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Editing\\XmlBlockScanner',
            'isIdentifier' => false,
          ),
        ),
        'default' => 
        array (
          'code' => 'new \\OCA\\Filinq\\Service\\Editing\\XmlBlockScanner()',
          'attributes' => 
          array (
            'startLine' => 141,
            'endLine' => 141,
            'startTokenPos' => 172,
            'startFilePos' => 4304,
            'endTokenPos' => 176,
            'endFilePos' => 4324,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 141,
        'endLine' => 141,
        'startColumn' => 3,
        'endColumn' => 67,
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
          'io' => 
          array (
            'name' => 'io',
            'default' => 
            array (
              'code' => 'new \\OCA\\Filinq\\Service\\Editing\\PackagePartIo()',
              'attributes' => 
              array (
                'startLine' => 140,
                'endLine' => 140,
                'startTokenPos' => 155,
                'startFilePos' => 4237,
                'endTokenPos' => 159,
                'endFilePos' => 4255,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Editing\\PackagePartIo',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 140,
            'endLine' => 140,
            'startColumn' => 3,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'scanner' => 
          array (
            'name' => 'scanner',
            'default' => 
            array (
              'code' => 'new \\OCA\\Filinq\\Service\\Editing\\XmlBlockScanner()',
              'attributes' => 
              array (
                'startLine' => 141,
                'endLine' => 141,
                'startTokenPos' => 172,
                'startFilePos' => 4304,
                'endTokenPos' => 176,
                'endFilePos' => 4324,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Editing\\XmlBlockScanner',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 141,
            'endLine' => 141,
            'startColumn' => 3,
            'endColumn' => 67,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor.
 *
 * @param PackagePartIo $io The package part reader/writer.
 * @param XmlBlockScanner $scanner The element-span scanner.
 *
 * @return void
 */',
        'startLine' => 139,
        'endLine' => 143,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'aliasName' => NULL,
      ),
      'types' => 
      array (
        'name' => 'types',
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
 * The chart types this codec can build.
 *
 * @return array<int, string> The type names.
 *
 * @spec openspec/specs/document-chart-embedding/spec.md
 */',
        'startLine' => 152,
        'endLine' => 154,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'aliasName' => NULL,
      ),
      'supports' => 
      array (
        'name' => 'supports',
        'parameters' => 
        array (
          'extension' => 
          array (
            'name' => 'extension',
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
            'startLine' => 165,
            'endLine' => 165,
            'startColumn' => 27,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Whether a chart can be embedded in this extension.
 *
 * @param string $extension The file extension.
 *
 * @return bool True when supported.
 *
 * @spec openspec/specs/document-chart-embedding/spec.md
 */',
        'startLine' => 165,
        'endLine' => 167,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'aliasName' => NULL,
      ),
      'embedChart' => 
      array (
        'name' => 'embedChart',
        'parameters' => 
        array (
          'packageBytes' => 
          array (
            'name' => 'packageBytes',
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
            'startLine' => 184,
            'endLine' => 184,
            'startColumn' => 3,
            'endColumn' => 22,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'extension' => 
          array (
            'name' => 'extension',
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
            'startLine' => 185,
            'endLine' => 185,
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'chart' => 
          array (
            'name' => 'chart',
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
            'startLine' => 186,
            'endLine' => 186,
            'startColumn' => 3,
            'endColumn' => 14,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'afterAnchor' => 
          array (
            'name' => 'afterAnchor',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 187,
                'endLine' => 187,
                'startTokenPos' => 280,
                'startFilePos' => 5602,
                'endTokenPos' => 280,
                'endFilePos' => 5605,
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 187,
            'endLine' => 187,
            'startColumn' => 3,
            'endColumn' => 29,
            'parameterIndex' => 3,
            'isOptional' => true,
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
 * Embed a chart, placing it after an anchored paragraph or at the end.
 *
 * @param string $packageBytes The raw package bytes.
 * @param string $extension The file extension.
 * @param array $chart The chart definition.
 * @param string|null $afterAnchor The anchor to place it after, or null for the end.
 *
 * @return array{bytes: string, chartPart: string, relationshipId: string}
 *
 * @throws RuntimeException When the format or definition is invalid.
 *
 * @spec openspec/specs/document-chart-embedding/spec.md
 */',
        'startLine' => 183,
        'endLine' => 251,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'aliasName' => NULL,
      ),
      'validate' => 
      array (
        'name' => 'validate',
        'parameters' => 
        array (
          'chart' => 
          array (
            'name' => 'chart',
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
            'startLine' => 267,
            'endLine' => 267,
            'startColumn' => 28,
            'endColumn' => 39,
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
 * Validate and normalise a chart definition.
 *
 * The caller is a language model, so every constraint is checked and named.
 * A series shorter than the category list is the interesting case: silently
 * padding it would draw a chart the caller did not describe, and silently
 * truncating the categories would drop data.
 *
 * @param array $chart The chart definition.
 *
 * @return array{type: string, title: string, categories: array<int, string>, series: array<int, array{name: string, values: array<int, float>}>}
 *
 * @throws RuntimeException When the definition is unusable.
 */',
        'startLine' => 267,
        'endLine' => 316,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'aliasName' => NULL,
      ),
      'buildChartXml' => 
      array (
        'name' => 'buildChartXml',
        'parameters' => 
        array (
          'chart' => 
          array (
            'name' => 'chart',
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
            'startLine' => 325,
            'endLine' => 325,
            'startColumn' => 33,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Build the chart part XML.
 *
 * @param array $chart The validated chart definition.
 *
 * @return string The chart XML.
 */',
        'startLine' => 325,
        'endLine' => 367,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'aliasName' => NULL,
      ),
      'buildSeries' => 
      array (
        'name' => 'buildSeries',
        'parameters' => 
        array (
          'index' => 
          array (
            'name' => 'index',
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
            'startLine' => 378,
            'endLine' => 378,
            'startColumn' => 31,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'entry' => 
          array (
            'name' => 'entry',
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
            'startLine' => 378,
            'endLine' => 378,
            'startColumn' => 43,
            'endColumn' => 54,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'categories' => 
          array (
            'name' => 'categories',
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
            'startLine' => 378,
            'endLine' => 378,
            'startColumn' => 57,
            'endColumn' => 73,
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
 * Build one series element.
 *
 * @param int $index The series index.
 * @param array $entry The series definition.
 * @param array $categories The category labels.
 *
 * @return string The series XML.
 */',
        'startLine' => 378,
        'endLine' => 416,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'aliasName' => NULL,
      ),
      'buildAxes' => 
      array (
        'name' => 'buildAxes',
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
 * Build the category and value axes.
 *
 * @return string The axis XML.
 */',
        'startLine' => 423,
        'endLine' => 432,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'aliasName' => NULL,
      ),
      'relationships' => 
      array (
        'name' => 'relationships',
        'parameters' => 
        array (
          'packageBytes' => 
          array (
            'name' => 'packageBytes',
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
            'startLine' => 441,
            'endLine' => 441,
            'startColumn' => 33,
            'endColumn' => 52,
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
 * Read the document relationships, or a minimal set when absent.
 *
 * @param string $packageBytes The package bytes.
 *
 * @return string The relationships XML.
 */',
        'startLine' => 441,
        'endLine' => 450,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'aliasName' => NULL,
      ),
      'nextRelationshipId' => 
      array (
        'name' => 'nextRelationshipId',
        'parameters' => 
        array (
          'relsXml' => 
          array (
            'name' => 'relsXml',
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
            'startLine' => 463,
            'endLine' => 463,
            'startColumn' => 38,
            'endColumn' => 52,
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
 * Pick a relationship id that is not already taken.
 *
 * Scanned rather than hard-coded. A fixed `rId1` works on a freshly generated
 * document and silently REPLACES a real relationship — an image, a hyperlink,
 * a header — on one that already has several.
 *
 * @param string $relsXml The relationships XML.
 *
 * @return string The new relationship id.
 */',
        'startLine' => 463,
        'endLine' => 472,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'aliasName' => NULL,
      ),
      'nextChartIndex' => 
      array (
        'name' => 'nextChartIndex',
        'parameters' => 
        array (
          'packageBytes' => 
          array (
            'name' => 'packageBytes',
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
            'startLine' => 481,
            'endLine' => 481,
            'startColumn' => 34,
            'endColumn' => 53,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Pick a chart part index that is not already taken.
 *
 * @param string $packageBytes The package bytes.
 *
 * @return int The next index.
 */',
        'startLine' => 481,
        'endLine' => 497,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'aliasName' => NULL,
      ),
      'withContentTypeOverride' => 
      array (
        'name' => 'withContentTypeOverride',
        'parameters' => 
        array (
          'xml' => 
          array (
            'name' => 'xml',
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
            'startLine' => 512,
            'endLine' => 512,
            'startColumn' => 43,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'partName' => 
          array (
            'name' => 'partName',
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
            'startLine' => 512,
            'endLine' => 512,
            'startColumn' => 56,
            'endColumn' => 71,
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
 * Add the chart\'s content-type override.
 *
 * Without this the suite does not know what the part is and refuses the whole
 * document — a missing Override is not a degraded chart, it is a corrupt file.
 *
 * @param string $xml The content types XML.
 * @param string $partName The part name, with a leading slash.
 *
 * @return string The rewritten XML.
 *
 * @throws RuntimeException When the Types element cannot be found.
 */',
        'startLine' => 512,
        'endLine' => 520,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'aliasName' => NULL,
      ),
      'withRelationship' => 
      array (
        'name' => 'withRelationship',
        'parameters' => 
        array (
          'xml' => 
          array (
            'name' => 'xml',
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
            'startLine' => 533,
            'endLine' => 533,
            'startColumn' => 36,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'relId' => 
          array (
            'name' => 'relId',
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
            'startLine' => 533,
            'endLine' => 533,
            'startColumn' => 49,
            'endColumn' => 61,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'target' => 
          array (
            'name' => 'target',
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
            'startLine' => 533,
            'endLine' => 533,
            'startColumn' => 64,
            'endColumn' => 77,
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
 * Add the chart relationship.
 *
 * @param string $xml The relationships XML.
 * @param string $relId The relationship id.
 * @param string $target The relationship target, relative to `word/`.
 *
 * @return string The rewritten XML.
 *
 * @throws RuntimeException When the Relationships element cannot be found.
 */',
        'startLine' => 533,
        'endLine' => 546,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'aliasName' => NULL,
      ),
      'withDrawing' => 
      array (
        'name' => 'withDrawing',
        'parameters' => 
        array (
          'xml' => 
          array (
            'name' => 'xml',
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
            'startLine' => 560,
            'endLine' => 560,
            'startColumn' => 31,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'relId' => 
          array (
            'name' => 'relId',
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
            'startLine' => 560,
            'endLine' => 560,
            'startColumn' => 44,
            'endColumn' => 56,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'title' => 
          array (
            'name' => 'title',
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
            'startLine' => 560,
            'endLine' => 560,
            'startColumn' => 59,
            'endColumn' => 71,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'afterAnchor' => 
          array (
            'name' => 'afterAnchor',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 560,
            'endLine' => 560,
            'startColumn' => 74,
            'endColumn' => 93,
            'parameterIndex' => 3,
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
 * Insert the drawing paragraph into the body.
 *
 * @param string $xml The document XML.
 * @param string $relId The chart relationship id.
 * @param string $title The chart title, used as the drawing\'s name.
 * @param string|null $afterAnchor The anchor to place it after, or null for the end.
 *
 * @return string The rewritten XML.
 *
 * @throws RuntimeException When the anchor does not resolve or the body cannot be found.
 */',
        'startLine' => 560,
        'endLine' => 592,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'aliasName' => NULL,
      ),
      'anchorOf' => 
      array (
        'name' => 'anchorOf',
        'parameters' => 
        array (
          'markup' => 
          array (
            'name' => 'markup',
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
            'startLine' => 605,
            'endLine' => 605,
            'startColumn' => 28,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Compute a paragraph\'s anchor, matching PackageCodec\'s scheme.
 *
 * Ordinals are not reproduced here: this resolves a single anchor for
 * placement, and a duplicate-text paragraph resolves to its first occurrence,
 * which is the same paragraph a reader would point at.
 *
 * @param string $markup The paragraph markup.
 *
 * @return string The anchor.
 */',
        'startLine' => 605,
        'endLine' => 610,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'aliasName' => NULL,
      ),
      'buildDrawingParagraph' => 
      array (
        'name' => 'buildDrawingParagraph',
        'parameters' => 
        array (
          'relId' => 
          array (
            'name' => 'relId',
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
            'startLine' => 620,
            'endLine' => 620,
            'startColumn' => 41,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'title' => 
          array (
            'name' => 'title',
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
            'startLine' => 620,
            'endLine' => 620,
            'startColumn' => 56,
            'endColumn' => 68,
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
 * Build the paragraph holding the drawing.
 *
 * @param string $relId The chart relationship id.
 * @param string $title The chart title.
 *
 * @return string The paragraph XML.
 */',
        'startLine' => 620,
        'endLine' => 641,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
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
            'startLine' => 650,
            'endLine' => 650,
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
 * XML-escape a value.
 *
 * @param string $value The value.
 *
 * @return string The escaped value.
 */',
        'startLine' => 650,
        'endLine' => 652,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
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