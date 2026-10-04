<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/AnonymizationPersistenceService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\AnonymizationPersistenceService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-577739a5514bd11e4e414e1dcd6f90abc02cb92f9ba144e2fd268163e92544ee',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/AnonymizationPersistenceService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
    'shortName' => 'AnonymizationPersistenceService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Records the anonymisation link and the unredacted-publication consents.
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
    'startLine' => 49,
    'endLine' => 446,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
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
        'startLine' => 61,
        'endLine' => 61,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
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
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 3,
        'endColumn' => 54,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'consentCrud' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'name' => 'consentCrud',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\ConsentCrudService',
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
        'endColumn' => 50,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'consentService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'name' => 'consentService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\ConsentService',
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
        'endColumn' => 49,
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
            'startLine' => 61,
            'endLine' => 61,
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
            'startLine' => 62,
            'endLine' => 62,
            'startColumn' => 3,
            'endColumn' => 54,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'consentCrud' => 
          array (
            'name' => 'consentCrud',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\ConsentCrudService',
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
            'endColumn' => 50,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'consentService' => 
          array (
            'name' => 'consentService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\ConsentService',
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
            'endColumn' => 49,
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
 * Constructor for AnonymizationPersistenceService
 *
 * @param LoggerInterface $logger Logger for best-effort persistence failures.
 * @param OpenRegisterServiceLocator $locator Resolver for OpenRegister services and mappers.
 * @param ConsentCrudService $consentCrud Consent CRUD service for register/schema config.
 * @param ConsentService $consentService Consent service for creating publication consents.
 *
 * @return void
 */',
        'startLine' => 60,
        'endLine' => 67,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'aliasName' => NULL,
      ),
      'recordAnonymizationLink' => 
      array (
        'name' => 'recordAnonymizationLink',
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
            'startLine' => 94,
            'endLine' => 94,
            'startColumn' => 42,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'sourceNode' => 
          array (
            'name' => 'sourceNode',
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
            'startLine' => 94,
            'endLine' => 94,
            'startColumn' => 55,
            'endColumn' => 71,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
            'startLine' => 94,
            'endLine' => 94,
            'startColumn' => 74,
            'endColumn' => 90,
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
 * Persist or update the mapping between a source file and its anonymised counterpart.
 *
 * Idempotent UPSERT keyed on `sourceFileId`: the first successful
 * anonymisation of a file creates an `anonymizationLink` object in the
 * `document` register; every subsequent re-anonymisation of the same
 * source file updates that same record (preserving its `@self`, which
 * triggers OpenRegister\'s update path) and increments `runCount`. Both
 * `sourceFileId` and `anonymizedFileId` are facetable on the schema so
 * OR\'s search API resolves the link in both directions.
 *
 * Best-effort: the anonymised file already exists and the run has
 * succeeded, so a persistence failure here MUST NOT abort or alter the
 * response. Failures are caught, logged at warning level, and the
 * unmodified `$resultInfo` is returned (without an `anonymizationLinkId`
 * key).
 *
 * @param int $fileId The source (unanonymised) Nextcloud file ID.
 * @param mixed $sourceNode The source file node (used for name/path/owner metadata).
 * @param array<string, mixed> $resultInfo Current result; carries anonymizedFileId/Name/Path + replacementCount.
 *
 * @return array<string, mixed> The `$resultInfo`, enriched with `anonymizationLinkId` on success.
 *
 * @spec openspec/specs/anonymization/spec.md
 */',
        'startLine' => 94,
        'endLine' => 133,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'aliasName' => NULL,
      ),
      'createConsentsForUnredactedEntities' => 
      array (
        'name' => 'createConsentsForUnredactedEntities',
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
            'startLine' => 150,
            'endLine' => 150,
            'startColumn' => 54,
            'endColumn' => 70,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'unredactedEntities' => 
          array (
            'name' => 'unredactedEntities',
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
            'startLine' => 150,
            'endLine' => 150,
            'startColumn' => 73,
            'endColumn' => 97,
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
 * Create publicationConsent records for each unredacted entity after a successful anonymise run.
 *
 * Calls ConsentService::createConsentRequest() once per entry. Any consent-creation
 * failure is logged but does NOT abort the response — the consent failure is surfaced as
 * a structured error entry in createdConsents[].
 *
 * @param array<string, mixed> $resultInfo Current anonymization result.
 * @param array<int, array<string, mixed>> $unredactedEntities Validated unredacted-entity entries.
 *
 * @return array<string, mixed> Result enriched with createdConsents[] field.
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-4
 */',
        'startLine' => 150,
        'endLine' => 173,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'aliasName' => NULL,
      ),
      'createOneConsent' => 
      array (
        'name' => 'createOneConsent',
        'parameters' => 
        array (
          'entry' => 
          array (
            'name' => 'entry',
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
            'startLine' => 186,
            'endLine' => 186,
            'startColumn' => 36,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'documentId' => 
          array (
            'name' => 'documentId',
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
            'startLine' => 186,
            'endLine' => 186,
            'startColumn' => 50,
            'endColumn' => 67,
            'parameterIndex' => 1,
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
            'startColumn' => 70,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create the publicationConsent record for one unredacted entity.
 *
 * @param array<string, mixed> $entry The unredacted-entity entry.
 * @param string $documentId The anonymised document id the consent belongs to.
 * @param array<string, string> $config Resolved consent register/schema configuration.
 *
 * @return array<string, mixed> The created-consent descriptor, or its structured failure entry.
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
 */',
        'startLine' => 186,
        'endLine' => 231,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'aliasName' => NULL,
      ),
      'buildLinkObject' => 
      array (
        'name' => 'buildLinkObject',
        'parameters' => 
        array (
          'objectService' => 
          array (
            'name' => 'objectService',
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
            'startLine' => 244,
            'endLine' => 244,
            'startColumn' => 35,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 244,
            'endLine' => 244,
            'startColumn' => 57,
            'endColumn' => 67,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
            'startLine' => 244,
            'endLine' => 244,
            'startColumn' => 70,
            'endColumn' => 86,
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
 * Build the anonymizationLink object, reusing the existing record when present.
 *
 * @param mixed $objectService OpenRegister ObjectService.
 * @param int $fileId The source Nextcloud file id.
 * @param array<string, mixed> $resultInfo The anonymise result.
 *
 * @return array<string, mixed> The object ready to be saved.
 *
 * @spec openspec/specs/anonymization/spec.md
 */',
        'startLine' => 244,
        'endLine' => 309,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'aliasName' => NULL,
      ),
      'applySourceNodeMetadata' => 
      array (
        'name' => 'applySourceNodeMetadata',
        'parameters' => 
        array (
          'object' => 
          array (
            'name' => 'object',
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
            'startLine' => 324,
            'endLine' => 324,
            'startColumn' => 43,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'sourceNode' => 
          array (
            'name' => 'sourceNode',
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
            'startLine' => 324,
            'endLine' => 324,
            'startColumn' => 58,
            'endColumn' => 74,
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
 * Apply best-effort source-node metadata (name, path, owner) to a link object.
 *
 * Each accessor is guarded with method_exists so the method tolerates any
 * file-node-like object (and mocks in unit tests) without fataling.
 *
 * @param array<string, mixed> $object The link object being built.
 * @param mixed $sourceNode The source file node.
 *
 * @return array<string, mixed> The object with any resolvable source metadata applied.
 *
 * @spec openspec/specs/anonymization/spec.md
 */',
        'startLine' => 324,
        'endLine' => 347,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'aliasName' => NULL,
      ),
      'extractLinkObjectData' => 
      array (
        'name' => 'extractLinkObjectData',
        'parameters' => 
        array (
          'candidate' => 
          array (
            'name' => 'candidate',
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
            'startLine' => 358,
            'endLine' => 358,
            'startColumn' => 41,
            'endColumn' => 56,
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
 * Normalise a searchObjects() candidate to a plain array including its `@self`.
 *
 * @param mixed $candidate A search result entry (array, or an OR entity object).
 *
 * @return array<string, mixed> The object data, or an empty array if it could not be read.
 *
 * @spec openspec/specs/anonymization/spec.md
 */',
        'startLine' => 358,
        'endLine' => 368,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'aliasName' => NULL,
      ),
      'extractObjectPayload' => 
      array (
        'name' => 'extractObjectPayload',
        'parameters' => 
        array (
          'candidate' => 
          array (
            'name' => 'candidate',
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
            'startLine' => 379,
            'endLine' => 379,
            'startColumn' => 40,
            'endColumn' => 56,
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
 * Read an OR entity object\'s payload, restoring its `@self` id when absent.
 *
 * @param object $candidate The OR entity object.
 *
 * @return array<string, mixed> The payload, or an empty array when it cannot be read.
 *
 * @spec openspec/specs/anonymization/spec.md
 */',
        'startLine' => 379,
        'endLine' => 404,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'aliasName' => NULL,
      ),
      'extractSavedObjectId' => 
      array (
        'name' => 'extractSavedObjectId',
        'parameters' => 
        array (
          'saved' => 
          array (
            'name' => 'saved',
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
            'startLine' => 415,
            'endLine' => 415,
            'startColumn' => 40,
            'endColumn' => 51,
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
 * Extract the persisted object\'s identifier from a saveObject() return value.
 *
 * @param mixed $saved The value returned by ObjectService::saveObject.
 *
 * @return string|null The object id/uuid, or null when it cannot be determined.
 *
 * @spec openspec/specs/anonymization/spec.md
 */',
        'startLine' => 415,
        'endLine' => 445,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymizationPersistenceService',
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