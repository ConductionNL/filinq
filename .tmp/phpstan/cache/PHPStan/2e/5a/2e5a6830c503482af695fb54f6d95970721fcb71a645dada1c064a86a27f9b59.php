<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Pdfa3ConversionService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Pdfa3ConversionService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-2d8568bcd98be616f1156d67a702d5f3934c3f65ff9400632a29dd60fcb3ec2a',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Pdfa3ConversionService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
    'shortName' => 'Pdfa3ConversionService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Converts an existing PDF or rendered HTML into a PDF/A-3b compliant
 * document with embedded attachments and MDTO/archival metadata.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 68,
    'endLine' => 658,
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
      'APP_ID' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'name' => 'APP_ID',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq\'',
          'attributes' => 
          array (
            'startLine' => 73,
            'endLine' => 73,
            'startTokenPos' => 75,
            'startFilePos' => 2646,
            'endTokenPos' => 75,
            'endFilePos' => 2653,
          ),
        ),
        'docComment' => '/**
 * App identifier used for IAppConfig reads.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 2,
        'endColumn' => 33,
      ),
      'CFG_ENABLED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'name' => 'CFG_ENABLED',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq.pdfa3.enabled\'',
          'attributes' => 
          array (
            'startLine' => 81,
            'endLine' => 81,
            'startTokenPos' => 88,
            'startFilePos' => 2948,
            'endTokenPos' => 88,
            'endFilePos' => 2969,
          ),
        ),
        'docComment' => '/**
 * App config key: master enable/disable switch for this service.
 * Default true; tenants disable to force a graceful "unavailable"
 * response (e.g. during a phased rollout, or when an install wants
 * to guarantee the endpoint is a hard no-op).
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 81,
        'endLine' => 81,
        'startColumn' => 2,
        'endColumn' => 52,
      ),
      'CFG_MAX_INPUT_BYTES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'name' => 'CFG_MAX_INPUT_BYTES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq.pdfa3.max_input_bytes\'',
          'attributes' => 
          array (
            'startLine' => 86,
            'endLine' => 86,
            'startTokenPos' => 101,
            'startFilePos' => 3075,
            'endTokenPos' => 101,
            'endFilePos' => 3104,
          ),
        ),
        'docComment' => '/**
 * App config key: maximum source PDF size, in bytes.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 86,
        'endLine' => 86,
        'startColumn' => 2,
        'endColumn' => 68,
      ),
      'CFG_MAX_SECONDS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'name' => 'CFG_MAX_SECONDS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq.pdfa3.max_seconds\'',
          'attributes' => 
          array (
            'startLine' => 91,
            'endLine' => 91,
            'startTokenPos' => 114,
            'startFilePos' => 3226,
            'endTokenPos' => 114,
            'endFilePos' => 3251,
          ),
        ),
        'docComment' => '/**
 * App config key: wall-clock time budget for one conversion, in seconds.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 91,
        'endLine' => 91,
        'startColumn' => 2,
        'endColumn' => 60,
      ),
      'DEFAULT_MAX_INPUT_BYTES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'name' => 'DEFAULT_MAX_INPUT_BYTES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '52428800',
          'attributes' => 
          array (
            'startLine' => 96,
            'endLine' => 96,
            'startTokenPos' => 127,
            'startFilePos' => 3342,
            'endTokenPos' => 127,
            'endFilePos' => 3349,
          ),
        ),
        'docComment' => '/**
 * Default cap: 50 MiB source PDF.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 96,
        'endLine' => 96,
        'startColumn' => 2,
        'endColumn' => 50,
      ),
      'DEFAULT_MAX_SECONDS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'name' => 'DEFAULT_MAX_SECONDS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '60',
          'attributes' => 
          array (
            'startLine' => 101,
            'endLine' => 101,
            'startTokenPos' => 140,
            'startFilePos' => 3444,
            'endTokenPos' => 140,
            'endFilePos' => 3445,
          ),
        ),
        'docComment' => '/**
 * Default cap: 60 seconds per conversion.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 101,
        'endLine' => 101,
        'startColumn' => 2,
        'endColumn' => 40,
      ),
      'PDFA_VERSION' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'name' => 'PDFA_VERSION',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'3-B\'',
          'attributes' => 
          array (
            'startLine' => 108,
            'endLine' => 108,
            'startTokenPos' => 153,
            'startFilePos' => 3690,
            'endTokenPos' => 153,
            'endFilePos' => 3694,
          ),
        ),
        'docComment' => '/**
 * PDF/A conformance level this service always targets. Part "3"
 * (embedded-file support), conformance "B" (Basic — matches the
 * font/colour rigor already used by PdfService\'s PDF/A-3b path).
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 108,
        'endLine' => 108,
        'startColumn' => 2,
        'endColumn' => 36,
      ),
    ),
    'immediateProperties' => 
    array (
      'metadataAssembler' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'name' => 'metadataAssembler',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Serialises MDTO/archival metadata into the XMP packet and the
 * embedded-attachment set.
 *
 * @var Pdfa3MetadataAssembler
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 116,
        'endLine' => 116,
        'startColumn' => 2,
        'endColumn' => 60,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'streamReaderFactory' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'name' => 'streamReaderFactory',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\PdfStreamReaderFactory',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Builds FPDI stream readers over in-memory PDF byte strings.
 *
 * @var PdfStreamReaderFactory
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 123,
        'endLine' => 123,
        'startColumn' => 2,
        'endColumn' => 62,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'pdfService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
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
        'startLine' => 141,
        'endLine' => 141,
        'startColumn' => 3,
        'endColumn' => 41,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'appConfig' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
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
        'startLine' => 142,
        'endLine' => 142,
        'startColumn' => 3,
        'endColumn' => 40,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
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
        'startLine' => 143,
        'endLine' => 143,
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
            'startLine' => 141,
            'endLine' => 141,
            'startColumn' => 3,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 142,
            'endLine' => 142,
            'startColumn' => 3,
            'endColumn' => 40,
            'parameterIndex' => 1,
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
            'startLine' => 143,
            'endLine' => 143,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'metadataAssembler' => 
          array (
            'name' => 'metadataAssembler',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 144,
                'endLine' => 144,
                'startTokenPos' => 221,
                'startFilePos' => 5230,
                'endTokenPos' => 221,
                'endFilePos' => 5233,
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
                      'name' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
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
            'startLine' => 144,
            'endLine' => 144,
            'startColumn' => 3,
            'endColumn' => 51,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'streamReaderFactory' => 
          array (
            'name' => 'streamReaderFactory',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 145,
                'endLine' => 145,
                'startTokenPos' => 231,
                'startFilePos' => 5285,
                'endTokenPos' => 231,
                'endFilePos' => 5288,
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
                      'name' => 'OCA\\Filinq\\Service\\PdfStreamReaderFactory',
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
            'startLine' => 145,
            'endLine' => 145,
            'startColumn' => 3,
            'endColumn' => 53,
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
 * @param PdfService $pdfService Shared font-directory resolution (keeps
 *                               the embedded DejaVu Sans set in lockstep
 *                               with print-preview / renderPdfA).
 * @param IAppConfig $appConfig Tenant configuration provider.
 * @param LoggerInterface $logger Logger for diagnostics.
 * @param Pdfa3MetadataAssembler|null $metadataAssembler MDTO metadata assembler; autowired in
 *                                                       production, defaulted here so existing
 *                                                       call sites stay source-compatible.
 * @param PdfStreamReaderFactory|null $streamReaderFactory FPDI stream-reader seam; autowired in
 *                                                         production, defaulted here so existing
 *                                                         call sites stay source-compatible.
 */',
        'startLine' => 140,
        'endLine' => 150,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'aliasName' => NULL,
      ),
      'convertExistingPdf' => 
      array (
        'name' => 'convertExistingPdf',
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
            'startLine' => 177,
            'endLine' => 177,
            'startColumn' => 37,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'metadata' => 
          array (
            'name' => 'metadata',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 177,
                'endLine' => 177,
                'startTokenPos' => 299,
                'startFilePos' => 6807,
                'endTokenPos' => 300,
                'endFilePos' => 6808,
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
            'startColumn' => 51,
            'endColumn' => 70,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'attachments' => 
          array (
            'name' => 'attachments',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 177,
                'endLine' => 177,
                'startTokenPos' => 309,
                'startFilePos' => 6832,
                'endTokenPos' => 310,
                'endFilePos' => 6833,
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
            'startColumn' => 73,
            'endColumn' => 95,
            'parameterIndex' => 2,
            'isOptional' => true,
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
                'startTokenPos' => 319,
                'startFilePos' => 6853,
                'endTokenPos' => 320,
                'endFilePos' => 6854,
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
            'startColumn' => 98,
            'endColumn' => 116,
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
 * Convert an existing PDF file to PDF/A-3b.
 *
 * Imports every page of the source PDF as-is (page content is
 * copied, not re-rendered) into a fresh PDF/A-3 container carrying
 * the supplied metadata and attachments.
 *
 * @param File $source The existing PDF to convert.
 * @param array<string,mixed> $metadata MDTO/archival metadata (title,
 *                                      author, subject, keywords,
 *                                      plus any archival fields —
 *                                      identifier, caseReference,
 *                                      archiefvormer,
 *                                      aggregatieniveau, etc.).
 * @param array<int,array<string,mixed>> $attachments Files to embed. Each entry:
 *                                                    {name, mime, content, description?,
 *                                                    AFRelationship?}.
 * @param array<string,mixed> $options {format?, orientation?}.
 *
 * @return array{content:string,checksumSha256:string,pages:int,conformance:string}
 *
 * @throws Pdfa3ConversionException On any guardrail violation or conversion failure.
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 */',
        'startLine' => 177,
        'endLine' => 220,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'aliasName' => NULL,
      ),
      'convertHtml' => 
      array (
        'name' => 'convertHtml',
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
            'startLine' => 242,
            'endLine' => 242,
            'startColumn' => 30,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'metadata' => 
          array (
            'name' => 'metadata',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 242,
                'endLine' => 242,
                'startTokenPos' => 630,
                'startFilePos' => 9184,
                'endTokenPos' => 631,
                'endFilePos' => 9185,
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
            'startLine' => 242,
            'endLine' => 242,
            'startColumn' => 44,
            'endColumn' => 63,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'attachments' => 
          array (
            'name' => 'attachments',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 242,
                'endLine' => 242,
                'startTokenPos' => 640,
                'startFilePos' => 9209,
                'endTokenPos' => 641,
                'endFilePos' => 9210,
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
            'startLine' => 242,
            'endLine' => 242,
            'startColumn' => 66,
            'endColumn' => 88,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 242,
                'endLine' => 242,
                'startTokenPos' => 650,
                'startFilePos' => 9230,
                'endTokenPos' => 651,
                'endFilePos' => 9231,
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
            'startLine' => 242,
            'endLine' => 242,
            'startColumn' => 91,
            'endColumn' => 109,
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
 * Convert freshly rendered HTML directly to PDF/A-3b.
 *
 * Unlike convertExistingPdf(), mPDF renders every element itself,
 * so font embedding and colour-space compliance are fully
 * guaranteed by mPDF\'s own PDF/A auto-correction — this is the
 * strongest-guarantee path and the one filinq\'s own generation
 * flow (PdfController::renderPdfA) uses.
 *
 * @param string $html Rendered HTML document body.
 * @param array<string,mixed> $metadata MDTO/archival metadata; see convertExistingPdf().
 * @param array<int,array<string,mixed>> $attachments Files to embed; see convertExistingPdf().
 * @param array<string,mixed> $options {format?, orientation?, margin?}.
 *
 * @return array{content:string,checksumSha256:string,pages:int,conformance:string}
 *
 * @throws Pdfa3ConversionException On any guardrail violation or conversion failure.
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 */',
        'startLine' => 242,
        'endLine' => 258,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'aliasName' => NULL,
      ),
      'buildPdfa3' => 
      array (
        'name' => 'buildPdfa3',
        'parameters' => 
        array (
          'pageBuilder' => 
          array (
            'name' => 'pageBuilder',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 278,
            'endLine' => 278,
            'startColumn' => 3,
            'endColumn' => 23,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'metadata' => 
          array (
            'name' => 'metadata',
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
            'startLine' => 279,
            'endLine' => 279,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'attachments' => 
          array (
            'name' => 'attachments',
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
            'startLine' => 280,
            'endLine' => 280,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 2,
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
            'startLine' => 281,
            'endLine' => 281,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'defaultTitle' => 
          array (
            'name' => 'defaultTitle',
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
            'startLine' => 282,
            'endLine' => 282,
            'startColumn' => 3,
            'endColumn' => 22,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'deadline' => 
          array (
            'name' => 'deadline',
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
            'startLine' => 283,
            'endLine' => 283,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 5,
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
 * Shared mPDF assembly: configure PDF/A-3, apply metadata and
 * attachments, run the caller-supplied page builder, then validate
 * and return the output.
 *
 * @param callable $pageBuilder function(Mpdf $mpdf): void — writes page
 *                              content.
 * @param array<string,mixed> $metadata MDTO/archival metadata.
 * @param array<int,array<string,mixed>> $attachments Files to embed.
 * @param array<string,mixed> $options {format?, orientation?, margin?}.
 * @param string $defaultTitle Title used when $metadata[\'title\'] is absent.
 * @param float $deadline Absolute microtime(true) deadline for this conversion.
 *
 * @return array{content:string,checksumSha256:string,pages:int,conformance:string}
 *
 * @throws Pdfa3ConversionException On render failure or output-validation failure.
 */',
        'startLine' => 277,
        'endLine' => 312,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'aliasName' => NULL,
      ),
      'instantiateMpdf' => 
      array (
        'name' => 'instantiateMpdf',
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
            'startLine' => 325,
            'endLine' => 325,
            'startColumn' => 35,
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
            'name' => 'Mpdf\\Mpdf',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Build mPDF\'s config array and instantiate it, pre-configured for
 * PDF/A-3 (embedded fonts, ICC output intent, XMP identification —
 * all driven by the PDFA/PDFAauto/PDFAversion keys).
 *
 * @param array<string,mixed> $options {format?, orientation?, margin?}.
 *
 * @return Mpdf
 *
 * @throws Pdfa3ConversionException REASON_CONVERTER_UNAVAILABLE.
 */',
        'startLine' => 325,
        'endLine' => 373,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'aliasName' => NULL,
      ),
      'renderPages' => 
      array (
        'name' => 'renderPages',
        'parameters' => 
        array (
          'mpdf' => 
          array (
            'name' => 'mpdf',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Mpdf\\Mpdf',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 389,
            'endLine' => 389,
            'startColumn' => 31,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'pageBuilder' => 
          array (
            'name' => 'pageBuilder',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 389,
            'endLine' => 389,
            'startColumn' => 43,
            'endColumn' => 63,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'deadline' => 
          array (
            'name' => 'deadline',
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
            'startLine' => 389,
            'endLine' => 389,
            'startColumn' => 66,
            'endColumn' => 80,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Run the page builder against the deadline and capture the
 * rendered output. Isolated from buildPdfa3() so each concern
 * (config, metadata, rendering) stays independently readable.
 *
 * @param Mpdf $mpdf Configured, metadata/attachment-primed document.
 * @param callable $pageBuilder function(Mpdf $mpdf): void — writes page content.
 * @param float $deadline Absolute microtime(true) deadline.
 *
 * @return array{content:string,pages:int}
 *
 * @throws Pdfa3ConversionException REASON_TIME_LIMIT_EXCEEDED, REASON_RENDER_FAILED,
 *                                  or REASON_SOURCE_UNREADABLE.
 */',
        'startLine' => 389,
        'endLine' => 433,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'aliasName' => NULL,
      ),
      'importAllPages' => 
      array (
        'name' => 'importAllPages',
        'parameters' => 
        array (
          'mpdf' => 
          array (
            'name' => 'mpdf',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Mpdf\\Mpdf',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 451,
            'endLine' => 451,
            'startColumn' => 34,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'raw' => 
          array (
            'name' => 'raw',
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
            'startLine' => 451,
            'endLine' => 451,
            'startColumn' => 46,
            'endColumn' => 56,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'deadline' => 
          array (
            'name' => 'deadline',
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
            'startLine' => 451,
            'endLine' => 451,
            'startColumn' => 59,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Import every page of a raw PDF byte string into the given mPDF
 * document, checking the time budget between pages so a
 * pathologically large source cannot hang the request
 * indefinitely (best-effort — PHP has no preemptive interrupt for
 * a single synchronous mPDF call; the deadline is enforced at
 * page granularity).
 *
 * @param Mpdf $mpdf Target document (already PDF/A-3 configured).
 * @param string $raw Raw PDF bytes of the source document.
 * @param float $deadline Absolute microtime(true) deadline.
 *
 * @return void
 *
 * @throws Pdfa3ConversionException REASON_SOURCE_UNREADABLE or REASON_TIME_LIMIT_EXCEEDED.
 */',
        'startLine' => 451,
        'endLine' => 483,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'aliasName' => NULL,
      ),
      'timeLimitExceeded' => 
      array (
        'name' => 'timeLimitExceeded',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Build a Pdfa3ConversionException for the time-budget guardrail.
 *
 * @return Pdfa3ConversionException
 */',
        'startLine' => 490,
        'endLine' => 504,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'aliasName' => NULL,
      ),
      'validateOutput' => 
      array (
        'name' => 'validateOutput',
        'parameters' => 
        array (
          'pdfBytes' => 
          array (
            'name' => 'pdfBytes',
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
            'startLine' => 520,
            'endLine' => 520,
            'startColumn' => 34,
            'endColumn' => 49,
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
 * No-silent-passthrough guardrail: assert the assembled bytes carry
 * the minimum markers a genuine PDF/A-3 file must have. Full
 * veraPDF-grade structural/content validation is out of scope (see
 * design.md); this is a defence-in-depth check against a future
 * regression that accidentally drops the PDFA flag while still
 * returning 200.
 *
 * @param string $pdfBytes Assembled PDF bytes.
 *
 * @return void
 *
 * @throws Pdfa3ConversionException REASON_OUTPUT_VALIDATION_FAILED.
 */',
        'startLine' => 520,
        'endLine' => 542,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'aliasName' => NULL,
      ),
      'assertAvailable' => 
      array (
        'name' => 'assertAvailable',
        'parameters' => 
        array (
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
 * Whether the converter is available: the tenant has not disabled
 * it, and the mPDF classes this service depends on are actually
 * autoloadable.
 *
 * @return void
 *
 * @throws Pdfa3ConversionException REASON_CONVERTER_UNAVAILABLE.
 */',
        'startLine' => 553,
        'endLine' => 573,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'aliasName' => NULL,
      ),
      'ensureTempDirectory' => 
      array (
        'name' => 'ensureTempDirectory',
        'parameters' => 
        array (
          'tempDir' => 
          array (
            'name' => 'tempDir',
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
            'startLine' => 582,
            'endLine' => 582,
            'startColumn' => 39,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Ensure the mPDF temp directory exists and is writable.
 *
 * @param string $tempDir The temp directory path.
 *
 * @return void
 */',
        'startLine' => 582,
        'endLine' => 589,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'aliasName' => NULL,
      ),
      'deadline' => 
      array (
        'name' => 'deadline',
        'parameters' => 
        array (
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
 * Absolute deadline for the current conversion.
 *
 * @return float microtime(true) deadline.
 */',
        'startLine' => 596,
        'endLine' => 598,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'aliasName' => NULL,
      ),
      'now' => 
      array (
        'name' => 'now',
        'parameters' => 
        array (
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
 * Current wall-clock time. Isolated behind a method (rather than a
 * bare microtime(true) call at each use site) so tests can
 * construct a partial mock that overrides this to deterministically
 * simulate the time-budget guardrail without real sleeping.
 *
 * @return float
 */',
        'startLine' => 608,
        'endLine' => 610,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'aliasName' => NULL,
      ),
      'resolveMaxInputBytes' => 
      array (
        'name' => 'resolveMaxInputBytes',
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
 * Read the max-input-bytes tenant config. Defaults to 50 MiB.
 *
 * @return int Positive byte cap.
 */',
        'startLine' => 617,
        'endLine' => 625,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'aliasName' => NULL,
      ),
      'resolveMaxSeconds' => 
      array (
        'name' => 'resolveMaxSeconds',
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
 * Read the max-seconds tenant config. Defaults to 60 seconds.
 *
 * @return int Positive second cap.
 */',
        'startLine' => 632,
        'endLine' => 640,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
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
            'startLine' => 650,
            'endLine' => 650,
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
 * Return $name without its trailing `.ext` suffix, for use as a
 * default document title.
 *
 * @param string $name File name with extension.
 *
 * @return string Name without extension.
 */',
        'startLine' => 650,
        'endLine' => 657,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Pdfa3ConversionService',
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