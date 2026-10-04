<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/EmlPdfAssemblyService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\EmlPdfAssemblyService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-97dd6a54e1909ab5c81a70bcee5ff855adb82141b3410fb5a62267cc9bda8c64',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/EmlPdfAssemblyService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
    'shortName' => 'EmlPdfAssemblyService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Builds a PDF/A-3b from a redacted EML structure.
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
    'startLine' => 52,
    'endLine' => 526,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'name' => 'APP_ID',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq\'',
          'attributes' => 
          array (
            'startLine' => 57,
            'endLine' => 57,
            'startTokenPos' => 55,
            'startFilePos' => 1969,
            'endTokenPos' => 55,
            'endFilePos' => 1976,
          ),
        ),
        'docComment' => '/**
 * App identifier used for IAppConfig reads.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 2,
        'endColumn' => 33,
      ),
      'KEY_APPEND_PAGES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'name' => 'KEY_APPEND_PAGES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq.conversion.eml.append_attachment_pages\'',
          'attributes' => 
          array (
            'startLine' => 63,
            'endLine' => 63,
            'startTokenPos' => 68,
            'startFilePos' => 2156,
            'endTokenPos' => 68,
            'endFilePos' => 2202,
          ),
        ),
        'docComment' => '/**
 * Config key: when false, only the redacted envelope renders; renderable
 * attachments are not appended as pages. Default true.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 63,
        'endLine' => 63,
        'startColumn' => 2,
        'endColumn' => 82,
      ),
      'KEY_MAX_SIZE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'name' => 'KEY_MAX_SIZE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq.conversion.eml.max_attachment_render_size_bytes\'',
          'attributes' => 
          array (
            'startLine' => 69,
            'endLine' => 69,
            'startTokenPos' => 81,
            'startFilePos' => 2362,
            'endTokenPos' => 81,
            'endFilePos' => 2417,
          ),
        ),
        'docComment' => '/**
 * Config key: redacted attachments larger than this (bytes) get a
 * placeholder page. Default 26214400 (25 MB).
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 69,
        'endLine' => 69,
        'startColumn' => 2,
        'endColumn' => 87,
      ),
      'DEFAULT_MAX_SIZE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'name' => 'DEFAULT_MAX_SIZE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '26214400',
          'attributes' => 
          array (
            'startLine' => 74,
            'endLine' => 74,
            'startTokenPos' => 94,
            'startFilePos' => 2527,
            'endTokenPos' => 94,
            'endFilePos' => 2534,
          ),
        ),
        'docComment' => '/**
 * Default value for the max-render-size config key (25 MB).
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 74,
        'endLine' => 74,
        'startColumn' => 2,
        'endColumn' => 43,
      ),
    ),
    'immediateProperties' => 
    array (
      'envelopeRenderer' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'name' => 'envelopeRenderer',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\EmlEnvelopeRenderer',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Renders the redacted envelope (headers + body) of a structure.
 *
 * @var EmlEnvelopeRenderer
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 81,
        'endLine' => 81,
        'startColumn' => 2,
        'endColumn' => 56,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'attachmentRenderer' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'name' => 'attachmentRenderer',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\EmlAttachmentRenderer',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Renders dividers and redacted attachment pages.
 *
 * @var EmlAttachmentRenderer
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 88,
        'endLine' => 88,
        'startColumn' => 2,
        'endColumn' => 60,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'pdfService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
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
        'startLine' => 113,
        'endLine' => 113,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
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
        'startLine' => 115,
        'endLine' => 115,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
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
            'startLine' => 113,
            'endLine' => 113,
            'startColumn' => 3,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'templateRenderer' => 
          array (
            'name' => 'templateRenderer',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\TemplateRenderer',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 114,
            'endLine' => 114,
            'startColumn' => 3,
            'endColumn' => 36,
            'parameterIndex' => 1,
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
            'startLine' => 115,
            'endLine' => 115,
            'startColumn' => 3,
            'endColumn' => 40,
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
          'envelopeRenderer' => 
          array (
            'name' => 'envelopeRenderer',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 117,
                'endLine' => 117,
                'startTokenPos' => 167,
                'startFilePos' => 4318,
                'endTokenPos' => 167,
                'endFilePos' => 4321,
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
                      'name' => 'OCA\\Filinq\\Service\\EmlEnvelopeRenderer',
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
            'endColumn' => 47,
            'parameterIndex' => 4,
            'isOptional' => true,
          ),
          'attachmentRenderer' => 
          array (
            'name' => 'attachmentRenderer',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 118,
                'endLine' => 118,
                'startTokenPos' => 177,
                'startFilePos' => 4371,
                'endTokenPos' => 177,
                'endFilePos' => 4374,
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
                      'name' => 'OCA\\Filinq\\Service\\EmlAttachmentRenderer',
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
            'startLine' => 118,
            'endLine' => 118,
            'startColumn' => 3,
            'endColumn' => 51,
            'parameterIndex' => 5,
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
 * Renderable redacted attachment bytes are rendered within the shared mPDF
 * instance: PDF via FPDI import, images inline as data URIs, text in a
 * `<pre>` block, Word-family via PhpWord\'s HTML writer (the same engine
 * `PhpWordBackend` uses), and nested EML by recursion. The cascade
 * backends are NOT injected here — they operate on `OCP\\Files\\File` nodes,
 * not the raw redacted bytes this service holds, so reusing the PhpWord
 * engine directly is the clean path (see DEFERRED_QUESTIONS).
 *
 * The two renderers are injected; the null defaults keep the historical
 * four-argument signature usable, in which case equivalent renderers are
 * built from the same dependencies.
 *
 * @param PdfService $pdfService Shared mPDF/PDF-A configuration.
 * @param TemplateRenderer $templateRenderer Sandboxed Twig renderer.
 * @param IAppConfig $appConfig Tenant configuration provider.
 * @param LoggerInterface $logger Logger for diagnostics.
 * @param EmlEnvelopeRenderer|null $envelopeRenderer Envelope renderer; built from the above when null.
 * @param EmlAttachmentRenderer|null $attachmentRenderer Attachment renderer; built from the above when null.
 */',
        'startLine' => 112,
        'endLine' => 133,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'currentClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'aliasName' => NULL,
      ),
      'assemble' => 
      array (
        'name' => 'assemble',
        'parameters' => 
        array (
          'result' => 
          array (
            'name' => 'result',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'object',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 150,
            'endLine' => 150,
            'startColumn' => 27,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'sourceFilename' => 
          array (
            'name' => 'sourceFilename',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 150,
                'endLine' => 150,
                'startTokenPos' => 266,
                'startFilePos' => 5412,
                'endTokenPos' => 266,
                'endFilePos' => 5415,
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
            'startLine' => 150,
            'endLine' => 150,
            'startColumn' => 43,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Assemble a PDF/A-3b from a redacted EML structure and return its bytes.
 *
 * The caller writes the returned bytes to Nextcloud Files (mirroring the
 * cascade\'s `_anonymized` naming). Returning bytes — rather than a File —
 * keeps this service free of filesystem coupling and lets nested EML
 * attachments be rendered inline within the same mPDF document.
 *
 * @param object $result OR\'s AnonymisedEmlStructure (redacted).
 * @param string|null $sourceFilename Original .eml filename, for the PDF title.
 *
 * @return string PDF/A-3b binary content.
 *
 * @throws ConversionFailedException When no output can be produced.
 */',
        'startLine' => 150,
        'endLine' => 226,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'currentClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'aliasName' => NULL,
      ),
      'renderStructure' => 
      array (
        'name' => 'renderStructure',
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
            'startLine' => 242,
            'endLine' => 242,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'result' => 
          array (
            'name' => 'result',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'object',
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
            'endColumn' => 16,
            'parameterIndex' => 1,
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
            'startLine' => 244,
            'endLine' => 244,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'appendPages' => 
          array (
            'name' => 'appendPages',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
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
            'endColumn' => 19,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'maxSize' => 
          array (
            'name' => 'maxSize',
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
            'endColumn' => 14,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'isRoot' => 
          array (
            'name' => 'isRoot',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
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
            'endColumn' => 14,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Render one EML structure (envelope + attachments) into the shared mPDF
 * instance. Recurses for nested EML attachments.
 *
 * @param \\Mpdf\\Mpdf $mpdf Shared mPDF instance.
 * @param object $result AnonymisedEmlStructure to render.
 * @param array<string,mixed> $options PDF options (for print CSS).
 * @param bool $appendPages Whether renderable attachments are appended.
 * @param int $maxSize Max attachment render size in bytes.
 * @param bool $isRoot True for the outermost message (no leading page break).
 *
 * @return void
 */',
        'startLine' => 241,
        'endLine' => 278,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'currentClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'aliasName' => NULL,
      ),
      'renderAttachment' => 
      array (
        'name' => 'renderAttachment',
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
            'startLine' => 298,
            'endLine' => 298,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'attachment' => 
          array (
            'name' => 'attachment',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'object',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 299,
            'endLine' => 299,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
            'startLine' => 300,
            'endLine' => 300,
            'startColumn' => 3,
            'endColumn' => 12,
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
            'startLine' => 301,
            'endLine' => 301,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'appendPages' => 
          array (
            'name' => 'appendPages',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 302,
            'endLine' => 302,
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'maxSize' => 
          array (
            'name' => 'maxSize',
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
            'startLine' => 303,
            'endLine' => 303,
            'startColumn' => 3,
            'endColumn' => 14,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Render a single redacted attachment: either appended rendered pages
 * (renderable + within cap + append enabled) or a placeholder page.
 *
 * The guards below are ordered exactly as the decision table in
 * design.md D6: "no anonymiser" wins over "nested EML", which wins over
 * "no redacted bytes".
 *
 * @param \\Mpdf\\Mpdf $mpdf Shared mPDF instance.
 * @param object $attachment AnonymisedEmlAttachment.
 * @param int $index 1-based attachment index.
 * @param array<string,mixed> $options PDF options.
 * @param bool $appendPages Whether to append renderable pages.
 * @param int $maxSize Max render size in bytes.
 *
 * @return void
 */',
        'startLine' => 297,
        'endLine' => 342,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'currentClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'aliasName' => NULL,
      ),
      'renderNestedEml' => 
      array (
        'name' => 'renderNestedEml',
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
            'startLine' => 358,
            'endLine' => 358,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'meta' => 
          array (
            'name' => 'meta',
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
            'startLine' => 359,
            'endLine' => 359,
            'startColumn' => 3,
            'endColumn' => 13,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
            'startLine' => 360,
            'endLine' => 360,
            'startColumn' => 3,
            'endColumn' => 12,
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
            'startLine' => 361,
            'endLine' => 361,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'appendPages' => 
          array (
            'name' => 'appendPages',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 362,
            'endLine' => 362,
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'maxSize' => 
          array (
            'name' => 'maxSize',
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
            'startLine' => 363,
            'endLine' => 363,
            'startColumn' => 3,
            'endColumn' => 14,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Render a nested `message/rfc822` attachment: divider plus the recursed
 * redacted structure, or a depth-limit placeholder when OR supplied none.
 *
 * @param \\Mpdf\\Mpdf $mpdf Shared mPDF instance.
 * @param array<string, mixed> $meta Attachment metadata from `EmlAttachmentRenderer::metaOf()`.
 * @param int $index 1-based attachment index.
 * @param array<string,mixed> $options PDF options.
 * @param bool $appendPages Whether to append renderable pages.
 * @param int $maxSize Max render size in bytes.
 *
 * @return void
 */',
        'startLine' => 357,
        'endLine' => 380,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'currentClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'aliasName' => NULL,
      ),
      'renderRedactedBytes' => 
      array (
        'name' => 'renderRedactedBytes',
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
            'startLine' => 396,
            'endLine' => 396,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'meta' => 
          array (
            'name' => 'meta',
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
            'startLine' => 397,
            'endLine' => 397,
            'startColumn' => 3,
            'endColumn' => 13,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
            'startLine' => 398,
            'endLine' => 398,
            'startColumn' => 3,
            'endColumn' => 12,
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
            'startLine' => 399,
            'endLine' => 399,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'appendPages' => 
          array (
            'name' => 'appendPages',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
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
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'maxSize' => 
          array (
            'name' => 'maxSize',
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
            'startLine' => 401,
            'endLine' => 401,
            'startColumn' => 3,
            'endColumn' => 14,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Render an attachment that carries redacted bytes: a placeholder when it
 * is oversize or non-renderable, otherwise a divider plus its pages.
 *
 * @param \\Mpdf\\Mpdf $mpdf Shared mPDF instance.
 * @param array<string, mixed> $meta Attachment metadata from `EmlAttachmentRenderer::metaOf()`.
 * @param int $index 1-based attachment index.
 * @param array<string,mixed> $options PDF options.
 * @param bool $appendPages Whether to append renderable pages.
 * @param int $maxSize Max render size in bytes.
 *
 * @return void
 */',
        'startLine' => 395,
        'endLine' => 448,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'currentClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'aliasName' => NULL,
      ),
      'writePlaceholder' => 
      array (
        'name' => 'writePlaceholder',
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
            'startLine' => 461,
            'endLine' => 461,
            'startColumn' => 36,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'meta' => 
          array (
            'name' => 'meta',
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
            'startLine' => 461,
            'endLine' => 461,
            'startColumn' => 54,
            'endColumn' => 64,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
            'startLine' => 461,
            'endLine' => 461,
            'startColumn' => 67,
            'endColumn' => 76,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'size' => 
          array (
            'name' => 'size',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 461,
            'endLine' => 461,
            'startColumn' => 79,
            'endColumn' => 88,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'variant' => 
          array (
            'name' => 'variant',
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
            'startLine' => 461,
            'endLine' => 461,
            'startColumn' => 91,
            'endColumn' => 105,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Write one divider/placeholder page for an attachment.
 *
 * @param \\Mpdf\\Mpdf $mpdf Shared mPDF instance.
 * @param array<string, mixed> $meta Attachment metadata from `EmlAttachmentRenderer::metaOf()`.
 * @param int $index 1-based attachment index.
 * @param int|null $size Size in bytes, or null.
 * @param string $variant Divider variant.
 *
 * @return void
 */',
        'startLine' => 461,
        'endLine' => 471,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'currentClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'aliasName' => NULL,
      ),
      'deriveTitle' => 
      array (
        'name' => 'deriveTitle',
        'parameters' => 
        array (
          'result' => 
          array (
            'name' => 'result',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'object',
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
            'startColumn' => 31,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'sourceFilename' => 
          array (
            'name' => 'sourceFilename',
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
            'startLine' => 481,
            'endLine' => 481,
            'startColumn' => 47,
            'endColumn' => 69,
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
 * Derive the PDF title from the source filename or the redacted subject.
 *
 * @param object $result AnonymisedEmlStructure.
 * @param string|null $sourceFilename Original .eml filename.
 *
 * @return string PDF title.
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
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'currentClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'aliasName' => NULL,
      ),
      'shouldAppendPages' => 
      array (
        'name' => 'shouldAppendPages',
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
 * Whether renderable attachments should be appended (config-driven).
 *
 * @return bool
 */',
        'startLine' => 504,
        'endLine' => 507,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'currentClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'aliasName' => NULL,
      ),
      'maxAttachmentSize' => 
      array (
        'name' => 'maxAttachmentSize',
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
 * Resolve the max-attachment-render-size config (positive integer).
 *
 * @return int Size in bytes.
 */',
        'startLine' => 514,
        'endLine' => 525,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
        'currentClassName' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
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