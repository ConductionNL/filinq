<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/SigningAuditService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\SigningAuditService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-90f24315bc243f8cb858c7fa62b323ae923745b0633aa833bc6d75d355d40f1f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\SigningAuditService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/SigningAuditService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\SigningAuditService',
    'shortName' => 'SigningAuditService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service for immutable signing audit trail via OR audit-trail-immutable.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/migrate-signing-audit-to-or-audit/tasks.md#D-1
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 43,
    'endLine' => 264,
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
      'VALID_ACTIONS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningAuditService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningAuditService',
        'name' => 'VALID_ACTIONS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'CREATED\', \'START\', \'SIGNED\', \'DECLINED\', \'CANCELLED\', \'EXPIRED\', \'COMPLETED\', \'VIEWED\']',
          'attributes' => 
          array (
            'startLine' => 50,
            'endLine' => 59,
            'startTokenPos' => 70,
            'startFilePos' => 1350,
            'endTokenPos' => 96,
            'endFilePos' => 1458,
          ),
        ),
        'docComment' => '/**
 * Valid audit action types.
 *
 * @var array<string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 50,
        'endLine' => 59,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'auditTrailMapper' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningAuditService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningAuditService',
        'name' => 'auditTrailMapper',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\OpenRegister\\Db\\AuditTrailMapper',
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
        'endColumn' => 53,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningAuditService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningAuditService',
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
        'startLine' => 79,
        'endLine' => 79,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'settingsService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningAuditService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningAuditService',
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
        'startLine' => 80,
        'endLine' => 80,
        'startColumn' => 3,
        'endColumn' => 51,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'config' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningAuditService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningAuditService',
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
        'startLine' => 81,
        'endLine' => 81,
        'startColumn' => 3,
        'endColumn' => 37,
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
          'auditTrailMapper' => 
          array (
            'name' => 'auditTrailMapper',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\OpenRegister\\Db\\AuditTrailMapper',
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
            'endColumn' => 53,
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
            'startLine' => 79,
            'endLine' => 79,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
            'startLine' => 80,
            'endLine' => 80,
            'startColumn' => 3,
            'endColumn' => 51,
            'parameterIndex' => 2,
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
            'startLine' => 81,
            'endLine' => 81,
            'startColumn' => 3,
            'endColumn' => 37,
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
 * Constructor.
 *
 * @param AuditTrailMapper $auditTrailMapper OR audit trail mapper.
 * @param LoggerInterface $logger Logger.
 * @param SettingsService $settingsService Settings service (resolves the real
 *                                         signing-request ObjectEntity via OR\'s
 *                                         ObjectService, signing-trust-rebuild
 *                                         REQ-DDSTR-006).
 * @param IAppConfig $config App config (resolves the signingRequest
 *                           register/schema).
 *
 * @return void
 *
 * @spec openspec/changes/migrate-signing-audit-to-or-audit/tasks.md#D-1.1
 */',
        'startLine' => 77,
        'endLine' => 84,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningAuditService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningAuditService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningAuditService',
        'aliasName' => NULL,
      ),
      'logEvent' => 
      array (
        'name' => 'logEvent',
        'parameters' => 
        array (
          'signingRequestId' => 
          array (
            'name' => 'signingRequestId',
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
            'startLine' => 114,
            'endLine' => 114,
            'startColumn' => 3,
            'endColumn' => 26,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'action' => 
          array (
            'name' => 'action',
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
            'startLine' => 115,
            'endLine' => 115,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'actorUserId' => 
          array (
            'name' => 'actorUserId',
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
            'startLine' => 116,
            'endLine' => 116,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'actorDisplayName' => 
          array (
            'name' => 'actorDisplayName',
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
            'startLine' => 117,
            'endLine' => 117,
            'startColumn' => 3,
            'endColumn' => 26,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'ipAddress' => 
          array (
            'name' => 'ipAddress',
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
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'signatureLevel' => 
          array (
            'name' => 'signatureLevel',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 119,
                'endLine' => 119,
                'startTokenPos' => 191,
                'startFilePos' => 3864,
                'endTokenPos' => 191,
                'endFilePos' => 3865,
              ),
            ),
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
            'startColumn' => 3,
            'endColumn' => 29,
            'parameterIndex' => 5,
            'isOptional' => true,
          ),
          'provider' => 
          array (
            'name' => 'provider',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 120,
                'endLine' => 120,
                'startTokenPos' => 200,
                'startFilePos' => 3889,
                'endTokenPos' => 200,
                'endFilePos' => 3890,
              ),
            ),
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
            'startLine' => 120,
            'endLine' => 120,
            'startColumn' => 3,
            'endColumn' => 23,
            'parameterIndex' => 6,
            'isOptional' => true,
          ),
          'metadata' => 
          array (
            'name' => 'metadata',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 121,
                'endLine' => 121,
                'startTokenPos' => 209,
                'startFilePos' => 3913,
                'endTokenPos' => 210,
                'endFilePos' => 3914,
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
            'startLine' => 121,
            'endLine' => 121,
            'startColumn' => 3,
            'endColumn' => 22,
            'parameterIndex' => 7,
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
 * Log a signing audit event via OR\'s native audit trail.
 *
 * Binds the entry to the REAL signing-request object (register `signing`,
 * schema `signingRequest`) so its register/schema/object-id linkage is
 * real and the hash chain anchors to an actual row — closing the #289
 * residual where every entry was created from a uuid-only `ObjectEntity`
 * stub (signing-trust-rebuild REQ-DDSTR-006). Fail-soft: when the request
 * no longer resolves (deleted mid-flight) the entry is still written with
 * the uuid-only fallback and a warning is logged — an unlinked audit entry
 * is acceptable, a dropped one is not.
 *
 * @param string $signingRequestId The signing request UUID.
 * @param string $action The action type (must be in VALID_ACTIONS).
 * @param string $actorUserId The actor user ID.
 * @param string $actorDisplayName The actor display name.
 * @param string $ipAddress The actor IP address.
 * @param string $signatureLevel The signature level.
 * @param string $provider The signing provider.
 * @param array<string, mixed> $metadata Additional metadata (pass-through).
 *
 * @return array<string, mixed> The created audit entry serialised.
 *
 * @throws RuntimeException If the action is not valid.
 *
 * @spec openspec/specs/signing-audit-via-or/spec.md
 */',
        'startLine' => 113,
        'endLine' => 157,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningAuditService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningAuditService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningAuditService',
        'aliasName' => NULL,
      ),
      'resolveSigningRequestObject' => 
      array (
        'name' => 'resolveSigningRequestObject',
        'parameters' => 
        array (
          'signingRequestId' => 
          array (
            'name' => 'signingRequestId',
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
            'startLine' => 175,
            'endLine' => 175,
            'startColumn' => 47,
            'endColumn' => 70,
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
            'name' => 'OCA\\OpenRegister\\Db\\ObjectEntity',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolve the real signing-request ObjectEntity for audit binding.
 *
 * Uses OR\'s ObjectService so the returned entity carries a real
 * register/schema/object-id triple (`AuditTrailMapper::createAuditTrailEntry()`
 * reads `getId()`/`getRegister()`/`getSchema()`/`getUuid()` off it). Falls
 * back to a uuid-only stub — WITH a logged warning — when the request has
 * vanished mid-flight, so an audit entry is still written (fail-soft: an
 * unlinked entry is acceptable, a dropped one is not).
 *
 * @param string $signingRequestId The signing request UUID.
 *
 * @return ObjectEntity The resolved entity, or a uuid-only fallback stub.
 *
 * @spec openspec/specs/signing-audit-via-or/spec.md
 */',
        'startLine' => 175,
        'endLine' => 204,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningAuditService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningAuditService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningAuditService',
        'aliasName' => NULL,
      ),
      'getAuditTrail' => 
      array (
        'name' => 'getAuditTrail',
        'parameters' => 
        array (
          'signingRequestId' => 
          array (
            'name' => 'signingRequestId',
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
            'startLine' => 223,
            'endLine' => 223,
            'startColumn' => 32,
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
 * Get all audit entries for a signing request from OR\'s audit trail.
 *
 * Queries OR\'s audit trail scoped to the request\'s object identity
 * (`object_uuid` filter, pushed into `AuditTrailMapper::findAll()`)
 * instead of fetching every `docudesk.signing.*` entry fleet-wide and
 * filtering in PHP — closing the #289 residual (signing-trust-rebuild
 * REQ-DDSTR-007). The action-type filter is retained alongside it so a
 * non-signing entry that happened to share an objectUuid (should not
 * occur, but costs nothing to exclude) can never leak in.
 *
 * @param string $signingRequestId The signing request UUID.
 *
 * @return array<int, array<string, mixed>> Audit entries in chronological order.
 *
 * @spec openspec/specs/signing-audit-via-or/spec.md
 */',
        'startLine' => 223,
        'endLine' => 263,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningAuditService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningAuditService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningAuditService',
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