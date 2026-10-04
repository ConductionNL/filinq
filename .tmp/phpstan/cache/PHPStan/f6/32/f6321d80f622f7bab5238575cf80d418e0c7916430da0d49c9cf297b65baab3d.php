<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/ConsentCrudService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\ConsentCrudService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-c2b6dc593e4b71d3cf792369316978a25ac7adf6ae99f162f394da9005a6e731',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/ConsentCrudService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\ConsentCrudService',
    'shortName' => 'ConsentCrudService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service for consent CRUD operations used by the controller
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/consent-management/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 42,
    'endLine' => 358,
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
      'POSITIONAL_CREATE_FIELDS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'name' => 'POSITIONAL_CREATE_FIELDS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'documentId\', \'entityType\', \'entityText\']',
          'attributes' => 
          array (
            'startLine' => 188,
            'endLine' => 192,
            'startTokenPos' => 618,
            'startFilePos' => 6408,
            'endTokenPos' => 629,
            'endFilePos' => 6459,
          ),
        ),
        'docComment' => '/**
 * Positional consent-creation fields consumed directly by createFromRequest.
 *
 * These three are passed as the named positional arguments to
 * {@see ConsentService::createConsentRequest()} and are therefore never
 * part of the forwarded `$extra` payload.
 *
 * @var array<string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 188,
        'endLine' => 192,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
      'SERVER_CONTROLLED_CREATE_FIELDS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'name' => 'SERVER_CONTROLLED_CREATE_FIELDS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'policyMatch\', \'matchKind\', \'consentStatus\', \'publicationDecision\', \'notificationStatus\', \'notificationSentAt\', \'objectionDeadline\', \'objectionReceivedAt\', \'objectionReason\', \'userId\', \'owner\']',
          'attributes' => 
          array (
            'startLine' => 207,
            'endLine' => 219,
            'startTokenPos' => 642,
            'startFilePos' => 7125,
            'endTokenPos' => 677,
            'endFilePos' => 7344,
          ),
        ),
        'docComment' => '/**
 * Server-controlled fields that callers must NOT set at creation time.
 *
 * These are the consent workflow/state fields whose values are owned by
 * the server\'s status machine and the policy-match engine. A request that
 * carries any of them is a probing/injection attempt: the field is
 * stripped and a structured security warning is logged naming the key
 * (NOT the value — ADR-005). DENYLIST, not allowlist — any field NOT
 * listed here (and not a framework key) is a legitimate extra that is
 * forwarded unchanged (finding #290b, PR #147 fifth-pass).
 *
 * @var array<string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 207,
        'endLine' => 219,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
      'FRAMEWORK_REQUEST_KEYS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'name' => 'FRAMEWORK_REQUEST_KEYS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'_route\', \'_method\', \'_format\']',
          'attributes' => 
          array (
            'startLine' => 228,
            'endLine' => 232,
            'startTokenPos' => 690,
            'startFilePos' => 7633,
            'endTokenPos' => 701,
            'endFilePos' => 7674,
          ),
        ),
        'docComment' => '/**
 * Framework / routing keys that leak into the request bag and must never
 * be forwarded to the domain layer. Stripped silently (not a security
 * event — they are an artefact of the request plumbing).
 *
 * @var array<string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 228,
        'endLine' => 232,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'settingsService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'name' => 'settingsService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\SettingsService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 53,
        'endLine' => 53,
        'startColumn' => 3,
        'endColumn' => 51,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'consentService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
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
        'startLine' => 54,
        'endLine' => 54,
        'startColumn' => 3,
        'endColumn' => 49,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
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
        'startLine' => 55,
        'endLine' => 55,
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
          'settingsService' => 
          array (
            'name' => 'settingsService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\SettingsService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 53,
            'endLine' => 53,
            'startColumn' => 3,
            'endColumn' => 51,
            'parameterIndex' => 0,
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
            'startLine' => 54,
            'endLine' => 54,
            'startColumn' => 3,
            'endColumn' => 49,
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
            'startLine' => 55,
            'endLine' => 55,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor for ConsentCrudService
 *
 * @param SettingsService $settingsService Settings service for register/schema IDs
 * @param ConsentService $consentService Consent service for consent operations
 * @param LoggerInterface $logger Logger for security/audit events
 *
 * @return void
 */',
        'startLine' => 52,
        'endLine' => 58,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'aliasName' => NULL,
      ),
      'getConsentConfig' => 
      array (
        'name' => 'getConsentConfig',
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
 * Get the consent register and schema IDs from settings
 *
 * @return array{register: string, schema: string}|null Config or null if not configured
 *
 * @spec openspec/specs/consent-management/spec.md
 */',
        'startLine' => 67,
        'endLine' => 85,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'aliasName' => NULL,
      ),
      'listConsents' => 
      array (
        'name' => 'listConsents',
        'parameters' => 
        array (
          'register' => 
          array (
            'name' => 'register',
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
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 31,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'schema' => 
          array (
            'name' => 'schema',
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
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 49,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'ownerUid' => 
          array (
            'name' => 'ownerUid',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 104,
                'endLine' => 104,
                'startTokenPos' => 258,
                'startFilePos' => 3523,
                'endTokenPos' => 258,
                'endFilePos' => 3526,
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
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 65,
            'endColumn' => 88,
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
 * List consent records, optionally scoped to a single owner
 *
 * When $ownerUid is provided (non-admin callers) the search is filtered
 * server-side so only records whose @self.owner matches the caller are
 * returned. Passing null returns all records (admin callers only).
 *
 * @param string $register The register ID
 * @param string $schema The schema ID
 * @param string|null $ownerUid UID to scope results to, or null for all
 *
 * @return array<int, array<string, mixed>> List of consent records
 *
 * @throws Exception If listing fails
 *
 * @spec openspec/specs/consent-management/spec.md
 */',
        'startLine' => 104,
        'endLine' => 129,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'aliasName' => NULL,
      ),
      'getConsent' => 
      array (
        'name' => 'getConsent',
        'parameters' => 
        array (
          'consentId' => 
          array (
            'name' => 'consentId',
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
            'startLine' => 144,
            'endLine' => 144,
            'startColumn' => 29,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'register' => 
          array (
            'name' => 'register',
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
            'startLine' => 144,
            'endLine' => 144,
            'startColumn' => 48,
            'endColumn' => 63,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'schema' => 
          array (
            'name' => 'schema',
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
            'startLine' => 144,
            'endLine' => 144,
            'startColumn' => 66,
            'endColumn' => 79,
            'parameterIndex' => 2,
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
 * Get a single consent record by ID
 *
 * @param string $consentId The consent record UUID
 * @param string $register The register ID
 * @param string $schema The schema ID
 *
 * @return array<string, mixed>|null The consent record or null if not found
 *
 * @throws Exception If retrieval fails
 *
 * @spec openspec/specs/consent-management/spec.md
 */',
        'startLine' => 144,
        'endLine' => 177,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'aliasName' => NULL,
      ),
      'createFromRequest' => 
      array (
        'name' => 'createFromRequest',
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
            'startLine' => 261,
            'endLine' => 261,
            'startColumn' => 36,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'register' => 
          array (
            'name' => 'register',
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
            'startLine' => 261,
            'endLine' => 261,
            'startColumn' => 49,
            'endColumn' => 64,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'schema' => 
          array (
            'name' => 'schema',
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
            'startLine' => 261,
            'endLine' => 261,
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
 * Create a consent request from controller data
 *
 * Uses a DENYLIST: the three positional fields (documentId, entityType,
 * entityText) are consumed directly; server-controlled status/policy
 * fields ({@see SERVER_CONTROLLED_CREATE_FIELDS}) are STRIPPED and a
 * security warning is logged naming the stripped keys; framework routing
 * keys are stripped silently; everything else is forwarded to
 * ConsentService as the `$extra` payload unchanged. This prevents callers
 * from forcing internal status fields at creation time (finding #290b)
 * while still allowing legitimate extra fields such as `consentScope`
 * (PR #147 fifth-pass).
 *
 * @param array<string, mixed> $data The request data
 * @param string $register The register ID
 * @param string $schema The schema ID
 *
 * @return array<string, mixed> The created or idempotently-updated consent
 *                              record, including the `wasUpdated` discriminator
 *
 * @throws \\OCA\\Filinq\\Exception\\PolicyRejectedException Propagated unwrapped when a
 *                                                       publication-prohibition rule matches; the
 *                                                       controller maps it to HTTP 403.
 * @throws Exception If creation fails
 *
 * @spec openspec/specs/consent-management/spec.md
 */',
        'startLine' => 261,
        'endLine' => 304,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'aliasName' => NULL,
      ),
      'getConsentsByDocument' => 
      array (
        'name' => 'getConsentsByDocument',
        'parameters' => 
        array (
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
            'startLine' => 321,
            'endLine' => 321,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'register' => 
          array (
            'name' => 'register',
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
            'startLine' => 322,
            'endLine' => 322,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'schema' => 
          array (
            'name' => 'schema',
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
            'startLine' => 323,
            'endLine' => 323,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'ownerUid' => 
          array (
            'name' => 'ownerUid',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 324,
                'endLine' => 324,
                'startTokenPos' => 1029,
                'startFilePos' => 11004,
                'endTokenPos' => 1029,
                'endFilePos' => 11007,
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
            'startLine' => 324,
            'endLine' => 324,
            'startColumn' => 3,
            'endColumn' => 26,
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
 * Get consent records for a specific document, optionally scoped to one owner
 *
 * @param string $documentId The document UUID
 * @param string $register The register ID
 * @param string $schema The schema ID
 * @param string|null $ownerUid UID to scope results to, or null for all
 *
 * @return array<int, array<string, mixed>> List of consent records
 *
 * @throws Exception If query fails
 *
 * @spec openspec/specs/consent-management/spec.md
 */',
        'startLine' => 320,
        'endLine' => 327,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'aliasName' => NULL,
      ),
      'updateConsentStatus' => 
      array (
        'name' => 'updateConsentStatus',
        'parameters' => 
        array (
          'consentId' => 
          array (
            'name' => 'consentId',
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
            'startLine' => 350,
            'endLine' => 350,
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'register' => 
          array (
            'name' => 'register',
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
            'startLine' => 351,
            'endLine' => 351,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'schema' => 
          array (
            'name' => 'schema',
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
            'startLine' => 352,
            'endLine' => 352,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
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
            'startLine' => 353,
            'endLine' => 353,
            'startColumn' => 3,
            'endColumn' => 13,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'user' => 
          array (
            'name' => 'user',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 354,
                'endLine' => 354,
                'startTokenPos' => 1099,
                'startFilePos' => 12129,
                'endTokenPos' => 1099,
                'endFilePos' => 12132,
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
                      'name' => 'OCP\\IUser',
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
            'startLine' => 354,
            'endLine' => 354,
            'startColumn' => 3,
            'endColumn' => 26,
            'parameterIndex' => 4,
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
 * Update consent status for a consent record, enforcing policy-transition rules
 *
 * Delegates to ConsentService::validateAndUpdateConsent() which checks for
 * policy-matched transition blocks and the override-up flow before saving.
 *
 * @param string $consentId The consent object UUID
 * @param string $register The register ID
 * @param string $schema The schema ID
 * @param array<string, mixed> $data The data to update
 * @param \\OCP\\IUser|null $user The acting user, or null for system context
 *
 * @return array<string, mixed> The updated consent record
 *
 * @throws Exception If update fails
 *
 * @spec openspec/specs/consent-management/spec.md
 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-7
 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-8
 */',
        'startLine' => 349,
        'endLine' => 357,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentCrudService',
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