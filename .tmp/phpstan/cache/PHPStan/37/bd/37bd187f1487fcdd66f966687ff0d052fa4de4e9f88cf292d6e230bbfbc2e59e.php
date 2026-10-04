<?php declare(strict_types = 1);

// osfsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/SettingsService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\SettingsService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-cf8549e61fa61b680622f76c52417eddd9e622b26b527f5dba37c2a2a8aa59c3-8.3-6.70.0.6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\SettingsService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/SettingsService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\SettingsService',
    'shortName' => 'SettingsService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service for handling settings-related operations in Filinq
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/ocr-document-scanning/tasks.md#task-4.1
 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md#requirement-optionally-suggest-batchfolder-analysis-priority-req-ddfcl-003
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 50,
    'endLine' => 546,
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
      'WRITABLE_KEYS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'name' => 'WRITABLE_KEYS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'publicationConsent_register\', \'publicationConsent_schema\', \'publicationConsent_source\', \'template_register\', \'template_schema\', \'template_source\', \'publication_objection_period_days\', \'enable_language_detection\', \'enable_keyword_extraction\', \'enable_topic_classification\', \'signing_enabled\', \'signing_provider\', \'signing_default_level\', \'signing_request_expiry_days\', \'signing_guardian_consent_age\', \'ocr_enabled\', \'ocr_languages\', \'ocr_dpi\', \'filinq.confidentiality.label_vocabulary\', \'filinq.confidentiality.prioritise_analysis\']',
          'attributes' => 
          array (
            'startLine' => 375,
            'endLine' => 396,
            'startTokenPos' => 1284,
            'startFilePos' => 13230,
            'endTokenPos' => 1346,
            'endFilePos' => 13806,
          ),
        ),
        'docComment' => '/**
 * Keys that are permitted to be written via the settings endpoint.
 *
 * This allowlist prevents any authenticated user (wave-3 C1) from
 * overwriting security-sensitive keys such as signing_verification_secret
 * through the open settings POST endpoint.  Secret keys (signing_* tokens
 * etc.) must be managed through dedicated, separately-secured endpoints.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 375,
        'endLine' => 396,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'appName' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'name' => 'appName',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * The application name for identification and configuration purposes
 *
 * @var string The name of the app
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 2,
        'endColumn' => 34,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'config' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsService',
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
        'startLine' => 75,
        'endLine' => 75,
        'startColumn' => 3,
        'endColumn' => 37,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsService',
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
        'startLine' => 76,
        'endLine' => 76,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'discoveryService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'name' => 'discoveryService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
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
        'endColumn' => 61,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'initializer' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'name' => 'initializer',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\SettingsInitializer',
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
        'endColumn' => 51,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'ocrService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'name' => 'ocrService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\OcrService',
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
        'endColumn' => 41,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'legalBasisProposal' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'name' => 'legalBasisProposal',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
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
        'endColumn' => 64,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'openRegister' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'name' => 'openRegister',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\OpenRegisterAvailabilityService',
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
        'endColumn' => 64,
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
            'startLine' => 75,
            'endLine' => 75,
            'startColumn' => 3,
            'endColumn' => 37,
            'parameterIndex' => 0,
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
            'startLine' => 76,
            'endLine' => 76,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'discoveryService' => 
          array (
            'name' => 'discoveryService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\RegisterDiscoveryService',
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
            'endColumn' => 61,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'initializer' => 
          array (
            'name' => 'initializer',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\SettingsInitializer',
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
            'endColumn' => 51,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'ocrService' => 
          array (
            'name' => 'ocrService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\OcrService',
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
            'endColumn' => 41,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'legalBasisProposal' => 
          array (
            'name' => 'legalBasisProposal',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
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
            'endColumn' => 64,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
          'openRegister' => 
          array (
            'name' => 'openRegister',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\OpenRegisterAvailabilityService',
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
            'endColumn' => 64,
            'parameterIndex' => 6,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * SettingsService constructor
 *
 * @param IAppConfig $config App configuration interface
 * @param LoggerInterface $logger Logger interface
 * @param RegisterDiscoveryService $discoveryService Register discovery service
 * @param SettingsInitializer $initializer Settings initializer
 * @param OcrService $ocrService OCR service for Tesseract status
 * @param LegalBasisProposalService $legalBasisProposal Grondslag-per-entity-type proposal service
 * @param OpenRegisterAvailabilityService $openRegister OpenRegister availability resolver
 *
 * @return void
 *
 * @spec openspec/changes/ocr-document-scanning/tasks.md#task-4.2
 */',
        'startLine' => 74,
        'endLine' => 85,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'aliasName' => NULL,
      ),
      'isOpenRegisterInstalled' => 
      array (
        'name' => 'isOpenRegisterInstalled',
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
 * Checks if OpenRegister is installed and meets version requirements
 *
 * @return bool True if OpenRegister is installed and meets version requirements
 */',
        'startLine' => 92,
        'endLine' => 94,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'aliasName' => NULL,
      ),
      'getObjectService' => 
      array (
        'name' => 'getObjectService',
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
                  'name' => 'OCA\\OpenRegister\\Service\\ObjectService',
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
 * Attempts to retrieve the OpenRegister service from the container
 *
 * @return \\OCA\\OpenRegister\\Service\\ObjectService|null The OpenRegister service
 *
 * @throws \\RuntimeException If the service is not available
 *
 * @spec openspec/specs/admin-settings/spec.md
 */',
        'startLine' => 105,
        'endLine' => 107,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'aliasName' => NULL,
      ),
      'initialize' => 
      array (
        'name' => 'initialize',
        'parameters' => 
        array (
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
 * Initializes the app with all required components
 *
 * @return array<string, mixed> The initialization results
 *
 * @throws \\RuntimeException If initialization fails
 *
 * @spec openspec/specs/admin-settings/spec.md
 */',
        'startLine' => 118,
        'endLine' => 120,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'aliasName' => NULL,
      ),
      'getFeatureToggles' => 
      array (
        'name' => 'getFeatureToggles',
        'parameters' => 
        array (
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
 * The feature toggles alone, without the rest of the settings payload.
 *
 * {@see getAllSettings()} assembles what the ADMIN SETTINGS PAGE needs:
 * every available register with its schemas, the object-type
 * configuration, OCR status and the grondslag selector data. That is
 * correct for a settings screen and catastrophic on a write path — the
 * register/schema discovery alone issued 1,471 `SchemaMapper::find()`
 * calls (one per schema on the instance) when it was reached from
 * {@see \\OCA\\Filinq\\EventListener\\EnrichmentRunner}, which runs inside
 * an unrelated app\'s object save. Measured 2026-07-29: 96% of ALL schema
 * reads during an OpenRegister object create originated there, and the
 * create took 9-17s.
 *
 * Anything that only needs a toggle MUST call this instead. These are
 * plain IAppConfig reads and touch no register or schema.
 *
 * @return array<string, mixed> Feature toggle settings.
 *
 * @spec openspec/specs/admin-settings/spec.md
 */',
        'startLine' => 143,
        'endLine' => 145,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'aliasName' => NULL,
      ),
      'loadFeatureToggles' => 
      array (
        'name' => 'loadFeatureToggles',
        'parameters' => 
        array (
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
 * Load feature toggle settings from app config
 *
 * @return array<string, mixed> Feature toggle settings
 *
 * @spec openspec/specs/admin-settings/spec.md
 */',
        'startLine' => 154,
        'endLine' => 252,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'aliasName' => NULL,
      ),
      'getConfidentialityVocabulary' => 
      array (
        'name' => 'getConfidentialityVocabulary',
        'parameters' => 
        array (
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
 * Read the configured confidentiality-label vocabulary for the settings
 * UI, falling back to ConfidentialityLabelService\'s default TSCP/BAILS
 * names when unset or unreadable.
 *
 * @return array<string, int> Map of label/tag name to normalised level
 *
 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md#requirement-read-a-files-confidentiality-label-availability-guarded-req-ddfcl-001
 */',
        'startLine' => 263,
        'endLine' => 279,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'aliasName' => NULL,
      ),
      'getOcrStatus' => 
      array (
        'name' => 'getOcrStatus',
        'parameters' => 
        array (
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
 * Get Tesseract OCR availability status
 *
 * @return array{tesseractAvailable: bool, tesseractVersion: string|null} OCR status
 *
 * @spec openspec/changes/ocr-document-scanning/tasks.md#task-4.2
 */',
        'startLine' => 288,
        'endLine' => 294,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'aliasName' => NULL,
      ),
      'getAllSettings' => 
      array (
        'name' => 'getAllSettings',
        'parameters' => 
        array (
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
 * Retrieve all settings
 *
 * @return array<string, mixed> The current settings configuration
 *
 * @throws RuntimeException If settings retrieval fails
 *
 * @spec openspec/specs/admin-settings/spec.md
 * @spec openspec/changes/ocr-document-scanning/tasks.md#task-4.3
 */',
        'startLine' => 306,
        'endLine' => 348,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'aliasName' => NULL,
      ),
      'convertValueToString' => 
      array (
        'name' => 'convertValueToString',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
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
            'startLine' => 357,
            'endLine' => 357,
            'startColumn' => 40,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Convert a setting value to string for storage
 *
 * @param mixed $value The value to convert
 *
 * @return string The string representation
 */',
        'startLine' => 357,
        'endLine' => 363,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'aliasName' => NULL,
      ),
      'updateSettings' => 
      array (
        'name' => 'updateSettings',
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
            'startLine' => 413,
            'endLine' => 413,
            'startColumn' => 33,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Update the settings configuration
 *
 * Only keys present in WRITABLE_KEYS may be written; all other keys are
 * silently skipped.  This prevents escalation via security-sensitive keys
 * such as signing_verification_secret (wave-3 C1).
 *
 * @param array<string, mixed> $data The settings data to update
 *
 * @return array<string, mixed> The updated settings configuration
 *
 * @throws \\RuntimeException If settings update fails
 *
 * @spec openspec/specs/admin-settings/spec.md
 */',
        'startLine' => 413,
        'endLine' => 448,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'aliasName' => NULL,
      ),
      'resolveSigningRequestBinding' => 
      array (
        'name' => 'resolveSigningRequestBinding',
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
                  'name' => 'array',
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
 * Resolve the signingRequest register/schema binding, or null when unset.
 *
 * These bindings live here, next to the settings surface that writes them,
 * rather than in each consumer: every call site used to read them inline
 * with an empty-string default and pass the result straight into
 * saveObject()/find(). Unconfigured, that wrote signing requests — the
 * audit trail behind an eIDAS-level signature — into register \'\' and schema
 * \'\', silently. Mirrors OpenRegisterResolver::getRegisterAndSchema(), which
 * already does exactly this for templates.
 *
 * @return array{register: string, schema: string}|null The binding, or null when unset.
 *
 * @spec openspec/specs/document-signing/spec.md
 */',
        'startLine' => 465,
        'endLine' => 473,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'aliasName' => NULL,
      ),
      'resolveSignerRecordBinding' => 
      array (
        'name' => 'resolveSignerRecordBinding',
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
                  'name' => 'array',
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
 * Resolve the signerRecord register/schema binding, or null when unset.
 *
 * A signer record carries the identity a signature is attributed to, so an
 * unconfigured binding loses exactly the evidence a signature exists to
 * provide.
 *
 * @return array{register: string, schema: string}|null The binding, or null when unset.
 *
 * @spec openspec/specs/document-signing/spec.md
 */',
        'startLine' => 486,
        'endLine' => 494,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'aliasName' => NULL,
      ),
      'resolveFinancialExtractionBinding' => 
      array (
        'name' => 'resolveFinancialExtractionBinding',
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
                  'name' => 'array',
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
 * Resolve the financialExtraction register/schema binding, or null when unset.
 *
 * @return array{register: string, schema: string}|null The binding, or null when unset.
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 */',
        'startLine' => 503,
        'endLine' => 511,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'aliasName' => NULL,
      ),
      'resolveGlAccountBookingBinding' => 
      array (
        'name' => 'resolveGlAccountBookingBinding',
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
                  'name' => 'array',
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
 * Resolve the glAccountBooking register/schema binding, or null when unset.
 *
 * @return array{register: string, schema: string}|null The binding, or null when unset.
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 */',
        'startLine' => 520,
        'endLine' => 528,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'aliasName' => NULL,
      ),
      'resolveGlAccountMappingRuleBinding' => 
      array (
        'name' => 'resolveGlAccountMappingRuleBinding',
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
                  'name' => 'array',
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
 * Resolve the glAccountMappingRule register/schema binding, or null when unset.
 *
 * @return array{register: string, schema: string}|null The binding, or null when unset.
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 */',
        'startLine' => 537,
        'endLine' => 545,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SettingsService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SettingsService',
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