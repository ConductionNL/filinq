<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DocumentValidationService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\DocumentValidationService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-332fb3eaa5eb8da08822ddd6605bbf2593685acb4caa8d6ea28f527d2b8df5c8',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DocumentValidationService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\DocumentValidationService',
    'shortName' => 'DocumentValidationService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Computes document validation verdicts (pure, no side effects).
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-validation-checks/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 56,
    'endLine' => 444,
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
      'CHECK_FORMAT_NOT_ALLOWED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'name' => 'CHECK_FORMAT_NOT_ALLOWED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'format-not-allowed\'',
          'attributes' => 
          array (
            'startLine' => 60,
            'endLine' => 60,
            'startTokenPos' => 65,
            'startFilePos' => 2023,
            'endTokenPos' => 65,
            'endFilePos' => 2042,
          ),
        ),
        'docComment' => '/**
 * Check identifiers.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 60,
        'endLine' => 60,
        'startColumn' => 2,
        'endColumn' => 62,
      ),
      'CHECK_EXTENSION_MIME' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'name' => 'CHECK_EXTENSION_MIME',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'extension-mime-mismatch\'',
          'attributes' => 
          array (
            'startLine' => 61,
            'endLine' => 61,
            'startTokenPos' => 76,
            'startFilePos' => 2082,
            'endTokenPos' => 76,
            'endFilePos' => 2106,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 61,
        'endLine' => 61,
        'startColumn' => 2,
        'endColumn' => 63,
      ),
      'CHECK_FILE_UNREADABLE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'name' => 'CHECK_FILE_UNREADABLE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'file-unreadable\'',
          'attributes' => 
          array (
            'startLine' => 62,
            'endLine' => 62,
            'startTokenPos' => 87,
            'startFilePos' => 2147,
            'endTokenPos' => 87,
            'endFilePos' => 2163,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 2,
        'endColumn' => 56,
      ),
      'CHECK_PDF_ENCRYPTED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'name' => 'CHECK_PDF_ENCRYPTED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'pdf-encrypted\'',
          'attributes' => 
          array (
            'startLine' => 63,
            'endLine' => 63,
            'startTokenPos' => 98,
            'startFilePos' => 2202,
            'endTokenPos' => 98,
            'endFilePos' => 2216,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 63,
        'endLine' => 63,
        'startColumn' => 2,
        'endColumn' => 52,
      ),
      'CHECK_TEXT_LAYER_MISSING' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'name' => 'CHECK_TEXT_LAYER_MISSING',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'text-layer-missing\'',
          'attributes' => 
          array (
            'startLine' => 64,
            'endLine' => 64,
            'startTokenPos' => 109,
            'startFilePos' => 2260,
            'endTokenPos' => 109,
            'endFilePos' => 2279,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 64,
        'endLine' => 64,
        'startColumn' => 2,
        'endColumn' => 62,
      ),
      'CHECK_METADATA_INCOMPLETE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'name' => 'CHECK_METADATA_INCOMPLETE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'metadata-incomplete\'',
          'attributes' => 
          array (
            'startLine' => 65,
            'endLine' => 65,
            'startTokenPos' => 120,
            'startFilePos' => 2324,
            'endTokenPos' => 120,
            'endFilePos' => 2344,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 65,
        'endLine' => 65,
        'startColumn' => 2,
        'endColumn' => 64,
      ),
      'SEVERITY_OFF' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'name' => 'SEVERITY_OFF',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'off\'',
          'attributes' => 
          array (
            'startLine' => 70,
            'endLine' => 70,
            'startTokenPos' => 133,
            'startFilePos' => 2408,
            'endTokenPos' => 133,
            'endFilePos' => 2412,
          ),
        ),
        'docComment' => '/**
 * Severity values.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 70,
        'endLine' => 70,
        'startColumn' => 2,
        'endColumn' => 35,
      ),
      'SEVERITY_WARNING' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'name' => 'SEVERITY_WARNING',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'warning\'',
          'attributes' => 
          array (
            'startLine' => 71,
            'endLine' => 71,
            'startTokenPos' => 144,
            'startFilePos' => 2448,
            'endTokenPos' => 144,
            'endFilePos' => 2456,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 71,
        'endLine' => 71,
        'startColumn' => 2,
        'endColumn' => 43,
      ),
      'SEVERITY_BLOCKING' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'name' => 'SEVERITY_BLOCKING',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'blocking\'',
          'attributes' => 
          array (
            'startLine' => 72,
            'endLine' => 72,
            'startTokenPos' => 155,
            'startFilePos' => 2493,
            'endTokenPos' => 155,
            'endFilePos' => 2502,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 72,
        'endLine' => 72,
        'startColumn' => 2,
        'endColumn' => 45,
      ),
      'STATUS_PASSED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'name' => 'STATUS_PASSED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'passed\'',
          'attributes' => 
          array (
            'startLine' => 77,
            'endLine' => 77,
            'startTokenPos' => 168,
            'startFilePos' => 2566,
            'endTokenPos' => 168,
            'endFilePos' => 2573,
          ),
        ),
        'docComment' => '/**
 * Verdict values.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 77,
        'endLine' => 77,
        'startColumn' => 2,
        'endColumn' => 39,
      ),
      'STATUS_WARNINGS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'name' => 'STATUS_WARNINGS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'warnings\'',
          'attributes' => 
          array (
            'startLine' => 78,
            'endLine' => 78,
            'startTokenPos' => 179,
            'startFilePos' => 2608,
            'endTokenPos' => 179,
            'endFilePos' => 2617,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 78,
        'endLine' => 78,
        'startColumn' => 2,
        'endColumn' => 43,
      ),
      'STATUS_FAILED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'name' => 'STATUS_FAILED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'failed\'',
          'attributes' => 
          array (
            'startLine' => 79,
            'endLine' => 79,
            'startTokenPos' => 190,
            'startFilePos' => 2650,
            'endTokenPos' => 190,
            'endFilePos' => 2657,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 79,
        'endLine' => 79,
        'startColumn' => 2,
        'endColumn' => 39,
      ),
    ),
    'immediateProperties' => 
    array (
      'profiles' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'name' => 'profiles',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Validation\\ValidationProfileResolver',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Resolves the effective profile for a document type.
 *
 * @var ValidationProfileResolver
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 86,
        'endLine' => 86,
        'startColumn' => 2,
        'endColumn' => 54,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'inspector' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'name' => 'inspector',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Runs the file-level probes the checks are built on.
 *
 * @var DocumentFileInspector
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 93,
        'endLine' => 93,
        'startColumn' => 2,
        'endColumn' => 51,
        'isPromoted' => false,
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 108,
            'endLine' => 108,
            'startColumn' => 30,
            'endColumn' => 52,
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 108,
            'endLine' => 108,
            'startColumn' => 55,
            'endColumn' => 75,
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
 * The two collaborators are composed here rather than injected so the
 * constructor signature (and therefore the DI wiring) stays unchanged.
 * The logger and app config are consumed only by those collaborators, so
 * they are not retained as properties.
 *
 * @param LoggerInterface $logger Logger.
 * @param IAppConfig $appConfig App configuration.
 *
 * @return void
 */',
        'startLine' => 108,
        'endLine' => 112,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'aliasName' => NULL,
      ),
      'validate' => 
      array (
        'name' => 'validate',
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
            'startLine' => 125,
            'endLine' => 125,
            'startColumn' => 27,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'record' => 
          array (
            'name' => 'record',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 125,
                'endLine' => 125,
                'startTokenPos' => 297,
                'startFilePos' => 4138,
                'endTokenPos' => 298,
                'endFilePos' => 4139,
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
            'startLine' => 125,
            'endLine' => 125,
            'startColumn' => 39,
            'endColumn' => 56,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'documentType' => 
          array (
            'name' => 'documentType',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 125,
                'endLine' => 125,
                'startTokenPos' => 308,
                'startFilePos' => 4166,
                'endTokenPos' => 308,
                'endFilePos' => 4169,
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
            'startLine' => 125,
            'endLine' => 125,
            'startColumn' => 59,
            'endColumn' => 86,
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
 * Validate a file + its document record against the resolved profile.
 *
 * @param File $file The file to validate.
 * @param array<string, mixed> $record The document record (metadata).
 * @param string|null $documentType Optional document-type hint.
 *
 * @return array{validationStatus:string, validationFindings:array<int, array<string,mixed>>}
 *
 * @spec openspec/specs/document-validation-checks/spec.md
 */',
        'startLine' => 125,
        'endLine' => 148,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'aliasName' => NULL,
      ),
      'resolveProfile' => 
      array (
        'name' => 'resolveProfile',
        'parameters' => 
        array (
          'documentType' => 
          array (
            'name' => 'documentType',
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolve the validation profile for a document type, falling back to default.
 *
 * @param string $documentType The document type.
 *
 * @return array{allowedMimes:array<int,string>, requiredFields:array<int,string>, severities:array<string,string>}
 *
 * @spec openspec/specs/document-validation-checks/spec.md
 */',
        'startLine' => 159,
        'endLine' => 161,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'aliasName' => NULL,
      ),
      'aggregate' => 
      array (
        'name' => 'aggregate',
        'parameters' => 
        array (
          'findings' => 
          array (
            'name' => 'findings',
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
            'startLine' => 172,
            'endLine' => 172,
            'startColumn' => 28,
            'endColumn' => 42,
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
 * Aggregate findings into a verdict.
 *
 * @param array<int, array<string, mixed>> $findings The findings.
 *
 * @return string The verdict (passed|warnings|failed).
 *
 * @spec openspec/specs/document-validation-checks/spec.md
 */',
        'startLine' => 172,
        'endLine' => 189,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'aliasName' => NULL,
      ),
      'readContent' => 
      array (
        'name' => 'readContent',
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
            'startLine' => 199,
            'endLine' => 199,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Read a file\'s bytes once, recording whether the read failed.
 *
 * @param File $file The file to read.
 *
 * @return array{content: mixed, failed: bool} The bytes (or \'\' on failure)
 *                                             and the failure flag.
 */',
        'startLine' => 199,
        'endLine' => 212,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'aliasName' => NULL,
      ),
      'formatFindings' => 
      array (
        'name' => 'formatFindings',
        'parameters' => 
        array (
          'profile' => 
          array (
            'name' => 'profile',
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
            'startLine' => 222,
            'endLine' => 222,
            'startColumn' => 34,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'mime' => 
          array (
            'name' => 'mime',
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
            'startLine' => 222,
            'endLine' => 222,
            'startColumn' => 50,
            'endColumn' => 61,
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
 * Check 1 — the file format is not on the profile\'s allowlist.
 *
 * @param array<string, mixed> $profile The resolved profile.
 * @param string $mime The detected mime type.
 *
 * @return array<int, array<string, mixed>> Zero or one finding.
 */',
        'startLine' => 222,
        'endLine' => 240,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'aliasName' => NULL,
      ),
      'extensionFindings' => 
      array (
        'name' => 'extensionFindings',
        'parameters' => 
        array (
          'profile' => 
          array (
            'name' => 'profile',
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
            'startLine' => 251,
            'endLine' => 251,
            'startColumn' => 37,
            'endColumn' => 50,
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
            'startLine' => 251,
            'endLine' => 251,
            'startColumn' => 53,
            'endColumn' => 64,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'mime' => 
          array (
            'name' => 'mime',
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
            'startLine' => 251,
            'endLine' => 251,
            'startColumn' => 67,
            'endColumn' => 78,
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
 * Check 2 — the file extension contradicts the detected content type.
 *
 * @param array<string, mixed> $profile The resolved profile.
 * @param string $name The file name.
 * @param string $mime The detected mime type.
 *
 * @return array<int, array<string, mixed>> Zero or one finding.
 */',
        'startLine' => 251,
        'endLine' => 268,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'aliasName' => NULL,
      ),
      'readabilityFindings' => 
      array (
        'name' => 'readabilityFindings',
        'parameters' => 
        array (
          'profile' => 
          array (
            'name' => 'profile',
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
            'startLine' => 278,
            'endLine' => 278,
            'startColumn' => 39,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'contentFailed' => 
          array (
            'name' => 'contentFailed',
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
            'startLine' => 278,
            'endLine' => 278,
            'startColumn' => 55,
            'endColumn' => 73,
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
 * Check 3 — the file could not be read or parsed.
 *
 * @param array<string, mixed> $profile The resolved profile.
 * @param bool $contentFailed Whether the content read failed.
 *
 * @return array<int, array<string, mixed>> Zero or one finding.
 */',
        'startLine' => 278,
        'endLine' => 295,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'aliasName' => NULL,
      ),
      'encryptionFindings' => 
      array (
        'name' => 'encryptionFindings',
        'parameters' => 
        array (
          'profile' => 
          array (
            'name' => 'profile',
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
            'startLine' => 306,
            'endLine' => 306,
            'startColumn' => 38,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'mime' => 
          array (
            'name' => 'mime',
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
            'startLine' => 306,
            'endLine' => 306,
            'startColumn' => 54,
            'endColumn' => 65,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'content' => 
          array (
            'name' => 'content',
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
            'startLine' => 306,
            'endLine' => 306,
            'startColumn' => 68,
            'endColumn' => 81,
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
 * Check 4 — the PDF is encrypted or password-protected.
 *
 * @param array<string, mixed> $profile The resolved profile.
 * @param string $mime The detected mime type.
 * @param mixed $content The file bytes.
 *
 * @return array<int, array<string, mixed>> Zero or one finding.
 */',
        'startLine' => 306,
        'endLine' => 327,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'aliasName' => NULL,
      ),
      'textLayerFindings' => 
      array (
        'name' => 'textLayerFindings',
        'parameters' => 
        array (
          'profile' => 
          array (
            'name' => 'profile',
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
            'startLine' => 339,
            'endLine' => 339,
            'startColumn' => 37,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'mime' => 
          array (
            'name' => 'mime',
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
            'startLine' => 339,
            'endLine' => 339,
            'startColumn' => 53,
            'endColumn' => 64,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'content' => 
          array (
            'name' => 'content',
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
            'startLine' => 339,
            'endLine' => 339,
            'startColumn' => 67,
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
 * Check 5 — the document has little or no extractable text (page-bearing
 * formats only: PDF here).
 *
 * @param array<string, mixed> $profile The resolved profile.
 * @param string $mime The detected mime type.
 * @param mixed $content The file bytes.
 *
 * @return array<int, array<string, mixed>> Zero or one finding.
 */',
        'startLine' => 339,
        'endLine' => 360,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'aliasName' => NULL,
      ),
      'metadataFindings' => 
      array (
        'name' => 'metadataFindings',
        'parameters' => 
        array (
          'profile' => 
          array (
            'name' => 'profile',
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
            'startLine' => 370,
            'endLine' => 370,
            'startColumn' => 36,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'record' => 
          array (
            'name' => 'record',
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
            'startLine' => 370,
            'endLine' => 370,
            'startColumn' => 52,
            'endColumn' => 64,
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
 * Check 6 — required metadata fields are missing (one finding per field).
 *
 * @param array<string, mixed> $profile The resolved profile.
 * @param array<string, mixed> $record The document record.
 *
 * @return array<int, array<string, mixed>> Zero or more findings.
 */',
        'startLine' => 370,
        'endLine' => 390,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'aliasName' => NULL,
      ),
      'checkSeverity' => 
      array (
        'name' => 'checkSeverity',
        'parameters' => 
        array (
          'profile' => 
          array (
            'name' => 'profile',
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
            'startLine' => 400,
            'endLine' => 400,
            'startColumn' => 33,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'check' => 
          array (
            'name' => 'check',
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
            'startLine' => 400,
            'endLine' => 400,
            'startColumn' => 49,
            'endColumn' => 61,
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
 * Resolve the severity for a check under a profile.
 *
 * @param array<string, mixed> $profile The resolved profile.
 * @param string $check The check id.
 *
 * @return string The severity.
 */',
        'startLine' => 400,
        'endLine' => 402,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'aliasName' => NULL,
      ),
      'finding' => 
      array (
        'name' => 'finding',
        'parameters' => 
        array (
          'checkId' => 
          array (
            'name' => 'checkId',
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
            'startLine' => 414,
            'endLine' => 414,
            'startColumn' => 27,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'profile' => 
          array (
            'name' => 'profile',
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
            'startLine' => 414,
            'endLine' => 414,
            'startColumn' => 44,
            'endColumn' => 57,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'messageKey' => 
          array (
            'name' => 'messageKey',
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
            'startLine' => 414,
            'endLine' => 414,
            'startColumn' => 60,
            'endColumn' => 77,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'params' => 
          array (
            'name' => 'params',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 414,
                'endLine' => 414,
                'startTokenPos' => 1828,
                'startFilePos' => 12942,
                'endTokenPos' => 1829,
                'endFilePos' => 12943,
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
            'startLine' => 414,
            'endLine' => 414,
            'startColumn' => 80,
            'endColumn' => 97,
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
 * Build a finding entry.
 *
 * @param string $checkId The check id.
 * @param array<string, mixed> $profile The resolved profile.
 * @param string $messageKey The English message source (translated by the UI).
 * @param array<string, mixed> $params Message placeholder params (non-content).
 *
 * @return array<string, mixed> The finding.
 */',
        'startLine' => 414,
        'endLine' => 422,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'aliasName' => NULL,
      ),
      'fieldMissing' => 
      array (
        'name' => 'fieldMissing',
        'parameters' => 
        array (
          'record' => 
          array (
            'name' => 'record',
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
            'startLine' => 432,
            'endLine' => 432,
            'startColumn' => 32,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'field' => 
          array (
            'name' => 'field',
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
            'startLine' => 432,
            'endLine' => 432,
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
 * Whether a required field is absent or empty on the record.
 *
 * @param array<string, mixed> $record The record.
 * @param string $field The field name.
 *
 * @return bool True when missing/empty.
 */',
        'startLine' => 432,
        'endLine' => 443,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentValidationService',
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