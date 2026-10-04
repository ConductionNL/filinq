<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/PdfService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\PdfService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-c10fd76fb963560a88ea499695dd84cc81e498e620970f239bb521443a85b886',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\PdfService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/PdfService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\PdfService',
    'shortName' => 'PdfService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service for generating PDF documents from Twig templates
 *
 * Supports standard PDF 1.4 output and PDF/A-3b archival compliance.
 * When PDF/A mode is enabled, fonts are embedded and print-optimized
 * CSS is injected automatically.
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
    'startLine' => 53,
    'endLine' => 559,
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
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PdfService',
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
        'startLine' => 63,
        'endLine' => 63,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'templateRenderer' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'name' => 'templateRenderer',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\TemplateRenderer',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 64,
        'endLine' => 64,
        'startColumn' => 3,
        'endColumn' => 53,
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
            'startLine' => 63,
            'endLine' => 63,
            'startColumn' => 3,
            'endColumn' => 42,
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 64,
            'endLine' => 64,
            'startColumn' => 3,
            'endColumn' => 53,
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
 * Constructor for PdfService
 *
 * @param LoggerInterface $logger Logger for error reporting
 * @param TemplateRenderer $templateRenderer Template renderer for Twig
 *
 * @return void
 */',
        'startLine' => 62,
        'endLine' => 67,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'aliasName' => NULL,
      ),
      'renderPdf' => 
      array (
        'name' => 'renderPdf',
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
            'startLine' => 91,
            'endLine' => 91,
            'startColumn' => 28,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'data' => 
          array (
            'name' => 'data',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 91,
                'endLine' => 91,
                'startTokenPos' => 98,
                'startFilePos' => 3226,
                'endTokenPos' => 99,
                'endFilePos' => 3227,
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
            'startLine' => 91,
            'endLine' => 91,
            'startColumn' => 53,
            'endColumn' => 68,
            'parameterIndex' => 1,
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
                'startLine' => 91,
                'endLine' => 91,
                'startTokenPos' => 108,
                'startFilePos' => 3247,
                'endTokenPos' => 109,
                'endFilePos' => 3248,
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
            'startLine' => 91,
            'endLine' => 91,
            'startColumn' => 71,
            'endColumn' => 89,
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
 * Render a PDF from a Twig template string and data context
 *
 * @param string $templateContent Twig template content (HTML with Twig syntax)
 * @param array $data Data context for template rendering
 * @param array $options PDF configuration options:
 *                       - format: Page size (A4, A3, Letter, Legal). Default: A4
 *                       - orientation: P (portrait) or L (landscape). Default: P
 *                       - margin: Array with top, right, bottom, left in mm. Default: 15
 *                       - title: PDF document title metadata. Default: empty
 *                       - pdfa: Enable PDF/A-3b compliance. Default: false
 *                       - cropMarks: Add 3mm bleed and crop marks. Default: false
 *                       - author: Author name for XMP metadata. Default: Filinq
 *                       - caseReference: Case reference for XMP keywords. Default: empty
 *
 * @return string PDF binary content
 *
 * @throws Exception If Twig rendering or PDF generation fails
 *
 * @spec openspec/specs/pdf-generation/spec.md
 * @spec openspec/changes/print-functionality/tasks.md#task-1
 */',
        'startLine' => 91,
        'endLine' => 98,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'aliasName' => NULL,
      ),
      'renderTemplateToHtml' => 
      array (
        'name' => 'renderTemplateToHtml',
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
            'startLine' => 119,
            'endLine' => 119,
            'startColumn' => 39,
            'endColumn' => 61,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'data' => 
          array (
            'name' => 'data',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 119,
                'endLine' => 119,
                'startTokenPos' => 183,
                'startFilePos' => 4250,
                'endTokenPos' => 184,
                'endFilePos' => 4251,
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
            'startLine' => 119,
            'endLine' => 119,
            'startColumn' => 64,
            'endColumn' => 79,
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
 * Render a Twig template to HTML without converting to PDF.
 *
 * Public wrapper around TemplateRenderer for callers that need the
 * rendered HTML itself rather than a PDF — used by
 * `PdfController::renderPdfA` to hand off to
 * `Pdfa3ConversionService::convertHtml` when the request carries
 * attachments or MDTO metadata, keeping Twig rendering centralised
 * in one place rather than duplicated per caller.
 *
 * @param string $templateContent Twig template content (HTML with Twig syntax)
 * @param array $data Data context for template rendering
 *
 * @return string Rendered HTML
 *
 * @throws Exception If Twig rendering fails
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 */',
        'startLine' => 119,
        'endLine' => 125,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'aliasName' => NULL,
      ),
      'generatePdfFromHtml' => 
      array (
        'name' => 'generatePdfFromHtml',
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
            'startLine' => 147,
            'endLine' => 147,
            'startColumn' => 38,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 147,
                'endLine' => 147,
                'startTokenPos' => 237,
                'startFilePos' => 5280,
                'endTokenPos' => 238,
                'endFilePos' => 5281,
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
            'startLine' => 147,
            'endLine' => 147,
            'startColumn' => 52,
            'endColumn' => 70,
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
 * Generate a PDF from a raw HTML string (no Twig pre-processing).
 *
 * Public wrapper around the private generatePdf path for callers
 * that already have rendered HTML — used by
 * `Service\\Conversion\\MpdfBackend` (and any future conversion
 * backend that wants the same PDF/A-3b configuration as
 * print-preview without re-implementing it).
 *
 * @param string $html Pre-rendered HTML document body.
 * @param array<string,mixed> $options PDF configuration options; same shape as
 *                                     {@see renderPdf} (`format`, `orientation`,
 *                                     `margin`, `title`, `pdfa`).
 *
 * @return string PDF binary content.
 *
 * @throws Exception When mPDF rendering fails.
 *
 * @spec openspec/specs/pdf-generation/spec.md
 */',
        'startLine' => 147,
        'endLine' => 149,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'aliasName' => NULL,
      ),
      'renderHtmlPreview' => 
      array (
        'name' => 'renderHtmlPreview',
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
            'startLine' => 164,
            'endLine' => 164,
            'startColumn' => 36,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'data' => 
          array (
            'name' => 'data',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 164,
                'endLine' => 164,
                'startTokenPos' => 287,
                'startFilePos' => 6036,
                'endTokenPos' => 288,
                'endFilePos' => 6037,
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
            'startLine' => 164,
            'endLine' => 164,
            'startColumn' => 61,
            'endColumn' => 76,
            'parameterIndex' => 1,
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
                'startLine' => 164,
                'endLine' => 164,
                'startTokenPos' => 297,
                'startFilePos' => 6057,
                'endTokenPos' => 298,
                'endFilePos' => 6058,
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
            'startLine' => 164,
            'endLine' => 164,
            'startColumn' => 79,
            'endColumn' => 97,
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
 * Render HTML from a Twig template string and data context (for print preview)
 *
 * @param string $templateContent Twig template content (HTML with Twig syntax)
 * @param array $data Data context for template rendering
 * @param array $options Options for CSS injection:
 *                       - format: Page size (A4, A3, Letter, Legal). Default: A4
 *                       - orientation: P (portrait) or L (landscape). Default: P
 *
 * @return string Rendered HTML with print-optimized CSS injected
 *
 * @spec openspec/specs/print-preview/spec.md
 */',
        'startLine' => 164,
        'endLine' => 175,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'aliasName' => NULL,
      ),
      'buildCropMarksHtml' => 
      array (
        'name' => 'buildCropMarksHtml',
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
            'startLine' => 190,
            'endLine' => 190,
            'startColumn' => 37,
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
 * Build HTML with 3mm bleed area and crop marks injected around the content
 *
 * Wraps the content in a bleed container and adds SVG crop marks at all four
 * corners. The bleed margin is 3mm on each side, making the effective printable
 * area 6mm wider and taller than the nominal page size.
 *
 * @param string $html Rendered HTML document content
 *
 * @return string HTML with bleed CSS and crop mark SVGs prepended
 *
 * @spec openspec/changes/print-functionality/tasks.md#task-1
 */',
        'startLine' => 190,
        'endLine' => 234,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PdfService',
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
            'startLine' => 245,
            'endLine' => 245,
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
 * Ensure the mPDF temp directory exists and is writable
 *
 * @param string $tempDir The temp directory path
 *
 * @return void
 *
 * @spec openspec/specs/pdf-generation/spec.md
 */',
        'startLine' => 245,
        'endLine' => 252,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'aliasName' => NULL,
      ),
      'buildMpdfConfig' => 
      array (
        'name' => 'buildMpdfConfig',
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
            'startLine' => 267,
            'endLine' => 267,
            'startColumn' => 35,
            'endColumn' => 49,
            'parameterIndex' => 0,
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
            'startLine' => 267,
            'endLine' => 267,
            'startColumn' => 52,
            'endColumn' => 65,
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
 * Build mPDF configuration array from options
 *
 * When PDF/A mode is enabled, configures mPDF for PDF/A-3b compliance
 * with embedded DejaVu Sans fonts and automatic PDF/A metadata.
 *
 * @param string $tempDir The temp directory path
 * @param array $options PDF configuration options
 *
 * @return array<string, mixed> mPDF configuration
 *
 * @spec openspec/specs/pdf-generation/spec.md
 */',
        'startLine' => 267,
        'endLine' => 312,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'aliasName' => NULL,
      ),
      'getFontDirectory' => 
      array (
        'name' => 'getFontDirectory',
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
 * Get the path to the bundled font directory
 *
 * Public so other services that build their own mPDF configuration
 * (e.g. `Pdfa3ConversionService`, which needs the same embedded
 * DejaVu Sans set for PDF/A-3 font-embedding compliance) can reuse
 * this instead of re-deriving the path.
 *
 * @return string|null The font directory path, or null if not found
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 */',
        'startLine' => 326,
        'endLine' => 333,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'aliasName' => NULL,
      ),
      'buildPrintCss' => 
      array (
        'name' => 'buildPrintCss',
        'parameters' => 
        array (
          'format' => 
          array (
            'name' => 'format',
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
            'startLine' => 348,
            'endLine' => 348,
            'startColumn' => 32,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'orientation' => 
          array (
            'name' => 'orientation',
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
            'startLine' => 348,
            'endLine' => 348,
            'startColumn' => 48,
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
 * Build print-optimized CSS for PDF/A and print preview output
 *
 * Generates a style block with @media print rules including page size,
 * page-break-inside avoidance, and margin normalization.
 *
 * @param string $format Page format (A4, A3, Letter, Legal)
 * @param string $orientation Page orientation (P for portrait, L for landscape)
 *
 * @return string HTML style block with print-optimized CSS
 *
 * @spec openspec/specs/print-preview/spec.md
 */',
        'startLine' => 348,
        'endLine' => 398,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'aliasName' => NULL,
      ),
      'createMpdfInstance' => 
      array (
        'name' => 'createMpdfInstance',
        'parameters' => 
        array (
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 424,
                'endLine' => 424,
                'startTokenPos' => 1087,
                'startFilePos' => 15258,
                'endTokenPos' => 1088,
                'endFilePos' => 15259,
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
            'startLine' => 424,
            'endLine' => 424,
            'startColumn' => 37,
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
            'name' => 'Mpdf\\Mpdf',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a configured mPDF instance for multi-pass assembly.
 *
 * Exposes the SAME mPDF configuration (temp dir, PDF/A-3b mode, font
 * embedding, margins) that the single-pass `generatePdf` path uses, so
 * callers that need to build a document across several `WriteHTML` /
 * `AddPage` / FPDI-import passes (e.g. `EmlPdfAssemblyService`) stay in
 * lockstep with print-preview\'s PDF/A-3b settings instead of
 * re-implementing them.
 *
 * The returned instance has title/author/creator metadata applied when
 * provided; the caller is responsible for writing content and calling
 * `Output()`.
 *
 * @param array<string,mixed> $options PDF configuration options; same shape as
 *                                     {@see renderPdf} (`format`, `orientation`,
 *                                     `margin`, `title`, `pdfa`).
 *
 * @return Mpdf The configured mPDF instance.
 *
 * @throws Exception When mPDF cannot be instantiated.
 *
 * @spec openspec/specs/pdf-generation/spec.md#requirement-mpdf-temp-directory-management-req-pdf-04
 */',
        'startLine' => 424,
        'endLine' => 457,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'aliasName' => NULL,
      ),
      'applyPrintCss' => 
      array (
        'name' => 'applyPrintCss',
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
            'startLine' => 472,
            'endLine' => 472,
            'startColumn' => 32,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 472,
                'endLine' => 472,
                'startTokenPos' => 1352,
                'startFilePos' => 16673,
                'endTokenPos' => 1353,
                'endFilePos' => 16674,
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
            'startLine' => 472,
            'endLine' => 472,
            'startColumn' => 46,
            'endColumn' => 64,
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
 * Build the print-optimised CSS prefix for an HTML fragment when PDF/A
 * mode is in effect, mirroring the prefix `generatePdf` injects.
 *
 * Used by multi-pass callers that render fragments through a shared mPDF
 * instance and want each fragment to carry the same print CSS the
 * single-pass path applies.
 *
 * @param string $html HTML fragment.
 * @param array<string,mixed> $options PDF options (`pdfa`, `format`, `orientation`).
 *
 * @return string The HTML fragment, prefixed with print CSS when `pdfa` is true.
 */',
        'startLine' => 472,
        'endLine' => 481,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'aliasName' => NULL,
      ),
      'generatePdf' => 
      array (
        'name' => 'generatePdf',
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
            'startLine' => 499,
            'endLine' => 499,
            'startColumn' => 31,
            'endColumn' => 42,
            'parameterIndex' => 0,
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
            'startLine' => 499,
            'endLine' => 499,
            'startColumn' => 45,
            'endColumn' => 58,
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
 * Generate a PDF from rendered HTML content
 *
 * Creates the mPDF temp directory if it does not exist,
 * initializes mPDF with the given options, and returns the PDF binary.
 * When PDF/A mode is enabled, injects print CSS and sets XMP metadata.
 *
 * @param string $html Rendered HTML content
 * @param array $options PDF configuration options
 *
 * @return string PDF binary content
 *
 * @throws Exception If mPDF fails to generate the PDF
 *
 * @spec openspec/specs/pdf-generation/spec.md
 */',
        'startLine' => 499,
        'endLine' => 558,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PdfService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PdfService',
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