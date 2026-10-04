<?php declare(strict_types = 1);

// osfsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/SigningService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\SigningService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-82f91ae4a89fb67421ba69f372de770e0d045f637745141e89623d7f7cb23422-8.3-6.70.0.6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\SigningService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/SigningService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\SigningService',
    'shortName' => 'SigningService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service for managing signing request lifecycle
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SignedArtifactProducer and SigningRequestValidator were extracted earlier;
 * SigningActorResolver (who is acting, and may they act as this signer) and
 * SigningConclusionEmitter (the cross-app conclusion contract) followed, so
 * the class now meets the length, complexity and parameter-list thresholds on
 * its own. Coupling went back over the line with the signing folder\'s mandate
 * service, which is a collaborator rather than work this class does itself.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) The thirteenth collaborator is
 * SigningMandateService, added so a direct signing attempt is refused by the
 * same rule that leaves the document out of the folder. Inlining that rule here
 * would be the second copy of it. The fourteenth is GuardianConsentGuard, for
 * the same reason: the guardian rule is applied here and kept there.
 *
 * @spec openspec/specs/document-signing/spec.md
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 56,
    'endLine' => 936,
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
      'STATUS_TRANSITIONS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'name' => 'STATUS_TRANSITIONS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'DRAFT\' => [\'PENDING\', \'CANCELLED\'], \'PENDING\' => [\'IN_PROGRESS\', \'CANCELLED\', \'EXPIRED\'], \'IN_PROGRESS\' => [\'COMPLETED\', \'DECLINED\', \'CANCELLED\', \'EXPIRED\'], \'COMPLETED\' => [], \'DECLINED\' => [], \'EXPIRED\' => [], \'CANCELLED\' => []]',
          'attributes' => 
          array (
            'startLine' => 63,
            'endLine' => 71,
            'startTokenPos' => 65,
            'startFilePos' => 2184,
            'endTokenPos' => 144,
            'endFilePos' => 2433,
          ),
        ),
        'docComment' => '/**
 * Valid status transitions for signing requests
 *
 * @var array<string, array<string>>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 63,
        'endLine' => 71,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
      'PROVENANCE_FIELDS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'name' => 'PROVENANCE_FIELDS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'sourceApp\', \'subjectRegister\', \'subjectSchema\', \'subjectId\', \'subjectLabel\', \'externalReference\', \'correlationId\']',
          'attributes' => 
          array (
            'startLine' => 118,
            'endLine' => 126,
            'startTokenPos' => 250,
            'startFilePos' => 4929,
            'endTokenPos' => 273,
            'endFilePos' => 5062,
          ),
        ),
        'docComment' => '/**
 * Provenance keys threaded from a cross-app DocumentSigningRequestedEvent
 * onto the persisted signing-request object so the terminal
 * SigningConcludedEvent can correlate back to the originating consumer.
 *
 * @var list<string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 118,
        'endLine' => 126,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'settingsService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningService',
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
        'startLine' => 99,
        'endLine' => 99,
        'startColumn' => 3,
        'endColumn' => 51,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'auditService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'name' => 'auditService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\SigningAuditService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 100,
        'endLine' => 100,
        'startColumn' => 3,
        'endColumn' => 52,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'artifactProducer' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'name' => 'artifactProducer',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\SignedArtifactProducer',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 101,
        'endLine' => 101,
        'startColumn' => 3,
        'endColumn' => 59,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'validator' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'name' => 'validator',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\SigningRequestValidator',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 102,
        'endLine' => 102,
        'startColumn' => 3,
        'endColumn' => 53,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'actorResolver' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'name' => 'actorResolver',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\SigningActorResolver',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 103,
        'endLine' => 103,
        'startColumn' => 3,
        'endColumn' => 54,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'emitter' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'name' => 'emitter',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\SigningConclusionEmitter',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 104,
        'endLine' => 104,
        'startColumn' => 3,
        'endColumn' => 52,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'consentGuard' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'name' => 'consentGuard',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Signing\\GuardianConsentGuard',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 105,
        'endLine' => 105,
        'startColumn' => 3,
        'endColumn' => 53,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'mandateService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'name' => 'mandateService',
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
                  'name' => 'OCA\\Filinq\\Service\\SigningMandateService',
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
            'startLine' => 106,
            'endLine' => 106,
            'startTokenPos' => 230,
            'startFilePos' => 4611,
            'endTokenPos' => 230,
            'endFilePos' => 4614,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 106,
        'endLine' => 106,
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
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 3,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'auditService' => 
          array (
            'name' => 'auditService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\SigningAuditService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 100,
            'endLine' => 100,
            'startColumn' => 3,
            'endColumn' => 52,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'artifactProducer' => 
          array (
            'name' => 'artifactProducer',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\SignedArtifactProducer',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 101,
            'endLine' => 101,
            'startColumn' => 3,
            'endColumn' => 59,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'validator' => 
          array (
            'name' => 'validator',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\SigningRequestValidator',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 102,
            'endLine' => 102,
            'startColumn' => 3,
            'endColumn' => 53,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'actorResolver' => 
          array (
            'name' => 'actorResolver',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\SigningActorResolver',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 103,
            'endLine' => 103,
            'startColumn' => 3,
            'endColumn' => 54,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'emitter' => 
          array (
            'name' => 'emitter',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\SigningConclusionEmitter',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 3,
            'endColumn' => 52,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
          'consentGuard' => 
          array (
            'name' => 'consentGuard',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Signing\\GuardianConsentGuard',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 105,
            'endLine' => 105,
            'startColumn' => 3,
            'endColumn' => 53,
            'parameterIndex' => 6,
            'isOptional' => false,
          ),
          'mandateService' => 
          array (
            'name' => 'mandateService',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 106,
                'endLine' => 106,
                'startTokenPos' => 230,
                'startFilePos' => 4611,
                'endTokenPos' => 230,
                'endFilePos' => 4614,
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
                      'name' => 'OCA\\Filinq\\Service\\SigningMandateService',
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
            'startLine' => 106,
            'endLine' => 106,
            'startColumn' => 3,
            'endColumn' => 64,
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
 * Constructor
 *
 * @param SettingsService $settingsService Settings service
 * @param SigningAuditService $auditService Audit service
 * @param SignedArtifactProducer $artifactProducer Produces + stores the verifiable signed artifact
 * @param SigningRequestValidator $validator Validates request data + the provider/level pair
 * @param SigningActorResolver $actorResolver Resolves the acting identity + authorises it
 * @param SigningConclusionEmitter $emitter Emits the cross-app SigningConcludedEvent
 * @param GuardianConsentGuard $consentGuard Holds the guardian rule for signers under the
 *                                           age of consent (signer-identity-rails
 *                                           REQ-DDSIR-008 to 010). Required, not a
 *                                           nullable seam: an unwired safety guard
 *                                           must fail construction, not pass silently.
 * @param SigningMandateService|null $mandateService Applies the consuming app\'s per-type
 *                                                   mandate declaration to a direct signing
 *                                                   attempt (signing-folder-across-cases
 *                                                   REQ-SFC-04). An ADDITIVE seam: null
 *                                                   behaves exactly as before, so callers
 *                                                   constructing this service by hand are
 *                                                   unchanged, while the DI container
 *                                                   resolves the real one.
 *
 * @return void
 */',
        'startLine' => 98,
        'endLine' => 109,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'aliasName' => NULL,
      ),
      'createRequest' => 
      array (
        'name' => 'createRequest',
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
            'startLine' => 142,
            'endLine' => 142,
            'startColumn' => 32,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a new signing request
 *
 * @param array<string, mixed> $data The signing request data
 *
 * @return array<string, mixed> The created signing request
 *
 * @throws RuntimeException If creation fails
 * @throws \\InvalidArgumentException With code 400 when a guardian entry is invalid,
 *                                   or a signer under the age of consent has no guardian.
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#3-2
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */',
        'startLine' => 142,
        'endLine' => 233,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'aliasName' => NULL,
      ),
      'persistSigners' => 
      array (
        'name' => 'persistSigners',
        'parameters' => 
        array (
          'requestId' => 
          array (
            'name' => 'requestId',
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
            'startColumn' => 34,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'signers' => 
          array (
            'name' => 'signers',
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
            'startColumn' => 53,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'prepared' => 
          array (
            'name' => 'prepared',
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
            'startColumn' => 69,
            'endColumn' => 83,
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
 * Persist the signer records of a new request, guardians last.
 *
 * A guardian\'s record points at the signer it stands beside by record id,
 * which exists only once that signer is saved. Signers are therefore saved
 * first and guardians after; the returned ids keep the order of the entries.
 *
 * @param string $requestId The id of the saved signing request.
 * @param array<int|string, mixed> $signers The signer entries as the consumer sent them.
 * @param array{fields: array<int|string, array<string, mixed>>, links: array<int|string, int|string>} $prepared The guardian
 *     fields per entry and the guardian-to-signer links, from GuardianConsentGuard::prepareSigners().
 *
 * @return list<string> The signer record ids, in entry order.
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */',
        'startLine' => 251,
        'endLine' => 286,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'aliasName' => NULL,
      ),
      'getRequest' => 
      array (
        'name' => 'getRequest',
        'parameters' => 
        array (
          'requestId' => 
          array (
            'name' => 'requestId',
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
            'startLine' => 324,
            'endLine' => 324,
            'startColumn' => 29,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'callerUserId' => 
          array (
            'name' => 'callerUserId',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 324,
                'endLine' => 324,
                'startTokenPos' => 1328,
                'startFilePos' => 13994,
                'endTokenPos' => 1328,
                'endFilePos' => 13995,
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
            'startLine' => 324,
            'endLine' => 324,
            'startColumn' => 48,
            'endColumn' => 72,
            'parameterIndex' => 1,
            'isOptional' => true,
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
 * Get a signing request by ID
 *
 * Access control: a SCOPED caller (non-empty $callerUserId) must be the
 * initiator or a listed signer. Pass callerUserId=\'\' to read UNSCOPED —
 * that is the single, explicit bypass, used by an NC admin caller and by
 * internal methods that have already verified access.
 *
 * There is deliberately no separate `isAdmin` flag: the previous guard was
 * `$callerUserId !== \'\' && $isAdmin === false`, so an admin caller and an
 * unscoped caller already took the identical path. Collapsing the two
 * spellings into one means there is exactly ONE way to bypass scoping, and
 * it is visible at the call site.
 *
 * @param string $requestId The signing request ID
 * @param string $callerUserId UID to scope the read to (\'\' = unscoped)
 *
 * @return array<string, mixed>|null The signing request, or null when a
 *                                   scoped caller (callerUserId set,
 *                                   non-admin) is neither initiator nor
 *                                   signer (access denied collapses to
 *                                   null). A genuinely not-found request
 *                                   throws RuntimeException(\'Signing request
 *                                   not found\') so the controller can map it
 *                                   to a 404.
 *
 * @throws RuntimeException When the underlying record does not exist.
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#3-1
 *
 * Wilco #6 blocker fix (filinq#100, 2026-06-06): the access-denied path
 * still returns null (indistinguishable from the controller\'s not-found
 * 404 for a scoped caller), so an unrelated user cannot probe request-ID
 * existence. Not-found now throws a fixed, ID-free message (\'Signing
 * request not found\') — no UUID is echoed, so nothing is leaked.
 */',
        'startLine' => 324,
        'endLine' => 357,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'aliasName' => NULL,
      ),
      'listRequests' => 
      array (
        'name' => 'listRequests',
        'parameters' => 
        array (
          'callerUserId' => 
          array (
            'name' => 'callerUserId',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 378,
                'endLine' => 378,
                'startTokenPos' => 1574,
                'startFilePos' => 16395,
                'endTokenPos' => 1574,
                'endFilePos' => 16396,
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
            'startLine' => 378,
            'endLine' => 378,
            'startColumn' => 31,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * List signing requests scoped to the calling user
 *
 * A SCOPED caller (non-empty $callerUserId) sees only requests where they
 * are the initiator or a listed signer (WF2 fix: previously returned all
 * requests regardless of ownership — full cross-tenant data disclosure).
 * Pass callerUserId=\'\' to list UNSCOPED — that is the single, explicit
 * bypass, used by an NC admin caller.
 *
 * As in getRequest(), there is deliberately no separate `isAdmin` flag:
 * the previous guard was `$isAdmin === false && $callerUserId !== \'\'`, so
 * an admin caller and an unscoped caller already took the identical path.
 *
 * @param string $callerUserId UID to scope the listing to (\'\' = unscoped)
 *
 * @return array<int, array<string, mixed>> List of signing requests
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#3-1
 */',
        'startLine' => 378,
        'endLine' => 415,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'aliasName' => NULL,
      ),
      'sign' => 
      array (
        'name' => 'sign',
        'parameters' => 
        array (
          'requestId' => 
          array (
            'name' => 'requestId',
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
            'startLine' => 447,
            'endLine' => 447,
            'startColumn' => 23,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'signerId' => 
          array (
            'name' => 'signerId',
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
            'startLine' => 447,
            'endLine' => 447,
            'startColumn' => 42,
            'endColumn' => 57,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'verifiedActor' => 
          array (
            'name' => 'verifiedActor',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 447,
                'endLine' => 447,
                'startTokenPos' => 1865,
                'startFilePos' => 19729,
                'endTokenPos' => 1865,
                'endFilePos' => 19732,
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 447,
            'endLine' => 447,
            'startColumn' => 60,
            'endColumn' => 87,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'signatureData' => 
          array (
            'name' => 'signatureData',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 447,
                'endLine' => 447,
                'startTokenPos' => 1875,
                'startFilePos' => 19759,
                'endTokenPos' => 1875,
                'endFilePos' => 19762,
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 447,
            'endLine' => 447,
            'startColumn' => 90,
            'endColumn' => 117,
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
 * Sign a document within a signing request
 *
 * @param string $requestId The signing request ID
 * @param string $signerId The signer record ID
 * @param array<string, mixed>|null $verifiedActor The already-resolved, verified
 *                                                 external actor (portal-signing-actions
 *                                                 REQ-DDPSA-005): `email` (invited signer
 *                                                 identity), and optionally `subjectRef`,
 *                                                 `identityRef`, `trust`, `jti` from the
 *                                                 verified portal assertion. When null
 *                                                 (default) the actor is the Nextcloud
 *                                                 session user, exactly as before — this
 *                                                 parameter is an ADDITIVE seam, not a
 *                                                 behaviour change for existing callers.
 * @param array<string, mixed>|null $signatureData Optional evidence to record on the
 *                                                 signer record\'s `signatureData` field
 *                                                 (portal-signing-surface REQ-DDPSS-002:
 *                                                 consent confirmation + optional drawn
 *                                                 signature). Never trusted for identity.
 *
 * @return array<string, mixed> The updated signer record
 *
 * @throws RuntimeException If signing fails
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#3-3
 * @spec openspec/specs/portal-signing-actions/spec.md
 * @spec openspec/specs/portal-signing-surface/spec.md
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */',
        'startLine' => 447,
        'endLine' => 535,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'aliasName' => NULL,
      ),
      'decline' => 
      array (
        'name' => 'decline',
        'parameters' => 
        array (
          'requestId' => 
          array (
            'name' => 'requestId',
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
            'startLine' => 557,
            'endLine' => 557,
            'startColumn' => 26,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'signerId' => 
          array (
            'name' => 'signerId',
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
            'startLine' => 557,
            'endLine' => 557,
            'startColumn' => 45,
            'endColumn' => 60,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'reason' => 
          array (
            'name' => 'reason',
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
            'startLine' => 557,
            'endLine' => 557,
            'startColumn' => 63,
            'endColumn' => 76,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'verifiedActor' => 
          array (
            'name' => 'verifiedActor',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 557,
                'endLine' => 557,
                'startTokenPos' => 2527,
                'startFilePos' => 24361,
                'endTokenPos' => 2527,
                'endFilePos' => 24364,
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 557,
            'endLine' => 557,
            'startColumn' => 79,
            'endColumn' => 106,
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
 * Decline a signing request
 *
 * @param string $requestId The signing request ID
 * @param string $signerId The signer record ID
 * @param string $reason The decline reason
 * @param array<string, mixed>|null $verifiedActor The already-resolved, verified
 *                                                 external actor (see `sign()`); null
 *                                                 (default) behaves exactly as before.
 *
 * @return array<string, mixed> The updated signer record
 *
 * @throws RuntimeException If the request is not in a state that can be
 *                          declined, or the actor is not authorised.
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#3-2
 * @spec openspec/specs/document-signing/spec.md
 * @spec openspec/specs/portal-signing-actions/spec.md
 * @spec openspec/specs/portal-signing-surface/spec.md
 */',
        'startLine' => 557,
        'endLine' => 620,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'aliasName' => NULL,
      ),
      'cancelRequest' => 
      array (
        'name' => 'cancelRequest',
        'parameters' => 
        array (
          'requestId' => 
          array (
            'name' => 'requestId',
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
            'startLine' => 638,
            'endLine' => 638,
            'startColumn' => 32,
            'endColumn' => 48,
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
 * Cancel a signing request
 *
 * @param string $requestId The signing request ID
 *
 * @return array<string, mixed>|null The cancelled request, or null
 *                                   when the request is not found (or
 *                                   not accessible to a non-admin
 *                                   caller). Wilco #6 fix (filinq#100,
 *                                   2026-06-06): callers must collapse
 *                                   null to a single 404 — never split
 *                                   into 404-vs-403, which would be an
 *                                   existence-probing oracle.
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#3-2
 */',
        'startLine' => 638,
        'endLine' => 675,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'aliasName' => NULL,
      ),
      'bulkSign' => 
      array (
        'name' => 'bulkSign',
        'parameters' => 
        array (
          'requestIds' => 
          array (
            'name' => 'requestIds',
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
            'startLine' => 686,
            'endLine' => 686,
            'startColumn' => 27,
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
 * Bulk sign multiple signing requests
 *
 * @param array<string> $requestIds Array of request IDs to sign
 *
 * @return array<string, array<string, mixed>> Results keyed by request ID
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#3-4
 */',
        'startLine' => 686,
        'endLine' => 728,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'aliasName' => NULL,
      ),
      'isValidTransition' => 
      array (
        'name' => 'isValidTransition',
        'parameters' => 
        array (
          'currentStatus' => 
          array (
            'name' => 'currentStatus',
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
            'startLine' => 740,
            'endLine' => 740,
            'startColumn' => 36,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'newStatus' => 
          array (
            'name' => 'newStatus',
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
            'startLine' => 740,
            'endLine' => 740,
            'startColumn' => 59,
            'endColumn' => 75,
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
 * Validate a status transition
 *
 * @param string $currentStatus The current status
 * @param string $newStatus The proposed new status
 *
 * @return bool True if transition is valid
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#3-2
 */',
        'startLine' => 740,
        'endLine' => 743,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'aliasName' => NULL,
      ),
      'updateRequestStatus' => 
      array (
        'name' => 'updateRequestStatus',
        'parameters' => 
        array (
          'requestId' => 
          array (
            'name' => 'requestId',
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
            'startLine' => 758,
            'endLine' => 758,
            'startColumn' => 39,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'request' => 
          array (
            'name' => 'request',
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
            'startLine' => 758,
            'endLine' => 758,
            'startColumn' => 58,
            'endColumn' => 71,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'verifiedActor' => 
          array (
            'name' => 'verifiedActor',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 758,
                'endLine' => 758,
                'startTokenPos' => 3698,
                'startFilePos' => 32195,
                'endTokenPos' => 3698,
                'endFilePos' => 32198,
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 758,
            'endLine' => 758,
            'startColumn' => 74,
            'endColumn' => 101,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Update the signing request status based on signer progress
 *
 * @param string $requestId The signing request ID
 * @param array<string, mixed> $request The current request data
 * @param array<string, mixed>|null $verifiedActor The verified external actor completing
 *                                                 this act, when portal-originated (see
 *                                                 `sign()`); threaded through to the
 *                                                 produced artifact\'s evidence binding
 *                                                 (portal-signing-surface REQ-DDPSS-004).
 *
 * @return void
 */',
        'startLine' => 758,
        'endLine' => 845,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'aliasName' => NULL,
      ),
      'emitExpiredConclusion' => 
      array (
        'name' => 'emitExpiredConclusion',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
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
            'startLine' => 861,
            'endLine' => 861,
            'startColumn' => 40,
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
 * Emit a terminal SigningConcludedEvent for an expired request.
 *
 * Public entry point for the SigningExpirationJob, which marks requests
 * EXPIRED outside this service. Delegates to the shared fail-soft helper so
 * the cross-app contract has a single emission source. Only fires for a
 * delegated (provenance-carrying) request; internal requests emit nothing.
 *
 * @param array<string, mixed> $request The persisted (EXPIRED) signing-request array
 *
 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
 *
 * @return void
 */',
        'startLine' => 861,
        'endLine' => 864,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'aliasName' => NULL,
      ),
      'toArray' => 
      array (
        'name' => 'toArray',
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
            'startLine' => 877,
            'endLine' => 877,
            'startColumn' => 27,
            'endColumn' => 39,
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
 * Normalise an ObjectService result to an array
 *
 * OpenRegister\'s ObjectService::saveObject()/find() return an ObjectEntity
 * instance, not a plain array. Callers that need array access must serialize
 * it first. This helper mirrors the pattern TemplateService already uses.
 *
 * @param mixed $object The ObjectEntity (or array) to normalise
 *
 * @return array<string, mixed> The serialized object
 */',
        'startLine' => 877,
        'endLine' => 883,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'aliasName' => NULL,
      ),
      'requireSigningRequestBinding' => 
      array (
        'name' => 'requireSigningRequestBinding',
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
 * Resolve the signingRequest binding, failing closed when unconfigured.
 *
 * Every call site used to read these two keys inline with an empty-string
 * default and pass the result straight into saveObject()/find(). On an
 * instance where an administrator has not bound them, that wrote signing
 * requests — the audit trail behind an eIDAS-level signature — into
 * register \'\' and schema \'\', silently. SettingsService owns the read;
 * this turns "unset" into the same RegisterNotConfiguredException
 * SigningController already handles.
 *
 * @return array{register: string, schema: string} The resolved binding.
 *
 * @throws RegisterNotConfiguredException When either half is unset.
 *
 * @spec openspec/specs/document-signing/spec.md
 */',
        'startLine' => 902,
        'endLine' => 911,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'aliasName' => NULL,
      ),
      'requireSignerRecordBinding' => 
      array (
        'name' => 'requireSignerRecordBinding',
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
 * Resolve the signerRecord binding, failing closed when unconfigured.
 *
 * A signer record carries the identity a signature is attributed to, so an
 * unconfigured binding loses exactly the evidence a signature exists to
 * provide.
 *
 * @return array{register: string, schema: string} The resolved binding.
 *
 * @throws RegisterNotConfiguredException When either half is unset.
 *
 * @spec openspec/specs/document-signing/spec.md
 */',
        'startLine' => 926,
        'endLine' => 935,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningService',
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