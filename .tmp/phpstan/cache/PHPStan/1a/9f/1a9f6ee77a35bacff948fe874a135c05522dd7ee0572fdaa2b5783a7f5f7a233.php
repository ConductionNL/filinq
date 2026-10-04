<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/PhpWordHtmlRenderer.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\PhpWordHtmlRenderer
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-dcf8e28bb2ad0edc4ef52875a8cf583c32e55d09a3fbef402a86b9718302c1e9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\PhpWordHtmlRenderer',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/PhpWordHtmlRenderer.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\PhpWordHtmlRenderer',
    'shortName' => 'PhpWordHtmlRenderer',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Renders Word-family bytes to an HTML fragment via PhpWord.
 *
 * @category  Service
 * @package   OCA\\Filinq\\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 45,
    'endLine' => 143,
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
      'READER_BY_EXT' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PhpWordHtmlRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PhpWordHtmlRenderer',
        'name' => 'READER_BY_EXT',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'doc\' => \\PhpOffice\\PhpWord\\Reader\\MsDoc::class, \'docx\' => \\PhpOffice\\PhpWord\\Reader\\Word2007::class, \'odt\' => \\PhpOffice\\PhpWord\\Reader\\ODText::class, \'rtf\' => \\PhpOffice\\PhpWord\\Reader\\RTF::class]',
          'attributes' => 
          array (
            'startLine' => 52,
            'endLine' => 57,
            'startTokenPos' => 69,
            'startFilePos' => 1632,
            'endTokenPos' => 107,
            'endFilePos' => 1738,
          ),
        ),
        'docComment' => '/**
 * PhpWord reader class per lowercased file extension.
 *
 * @var array<string, class-string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 52,
        'endLine' => 57,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
      'EXT_BY_MIME' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PhpWordHtmlRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PhpWordHtmlRenderer',
        'name' => 'EXT_BY_MIME',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'application/msword\' => \'doc\', \'application/vnd.openxmlformats-officedocument.wordprocessingml.document\' => \'docx\', \'application/vnd.oasis.opendocument.text\' => \'odt\', \'application/rtf\' => \'rtf\', \'text/rtf\' => \'rtf\']',
          'attributes' => 
          array (
            'startLine' => 65,
            'endLine' => 71,
            'startTokenPos' => 120,
            'startFilePos' => 1918,
            'endTokenPos' => 157,
            'endFilePos' => 2148,
          ),
        ),
        'docComment' => '/**
 * File extension per Word-family MIME type, used when the filename
 * carries no usable extension.
 *
 * @var array<string, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 65,
        'endLine' => 71,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'renderToHtml' => 
      array (
        'name' => 'renderToHtml',
        'parameters' => 
        array (
          'bytes' => 
          array (
            'name' => 'bytes',
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
            'startLine' => 88,
            'endLine' => 88,
            'startColumn' => 31,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'mimeType' => 
          array (
            'name' => 'mimeType',
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
            'startLine' => 88,
            'endLine' => 88,
            'startColumn' => 46,
            'endColumn' => 61,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'filename' => 
          array (
            'name' => 'filename',
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
            'startLine' => 88,
            'endLine' => 88,
            'startColumn' => 64,
            'endColumn' => 79,
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
 * Render Word-family bytes to an HTML fragment.
 *
 * PhpWord\'s HTML writer emits `@page` at-rules that mPDF would honour as
 * page geometry; they are stripped so the shared A4 layout wins.
 *
 * @param string $bytes Redacted Word bytes.
 * @param string $mimeType Lowercased MIME type.
 * @param string $filename Attachment filename (extension hint).
 *
 * @return string Rendered HTML fragment.
 *
 * @throws RuntimeException When no reader matches or the render yields nothing.
 * @throws \\Throwable On read failure (caught by the caller).
 */',
        'startLine' => 88,
        'endLine' => 115,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PhpWordHtmlRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PhpWordHtmlRenderer',
        'currentClassName' => 'OCA\\Filinq\\Service\\PhpWordHtmlRenderer',
        'aliasName' => NULL,
      ),
      'resolveReaderClass' => 
      array (
        'name' => 'resolveReaderClass',
        'parameters' => 
        array (
          'mimeType' => 
          array (
            'name' => 'mimeType',
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
            'startLine' => 130,
            'endLine' => 130,
            'startColumn' => 38,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'filename' => 
          array (
            'name' => 'filename',
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
            'startLine' => 130,
            'endLine' => 130,
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
 * Resolve the PhpWord reader class for an attachment.
 *
 * The filename extension wins when it names a known reader; otherwise the
 * MIME type decides.
 *
 * @param string $mimeType Lowercased MIME type.
 * @param string $filename Attachment filename.
 *
 * @return class-string The PhpWord reader class to instantiate.
 *
 * @throws RuntimeException When neither the extension nor the MIME maps to a reader.
 */',
        'startLine' => 130,
        'endLine' => 142,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PhpWordHtmlRenderer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PhpWordHtmlRenderer',
        'currentClassName' => 'OCA\\Filinq\\Service\\PhpWordHtmlRenderer',
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