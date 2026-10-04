<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DocumentAnonymizeRunner.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\DocumentAnonymizeRunner
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-bec6ea89d36a21efc4267848f4da57beca4831c02b6319c856f39ae2a8c96e54',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DocumentAnonymizeRunner.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
    'shortName' => 'DocumentAnonymizeRunner',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Runs the per-document anonymise pipeline.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/anonymization/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 51,
    'endLine' => 348,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
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
        'startLine' => 75,
        'endLine' => 75,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'locator' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'name' => 'locator',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\OpenRegisterServiceLocator',
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
        'endColumn' => 54,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'entityDetection' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'name' => 'entityDetection',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\EntityDetectionService',
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
        'endColumn' => 58,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'emlAnonymizer' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'name' => 'emlAnonymizer',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\EmlAnonymizationService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 78,
        'endLine' => 78,
        'startColumn' => 3,
        'endColumn' => 57,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'pdfOutput' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'name' => 'pdfOutput',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\AnonymisedPdfOutputService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 79,
        'endLine' => 79,
        'startColumn' => 3,
        'endColumn' => 56,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'replacementVerifier' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'name' => 'replacementVerifier',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\ReplacementVerificationService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 80,
        'endLine' => 80,
        'startColumn' => 3,
        'endColumn' => 70,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'persistence' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'name' => 'persistence',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 81,
        'endLine' => 81,
        'startColumn' => 3,
        'endColumn' => 63,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'summaryAttacher' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'name' => 'summaryAttacher',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\GrondslagenSummaryAttacher',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 82,
        'endLine' => 82,
        'startColumn' => 3,
        'endColumn' => 62,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'verdictRecorder' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'name' => 'verdictRecorder',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Redaction\\RedactionVerdictRecorder',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 83,
        'endLine' => 83,
        'startColumn' => 3,
        'endColumn' => 60,
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
            'startLine' => 75,
            'endLine' => 75,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'locator' => 
          array (
            'name' => 'locator',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\OpenRegisterServiceLocator',
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
            'endColumn' => 54,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'entityDetection' => 
          array (
            'name' => 'entityDetection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\EntityDetectionService',
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
            'endColumn' => 58,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'emlAnonymizer' => 
          array (
            'name' => 'emlAnonymizer',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\EmlAnonymizationService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 78,
            'endLine' => 78,
            'startColumn' => 3,
            'endColumn' => 57,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'pdfOutput' => 
          array (
            'name' => 'pdfOutput',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\AnonymisedPdfOutputService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 79,
            'endLine' => 79,
            'startColumn' => 3,
            'endColumn' => 56,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'replacementVerifier' => 
          array (
            'name' => 'replacementVerifier',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\ReplacementVerificationService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 80,
            'endLine' => 80,
            'startColumn' => 3,
            'endColumn' => 70,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
          'persistence' => 
          array (
            'name' => 'persistence',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 81,
            'endLine' => 81,
            'startColumn' => 3,
            'endColumn' => 63,
            'parameterIndex' => 6,
            'isOptional' => false,
          ),
          'summaryAttacher' => 
          array (
            'name' => 'summaryAttacher',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\GrondslagenSummaryAttacher',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 82,
            'endLine' => 82,
            'startColumn' => 3,
            'endColumn' => 62,
            'parameterIndex' => 7,
            'isOptional' => false,
          ),
          'verdictRecorder' => 
          array (
            'name' => 'verdictRecorder',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Redaction\\RedactionVerdictRecorder',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 3,
            'endColumn' => 60,
            'parameterIndex' => 8,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor for DocumentAnonymizeRunner
 *
 * @param LoggerInterface $logger Logger for error reporting.
 * @param OpenRegisterServiceLocator $locator Resolver for OpenRegister services and mappers.
 * @param EntityDetectionService $entityDetection Entity mapping / result parsing service.
 * @param EmlAnonymizationService $emlAnonymizer The EML anonymise + PDF/A-3b assembly path.
 * @param AnonymisedPdfOutputService $pdfOutput The PDF-conversion gate on the anonymised
 *                                              intermediate (cascade + rollback + pdf-only
 *                                              cleanup).
 * @param ReplacementVerificationService $replacementVerifier Replacement statistics for a run.
 * @param AnonymizationPersistenceService $persistence Post-run persistence: anonymisation link +
 *                                                     publication consents.
 * @param GrondslagenSummaryAttacher $summaryAttacher Renders and attaches the per-document
 *                                                    grondslagen summary.
 * @param RedactionVerdictRecorder $verdictRecorder Verifies the bytes that were actually
 *                                                  written and records the verdict on the
 *                                                  link, so a published copy can be shown
 *                                                  to have been checked.
 *
 * @return void
 */',
        'startLine' => 74,
        'endLine' => 86,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'aliasName' => NULL,
      ),
      'run' => 
      array (
        'name' => 'run',
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
            'startLine' => 131,
            'endLine' => 131,
            'startColumn' => 22,
            'endColumn' => 32,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 131,
            'endLine' => 131,
            'startColumn' => 35,
            'endColumn' => 49,
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
            'startLine' => 131,
            'endLine' => 131,
            'startColumn' => 52,
            'endColumn' => 65,
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
 * Anonymize entities in a document.
 *
 * When `$options[\'appendBasisSummary\']` is true, invokes the summary attacher
 * after the anonymised file has been written. For PDF output the summary is
 * appended as an extra page; otherwise a separate `<base>_grondslagen.pdf` is
 * written alongside. Summary failure is non-fatal: the anonymised file is
 * always preserved and a `warning` field is added to the response instead.
 *
 * When `$options[\'outputFormat\']` is "pdf-only" (default) or "pdf", the
 * anonymised intermediate is run through the PdfConversionService cascade and
 * replaced with the PDF; on cascade failure the intermediate is rolled back
 * (best-effort) and a ConversionFailedException is thrown for the controller
 * to surface as HTTP 422. "pdf-only" additionally best-effort deletes the
 * native anonymised intermediate after a successful conversion so only the PDF
 * remains; "pdf" keeps it too; "preserve" skips conversion entirely.
 *
 * EML inputs are routed to OpenRegister\'s dedicated anonymise-EML API and
 * assembled into a PDF/A-3b by EmlPdfAssemblyService (OR\'s anonymizeDocument
 * throws on message/rfc822); "preserve" is overridden to PDF for EML.
 *
 * When `$options[\'unredactedEntities\']` is non-empty, a publicationConsent
 * record is created for each entry AFTER the anonymise pipeline succeeds.
 *
 * @param int $fileId The Nextcloud file ID.
 * @param array<array<string, mixed>> $entities The entities to anonymize.
 * @param array<string, mixed> $options Run options: appendBasisSummary (bool),
 *                                      outputFormat (string), unredactedEntities
 *                                      (array), scope (string) and dossierKey
 *                                      (string|null).
 *
 * @return array<string, mixed> Anonymization result with optional
 *                              warning/summaryFileId/createdConsents fields.
 *
 * @throws Exception If anonymization fails.
 * @throws ConversionFailedException When the cascade could not convert the anonymised
 *                                   intermediate. The intermediate is deleted (best-effort)
 *                                   before the exception propagates.
 *
 * @spec openspec/specs/anonymization/spec.md
 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-2
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
 */',
        'startLine' => 131,
        'endLine' => 182,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'aliasName' => NULL,
      ),
      'anonymizeStandardDocument' => 
      array (
        'name' => 'anonymizeStandardDocument',
        'parameters' => 
        array (
          'fileService' => 
          array (
            'name' => 'fileService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 210,
            'endLine' => 210,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'mappedEntities' => 
          array (
            'name' => 'mappedEntities',
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
            'startLine' => 211,
            'endLine' => 211,
            'startColumn' => 3,
            'endColumn' => 23,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'context' => 
          array (
            'name' => 'context',
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
            'startLine' => 212,
            'endLine' => 212,
            'startColumn' => 3,
            'endColumn' => 16,
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
            'startLine' => 213,
            'endLine' => 213,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 3,
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
 * Run the standard (non-EML) anonymise path for one document.
 *
 * Captures a textual projection of the ORIGINAL document BEFORE anonymization
 * so the replacement statistics reflect which mapped entity values were
 * actually present (and therefore eligible for str_ireplace inside
 * OpenRegister\'s DocumentProcessingHandler) instead of a fabricated count —
 * closes #286.
 *
 * Scope and dossierKey are passed positionally to OpenRegister\'s
 * reflectively-resolved FileService; a null dossierKey lets OpenRegister fall
 * back to the file\'s parent folder.
 *
 * @param mixed $fileService OpenRegister FileService.
 * @param array<int, array<string,mixed>> $mappedEntities Entities forwarded to OpenRegister.
 * @param array<string, mixed> $context Run context (see finaliseResult).
 * @param array<string, mixed> $options outputFormat, scope, dossierKey and
 *                                      unredactedEntities for this run.
 *
 * @return array<string, mixed> The anonymisation result.
 *
 * @throws ConversionFailedException When the PDF cascade is exhausted.
 *
 * @spec openspec/specs/anonymization/spec.md
 */',
        'startLine' => 209,
        'endLine' => 260,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'aliasName' => NULL,
      ),
      'buildResultInfo' => 
      array (
        'name' => 'buildResultInfo',
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
                'name' => 'mixed',
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
            'startColumn' => 35,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'verification' => 
          array (
            'name' => 'verification',
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
            'startColumn' => 50,
            'endColumn' => 68,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'residualEntities' => 
          array (
            'name' => 'residualEntities',
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
            'startColumn' => 71,
            'endColumn' => 93,
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
 * Assemble the anonymise response payload.
 *
 * Surfaces the truth: `replacementsAttempted` is how many entities we forwarded
 * to OR; `replacementsApplied` is how many of those actually appeared (and were
 * therefore replaced) in the source text; `replacementsVerified` signals whether
 * the source could be read as text at all (binary formats cannot be verified at
 * this layer — see ReplacementVerificationService::readNodeText()). When
 * verified is false, `replacementsApplied` is null and `replacementCount` falls
 * back to attempted.
 *
 * @param mixed $result The anonymised node.
 * @param array<string, mixed> $verification The verify() outcome.
 * @param array<int, mixed> $residualEntities OpenRegister\'s best-effort residual list.
 *
 * @return array<string, mixed> The result info.
 *
 * @spec openspec/specs/anonymization/spec.md
 */',
        'startLine' => 281,
        'endLine' => 302,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'aliasName' => NULL,
      ),
      'finaliseResult' => 
      array (
        'name' => 'finaliseResult',
        'parameters' => 
        array (
          'resultInfo' => 
          array (
            'name' => 'resultInfo',
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
            'startLine' => 318,
            'endLine' => 318,
            'startColumn' => 34,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'context' => 
          array (
            'name' => 'context',
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
            'startLine' => 318,
            'endLine' => 318,
            'startColumn' => 53,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Apply the optional grondslagen summary and persist the anonymisation link.
 *
 * Shared tail of both the standard and the EML anonymise paths. The link is
 * recorded on the success path only, guarded on a known anonymised file id.
 *
 * @param array<string, mixed> $resultInfo The result assembled so far.
 * @param array<string, mixed> $context Run context: appendBasisSummary, anonymisedNode,
 *                                      sourceNode, fileId, placeholderMap.
 *
 * @return array<string, mixed> The finalised result info.
 *
 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-2
 */',
        'startLine' => 318,
        'endLine' => 347,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
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