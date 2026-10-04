<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Event/DocumentSigningRequestedEvent.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Event\DocumentSigningRequestedEvent
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-db76b094a254c5d93e426ed02dbaa09eb03060ad9e766a93d58f8ff681e39dfd',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Event/DocumentSigningRequestedEvent.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Event',
    'name' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
    'shortName' => 'DocumentSigningRequestedEvent',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Cross-app request event: a consumer app asks Filinq to raise a signing request.
 *
 * All request fields are immutable (constructor-injected getters). Nextcloud
 * typed dispatch is synchronous, so the single result slot (signingRequestId +
 * handled) is written by Filinq\'s listener and read by the producer right
 * after dispatch — the standard NC request/response-over-the-bus pattern.
 *
 * @category Event
 * @package  OCA\\Filinq\\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 50,
    'endLine' => 260,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'OCP\\EventDispatcher\\Event',
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
      'signingRequestId' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'name' => 'signingRequestId',
        'modifiers' => 4,
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
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 57,
            'endLine' => 57,
            'startTokenPos' => 45,
            'startFilePos' => 2012,
            'endTokenPos' => 45,
            'endFilePos' => 2015,
          ),
        ),
        'docComment' => '/**
 * The id of the signing request Filinq created (result slot).
 *
 * @var string|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 2,
        'endColumn' => 42,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'handled' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'name' => 'handled',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 64,
            'endLine' => 64,
            'startTokenPos' => 58,
            'startFilePos' => 2141,
            'endTokenPos' => 58,
            'endFilePos' => 2145,
          ),
        ),
        'docComment' => '/**
 * Whether Filinq\'s listener handled this request (result slot).
 *
 * @var boolean
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 64,
        'endLine' => 64,
        'startColumn' => 2,
        'endColumn' => 31,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'provenance' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'name' => 'provenance',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Event\\SigningProvenance',
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
        'endColumn' => 48,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'subjectLabel' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'name' => 'subjectLabel',
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
        'default' => 
        array (
          'code' => '\'\'',
          'attributes' => 
          array (
            'startLine' => 101,
            'endLine' => 101,
            'startTokenPos' => 89,
            'startFilePos' => 4208,
            'endTokenPos' => 89,
            'endFilePos' => 4209,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 101,
        'endLine' => 101,
        'startColumn' => 3,
        'endColumn' => 44,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'documentReference' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'name' => 'documentReference',
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
        'default' => 
        array (
          'code' => '\'\'',
          'attributes' => 
          array (
            'startLine' => 102,
            'endLine' => 102,
            'startTokenPos' => 102,
            'startFilePos' => 4259,
            'endTokenPos' => 102,
            'endFilePos' => 4260,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 102,
        'endLine' => 102,
        'startColumn' => 3,
        'endColumn' => 49,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'signers' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'name' => 'signers',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 103,
            'endLine' => 103,
            'startTokenPos' => 115,
            'startFilePos' => 4299,
            'endTokenPos' => 116,
            'endFilePos' => 4300,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 103,
        'endLine' => 103,
        'startColumn' => 3,
        'endColumn' => 38,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'signatureLevel' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'name' => 'signatureLevel',
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
        'default' => 
        array (
          'code' => '\'SES\'',
          'attributes' => 
          array (
            'startLine' => 104,
            'endLine' => 104,
            'startTokenPos' => 129,
            'startFilePos' => 4347,
            'endTokenPos' => 129,
            'endFilePos' => 4351,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 104,
        'endLine' => 104,
        'startColumn' => 3,
        'endColumn' => 49,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'signingMode' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'name' => 'signingMode',
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
        'default' => 
        array (
          'code' => '\'sequential\'',
          'attributes' => 
          array (
            'startLine' => 105,
            'endLine' => 105,
            'startTokenPos' => 142,
            'startFilePos' => 4395,
            'endTokenPos' => 142,
            'endFilePos' => 4406,
          ),
        ),
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
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'provenance' => 
          array (
            'name' => 'provenance',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Event\\SigningProvenance',
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
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'subjectLabel' => 
          array (
            'name' => 'subjectLabel',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 101,
                'endLine' => 101,
                'startTokenPos' => 89,
                'startFilePos' => 4208,
                'endTokenPos' => 89,
                'endFilePos' => 4209,
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 101,
            'endLine' => 101,
            'startColumn' => 3,
            'endColumn' => 44,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'documentReference' => 
          array (
            'name' => 'documentReference',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 102,
                'endLine' => 102,
                'startTokenPos' => 102,
                'startFilePos' => 4259,
                'endTokenPos' => 102,
                'endFilePos' => 4260,
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 102,
            'endLine' => 102,
            'startColumn' => 3,
            'endColumn' => 49,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'signers' => 
          array (
            'name' => 'signers',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 103,
                'endLine' => 103,
                'startTokenPos' => 115,
                'startFilePos' => 4299,
                'endTokenPos' => 116,
                'endFilePos' => 4300,
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 103,
            'endLine' => 103,
            'startColumn' => 3,
            'endColumn' => 38,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'signatureLevel' => 
          array (
            'name' => 'signatureLevel',
            'default' => 
            array (
              'code' => '\'SES\'',
              'attributes' => 
              array (
                'startLine' => 104,
                'endLine' => 104,
                'startTokenPos' => 129,
                'startFilePos' => 4347,
                'endTokenPos' => 129,
                'endFilePos' => 4351,
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 3,
            'endColumn' => 49,
            'parameterIndex' => 4,
            'isOptional' => true,
          ),
          'signingMode' => 
          array (
            'name' => 'signingMode',
            'default' => 
            array (
              'code' => '\'sequential\'',
              'attributes' => 
              array (
                'startLine' => 105,
                'endLine' => 105,
                'startTokenPos' => 142,
                'startFilePos' => 4395,
                'endTokenPos' => 142,
                'endFilePos' => 4406,
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 105,
            'endLine' => 105,
            'startColumn' => 3,
            'endColumn' => 53,
            'parameterIndex' => 5,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Construct the request event.
 *
 * The six provenance fields (sourceApp, subjectRegister, subjectSchema,
 * subjectId, externalReference, correlationId) are grouped into
 * {@see SigningProvenance} — the same value object its sibling
 * {@see SigningConcludedEvent} already takes. The two events describe the
 * two ends of one exchange, so they now carry provenance the same way.
 *
 * The READ surface is unchanged: every flat accessor below still exists and
 * still returns what it did, so listeners and consumers that only read the
 * event need no change. Only construction moved.
 *
 * @param SigningProvenance $provenance Who asked, and about which object.
 * @param string $subjectLabel Human display label for the subject
 * @param string $documentReference NC Files file id / path or document content reference
 * @param array<int, mixed> $signers Ordered signers list (userId/displayName/email/order).
 *                                   A learner-facing consumer (learniq OPP and POK,
 *                                   portaliq toestemmingsformulieren) may add, per
 *                                   entry: `birthDate` (YYYY-MM-DD), `role`
 *                                   (signer|guardian), `guardianFor` (the userId or
 *                                   email of another entry), `guardianAct`
 *                                   (co-sign|consent), `consentStatement` and
 *                                   `guardianRef`. A signer under the guardian consent
 *                                   age then signs only with a guardian beside them
 *                                   (signer-identity-rails REQ-DDSIR-008 to 011).
 * @param string $signatureLevel Signature level (SES|AdES|QES)
 * @param string $signingMode Signing mode (sequential|parallel)
 *
 * @return void
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */',
        'startLine' => 99,
        'endLine' => 109,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'aliasName' => NULL,
      ),
      'getProvenance' => 
      array (
        'name' => 'getProvenance',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Event\\SigningProvenance',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the provenance block.
 *
 * @return SigningProvenance The provenance.
 */',
        'startLine' => 116,
        'endLine' => 118,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'aliasName' => NULL,
      ),
      'getSourceApp' => 
      array (
        'name' => 'getSourceApp',
        'parameters' => 
        array (
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
 * Get the consumer app that requested the signature.
 *
 * @return string The source app id.
 */',
        'startLine' => 125,
        'endLine' => 127,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'aliasName' => NULL,
      ),
      'getSubjectRegister' => 
      array (
        'name' => 'getSubjectRegister',
        'parameters' => 
        array (
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
 * Get the OpenRegister register of the originating object.
 *
 * @return string The subject register.
 */',
        'startLine' => 134,
        'endLine' => 136,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'aliasName' => NULL,
      ),
      'getSubjectSchema' => 
      array (
        'name' => 'getSubjectSchema',
        'parameters' => 
        array (
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
 * Get the OpenRegister schema of the originating object.
 *
 * @return string The subject schema.
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
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'aliasName' => NULL,
      ),
      'getSubjectId' => 
      array (
        'name' => 'getSubjectId',
        'parameters' => 
        array (
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
 * Get the OpenRegister id of the originating object.
 *
 * @return string The subject id.
 */',
        'startLine' => 152,
        'endLine' => 154,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'aliasName' => NULL,
      ),
      'getSubjectLabel' => 
      array (
        'name' => 'getSubjectLabel',
        'parameters' => 
        array (
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
 * Get the human display label for the subject.
 *
 * @return string The subject label.
 */',
        'startLine' => 161,
        'endLine' => 163,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'aliasName' => NULL,
      ),
      'getDocumentReference' => 
      array (
        'name' => 'getDocumentReference',
        'parameters' => 
        array (
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
 * Get the document reference (NC Files ref or content reference).
 *
 * @return string The document reference.
 */',
        'startLine' => 170,
        'endLine' => 172,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'aliasName' => NULL,
      ),
      'getSigners' => 
      array (
        'name' => 'getSigners',
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
 * Get the ordered signers list.
 *
 * @return array<int, mixed> The signers.
 */',
        'startLine' => 179,
        'endLine' => 181,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'aliasName' => NULL,
      ),
      'getSignatureLevel' => 
      array (
        'name' => 'getSignatureLevel',
        'parameters' => 
        array (
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
 * Get the requested signature level (SES|AdES|QES).
 *
 * @return string The signature level.
 */',
        'startLine' => 188,
        'endLine' => 190,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'aliasName' => NULL,
      ),
      'getSigningMode' => 
      array (
        'name' => 'getSigningMode',
        'parameters' => 
        array (
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
 * Get the requested signing mode (sequential|parallel).
 *
 * @return string The signing mode.
 */',
        'startLine' => 197,
        'endLine' => 199,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'aliasName' => NULL,
      ),
      'getExternalReference' => 
      array (
        'name' => 'getExternalReference',
        'parameters' => 
        array (
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
 * Get the consumer\'s own external reference.
 *
 * @return string The external reference.
 */',
        'startLine' => 206,
        'endLine' => 208,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'aliasName' => NULL,
      ),
      'getCorrelationId' => 
      array (
        'name' => 'getCorrelationId',
        'parameters' => 
        array (
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
 * Get the correlation id echoed on the conclusion event.
 *
 * @return string The correlation id.
 */',
        'startLine' => 215,
        'endLine' => 217,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'aliasName' => NULL,
      ),
      'getSigningRequestId' => 
      array (
        'name' => 'getSigningRequestId',
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
 * Get the id of the signing request Filinq created (result slot).
 *
 * @return string|null Null until Filinq\'s listener has handled the event.
 */',
        'startLine' => 224,
        'endLine' => 226,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'aliasName' => NULL,
      ),
      'setSigningRequestId' => 
      array (
        'name' => 'setSigningRequestId',
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
            'startLine' => 235,
            'endLine' => 235,
            'startColumn' => 38,
            'endColumn' => 61,
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
 * Set the resolved signing-request id (written by Filinq\'s listener).
 *
 * @param string $signingRequestId The created signing-request id.
 *
 * @return void
 */',
        'startLine' => 235,
        'endLine' => 238,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'aliasName' => NULL,
      ),
      'isHandled' => 
      array (
        'name' => 'isHandled',
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
 * Whether Filinq\'s listener handled this request.
 *
 * @return bool True when Filinq created a signing request.
 */',
        'startLine' => 245,
        'endLine' => 247,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'aliasName' => NULL,
      ),
      'setHandled' => 
      array (
        'name' => 'setHandled',
        'parameters' => 
        array (
          'handled' => 
          array (
            'name' => 'handled',
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
            'startLine' => 256,
            'endLine' => 256,
            'startColumn' => 29,
            'endColumn' => 41,
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
 * Mark whether Filinq\'s listener handled this request.
 *
 * @param bool $handled True when Filinq created a signing request.
 *
 * @return void
 */',
        'startLine' => 256,
        'endLine' => 259,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
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