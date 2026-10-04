<?php declare(strict_types = 1);

// osfsl-/home/rubenlinde/memcap-work/lq-lanes/fq/vendor/composer/../phpoffice/phpword/src/PhpWord/Style/Paragraph.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PhpOffice\PhpWord\Style\Paragraph
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-62b6a2062d6a1f116dff947625dba49ca7ba680eb305f7ae40bdcc99c947f794-8.3-6.70.0.6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/vendor/composer/../phpoffice/phpword/src/PhpWord/Style/Paragraph.php',
      ),
    ),
    'namespace' => 'PhpOffice\\PhpWord\\Style',
    'name' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
    'shortName' => 'Paragraph',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Paragraph style.
 *
 * OOXML:
 * - General: alignment, outline level
 * - Indentation: left, right, firstline, hanging
 * - Spacing: before, after, line spacing
 * - Pagination: widow control, keep next, keep line, page break before
 * - Formatting exception: suppress line numbers, don\'t hyphenate
 * - Textbox options
 * - Tabs
 * - Shading
 * - Borders
 *
 * OpenOffice:
 * - Indents & spacing
 * - Alignment
 * - Text flow
 * - Outline & numbering
 * - Tabs
 * - Dropcaps
 * - Tabs
 * - Borders
 * - Background
 *
 * @see  http://www.schemacentral.com/sc/ooxml/t-w_CT_PPr.html
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 54,
    'endLine' => 892,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'PhpOffice\\PhpWord\\Style\\Border',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'LINE_HEIGHT' => 
      array (
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'name' => 'LINE_HEIGHT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '240',
          'attributes' => 
          array (
            'startLine' => 59,
            'endLine' => 59,
            'startTokenPos' => 54,
            'startFilePos' => 1593,
            'endTokenPos' => 54,
            'endFilePos' => 1595,
          ),
        ),
        'docComment' => '/**
 * @const int One line height equals 240 twip
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 59,
        'endLine' => 59,
        'startColumn' => 5,
        'endColumn' => 28,
      ),
    ),
    'immediateProperties' => 
    array (
      'aliases' => 
      array (
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'name' => 'aliases',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'line-height\' => \'lineHeight\', \'line-spacing\' => \'spacing\']',
          'attributes' => 
          array (
            'startLine' => 66,
            'endLine' => 66,
            'startTokenPos' => 65,
            'startFilePos' => 1681,
            'endTokenPos' => 78,
            'endFilePos' => 1740,
          ),
        ),
        'docComment' => '/**
 * Aliases.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 66,
        'endLine' => 66,
        'startColumn' => 5,
        'endColumn' => 86,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'basedOn' => 
      array (
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'name' => 'basedOn',
        'modifiers' => 4,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Normal\'',
          'attributes' => 
          array (
            'startLine' => 73,
            'endLine' => 73,
            'startTokenPos' => 89,
            'startFilePos' => 1830,
            'endTokenPos' => 89,
            'endFilePos' => 1837,
          ),
        ),
        'docComment' => '/**
 * Parent style.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 5,
        'endColumn' => 32,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'next' => 
      array (
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'name' => 'next',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * Style for next paragraph.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 80,
        'endLine' => 80,
        'startColumn' => 5,
        'endColumn' => 18,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'alignment' => 
      array (
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'name' => 'alignment',
        'modifiers' => 4,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'\'',
          'attributes' => 
          array (
            'startLine' => 85,
            'endLine' => 85,
            'startTokenPos' => 107,
            'startFilePos' => 1996,
            'endTokenPos' => 107,
            'endFilePos' => 1997,
          ),
        ),
        'docComment' => '/**
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 85,
        'endLine' => 85,
        'startColumn' => 5,
        'endColumn' => 28,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'indentation' => 
      array (
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'name' => 'indentation',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * Indentation.
 *
 * @var null|Indentation
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 92,
        'endLine' => 92,
        'startColumn' => 5,
        'endColumn' => 25,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'spacing' => 
      array (
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'name' => 'spacing',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * Spacing.
 *
 * @var Spacing
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 99,
        'endLine' => 99,
        'startColumn' => 5,
        'endColumn' => 21,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'lineHeight' => 
      array (
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'name' => 'lineHeight',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * Text line height.
 *
 * @var null|float|int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 106,
        'endLine' => 106,
        'startColumn' => 5,
        'endColumn' => 24,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'widowControl' => 
      array (
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'name' => 'widowControl',
        'modifiers' => 4,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'true',
          'attributes' => 
          array (
            'startLine' => 113,
            'endLine' => 113,
            'startTokenPos' => 139,
            'startFilePos' => 2411,
            'endTokenPos' => 139,
            'endFilePos' => 2414,
          ),
        ),
        'docComment' => '/**
 * Allow first/last line to display on a separate page.
 *
 * @var bool
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 113,
        'endLine' => 113,
        'startColumn' => 5,
        'endColumn' => 33,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'keepNext' => 
      array (
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'name' => 'keepNext',
        'modifiers' => 4,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 120,
            'endLine' => 120,
            'startTokenPos' => 150,
            'startFilePos' => 2525,
            'endTokenPos' => 150,
            'endFilePos' => 2529,
          ),
        ),
        'docComment' => '/**
 * Keep paragraph with next paragraph.
 *
 * @var bool
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 120,
        'endLine' => 120,
        'startColumn' => 5,
        'endColumn' => 30,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'keepLines' => 
      array (
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'name' => 'keepLines',
        'modifiers' => 4,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 127,
            'endLine' => 127,
            'startTokenPos' => 161,
            'startFilePos' => 2633,
            'endTokenPos' => 161,
            'endFilePos' => 2637,
          ),
        ),
        'docComment' => '/**
 * Keep all lines on one page.
 *
 * @var bool
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 127,
        'endLine' => 127,
        'startColumn' => 5,
        'endColumn' => 31,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'pageBreakBefore' => 
      array (
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'name' => 'pageBreakBefore',
        'modifiers' => 4,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 134,
            'endLine' => 134,
            'startTokenPos' => 172,
            'startFilePos' => 2749,
            'endTokenPos' => 172,
            'endFilePos' => 2753,
          ),
        ),
        'docComment' => '/**
 * Start paragraph on next page.
 *
 * @var bool
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 134,
        'endLine' => 134,
        'startColumn' => 5,
        'endColumn' => 37,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'numStyle' => 
      array (
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'name' => 'numStyle',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * Numbering style name.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 141,
        'endLine' => 141,
        'startColumn' => 5,
        'endColumn' => 22,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'numLevel' => 
      array (
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'name' => 'numLevel',
        'modifiers' => 4,
        'type' => NULL,
        'default' => 
        array (
          'code' => '0',
          'attributes' => 
          array (
            'startLine' => 148,
            'endLine' => 148,
            'startTokenPos' => 190,
            'startFilePos' => 2939,
            'endTokenPos' => 190,
            'endFilePos' => 2939,
          ),
        ),
        'docComment' => '/**
 * Numbering level.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 148,
        'endLine' => 148,
        'startColumn' => 5,
        'endColumn' => 26,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'tabs' => 
      array (
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'name' => 'tabs',
        'modifiers' => 4,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 155,
            'endLine' => 155,
            'startTokenPos' => 201,
            'startFilePos' => 3036,
            'endTokenPos' => 202,
            'endFilePos' => 3037,
          ),
        ),
        'docComment' => '/**
 * Set of Custom Tab Stops.
 *
 * @var Tab[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 155,
        'endLine' => 155,
        'startColumn' => 5,
        'endColumn' => 23,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'shading' => 
      array (
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'name' => 'shading',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * Shading.
 *
 * @var Shading
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 162,
        'endLine' => 162,
        'startColumn' => 5,
        'endColumn' => 21,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'contextualSpacing' => 
      array (
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'name' => 'contextualSpacing',
        'modifiers' => 4,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 169,
            'endLine' => 169,
            'startTokenPos' => 220,
            'startFilePos' => 3263,
            'endTokenPos' => 220,
            'endFilePos' => 3267,
          ),
        ),
        'docComment' => '/**
 * Ignore Spacing Above and Below When Using Identical Styles.
 *
 * @var bool
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 169,
        'endLine' => 169,
        'startColumn' => 5,
        'endColumn' => 39,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'bidi' => 
      array (
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'name' => 'bidi',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * Right to Left Paragraph Layout.
 *
 * @var ?bool
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 176,
        'endLine' => 176,
        'startColumn' => 5,
        'endColumn' => 18,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'textAlignment' => 
      array (
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'name' => 'textAlignment',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * Vertical Character Alignment on Line.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 183,
        'endLine' => 183,
        'startColumn' => 5,
        'endColumn' => 27,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'suppressAutoHyphens' => 
      array (
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'name' => 'suppressAutoHyphens',
        'modifiers' => 4,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 190,
            'endLine' => 190,
            'startTokenPos' => 245,
            'startFilePos' => 3605,
            'endTokenPos' => 245,
            'endFilePos' => 3609,
          ),
        ),
        'docComment' => '/**
 * Suppress hyphenation for paragraph.
 *
 * @var bool
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 190,
        'endLine' => 190,
        'startColumn' => 5,
        'endColumn' => 41,
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
      'setStyleValue' => 
      array (
        'name' => 'setStyleValue',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 200,
            'endLine' => 200,
            'startColumn' => 35,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 200,
            'endLine' => 200,
            'startColumn' => 41,
            'endColumn' => 46,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set Style value.
 *
 * @param string $key
 * @param mixed $value
 *
 * @return self
 */',
        'startLine' => 200,
        'endLine' => 208,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'getStyleValues' => 
      array (
        'name' => 'getStyleValues',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get style values.
 *
 * An experiment to retrieve all style values in one function. This will
 * reduce function call and increase cohesion between functions. Should be
 * implemented in all styles.
 *
 * @ignoreScrutinizerPatch
 *
 * @return array
 */',
        'startLine' => 221,
        'endLine' => 249,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'getAlignment' => 
      array (
        'name' => 'getAlignment',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @since 0.13.0
 *
 * @return string
 */',
        'startLine' => 256,
        'endLine' => 259,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setAlignment' => 
      array (
        'name' => 'setAlignment',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 268,
            'endLine' => 268,
            'startColumn' => 34,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @since 0.13.0
 *
 * @param string $value
 *
 * @return self
 */',
        'startLine' => 268,
        'endLine' => 275,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'getBasedOn' => 
      array (
        'name' => 'getBasedOn',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get parent style ID.
 *
 * @return string
 */',
        'startLine' => 282,
        'endLine' => 285,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setBasedOn' => 
      array (
        'name' => 'setBasedOn',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => '\'Normal\'',
              'attributes' => 
              array (
                'startLine' => 294,
                'endLine' => 294,
                'startTokenPos' => 668,
                'startFilePos' => 6151,
                'endTokenPos' => 668,
                'endFilePos' => 6158,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 294,
            'endLine' => 294,
            'startColumn' => 32,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set parent style ID.
 *
 * @param string $value
 *
 * @return self
 */',
        'startLine' => 294,
        'endLine' => 299,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'getNext' => 
      array (
        'name' => 'getNext',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get style for next paragraph.
 *
 * @return string
 */',
        'startLine' => 306,
        'endLine' => 309,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setNext' => 
      array (
        'name' => 'setNext',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 318,
                'endLine' => 318,
                'startTokenPos' => 722,
                'startFilePos' => 6535,
                'endTokenPos' => 722,
                'endFilePos' => 6538,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 318,
            'endLine' => 318,
            'startColumn' => 29,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set style for next paragraph.
 *
 * @param string $value
 *
 * @return self
 */',
        'startLine' => 318,
        'endLine' => 323,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'getHanging' => 
      array (
        'name' => 'getHanging',
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
                  'name' => 'float',
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
 * Get hanging.
 */',
        'startLine' => 328,
        'endLine' => 331,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'getIndent' => 
      array (
        'name' => 'getIndent',
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
                  'name' => 'float',
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
 * Get indentation.
 *
 * @deprecated 1.4.0 Use getIndentLeft
 */',
        'startLine' => 338,
        'endLine' => 341,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'getIndentation' => 
      array (
        'name' => 'getIndentation',
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
                  'name' => 'PhpOffice\\PhpWord\\Style\\Indentation',
                  'isIdentifier' => false,
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
 * Get indentation.
 */',
        'startLine' => 346,
        'endLine' => 349,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'getIndentFirstLine' => 
      array (
        'name' => 'getIndentFirstLine',
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
                  'name' => 'float',
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
 * Get firstLine.
 */',
        'startLine' => 354,
        'endLine' => 357,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'getIndentLeft' => 
      array (
        'name' => 'getIndentLeft',
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
                  'name' => 'float',
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
 * Get left indentation.
 */',
        'startLine' => 362,
        'endLine' => 365,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'getIndentRight' => 
      array (
        'name' => 'getIndentRight',
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
                  'name' => 'float',
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
 * Get right indentation.
 */',
        'startLine' => 370,
        'endLine' => 373,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setHanging' => 
      array (
        'name' => 'setHanging',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 380,
                'endLine' => 380,
                'startTokenPos' => 948,
                'startFilePos' => 7780,
                'endTokenPos' => 948,
                'endFilePos' => 7783,
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
                      'name' => 'float',
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
            'startLine' => 380,
            'endLine' => 380,
            'startColumn' => 32,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set hanging.
 *
 * @deprecated 1.4.0 Use setIndentHanging
 */',
        'startLine' => 380,
        'endLine' => 383,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setIndent' => 
      array (
        'name' => 'setIndent',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 390,
                'endLine' => 390,
                'startTokenPos' => 989,
                'startFilePos' => 8002,
                'endTokenPos' => 989,
                'endFilePos' => 8005,
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
                      'name' => 'float',
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
            'startLine' => 390,
            'endLine' => 390,
            'startColumn' => 31,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set indentation.
 *
 * @deprecated 1.4.0 Use setIndentLeft
 */',
        'startLine' => 390,
        'endLine' => 393,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setIndentation' => 
      array (
        'name' => 'setIndentation',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 405,
                'endLine' => 405,
                'startTokenPos' => 1029,
                'startFilePos' => 8419,
                'endTokenPos' => 1030,
                'endFilePos' => 8420,
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
            'startLine' => 405,
            'endLine' => 405,
            'startColumn' => 36,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set indentation.
 *
 * @param array{
 *     left?:null|float|int|numeric-string,
 *     right?:null|float|int|numeric-string,
 *     hanging?:null|float|int|numeric-string,
 *     firstLine?:null|float|int|numeric-string
 * } $value
 */',
        'startLine' => 405,
        'endLine' => 417,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setIndentHanging' => 
      array (
        'name' => 'setIndentHanging',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 422,
                'endLine' => 422,
                'startTokenPos' => 1134,
                'startFilePos' => 8865,
                'endTokenPos' => 1134,
                'endFilePos' => 8868,
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
                      'name' => 'float',
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
            'startLine' => 422,
            'endLine' => 422,
            'startColumn' => 38,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set hanging indentation.
 */',
        'startLine' => 422,
        'endLine' => 425,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setIndentFirstLine' => 
      array (
        'name' => 'setIndentFirstLine',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 430,
                'endLine' => 430,
                'startTokenPos' => 1175,
                'startFilePos' => 9056,
                'endTokenPos' => 1175,
                'endFilePos' => 9059,
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
                      'name' => 'float',
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
            'startLine' => 430,
            'endLine' => 430,
            'startColumn' => 40,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set firstline indentation.
 */',
        'startLine' => 430,
        'endLine' => 433,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setIndentFirstLineChars' => 
      array (
        'name' => 'setIndentFirstLineChars',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 438,
                'endLine' => 438,
                'startTokenPos' => 1215,
                'startFilePos' => 9256,
                'endTokenPos' => 1215,
                'endFilePos' => 9256,
              ),
            ),
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
            'startLine' => 438,
            'endLine' => 438,
            'startColumn' => 45,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set firstlineChars indentation.
 */',
        'startLine' => 438,
        'endLine' => 441,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setIndentLeft' => 
      array (
        'name' => 'setIndentLeft',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 446,
                'endLine' => 446,
                'startTokenPos' => 1256,
                'startFilePos' => 9441,
                'endTokenPos' => 1256,
                'endFilePos' => 9444,
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
                      'name' => 'float',
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
            'startLine' => 446,
            'endLine' => 446,
            'startColumn' => 35,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set left indentation.
 */',
        'startLine' => 446,
        'endLine' => 449,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setIndentRight' => 
      array (
        'name' => 'setIndentRight',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 454,
                'endLine' => 454,
                'startTokenPos' => 1297,
                'startFilePos' => 9621,
                'endTokenPos' => 1297,
                'endFilePos' => 9624,
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
                      'name' => 'float',
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
            'startLine' => 454,
            'endLine' => 454,
            'startColumn' => 36,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set right indentation.
 */',
        'startLine' => 454,
        'endLine' => 457,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'getSpace' => 
      array (
        'name' => 'getSpace',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get spacing.
 *
 * @return Spacing
 *
 * @todo Rename to getSpacing in 1.0
 */',
        'startLine' => 466,
        'endLine' => 469,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setSpace' => 
      array (
        'name' => 'setSpace',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 480,
                'endLine' => 480,
                'startTokenPos' => 1356,
                'startFilePos' => 10077,
                'endTokenPos' => 1356,
                'endFilePos' => 10080,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 480,
            'endLine' => 480,
            'startColumn' => 30,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set spacing.
 *
 * @param mixed $value
 *
 * @return self
 *
 * @todo Rename to setSpacing in 1.0
 */',
        'startLine' => 480,
        'endLine' => 485,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'getSpaceBefore' => 
      array (
        'name' => 'getSpaceBefore',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get space before paragraph.
 *
 * @return null|float|int
 */',
        'startLine' => 492,
        'endLine' => 495,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setSpaceBefore' => 
      array (
        'name' => 'setSpaceBefore',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 504,
                'endLine' => 504,
                'startTokenPos' => 1425,
                'startFilePos' => 10554,
                'endTokenPos' => 1425,
                'endFilePos' => 10557,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 504,
            'endLine' => 504,
            'startColumn' => 36,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set space before paragraph.
 *
 * @param null|float|int $value
 *
 * @return self
 */',
        'startLine' => 504,
        'endLine' => 507,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'getSpaceAfter' => 
      array (
        'name' => 'getSpaceAfter',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get space after paragraph.
 *
 * @return null|float|int
 */',
        'startLine' => 514,
        'endLine' => 517,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setSpaceAfter' => 
      array (
        'name' => 'setSpaceAfter',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 526,
                'endLine' => 526,
                'startTokenPos' => 1489,
                'startFilePos' => 10993,
                'endTokenPos' => 1489,
                'endFilePos' => 10996,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 526,
            'endLine' => 526,
            'startColumn' => 35,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set space after paragraph.
 *
 * @param null|float|int $value
 *
 * @return self
 */',
        'startLine' => 526,
        'endLine' => 529,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'getSpacing' => 
      array (
        'name' => 'getSpacing',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get spacing between lines.
 *
 * @return null|float|int
 */',
        'startLine' => 536,
        'endLine' => 539,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setSpacing' => 
      array (
        'name' => 'setSpacing',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 548,
                'endLine' => 548,
                'startTokenPos' => 1553,
                'startFilePos' => 11424,
                'endTokenPos' => 1553,
                'endFilePos' => 11427,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 548,
            'endLine' => 548,
            'startColumn' => 32,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set spacing between lines.
 *
 * @param null|float|int $value
 *
 * @return self
 */',
        'startLine' => 548,
        'endLine' => 551,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'getSpacingLineRule' => 
      array (
        'name' => 'getSpacingLineRule',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get spacing line rule.
 *
 * @return string
 */',
        'startLine' => 558,
        'endLine' => 561,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setSpacingLineRule' => 
      array (
        'name' => 'setSpacingLineRule',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 570,
            'endLine' => 570,
            'startColumn' => 40,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set the spacing line rule.
 *
 * @param string $value Possible values are defined in LineSpacingRule
 *
 * @return Paragraph
 */',
        'startLine' => 570,
        'endLine' => 573,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'getLineHeight' => 
      array (
        'name' => 'getLineHeight',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get line height.
 *
 * @return null|float|int
 */',
        'startLine' => 580,
        'endLine' => 583,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setLineHeight' => 
      array (
        'name' => 'setLineHeight',
        'parameters' => 
        array (
          'lineHeight' => 
          array (
            'name' => 'lineHeight',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 592,
            'endLine' => 592,
            'startColumn' => 35,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set the line height.
 *
 * @param float|int|string $lineHeight
 *
 * @return self
 */',
        'startLine' => 592,
        'endLine' => 607,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'hasWidowControl' => 
      array (
        'name' => 'hasWidowControl',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get allow first/last line to display on a separate page setting.
 *
 * @return bool
 */',
        'startLine' => 614,
        'endLine' => 617,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setWidowControl' => 
      array (
        'name' => 'setWidowControl',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => 'true',
              'attributes' => 
              array (
                'startLine' => 626,
                'endLine' => 626,
                'startTokenPos' => 1821,
                'startFilePos' => 13217,
                'endTokenPos' => 1821,
                'endFilePos' => 13220,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 626,
            'endLine' => 626,
            'startColumn' => 37,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set keep paragraph with next paragraph setting.
 *
 * @param bool $value
 *
 * @return self
 */',
        'startLine' => 626,
        'endLine' => 631,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'isKeepNext' => 
      array (
        'name' => 'isKeepNext',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get keep paragraph with next paragraph setting.
 *
 * @return bool
 */',
        'startLine' => 638,
        'endLine' => 641,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setKeepNext' => 
      array (
        'name' => 'setKeepNext',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => 'true',
              'attributes' => 
              array (
                'startLine' => 650,
                'endLine' => 650,
                'startTokenPos' => 1885,
                'startFilePos' => 13685,
                'endTokenPos' => 1885,
                'endFilePos' => 13688,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 650,
            'endLine' => 650,
            'startColumn' => 33,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set keep paragraph with next paragraph setting.
 *
 * @param bool $value
 *
 * @return self
 */',
        'startLine' => 650,
        'endLine' => 655,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'isKeepLines' => 
      array (
        'name' => 'isKeepLines',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get keep all lines on one page setting.
 *
 * @return bool
 */',
        'startLine' => 662,
        'endLine' => 665,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setKeepLines' => 
      array (
        'name' => 'setKeepLines',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => 'true',
              'attributes' => 
              array (
                'startLine' => 674,
                'endLine' => 674,
                'startTokenPos' => 1949,
                'startFilePos' => 14132,
                'endTokenPos' => 1949,
                'endFilePos' => 14135,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 674,
            'endLine' => 674,
            'startColumn' => 34,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set keep all lines on one page setting.
 *
 * @param bool $value
 *
 * @return self
 */',
        'startLine' => 674,
        'endLine' => 679,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'hasPageBreakBefore' => 
      array (
        'name' => 'hasPageBreakBefore',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get start paragraph on next page setting.
 *
 * @return bool
 */',
        'startLine' => 686,
        'endLine' => 689,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setPageBreakBefore' => 
      array (
        'name' => 'setPageBreakBefore',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => 'true',
              'attributes' => 
              array (
                'startLine' => 698,
                'endLine' => 698,
                'startTokenPos' => 2013,
                'startFilePos' => 14604,
                'endTokenPos' => 2013,
                'endFilePos' => 14607,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 698,
            'endLine' => 698,
            'startColumn' => 40,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set start paragraph on next page setting.
 *
 * @param bool $value
 *
 * @return self
 */',
        'startLine' => 698,
        'endLine' => 703,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'getNumStyle' => 
      array (
        'name' => 'getNumStyle',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get numbering style name.
 *
 * @return string
 */',
        'startLine' => 710,
        'endLine' => 713,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setNumStyle' => 
      array (
        'name' => 'setNumStyle',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 722,
            'endLine' => 722,
            'startColumn' => 33,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set numbering style name.
 *
 * @param string $value
 *
 * @return self
 */',
        'startLine' => 722,
        'endLine' => 727,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'getNumLevel' => 
      array (
        'name' => 'getNumLevel',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get numbering level.
 *
 * @return int
 */',
        'startLine' => 734,
        'endLine' => 737,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setNumLevel' => 
      array (
        'name' => 'setNumLevel',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 746,
                'endLine' => 746,
                'startTokenPos' => 2127,
                'startFilePos' => 15401,
                'endTokenPos' => 2127,
                'endFilePos' => 15401,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 746,
            'endLine' => 746,
            'startColumn' => 33,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set numbering level.
 *
 * @param int $value
 *
 * @return self
 */',
        'startLine' => 746,
        'endLine' => 751,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'getTabs' => 
      array (
        'name' => 'getTabs',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get tabs.
 *
 * @return Tab[]
 */',
        'startLine' => 758,
        'endLine' => 761,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setTabs' => 
      array (
        'name' => 'setTabs',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 770,
                'endLine' => 770,
                'startTokenPos' => 2191,
                'startFilePos' => 15772,
                'endTokenPos' => 2191,
                'endFilePos' => 15775,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 770,
            'endLine' => 770,
            'startColumn' => 29,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set tabs.
 *
 * @param array $value
 *
 * @return self
 */',
        'startLine' => 770,
        'endLine' => 777,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'getShading' => 
      array (
        'name' => 'getShading',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get shading.
 *
 * @return Shading
 */',
        'startLine' => 784,
        'endLine' => 787,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setShading' => 
      array (
        'name' => 'setShading',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 796,
                'endLine' => 796,
                'startTokenPos' => 2258,
                'startFilePos' => 16170,
                'endTokenPos' => 2258,
                'endFilePos' => 16173,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 796,
            'endLine' => 796,
            'startColumn' => 32,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set shading.
 *
 * @param mixed $value
 *
 * @return self
 */',
        'startLine' => 796,
        'endLine' => 801,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'hasContextualSpacing' => 
      array (
        'name' => 'hasContextualSpacing',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get contextualSpacing.
 *
 * @return bool
 */',
        'startLine' => 808,
        'endLine' => 811,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setContextualSpacing' => 
      array (
        'name' => 'setContextualSpacing',
        'parameters' => 
        array (
          'contextualSpacing' => 
          array (
            'name' => 'contextualSpacing',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 820,
            'endLine' => 820,
            'startColumn' => 42,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set contextualSpacing.
 *
 * @param bool $contextualSpacing
 *
 * @return self
 */',
        'startLine' => 820,
        'endLine' => 825,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'isBidi' => 
      array (
        'name' => 'isBidi',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get bidirectional.
 *
 * @return ?bool
 */',
        'startLine' => 832,
        'endLine' => 835,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setBidi' => 
      array (
        'name' => 'setBidi',
        'parameters' => 
        array (
          'bidi' => 
          array (
            'name' => 'bidi',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 845,
            'endLine' => 845,
            'startColumn' => 29,
            'endColumn' => 33,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set bidi.
 *
 * @param ?bool $bidi
 *            Set to true to write from right to left
 *
 * @return self
 */',
        'startLine' => 845,
        'endLine' => 850,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'getTextAlignment' => 
      array (
        'name' => 'getTextAlignment',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get textAlignment.
 *
 * @return string
 */',
        'startLine' => 857,
        'endLine' => 860,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setTextAlignment' => 
      array (
        'name' => 'setTextAlignment',
        'parameters' => 
        array (
          'textAlignment' => 
          array (
            'name' => 'textAlignment',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 869,
            'endLine' => 869,
            'startColumn' => 38,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set textAlignment.
 *
 * @param string $textAlignment
 *
 * @return self
 */',
        'startLine' => 869,
        'endLine' => 875,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'hasSuppressAutoHyphens' => 
      array (
        'name' => 'hasSuppressAutoHyphens',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return bool
 */',
        'startLine' => 880,
        'endLine' => 883,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'aliasName' => NULL,
      ),
      'setSuppressAutoHyphens' => 
      array (
        'name' => 'setSuppressAutoHyphens',
        'parameters' => 
        array (
          'suppressAutoHyphens' => 
          array (
            'name' => 'suppressAutoHyphens',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 888,
            'endLine' => 888,
            'startColumn' => 44,
            'endColumn' => 63,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param bool $suppressAutoHyphens
 */',
        'startLine' => 888,
        'endLine' => 891,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpOffice\\PhpWord\\Style',
        'declaringClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'implementingClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
        'currentClassName' => 'PhpOffice\\PhpWord\\Style\\Paragraph',
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