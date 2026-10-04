<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/OcrService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\OcrService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-ab98e599587e3881543443caf2404af6ccf46d168fcbe0d960f404b84e6ba7c4',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\OcrService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/OcrService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\OcrService',
    'shortName' => 'OcrService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service for OCR text extraction from scanned documents
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 54,
    'endLine' => 621,
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
      'IMAGE_MIME_TYPES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'name' => 'IMAGE_MIME_TYPES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'image/png\', \'image/jpeg\', \'image/tiff\', \'image/bmp\', \'image/gif\']',
          'attributes' => 
          array (
            'startLine' => 61,
            'endLine' => 67,
            'startTokenPos' => 75,
            'startFilePos' => 1838,
            'endTokenPos' => 92,
            'endFilePos' => 1918,
          ),
        ),
        'docComment' => '/**
 * MIME types that always require OCR processing
 *
 * @var array<string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 61,
        'endLine' => 67,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
      'PDF_MIME_TYPES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'name' => 'PDF_MIME_TYPES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'application/pdf\']',
          'attributes' => 
          array (
            'startLine' => 74,
            'endLine' => 76,
            'startTokenPos' => 105,
            'startFilePos' => 2050,
            'endTokenPos' => 110,
            'endFilePos' => 2074,
          ),
        ),
        'docComment' => '/**
 * MIME types that may require OCR if no text is embedded
 *
 * @var array<string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 74,
        'endLine' => 76,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
      'DEFAULT_LANGUAGES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'name' => 'DEFAULT_LANGUAGES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'nld+eng\'',
          'attributes' => 
          array (
            'startLine' => 83,
            'endLine' => 83,
            'startTokenPos' => 123,
            'startFilePos' => 2183,
            'endTokenPos' => 123,
            'endFilePos' => 2191,
          ),
        ),
        'docComment' => '/**
 * Default OCR languages for Tesseract
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 83,
        'endLine' => 83,
        'startColumn' => 2,
        'endColumn' => 45,
      ),
      'DEFAULT_DPI' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'name' => 'DEFAULT_DPI',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '300',
          'attributes' => 
          array (
            'startLine' => 90,
            'endLine' => 90,
            'startTokenPos' => 136,
            'startFilePos' => 2295,
            'endTokenPos' => 136,
            'endFilePos' => 2297,
          ),
        ),
        'docComment' => '/**
 * Default DPI for PDF-to-image conversion
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 90,
        'endLine' => 90,
        'startColumn' => 2,
        'endColumn' => 33,
      ),
      'APP_NAME' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'name' => 'APP_NAME',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq\'',
          'attributes' => 
          array (
            'startLine' => 97,
            'endLine' => 97,
            'startTokenPos' => 149,
            'startFilePos' => 2397,
            'endTokenPos' => 149,
            'endFilePos' => 2404,
          ),
        ),
        'docComment' => '/**
 * Application name for config lookups
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 97,
        'endLine' => 97,
        'startColumn' => 2,
        'endColumn' => 35,
      ),
    ),
    'immediateProperties' => 
    array (
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OcrService',
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
        'startLine' => 110,
        'endLine' => 110,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'config' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'name' => 'config',
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
        'startLine' => 111,
        'endLine' => 111,
        'startColumn' => 3,
        'endColumn' => 37,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'rootFolder' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'name' => 'rootFolder',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\Files\\IRootFolder',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 112,
        'endLine' => 112,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'userSession' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'name' => 'userSession',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IUserSession',
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
        'endColumn' => 44,
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
            'startLine' => 110,
            'endLine' => 110,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'config' => 
          array (
            'name' => 'config',
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
            'startLine' => 111,
            'endLine' => 111,
            'startColumn' => 3,
            'endColumn' => 37,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'rootFolder' => 
          array (
            'name' => 'rootFolder',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Files\\IRootFolder',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 112,
            'endLine' => 112,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'userSession' => 
          array (
            'name' => 'userSession',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IUserSession',
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
            'endColumn' => 44,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor for OcrService
 *
 * @param LoggerInterface $logger Logger for error reporting
 * @param IAppConfig $config App configuration interface
 * @param IRootFolder $rootFolder Root folder for file access
 * @param IUserSession $userSession User session for current user
 *
 * @return void
 */',
        'startLine' => 109,
        'endLine' => 116,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'currentClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'aliasName' => NULL,
      ),
      'isTesseractAvailable' => 
      array (
        'name' => 'isTesseractAvailable',
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
 * Check if Tesseract OCR binary is available on the system
 *
 * @return bool True if Tesseract is installed and executable
 *
 * @spec openspec/specs/ocr-document-scanning/spec.md
 */',
        'startLine' => 125,
        'endLine' => 139,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'currentClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'aliasName' => NULL,
      ),
      'getTesseractVersion' => 
      array (
        'name' => 'getTesseractVersion',
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
 * Get the installed Tesseract version string
 *
 * @return string|null The version string or null if not available
 *
 * @spec openspec/specs/ocr-document-scanning/spec.md
 */',
        'startLine' => 148,
        'endLine' => 167,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'currentClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'aliasName' => NULL,
      ),
      'needsOcr' => 
      array (
        'name' => 'needsOcr',
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
            'startLine' => 179,
            'endLine' => 179,
            'startColumn' => 27,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'existingText' => 
          array (
            'name' => 'existingText',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 179,
                'endLine' => 179,
                'startTokenPos' => 465,
                'startFilePos' => 4629,
                'endTokenPos' => 465,
                'endFilePos' => 4632,
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
            'startLine' => 179,
            'endLine' => 179,
            'startColumn' => 45,
            'endColumn' => 72,
            'parameterIndex' => 1,
            'isOptional' => true,
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
 * Determine if a file needs OCR processing based on MIME type and existing text
 *
 * @param string $mimeType The file MIME type
 * @param string|null $existingText Existing extracted text content
 *
 * @return bool True if the file needs OCR processing
 *
 * @spec openspec/specs/ocr-document-scanning/spec.md
 */',
        'startLine' => 179,
        'endLine' => 192,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'currentClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'aliasName' => NULL,
      ),
      'isOcrEnabled' => 
      array (
        'name' => 'isOcrEnabled',
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
 * Check if OCR is enabled in admin settings
 *
 * @return bool True if OCR is enabled
 *
 * @spec openspec/specs/ocr-document-scanning/spec.md
 */',
        'startLine' => 201,
        'endLine' => 208,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'currentClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'aliasName' => NULL,
      ),
      'getOcrLanguages' => 
      array (
        'name' => 'getOcrLanguages',
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
 * Get configured OCR languages
 *
 * @return string Tesseract language string (e.g., "nld+eng")
 *
 * @spec openspec/specs/ocr-document-scanning/spec.md
 */',
        'startLine' => 217,
        'endLine' => 236,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'currentClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'aliasName' => NULL,
      ),
      'getOcrDpi' => 
      array (
        'name' => 'getOcrDpi',
        'parameters' => 
        array (
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
 * Get configured OCR DPI for PDF conversion
 *
 * @return int DPI value
 *
 * @spec openspec/specs/ocr-document-scanning/spec.md
 */',
        'startLine' => 245,
        'endLine' => 264,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'currentClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'aliasName' => NULL,
      ),
      'extractTextFromImage' => 
      array (
        'name' => 'extractTextFromImage',
        'parameters' => 
        array (
          'filePath' => 
          array (
            'name' => 'filePath',
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
            'startLine' => 280,
            'endLine' => 280,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'languages' => 
          array (
            'name' => 'languages',
            'default' => 
            array (
              'code' => 'self::DEFAULT_LANGUAGES',
              'attributes' => 
              array (
                'startLine' => 281,
                'endLine' => 281,
                'startTokenPos' => 807,
                'startFilePos' => 7209,
                'endTokenPos' => 809,
                'endFilePos' => 7231,
              ),
            ),
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
            'startLine' => 281,
            'endLine' => 281,
            'startColumn' => 3,
            'endColumn' => 45,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'dpi' => 
          array (
            'name' => 'dpi',
            'default' => 
            array (
              'code' => 'self::DEFAULT_DPI',
              'attributes' => 
              array (
                'startLine' => 282,
                'endLine' => 282,
                'startTokenPos' => 818,
                'startFilePos' => 7247,
                'endTokenPos' => 820,
                'endFilePos' => 7263,
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
            'startLine' => 282,
            'endLine' => 282,
            'startColumn' => 3,
            'endColumn' => 30,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Extract text from an image file using Tesseract OCR
 *
 * @param string $filePath Path to the image file
 * @param string $languages Tesseract language string
 * @param int $dpi DPI setting (unused for direct images)
 *
 * @return array{text: string, confidence: float} Extracted text and confidence
 *
 * @throws Exception If OCR processing fails
 *
 * @spec openspec/specs/ocr-document-scanning/spec.md
 */',
        'startLine' => 279,
        'endLine' => 326,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'currentClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'aliasName' => NULL,
      ),
      'extractTextFromPdf' => 
      array (
        'name' => 'extractTextFromPdf',
        'parameters' => 
        array (
          'filePath' => 
          array (
            'name' => 'filePath',
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
            'startLine' => 342,
            'endLine' => 342,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'languages' => 
          array (
            'name' => 'languages',
            'default' => 
            array (
              'code' => 'self::DEFAULT_LANGUAGES',
              'attributes' => 
              array (
                'startLine' => 343,
                'endLine' => 343,
                'startTokenPos' => 1117,
                'startFilePos' => 8869,
                'endTokenPos' => 1119,
                'endFilePos' => 8891,
              ),
            ),
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
            'startLine' => 343,
            'endLine' => 343,
            'startColumn' => 3,
            'endColumn' => 45,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'dpi' => 
          array (
            'name' => 'dpi',
            'default' => 
            array (
              'code' => 'self::DEFAULT_DPI',
              'attributes' => 
              array (
                'startLine' => 344,
                'endLine' => 344,
                'startTokenPos' => 1128,
                'startFilePos' => 8907,
                'endTokenPos' => 1130,
                'endFilePos' => 8923,
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
            'startLine' => 344,
            'endLine' => 344,
            'startColumn' => 3,
            'endColumn' => 30,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Extract text from a PDF by converting pages to images and running OCR
 *
 * @param string $filePath Path to the PDF file
 * @param string $languages Tesseract language string
 * @param int $dpi DPI for PDF-to-image conversion
 *
 * @return array{text: string, confidence: float} Extracted text and average confidence
 *
 * @throws Exception If PDF conversion or OCR processing fails
 *
 * @spec openspec/specs/ocr-document-scanning/spec.md
 */',
        'startLine' => 341,
        'endLine' => 429,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'currentClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'aliasName' => NULL,
      ),
      'processFile' => 
      array (
        'name' => 'processFile',
        'parameters' => 
        array (
          'fileId' => 
          array (
            'name' => 'fileId',
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
            'startLine' => 443,
            'endLine' => 443,
            'startColumn' => 30,
            'endColumn' => 40,
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
 * Process a file for OCR text extraction
 *
 * Main entry point that reads OCR settings, gets the file from
 * Nextcloud filesystem, determines the processing path, and returns results.
 *
 * @param int $fileId The Nextcloud file ID
 *
 * @return array{text: string, confidence: float, ocrProcessed: bool} OCR results
 *
 * @spec openspec/specs/ocr-document-scanning/spec.md
 */',
        'startLine' => 443,
        'endLine' => 512,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'currentClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'aliasName' => NULL,
      ),
      'getFileById' => 
      array (
        'name' => 'getFileById',
        'parameters' => 
        array (
          'fileId' => 
          array (
            'name' => 'fileId',
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
            'startLine' => 521,
            'endLine' => 521,
            'startColumn' => 31,
            'endColumn' => 41,
            'parameterIndex' => 0,
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
                  'name' => 'OCP\\Files\\File',
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
 * Get a file by its Nextcloud file ID
 *
 * @param int $fileId The Nextcloud file ID
 *
 * @return File|null The file or null if not found
 */',
        'startLine' => 521,
        'endLine' => 540,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'currentClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'aliasName' => NULL,
      ),
      'writeToTemp' => 
      array (
        'name' => 'writeToTemp',
        'parameters' => 
        array (
          'file' => 
          array (
            'name' => 'file',
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
            'startLine' => 551,
            'endLine' => 551,
            'startColumn' => 31,
            'endColumn' => 40,
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
 * Write a Nextcloud file to a temporary location for processing
 *
 * @param File $file The Nextcloud file
 *
 * @return string Path to the temporary file
 *
 * @throws Exception If writing fails
 */',
        'startLine' => 551,
        'endLine' => 561,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'currentClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'aliasName' => NULL,
      ),
      'getConfidenceScore' => 
      array (
        'name' => 'getConfidenceScore',
        'parameters' => 
        array (
          'filePath' => 
          array (
            'name' => 'filePath',
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
            'startLine' => 576,
            'endLine' => 576,
            'startColumn' => 38,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'languages' => 
          array (
            'name' => 'languages',
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
            'startLine' => 576,
            'endLine' => 576,
            'startColumn' => 56,
            'endColumn' => 72,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'dpi' => 
          array (
            'name' => 'dpi',
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
            'startLine' => 576,
            'endLine' => 576,
            'startColumn' => 75,
            'endColumn' => 82,
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
            'name' => 'float',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get OCR confidence score for a file
 *
 * Uses Tesseract\'s hocr output to extract mean confidence.
 *
 * @param string $filePath Path to the image file
 * @param string $languages Tesseract language string
 * @param int $dpi DPI setting
 *
 * @return float Confidence score (0-100)
 *
 * @spec openspec/specs/ocr-document-scanning/spec.md
 */',
        'startLine' => 576,
        'endLine' => 620,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OcrService',
        'currentClassName' => 'OCA\\Filinq\\Service\\OcrService',
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