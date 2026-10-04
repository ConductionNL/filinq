<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/XmlBlockScanner.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Editing\XmlBlockScanner
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-74c0875dc3c4a6c0d6b80392eb7da3a0a145da5bb6befceb8aaa978312397125',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Editing\\XmlBlockScanner',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/XmlBlockScanner.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Editing',
    'name' => 'OCA\\Filinq\\Service\\Editing\\XmlBlockScanner',
    'shortName' => 'XmlBlockScanner',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Finds element spans in a document part.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-untouched-parts-of-a-document-package-survive-an-edit-unchanged
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 45,
    'endLine' => 221,
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
      'NAME_DELIMITERS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\XmlBlockScanner',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\XmlBlockScanner',
        'name' => 'NAME_DELIMITERS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\' \', \'>\', \'/\', "\\t", "\\n", "\\r"]',
          'attributes' => 
          array (
            'startLine' => 54,
            'endLine' => 54,
            'startTokenPos' => 35,
            'startFilePos' => 1785,
            'endTokenPos' => 52,
            'endFilePos' => 1817,
          ),
        ),
        'docComment' => '/**
 * Characters that may follow an element name in a well-formed start tag.
 *
 * Without this check, `<w:t` matches `<w:tab/>` and `<w:tbl>`.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 54,
        'endLine' => 54,
        'startColumn' => 2,
        'endColumn' => 67,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'spansForTags' => 
      array (
        'name' => 'spansForTags',
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
            'startLine' => 78,
            'endLine' => 78,
            'startColumn' => 31,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'tags' => 
          array (
            'name' => 'tags',
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
            'startLine' => 78,
            'endLine' => 78,
            'startColumn' => 44,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Locate every top-level occurrence of ANY of several elements, in document
 * order.
 *
 * 🔴 One block model, several element names. ODF writes a paragraph as
 * `text:p` and a HEADING as `text:h` — a different element, not a styled
 * paragraph. Scanning only `text:p` therefore made every heading in an
 * `.odt` invisible: measured on a four-block document, readDocument
 * reported three, and an agent asked to edit the heading was told its
 * anchor did not exist for text plainly on the page.
 *
 * Nested occurrences are still not returned separately, and the merged
 * result is sorted by offset so callers keep the descending-rewrite
 * guarantee that stops one edit moving another\'s offsets.
 *
 * @param string $xml The part XML.
 * @param array<int, string> $tags The element names that count as a block.
 *
 * @return array<int, array{0: int, 1: int}> Offset/length pairs, in document order.
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-untouched-parts-of-a-document-package-survive-an-edit-unchanged
 */',
        'startLine' => 78,
        'endLine' => 89,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\XmlBlockScanner',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\XmlBlockScanner',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\XmlBlockScanner',
        'aliasName' => NULL,
      ),
      'spans' => 
      array (
        'name' => 'spans',
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
            'startLine' => 105,
            'endLine' => 105,
            'startColumn' => 24,
            'endColumn' => 34,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'tag' => 
          array (
            'name' => 'tag',
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
            'startLine' => 105,
            'endLine' => 105,
            'startColumn' => 37,
            'endColumn' => 47,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Locate every top-level occurrence of ONE element, in document order.
 *
 * Nested occurrences are NOT returned separately: an outer element\'s span
 * already contains them, and returning both would let one edit rewrite a
 * range another edit is still holding an offset into.
 *
 * @param string $xml The part XML.
 * @param string $tag The element name.
 *
 * @return array<int, array{0: int, 1: int}> Offset/length pairs.
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-untouched-parts-of-a-document-package-survive-an-edit-unchanged
 */',
        'startLine' => 105,
        'endLine' => 146,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\XmlBlockScanner',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\XmlBlockScanner',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\XmlBlockScanner',
        'aliasName' => NULL,
      ),
      'matchingClose' => 
      array (
        'name' => 'matchingClose',
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
            'startLine' => 159,
            'endLine' => 159,
            'startColumn' => 33,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'cursor' => 
          array (
            'name' => 'cursor',
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
            'startLine' => 159,
            'endLine' => 159,
            'startColumn' => 46,
            'endColumn' => 56,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'open' => 
          array (
            'name' => 'open',
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
            'startLine' => 159,
            'endLine' => 159,
            'startColumn' => 59,
            'endColumn' => 70,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'close' => 
          array (
            'name' => 'close',
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
            'startLine' => 159,
            'endLine' => 159,
            'startColumn' => 73,
            'endColumn' => 85,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'openLength' => 
          array (
            'name' => 'openLength',
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
            'startLine' => 159,
            'endLine' => 159,
            'startColumn' => 88,
            'endColumn' => 102,
            'parameterIndex' => 4,
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
                  'name' => 'int',
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
 * Walk forward to the close tag that matches an already-opened element.
 *
 * @param string $xml The part XML.
 * @param int $cursor The offset just past the opening tag.
 * @param string $open The `<name` prefix.
 * @param string $close The `</name>` string.
 * @param int $openLength The length of `$open`.
 *
 * @return int|null The offset just past the matching close tag, or null when the XML is malformed.
 */',
        'startLine' => 159,
        'endLine' => 189,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\XmlBlockScanner',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\XmlBlockScanner',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\XmlBlockScanner',
        'aliasName' => NULL,
      ),
      'opensAnotherLevel' => 
      array (
        'name' => 'opensAnotherLevel',
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
            'startLine' => 202,
            'endLine' => 202,
            'startColumn' => 37,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'nameEnd' => 
          array (
            'name' => 'nameEnd',
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
            'startLine' => 202,
            'endLine' => 202,
            'startColumn' => 50,
            'endColumn' => 61,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'tagEnd' => 
          array (
            'name' => 'tagEnd',
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
            'startLine' => 202,
            'endLine' => 202,
            'startColumn' => 64,
            'endColumn' => 74,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Whether a start tag at this position opens a nested level of the same element.
 *
 * A self-closing tag does not: it opens and closes in one place.
 *
 * @param string $xml The part XML.
 * @param int $nameEnd The offset just past the element name.
 * @param int $tagEnd The offset of the tag\'s closing angle bracket.
 *
 * @return bool True when depth increases here.
 */',
        'startLine' => 202,
        'endLine' => 208,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\XmlBlockScanner',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\XmlBlockScanner',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\XmlBlockScanner',
        'aliasName' => NULL,
      ),
      'isElementStart' => 
      array (
        'name' => 'isElementStart',
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
            'startLine' => 218,
            'endLine' => 218,
            'startColumn' => 34,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'position' => 
          array (
            'name' => 'position',
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
            'startLine' => 218,
            'endLine' => 218,
            'startColumn' => 47,
            'endColumn' => 59,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Whether the character at a position legitimately ends an element name.
 *
 * @param string $xml The part XML.
 * @param int $position The offset just past the element name.
 *
 * @return bool True when the name ends here.
 */',
        'startLine' => 218,
        'endLine' => 220,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\XmlBlockScanner',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\XmlBlockScanner',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\XmlBlockScanner',
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