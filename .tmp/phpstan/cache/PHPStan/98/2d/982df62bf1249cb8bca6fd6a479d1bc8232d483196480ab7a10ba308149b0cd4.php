<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/LegalBasesSummaryService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\LegalBasesSummaryService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-647227f85f6b5fed74c6f8a00acdc5ade0f3ba158f461073f2d4e6dd0f65b881',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/LegalBasesSummaryService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
    'shortName' => 'LegalBasesSummaryService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Renderer for the per-document and per-dossier grondslagen summary PDFs.
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
    'startLine' => 66,
    'endLine' => 677,
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
      'SUMMARY_FILE_SUFFIX' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'name' => 'SUMMARY_FILE_SUFFIX',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'_grondslagen.pdf\'',
          'attributes' => 
          array (
            'startLine' => 73,
            'endLine' => 73,
            'startTokenPos' => 70,
            'startFilePos' => 2725,
            'endTokenPos' => 70,
            'endFilePos' => 2742,
          ),
        ),
        'docComment' => '/**
 * Suffix applied to the source file\'s base name when the per-document
 * append falls back to a separate-PDF file (operator chose to preserve
 * the native output format and the anonymised file isn\'t a PDF).
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 2,
        'endColumn' => 56,
      ),
      'LOCALIZABLE_ENTITY_TYPES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'name' => 'LOCALIZABLE_ENTITY_TYPES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'PERSON\', \'ORGANIZATION\', \'LOCATION\', \'EMAIL\', \'PHONE\', \'ADDRESS\', \'DATE\', \'IBAN\', \'SSN\', \'IP_ADDRESS\']',
          'attributes' => 
          array (
            'startLine' => 86,
            'endLine' => 97,
            'startTokenPos' => 83,
            'startFilePos' => 3323,
            'endTokenPos' => 115,
            'endFilePos' => 3450,
          ),
        ),
        'docComment' => '/**
 * Entity-type labels localised in the placeholder, mirroring
 * OpenRegister\'s `DocumentProcessingHandler::LOCALIZABLE_ENTITY_TYPES`
 * (the `EntityRecognitionHandler::ENTITY_TYPE_*` values). Only these are
 * translated so the summary legend reads the same as the labels
 * OpenRegister wrote into the redacted document; an unknown type falls
 * back to its raw string. Filinq\'s `l10n/` carries the same Dutch
 * translations so the two apps resolve identically for a given language.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 86,
        'endLine' => 97,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'data' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'name' => 'data',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Everything the report needs to read.
 *
 * @var DossierSummaryDataService
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 104,
        'endLine' => 104,
        'startColumn' => 2,
        'endColumn' => 50,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'pdfWriter' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'name' => 'pdfWriter',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Everything the report needs to render and write.
 *
 * @var GrondslagenPdfWriter
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 111,
        'endLine' => 111,
        'startColumn' => 2,
        'endColumn' => 50,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
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
        'startLine' => 138,
        'endLine' => 138,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
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
        'startLine' => 141,
        'endLine' => 141,
        'startColumn' => 3,
        'endColumn' => 44,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'l10n' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'name' => 'l10n',
        'modifiers' => 132,
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
                  'name' => 'OCP\\IL10N',
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
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 144,
            'endLine' => 144,
            'startTokenPos' => 198,
            'startFilePos' => 5515,
            'endTokenPos' => 198,
            'endFilePos' => 5518,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 144,
        'endLine' => 144,
        'startColumn' => 3,
        'endColumn' => 38,
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
            'startLine' => 138,
            'endLine' => 138,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'pdfWriter' => 
          array (
            'name' => 'pdfWriter',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\GrondslagenPdfWriter',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 139,
            'endLine' => 139,
            'startColumn' => 3,
            'endColumn' => 33,
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 140,
            'endLine' => 140,
            'startColumn' => 3,
            'endColumn' => 25,
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
            'startLine' => 141,
            'endLine' => 141,
            'startColumn' => 3,
            'endColumn' => 44,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'appManager' => 
          array (
            'name' => 'appManager',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\App\\IAppManager',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 142,
            'endLine' => 142,
            'startColumn' => 3,
            'endColumn' => 25,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'container' => 
          array (
            'name' => 'container',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Psr\\Container\\ContainerInterface',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 143,
            'endLine' => 143,
            'startColumn' => 3,
            'endColumn' => 31,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
          'l10n' => 
          array (
            'name' => 'l10n',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 144,
                'endLine' => 144,
                'startTokenPos' => 198,
                'startFilePos' => 5515,
                'endTokenPos' => 198,
                'endFilePos' => 5518,
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
                      'name' => 'OCP\\IL10N',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 144,
            'endLine' => 144,
            'startColumn' => 3,
            'endColumn' => 38,
            'parameterIndex' => 6,
            'isOptional' => true,
          ),
          'data' => 
          array (
            'name' => 'data',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 145,
                'endLine' => 145,
                'startTokenPos' => 208,
                'startFilePos' => 5558,
                'endTokenPos' => 208,
                'endFilePos' => 5561,
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
                      'name' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
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
            'endColumn' => 41,
            'parameterIndex' => 7,
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
 * The writer is injected rather than built here. It used to be wired from a
 * PdfService this class held only to pass on, and once the writer also needed
 * the final-document guard that arrangement meant carrying two dependencies
 * this class never uses. Nextcloud\'s container builds the writer, and the
 * data source keeps its null default because it is wired from dependencies
 * this class does use.
 *
 * @param LoggerInterface $logger Structured logger.
 * @param GrondslagenPdfWriter $pdfWriter PDF rendering and persistence, guard included.
 * @param IRootFolder $rootFolder Nextcloud file API entry point.
 * @param IUserSession $userSession Session-user lookup for the "operator" header field.
 * @param IAppManager $appManager App-availability check for OpenRegister.
 * @param ContainerInterface $container DI container for OpenRegister-side services
 *                                      (EntityRelationMapper, ObjectService).
 * @param IL10N|null $l10n Acting-user localisation, used to translate the
 *                         placeholder TYPE label (PERSON → PERSOON on a Dutch
 *                         instance) so the summary legend matches the localized
 *                         labels OpenRegister wrote into the redacted document.
 *                         Nullable: when absent the raw English label is used.
 * @param DossierSummaryDataService|null $data Report data source; wired from the above when null.
 */',
        'startLine' => 137,
        'endLine' => 157,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'aliasName' => NULL,
      ),
      'appendSummaryToPdf' => 
      array (
        'name' => 'appendSummaryToPdf',
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
            'startLine' => 186,
            'endLine' => 186,
            'startColumn' => 37,
            'endColumn' => 56,
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
            'startLine' => 186,
            'endLine' => 186,
            'startColumn' => 59,
            'endColumn' => 75,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'placeholderMap' => 
          array (
            'name' => 'placeholderMap',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 186,
                'endLine' => 186,
                'startTokenPos' => 289,
                'startFilePos' => 7523,
                'endTokenPos' => 290,
                'endFilePos' => 7524,
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
            'startLine' => 186,
            'endLine' => 186,
            'startColumn' => 78,
            'endColumn' => 103,
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
            'name' => 'OCP\\Files\\File',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Append a grondslagen summary page to an already-anonymised PDF file.
 *
 * Loads the anonymised file\'s entity relations from OR, resolves their
 * `bases` to human-readable `base.name` labels, renders the per-document
 * template, and appends the resulting PDF page to the anonymised file.
 *
 * The append is atomic — on any rendering or PDF-merge failure the
 * anonymised file is left untouched and the caller receives a warning
 * via the thrown exception. The caller MUST handle the failure
 * gracefully (the spec calls for returning HTTP 200 with a `warning`
 * field rather than failing the entire anonymise call).
 *
 * @param File $anonymisedFile The anonymised PDF file (must already be a PDF).
 * @param int $sourceFileId The Nextcloud file ID of the original (pre-anonymisation)
 *                          source — used to read the EntityRelation rows that
 *                          record the redactions performed against it.
 * @param array<string, string> $placeholderMap Optional global entity id → emitted placeholder
 *                                              map (e.g. "7" => "[PERSOON: 1]"); when set the summary renders the
 *                                              SAME placeholder the document carries instead of re-deriving it.
 *
 * @return File The same anonymised file, with the summary page appended.
 *
 * @throws \\RuntimeException When template rendering, PDF merging, or file write fails.
 *
 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md#requirement-the-per-document-anonymise-endpoint-must-accept-an-optional-appendbasissummary-field
 */',
        'startLine' => 186,
        'endLine' => 204,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'aliasName' => NULL,
      ),
      'renderSummaryBesideFile' => 
      array (
        'name' => 'renderSummaryBesideFile',
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
            'startLine' => 225,
            'endLine' => 225,
            'startColumn' => 42,
            'endColumn' => 61,
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
            'startLine' => 225,
            'endLine' => 225,
            'startColumn' => 64,
            'endColumn' => 80,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'placeholderMap' => 
          array (
            'name' => 'placeholderMap',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 225,
                'endLine' => 225,
                'startTokenPos' => 413,
                'startFilePos' => 9102,
                'endTokenPos' => 414,
                'endFilePos' => 9103,
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
            'startLine' => 225,
            'endLine' => 225,
            'startColumn' => 83,
            'endColumn' => 108,
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
            'name' => 'OCP\\Files\\File',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Produce a separate grondslagen-summary PDF beside the anonymised file.
 *
 * Used when the operator chose `outputFormat: "preserve"` and the
 * anonymised file is not a PDF — the summary cannot be appended in
 * place, so we write it as `<anonymised-base>_grondslagen.pdf` in the
 * same parent folder.
 *
 * @param File $anonymisedFile The anonymised file (any format).
 * @param int $sourceFileId The pre-anonymisation source file ID.
 * @param array<string, string> $placeholderMap Optional global entity id → emitted placeholder
 *                                              map so the summary renders the SAME placeholder the document carries.
 *
 * @return File The newly-written summary PDF.
 *
 * @throws \\RuntimeException When rendering or write fails.
 *
 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md#requirement-the-per-document-anonymise-endpoint-must-accept-an-optional-appendbasissummary-field
 */',
        'startLine' => 225,
        'endLine' => 250,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'aliasName' => NULL,
      ),
      'authorizeAccess' => 
      array (
        'name' => 'authorizeAccess',
        'parameters' => 
        array (
          'dossierId' => 
          array (
            'name' => 'dossierId',
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
            'startLine' => 275,
            'endLine' => 275,
            'startColumn' => 34,
            'endColumn' => 50,
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
 * Assert the dossier exists before (re)generating its summary.
 *
 * ⚠️ The name of this method overstates what it does, and the docblock it
 * replaces overstated it further ("a successful resolution means the
 * operator is permitted"). It is an EXISTENCE check. See
 * `DossierSummaryDataService::assertDossierReadable()` for the measured
 * reason: OpenRegister\'s RBAC cascade resolves to "configured nowhere" for
 * the `dossier` schema, which OpenRegister treats as open, so the refusal
 * this method relies on cannot fire for an existing dossier.
 *
 * What remains true: the HTTP layer (`DossierController`) does call this
 * before `renderDossierSummary`, and the render itself deliberately runs
 * as a system operation with RBAC disabled — so this call is the ONLY
 * pre-render check there is, which is exactly why its real strength
 * matters. Tracked in ConductionNL/filinq#441.
 *
 * @param string $dossierId The OR dossier object UUID.
 *
 * @return void
 *
 * @throws \\RuntimeException 403 when the dossier cannot be resolved at all.
 */',
        'startLine' => 275,
        'endLine' => 278,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'aliasName' => NULL,
      ),
      'renderDossierSummary' => 
      array (
        'name' => 'renderDossierSummary',
        'parameters' => 
        array (
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
            'startLine' => 302,
            'endLine' => 302,
            'startColumn' => 39,
            'endColumn' => 57,
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
 * Render the per-dossier summary PDF for one dossier.
 *
 * Aggregates anonymisation data across every file under the dossier\'s
 * folder. The resulting PDF is written to a deterministic location
 * (per Wave 2\'s `anonymisation-output-folder-layout` when shipped:
 * `<dossier-folder>/anonymised/grondslagen.pdf`; until then:
 * `<dossier-folder>/grondslagen.pdf`).
 *
 * On success the method also updates the dossier object\'s
 * `configuration.grondslagen.{fileId, lastGeneratedAt}` so the dossier
 * UI can badge the summary\'s freshness.
 *
 * @param string $dossierUuid The OR UUID of the dossier object.
 *
 * @return File The generated summary PDF.
 *
 * @throws \\RuntimeException When the dossier can\'t be loaded, the folder
 *                           isn\'t accessible, or rendering fails.
 *
 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md#requirement-a-per-dossier-summary-endpoint-must-exist
 */',
        'startLine' => 302,
        'endLine' => 364,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'aliasName' => NULL,
      ),
      'localisePlaceholderMap' => 
      array (
        'name' => 'localisePlaceholderMap',
        'parameters' => 
        array (
          'ranking' => 
          array (
            'name' => 'ranking',
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
            'startLine' => 384,
            'endLine' => 384,
            'startColumn' => 42,
            'endColumn' => 55,
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
 * Pair a dossier ranking with localized TYPE labels to form the map.
 *
 * The per-dossier report is regenerated without a live anonymise run, so
 * there is no placeholder map from OpenRegister to reuse — without one,
 * each entity falls back to its GLOBAL entity_id (the 1600+ numbers). The
 * ranking is reproduced up-front (reusing OpenRegister\'s own deterministic
 * ordering, so it can never drift from the anonymise path); this method
 * pairs each rank with the LOCALIZED type label so the report shows the
 * SAME `[<TYPE>: <number>]` the documents carry.
 *
 * An empty ranking (OpenRegister absent or too old) yields an empty map;
 * callers then fall back to the global-id behaviour.
 *
 * @param array{ranks: array<array-key, int>, types: array<string, string>} $ranking The dossier ranking.
 *
 * @return array<string, string> Map of global entity id → "[<localizedTYPE>: <dossier number>]".
 */',
        'startLine' => 384,
        'endLine' => 392,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'aliasName' => NULL,
      ),
      'aggregateForDossier' => 
      array (
        'name' => 'aggregateForDossier',
        'parameters' => 
        array (
          'perFile' => 
          array (
            'name' => 'perFile',
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
            'startLine' => 422,
            'endLine' => 422,
            'startColumn' => 39,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'labelMap' => 
          array (
            'name' => 'labelMap',
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
            'startLine' => 422,
            'endLine' => 422,
            'startColumn' => 55,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Build the row set the per-dossier template renders.
 *
 * Produces ONE row per distinct entity (`entityType:entityId`). Because the
 * dossier number is consistent across the dossier\'s files, the same
 * person/date appears once — its occurrence `count` is summed, the files it
 * appears in are collected into a comma-joined `filename` list, and its
 * grondslagen are unioned. Rows are sorted by TYPE then NUMERIC id ascending
 * so the type blocks read 1,2,…,10,11.
 *
 * Per-file entities arrive pre-aggregated from the entity collector: each
 * entry already has `placeholder`, `count`, and `basesText` (Dutch labels
 * joined).
 *
 * @param array<int, mixed> $perFile Per-file rows — each entry shaped as
 *                                   `{fileId, filename, entities[]}`.
 * @param array<string, string> $labelMap Map of base-ref → human-readable label
 *                                        (unused here — labels are already
 *                                        resolved upstream; kept for signature
 *                                        compat).
 *
 * @return array<string, mixed> Shape:
 *                              `{ rows: array<int, {placeholder, filename,
 *                              fileCount, count, baseLabels, basesText,
 *                              entityType, entityId}>,
 *                              totals: { documentCount, entityCount,
 *                              distinctEntityCount, distinctBasesCount } }`.
 */',
        'startLine' => 422,
        'endLine' => 483,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'aliasName' => NULL,
      ),
      'buildDossierRows' => 
      array (
        'name' => 'buildDossierRows',
        'parameters' => 
        array (
          'grouped' => 
          array (
            'name' => 'grouped',
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
            'startLine' => 495,
            'endLine' => 495,
            'startColumn' => 36,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Turn the grouped dossier entities into the sorted template row set.
 *
 * Rows are ordered by TYPE then NUMERIC id ascending (1,2,…,10,11 — not
 * the lexical 1,10,11,2), then by the joined filename list.
 *
 * @param array<string, array<string, mixed>> $grouped Entities grouped by `entityType:entityId`.
 *
 * @return array<int, array<string, mixed>> The sorted rows.
 */',
        'startLine' => 495,
        'endLine' => 532,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'aliasName' => NULL,
      ),
      'resolveBaseLabels' => 
      array (
        'name' => 'resolveBaseLabels',
        'parameters' => 
        array (
          'baseRefs' => 
          array (
            'name' => 'baseRefs',
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
            'startLine' => 544,
            'endLine' => 544,
            'startColumn' => 37,
            'endColumn' => 51,
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
 * Resolve a list of `base` references (slugs or UUIDs) to human-readable labels.
 *
 * @param array<int, string> $baseRefs Slugs or UUIDs of base records.
 *
 * @return array<string, array{name: string, description: string}> Map from each
 *                                                                 reference to its
 *                                                                 display name and
 *                                                                 Woo Art. 5 toelichting.
 */',
        'startLine' => 544,
        'endLine' => 546,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'aliasName' => NULL,
      ),
      'collectAssignedBases' => 
      array (
        'name' => 'collectAssignedBases',
        'parameters' => 
        array (
          'entities' => 
          array (
            'name' => 'entities',
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
            'startLine' => 557,
            'endLine' => 557,
            'startColumn' => 40,
            'endColumn' => 54,
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
 * Collect the distinct grondslagen assigned across the given entities,
 * each with its name + description (the Woo Art. 5 toelichting), for the
 * explanatory legend rendered under the summary table.
 *
 * @param array<int, array{bases?: array<int, string>}> $entities Shaped entities (each carrying a `bases` ref list).
 *
 * @return array<int, array{name: string, description: string}> Distinct bases, sorted by name.
 */',
        'startLine' => 557,
        'endLine' => 573,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'aliasName' => NULL,
      ),
      'localizeEntityType' => 
      array (
        'name' => 'localizeEntityType',
        'parameters' => 
        array (
          'entityType' => 
          array (
            'name' => 'entityType',
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
            'startLine' => 587,
            'endLine' => 587,
            'startColumn' => 38,
            'endColumn' => 55,
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
 * Localise an entity-type label for the summary placeholder so it reads
 * the same as the label OpenRegister wrote into the redacted document
 * (anonymisation-placeholder-id-scope). Only the enumerated
 * `LOCALIZABLE_ENTITY_TYPES` set is translated; an unknown / free-form type
 * is returned unchanged. When no `IL10N` is injected the raw label is
 * returned (the `en` / untranslated behaviour).
 *
 * @param string $entityType The raw entity type (e.g. \'PERSON\').
 *
 * @return string The localised label (e.g. \'PERSOON\' on nl), or the raw type.
 */',
        'startLine' => 587,
        'endLine' => 595,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'aliasName' => NULL,
      ),
      'renderPerDocumentSummary' => 
      array (
        'name' => 'renderPerDocumentSummary',
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
            'startLine' => 613,
            'endLine' => 613,
            'startColumn' => 44,
            'endColumn' => 63,
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
            'startLine' => 613,
            'endLine' => 613,
            'startColumn' => 66,
            'endColumn' => 82,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'placeholderMap' => 
          array (
            'name' => 'placeholderMap',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 613,
                'endLine' => 613,
                'startTokenPos' => 2240,
                'startFilePos' => 23858,
                'endTokenPos' => 2241,
                'endFilePos' => 23859,
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
            'startLine' => 613,
            'endLine' => 613,
            'startColumn' => 85,
            'endColumn' => 110,
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
 * Render the per-document summary template into PDF bytes.
 *
 * Shared between {@see appendSummaryToPdf} and
 * {@see renderSummaryBesideFile} — both produce the same summary
 * content; only the destination differs.
 *
 * @param File $anonymisedFile The anonymised file (for header context).
 * @param int $sourceFileId The pre-anonymisation source file id.
 * @param array<string, string> $placeholderMap Optional global entity id → emitted placeholder
 *                                              map, threaded to the entity collector.
 *
 * @return string The rendered PDF (PDF/A-3b) as raw bytes.
 *
 * @throws \\RuntimeException When template or PDF rendering fails.
 */',
        'startLine' => 613,
        'endLine' => 651,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'aliasName' => NULL,
      ),
      'countDistinctBases' => 
      array (
        'name' => 'countDistinctBases',
        'parameters' => 
        array (
          'entities' => 
          array (
            'name' => 'entities',
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
            'startLine' => 662,
            'endLine' => 662,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Count distinct base references across a set of shaped entity rows.
 *
 * Used by the per-doc template\'s footer total.
 *
 * @param array<int, array<string, mixed>> $entities Shaped entity rows.
 *
 * @return int Distinct base count.
 */',
        'startLine' => 662,
        'endLine' => 676,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
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