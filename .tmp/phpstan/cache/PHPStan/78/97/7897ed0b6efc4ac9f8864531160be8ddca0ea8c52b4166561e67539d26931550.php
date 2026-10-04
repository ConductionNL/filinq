<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Conversion/PhpWordBackend.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Conversion\PhpWordBackend
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-1cc9130a59f9393d411a868cae4d1084724b370467bc3951fa5bdd606f8a22e3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Conversion/PhpWordBackend.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Conversion',
    'name' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
    'shortName' => 'PhpWordBackend',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Converts DOC/DOCX/ODT/RTF/HTML to PDF via PhpWord-HTML + PdfService.
 *
 * PhpWord\'s HTML writer produces the intermediate representation;
 * PdfService renders that HTML to mPDF-emitted PDF/A-3b with its own
 * writable temp-dir handling. Inputs that PhpWord can\'t read raise
 * ConversionFailedException for the cascade to fall through.
 *
 * @category  Service
 * @package   OCA\\Filinq\\Service\\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 64,
    'endLine' => 456,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'OCA\\Filinq\\Service\\Conversion\\ConversionBackendInterface',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'ENABLED_KEY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'name' => 'ENABLED_KEY',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq.conversion.backends.phpword_enabled\'',
          'attributes' => 
          array (
            'startLine' => 70,
            'endLine' => 70,
            'startTokenPos' => 79,
            'startFilePos' => 2617,
            'endTokenPos' => 79,
            'endFilePos' => 2660,
          ),
        ),
        'docComment' => '/**
 * App config key controlling whether this backend is attempted.
 * Default true; tenants disable for testing or forced fall-through.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 70,
        'endLine' => 70,
        'startColumn' => 2,
        'endColumn' => 74,
      ),
      'APP_ID' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'name' => 'APP_ID',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq\'',
          'attributes' => 
          array (
            'startLine' => 75,
            'endLine' => 75,
            'startTokenPos' => 92,
            'startFilePos' => 2751,
            'endTokenPos' => 92,
            'endFilePos' => 2758,
          ),
        ),
        'docComment' => '/**
 * App identifier used for IAppConfig reads/writes.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 75,
        'endLine' => 75,
        'startColumn' => 2,
        'endColumn' => 33,
      ),
      'READER_BY_EXT' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'name' => 'READER_BY_EXT',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'doc\' => \'MsDoc\', \'docx\' => \'Word2007\', \'odt\' => \'ODText\', \'rtf\' => \'RTF\', \'html\' => \'HTML\', \'htm\' => \'HTML\']',
          'attributes' => 
          array (
            'startLine' => 85,
            'endLine' => 92,
            'startTokenPos' => 105,
            'startFilePos' => 3058,
            'endTokenPos' => 149,
            'endFilePos' => 3183,
          ),
        ),
        'docComment' => '/**
 * PhpWord reader name to use per extension. PhpWord\'s IOFactory
 * normally auto-detects, but explicit dispatch sidesteps the auto-
 * detect heuristic on edge cases (e.g. RTF files with no leading
 * magic bytes).
 *
 * @var array<string, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 85,
        'endLine' => 92,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'phpWordIo' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'name' => 'phpWordIo',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordIo',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Seam over PhpWord\'s reader/writer construction.
 *
 * @var PhpWordIo
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 99,
        'endLine' => 99,
        'startColumn' => 2,
        'endColumn' => 39,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'appConfig' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'name' => 'appConfig',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IAppConfig',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 113,
        'endLine' => 113,
        'startColumn' => 3,
        'endColumn' => 40,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'tempManager' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'name' => 'tempManager',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\ITempManager',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 114,
        'endLine' => 114,
        'startColumn' => 3,
        'endColumn' => 44,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'pdfService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'name' => 'pdfService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\PdfService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 115,
        'endLine' => 115,
        'startColumn' => 3,
        'endColumn' => 41,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
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
        'startLine' => 116,
        'endLine' => 116,
        'startColumn' => 3,
        'endColumn' => 42,
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
          'appConfig' => 
          array (
            'name' => 'appConfig',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IAppConfig',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 113,
            'endLine' => 113,
            'startColumn' => 3,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'tempManager' => 
          array (
            'name' => 'tempManager',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\ITempManager',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 114,
            'endLine' => 114,
            'startColumn' => 3,
            'endColumn' => 44,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'pdfService' => 
          array (
            'name' => 'pdfService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\PdfService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 115,
            'endLine' => 115,
            'startColumn' => 3,
            'endColumn' => 41,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
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
            'startLine' => 116,
            'endLine' => 116,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'phpWordIo' => 
          array (
            'name' => 'phpWordIo',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 117,
                'endLine' => 117,
                'startTokenPos' => 215,
                'startFilePos' => 4102,
                'endTokenPos' => 215,
                'endFilePos' => 4105,
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
                      'name' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordIo',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 117,
            'endLine' => 117,
            'startColumn' => 3,
            'endColumn' => 30,
            'parameterIndex' => 4,
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
 * @param IAppConfig $appConfig Tenant configuration provider.
 * @param ITempManager $tempManager Provides Nextcloud-managed temp paths.
 * @param PdfService $pdfService Renders the HTML produced by PhpWord to PDF/A-3b.
 * @param LoggerInterface $logger Logger for diagnostics.
 * @param PhpWordIo|null $phpWordIo Seam over PhpWord reader/writer construction;
 *                                  autowired in production, defaulted here so
 *                                  existing call sites stay source-compatible.
 */',
        'startLine' => 112,
        'endLine' => 121,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'aliasName' => NULL,
      ),
      'name' => 
      array (
        'name' => 'name',
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
 * Backend identifier surfaced in the 422 body\'s `conversionAttempts[].name`.
 *
 * @return string Identifier surfaced in 422 attempt records.
 */',
        'startLine' => 128,
        'endLine' => 130,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'aliasName' => NULL,
      ),
      'isAvailable' => 
      array (
        'name' => 'isAvailable',
        'parameters' => 
        array (
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
 * Available iff the tenant flag is set AND the PhpWord library is
 * actually present at runtime (autoload should always make it so
 * once composer require lands, but defensively check).
 *
 * @return bool
 */',
        'startLine' => 139,
        'endLine' => 146,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'aliasName' => NULL,
      ),
      'canHandle' => 
      array (
        'name' => 'canHandle',
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
            'startLine' => 156,
            'endLine' => 156,
            'startColumn' => 28,
            'endColumn' => 43,
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
            'startLine' => 156,
            'endLine' => 156,
            'startColumn' => 46,
            'endColumn' => 62,
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
 * Declare whether PhpWord can read the source format.
 *
 * @param string $mimeType Source MIME.
 * @param string $extension Source extension (lowercased, no dot).
 *
 * @return bool True for Word-family formats PhpWord can read.
 */',
        'startLine' => 156,
        'endLine' => 172,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'aliasName' => NULL,
      ),
      'convert' => 
      array (
        'name' => 'convert',
        'parameters' => 
        array (
          'source' => 
          array (
            'name' => 'source',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Files\\File',
                'isIdentifier' => false,
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
            'startColumn' => 26,
            'endColumn' => 37,
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
            'name' => 'OCP\\Files\\File',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Convert via PhpWord-HTML + PdfService. PhpWord reads the source
 * into its document model; the HTML writer\'s getContent() yields
 * an HTML string that PdfService renders to PDF/A-3b through mPDF.
 *
 * @param File $source Source file node.
 *
 * @return File Newly written PDF file node.
 *
 * @throws ConversionFailedException On read/render failure.
 */',
        'startLine' => 185,
        'endLine' => 201,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'aliasName' => NULL,
      ),
      'extractExtension' => 
      array (
        'name' => 'extractExtension',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
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
            'startLine' => 211,
            'endLine' => 211,
            'startColumn' => 36,
            'endColumn' => 47,
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
 * Return the lowercased extension of $name without the leading dot.
 *
 * @param string $name File name, with or without an extension.
 *
 * @return string Lowercased extension, or an empty string when the name
 *                carries no dot.
 */',
        'startLine' => 211,
        'endLine' => 218,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'aliasName' => NULL,
      ),
      'resolveReaderName' => 
      array (
        'name' => 'resolveReaderName',
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
            'startLine' => 229,
            'endLine' => 229,
            'startColumn' => 37,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Map a source extension onto the PhpWord reader that handles it.
 *
 * @param string $extension Lowercased extension without the dot.
 *
 * @return string PhpWord reader short name.
 *
 * @throws ConversionFailedException When no reader is mapped for $extension.
 */',
        'startLine' => 229,
        'endLine' => 246,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'aliasName' => NULL,
      ),
      'materialiseSource' => 
      array (
        'name' => 'materialiseSource',
        'parameters' => 
        array (
          'source' => 
          array (
            'name' => 'source',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Files\\File',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 262,
            'endLine' => 262,
            'startColumn' => 37,
            'endColumn' => 48,
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
            'startLine' => 262,
            'endLine' => 262,
            'startColumn' => 51,
            'endColumn' => 67,
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
 * Write the source bytes to a Nextcloud-managed temp file.
 *
 * PhpWord readers operate on file paths, not streams, so the node\'s
 * content has to be materialised on disk first.
 *
 * @param File $source Source file node.
 * @param string $extension Lowercased extension, used for the temp suffix.
 *
 * @return string Absolute path of the temp file holding the source bytes.
 *
 * @throws ConversionFailedException When the node yields no readable content,
 *                                   or no temp file could be allocated.
 */',
        'startLine' => 262,
        'endLine' => 299,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'aliasName' => NULL,
      ),
      'renderHtml' => 
      array (
        'name' => 'renderHtml',
        'parameters' => 
        array (
          'sourceTmp' => 
          array (
            'name' => 'sourceTmp',
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
            'startLine' => 311,
            'endLine' => 311,
            'startColumn' => 30,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'readerName' => 
          array (
            'name' => 'readerName',
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
            'startLine' => 311,
            'endLine' => 311,
            'startColumn' => 49,
            'endColumn' => 66,
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
 * Parse the source with PhpWord and render it to the HTML intermediate.
 *
 * @param string $sourceTmp Path of the materialised source document.
 * @param string $readerName PhpWord reader short name.
 *
 * @return string Non-empty HTML, with PhpWord\'s `@page` rules stripped.
 *
 * @throws ConversionFailedException On read failure, writer failure, or empty output.
 */',
        'startLine' => 311,
        'endLine' => 361,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'aliasName' => NULL,
      ),
      'renderPdf' => 
      array (
        'name' => 'renderPdf',
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
            'startLine' => 380,
            'endLine' => 380,
            'startColumn' => 29,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'name' => 
          array (
            'name' => 'name',
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
            'startLine' => 380,
            'endLine' => 380,
            'startColumn' => 43,
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
 * Render the HTML intermediate to PDF/A-3b bytes.
 *
 * Mirrors MpdfBackend: request PDF/A-3b output and a known page format so
 * the docx PDF carries the same normalization print CSS
 * (PdfService::buildPrintCss) and archival container as every other
 * anonymised output. Without these options the renderer skips the PDF/A +
 * print-CSS branch entirely, leaving the docx PDF non-conformant and
 * un-normalized.
 *
 * @param string $html HTML produced by PhpWord\'s HTML writer.
 * @param string $name Source file name, used to derive the PDF title.
 *
 * @return string Non-empty PDF bytes.
 *
 * @throws ConversionFailedException On renderer failure or empty output.
 */',
        'startLine' => 380,
        'endLine' => 420,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'aliasName' => NULL,
      ),
      'stripAtPageRules' => 
      array (
        'name' => 'stripAtPageRules',
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
            'startLine' => 437,
            'endLine' => 437,
            'startColumn' => 36,
            'endColumn' => 47,
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
 * Strip CSS `@page` rules emitted by PhpWord\'s HTML writer.
 *
 * PhpWord emits a per-section `@page pageN { size: A4 portrait;
 * margin-*: ...; }` rule. mPDF\'s CSS parser mishandles that
 * construct and degenerates into one character per page (the
 * same root cause noted in PdfService::buildPrintCss). Page
 * geometry is already set by mPDF\'s config (format / orientation
 * / margin_* in PdfService::buildMpdfConfig), so the rule is
 * redundant — dropping it is safe.
 *
 * @param string $html HTML output from PhpWord\'s HTML writer.
 *
 * @return string Same HTML with all `@page` rules removed.
 */',
        'startLine' => 437,
        'endLine' => 439,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'aliasName' => NULL,
      ),
      'stripExtension' => 
      array (
        'name' => 'stripExtension',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
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
            'startLine' => 448,
            'endLine' => 448,
            'startColumn' => 34,
            'endColumn' => 45,
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
 * Return $name without its trailing `.ext`.
 *
 * @param string $name File name with extension.
 *
 * @return string
 */',
        'startLine' => 448,
        'endLine' => 455,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\PhpWordBackend',
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