<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/GrondslagenPdfWriter.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\GrondslagenPdfWriter
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-dbb9bfff317f6312d3dfc351236f46a499f352706e0811e6c648a221f03d2d1f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/GrondslagenPdfWriter.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
    'shortName' => 'GrondslagenPdfWriter',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Renders and persists the grondslagen summary PDFs.
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
    'startLine' => 45,
    'endLine' => 376,
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
      'TEMPLATE_DIR' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'name' => 'TEMPLATE_DIR',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'/Resources/templates/grondslagen/\'',
          'attributes' => 
          array (
            'startLine' => 50,
            'endLine' => 50,
            'startTokenPos' => 65,
            'startFilePos' => 1443,
            'endTokenPos' => 65,
            'endFilePos' => 1477,
          ),
        ),
        'docComment' => '/**
 * Relative path (from the app root) where the Twig templates live.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 50,
        'endLine' => 50,
        'startColumn' => 2,
        'endColumn' => 66,
      ),
      'TEMPLATE_PER_DOC' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'name' => 'TEMPLATE_PER_DOC',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'summary_per_doc.twig\'',
          'attributes' => 
          array (
            'startLine' => 55,
            'endLine' => 55,
            'startTokenPos' => 78,
            'startFilePos' => 1578,
            'endTokenPos' => 78,
            'endFilePos' => 1599,
          ),
        ),
        'docComment' => '/**
 * Template file for the per-document summary page.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 55,
        'endLine' => 55,
        'startColumn' => 2,
        'endColumn' => 57,
      ),
      'TEMPLATE_PER_DOSSIER' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'name' => 'TEMPLATE_PER_DOSSIER',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'summary_per_dossier.twig\'',
          'attributes' => 
          array (
            'startLine' => 60,
            'endLine' => 60,
            'startTokenPos' => 91,
            'startFilePos' => 1702,
            'endTokenPos' => 91,
            'endFilePos' => 1727,
          ),
        ),
        'docComment' => '/**
 * Template file for the per-dossier summary PDF.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 60,
        'endLine' => 60,
        'startColumn' => 2,
        'endColumn' => 65,
      ),
      'DOSSIER_SUMMARY_NAME' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'name' => 'DOSSIER_SUMMARY_NAME',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'grondslagen.pdf\'',
          'attributes' => 
          array (
            'startLine' => 65,
            'endLine' => 65,
            'startTokenPos' => 104,
            'startFilePos' => 1847,
            'endTokenPos' => 104,
            'endFilePos' => 1863,
          ),
        ),
        'docComment' => '/**
 * File name of the per-dossier summary inside the dossier folder.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 65,
        'endLine' => 65,
        'startColumn' => 2,
        'endColumn' => 56,
      ),
    ),
    'immediateProperties' => 
    array (
      'pdfService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
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
        'startLine' => 76,
        'endLine' => 76,
        'startColumn' => 3,
        'endColumn' => 41,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'finalDocuments' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'name' => 'finalDocuments',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\FinalDocumentService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 77,
        'endLine' => 77,
        'startColumn' => 3,
        'endColumn' => 55,
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
            'startLine' => 76,
            'endLine' => 76,
            'startColumn' => 3,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'finalDocuments' => 
          array (
            'name' => 'finalDocuments',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\FinalDocumentService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 77,
            'endLine' => 77,
            'startColumn' => 3,
            'endColumn' => 55,
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
 * Constructor.
 *
 * @param PdfService $pdfService Twig + mPDF renderer.
 * @param FinalDocumentService $finalDocuments The final-document guard.
 *
 * @return void
 */',
        'startLine' => 75,
        'endLine' => 80,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'currentClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'aliasName' => NULL,
      ),
      'renderPerDocumentPdf' => 
      array (
        'name' => 'renderPerDocumentPdf',
        'parameters' => 
        array (
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
            'startLine' => 92,
            'endLine' => 92,
            'startColumn' => 39,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'sourceFileId' => 
          array (
            'name' => 'sourceFileId',
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
            'startLine' => 92,
            'endLine' => 92,
            'startColumn' => 52,
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
 * Render the per-document summary template into PDF bytes.
 *
 * @param array<string, mixed> $data The Twig context.
 * @param int $sourceFileId The pre-anonymisation source file id (error context).
 *
 * @return string The rendered PDF (PDF/A-3b) as raw bytes.
 *
 * @throws RuntimeException When template or PDF rendering fails.
 */',
        'startLine' => 92,
        'endLine' => 106,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'currentClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'aliasName' => NULL,
      ),
      'renderDossierPdf' => 
      array (
        'name' => 'renderDossierPdf',
        'parameters' => 
        array (
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
            'startLine' => 118,
            'endLine' => 118,
            'startColumn' => 35,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'dossierUuid' => 
          array (
            'name' => 'dossierUuid',
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
            'startLine' => 118,
            'endLine' => 118,
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
 * Render the per-dossier summary template into PDF bytes.
 *
 * @param array<string, mixed> $data The Twig context.
 * @param string $dossierUuid The dossier UUID (error context).
 *
 * @return string The rendered PDF (PDF/A-3b) as raw bytes.
 *
 * @throws RuntimeException When template or PDF rendering fails.
 */',
        'startLine' => 118,
        'endLine' => 132,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'currentClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'aliasName' => NULL,
      ),
      'appendToPdf' => 
      array (
        'name' => 'appendToPdf',
        'parameters' => 
        array (
          'anonymisedFile' => 
          array (
            'name' => 'anonymisedFile',
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
            'startLine' => 150,
            'endLine' => 150,
            'startColumn' => 30,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'summaryBytes' => 
          array (
            'name' => 'summaryBytes',
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
            'startLine' => 150,
            'endLine' => 150,
            'startColumn' => 52,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Append the summary PDF to an anonymised PDF file, in place.
 *
 * The append is atomic — on any PDF-merge or write failure the anonymised
 * file is left untouched and the caller receives the thrown exception.
 *
 * @param File $anonymisedFile The anonymised PDF file.
 * @param string $summaryBytes The rendered summary PDF bytes.
 *
 * @return void
 *
 * @throws DocumentFinalException When the anonymised file\'s current version is final.
 * @throws RuntimeException When FPDI merging or the file write fails.
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */',
        'startLine' => 150,
        'endLine' => 170,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'currentClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'aliasName' => NULL,
      ),
      'writeBesideFile' => 
      array (
        'name' => 'writeBesideFile',
        'parameters' => 
        array (
          'anonymisedFile' => 
          array (
            'name' => 'anonymisedFile',
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
            'startLine' => 189,
            'endLine' => 189,
            'startColumn' => 34,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'summaryFileName' => 
          array (
            'name' => 'summaryFileName',
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
            'startLine' => 189,
            'endLine' => 189,
            'startColumn' => 56,
            'endColumn' => 78,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'summaryBytes' => 
          array (
            'name' => 'summaryBytes',
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
            'startLine' => 189,
            'endLine' => 189,
            'startColumn' => 81,
            'endColumn' => 100,
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
 * Write the summary PDF beside the anonymised file.
 *
 * Refreshes the existing file when one is already present, so repeated
 * runs do not litter the folder.
 *
 * @param File $anonymisedFile The anonymised file (any format).
 * @param string $summaryFileName The summary file\'s name.
 * @param string $summaryBytes The rendered summary PDF bytes.
 *
 * @return array{file: File, refreshed: bool} The written file and whether it already existed.
 *
 * @throws DocumentFinalException When the existing summary\'s current version is final.
 * @throws RuntimeException When the write fails.
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */',
        'startLine' => 189,
        'endLine' => 219,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'currentClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'aliasName' => NULL,
      ),
      'saveDossierSummary' => 
      array (
        'name' => 'saveDossierSummary',
        'parameters' => 
        array (
          'folder' => 
          array (
            'name' => 'folder',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Files\\Folder',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 240,
            'endLine' => 240,
            'startColumn' => 37,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 240,
            'endLine' => 240,
            'startColumn' => 53,
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
            'name' => 'OCP\\Files\\File',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Save the rendered per-dossier summary PDF.
 *
 * Destination convention: `<dossier-folder>/grondslagen.pdf`. Wave 2
 * (`anonymisation-output-folder-layout`) will introduce a
 * `<dossier-folder>/anonymised/` subfolder; this method will follow that
 * convention once the helper from Wave 2 lands. For v1, we use the flat
 * path inside the dossier folder.
 *
 * @param Folder $folder The dossier folder.
 * @param string $pdfBytes The freshly-rendered PDF bytes.
 *
 * @return File The newly-written / refreshed summary file.
 *
 * @throws DocumentFinalException When the existing report\'s current version is final.
 * @throws RuntimeException On write failure.
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */',
        'startLine' => 240,
        'endLine' => 268,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'currentClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'aliasName' => NULL,
      ),
      'mergeSummaryIntoPdf' => 
      array (
        'name' => 'mergeSummaryIntoPdf',
        'parameters' => 
        array (
          'originalPdfBytes' => 
          array (
            'name' => 'originalPdfBytes',
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
            'startLine' => 289,
            'endLine' => 289,
            'startColumn' => 39,
            'endColumn' => 62,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'summaryPdfBytes' => 
          array (
            'name' => 'summaryPdfBytes',
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
            'startLine' => 289,
            'endLine' => 289,
            'startColumn' => 65,
            'endColumn' => 87,
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
 * Merge an anonymised PDF + the freshly-rendered summary PDF into one PDF.
 *
 * Uses FPDI to import every page of both inputs and emit them as a single
 * combined PDF. The result is **not strictly PDF/A** (FPDI doesn\'t enforce
 * that on import — the upstream PDF\'s compliance isn\'t guaranteed); the
 * per-dossier render path uses pure mPDF and IS PDF/A-3b. This trade-off
 * is documented in design.md.
 *
 * @param string $originalPdfBytes Anonymised PDF bytes.
 * @param string $summaryPdfBytes Summary PDF bytes.
 *
 * @return string Combined PDF bytes.
 *
 * @throws RuntimeException When FPDI import or output fails.
 *
 * @psalm-suppress UndefinedMethod FPDI extends FPDF; Output() is inherited from FPDF
 *                                 and Psalm lacks stubs for it.
 */',
        'startLine' => 289,
        'endLine' => 324,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'currentClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'aliasName' => NULL,
      ),
      'importAllPages' => 
      array (
        'name' => 'importAllPages',
        'parameters' => 
        array (
          'pdf' => 
          array (
            'name' => 'pdf',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'setasign\\Fpdi\\Fpdi',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 334,
            'endLine' => 334,
            'startColumn' => 34,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'pageCount' => 
          array (
            'name' => 'pageCount',
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
            'startLine' => 334,
            'endLine' => 334,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Import every page of the currently-selected FPDI source file.
 *
 * @param Fpdi $pdf The FPDI document under construction.
 * @param int $pageCount Number of pages in the selected source file.
 *
 * @return void
 */',
        'startLine' => 334,
        'endLine' => 342,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'currentClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'aliasName' => NULL,
      ),
      'loadTemplate' => 
      array (
        'name' => 'loadTemplate',
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
            'startLine' => 358,
            'endLine' => 358,
            'startColumn' => 32,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Load a Twig template\'s source from disk.
 *
 * The templates live under `lib/Resources/templates/grondslagen/`. This
 * helper reads the file as a string so it can be passed to
 * `PdfService::renderPdf($templateContent, ...)`. Throws if the file is
 * missing — every release MUST ship both templates.
 *
 * @param string $name The template file name (e.g. `summary_per_doc.twig`).
 *
 * @return string The template\'s UTF-8 source.
 *
 * @throws RuntimeException When the template file is missing or unreadable.
 */',
        'startLine' => 358,
        'endLine' => 375,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
        'currentClassName' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
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