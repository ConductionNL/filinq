<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Event/SigningConcludedEvent.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Event\SigningConcludedEvent
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-157571d76b3dae1a8690c195ae12f92cdad91e88e154c627286d047d167790d6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Event/SigningConcludedEvent.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Event',
    'name' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
    'shortName' => 'SigningConcludedEvent',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Cross-app conclusion event: Filinq reports a concluded delegated signing request.
 *
 * Fully immutable — Filinq constructs it from the persisted signing-request
 * array and the normalised terminal status; consumers only read. The
 * array-to-event mapping (including the eIDAS assurance-level resolution)
 * lives in the injectable {@see SigningConcludedEventFactory}, not in a static
 * named constructor on this value object.
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
    'endLine' => 215,
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
        'declaringClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'name' => 'signingRequestId',
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
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 72,
        'endLine' => 72,
        'startColumn' => 3,
        'endColumn' => 43,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'status' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'name' => 'status',
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
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 3,
        'endColumn' => 33,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'signedDocumentRef' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'name' => 'signedDocumentRef',
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
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 74,
        'endLine' => 74,
        'startColumn' => 3,
        'endColumn' => 45,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'signers' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
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
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 75,
        'endLine' => 75,
        'startColumn' => 3,
        'endColumn' => 33,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'signedAt' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'name' => 'signedAt',
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
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 76,
        'endLine' => 76,
        'startColumn' => 3,
        'endColumn' => 36,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'provenance' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
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
        'startLine' => 77,
        'endLine' => 77,
        'startColumn' => 3,
        'endColumn' => 48,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'assuranceLevel' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'name' => 'assuranceLevel',
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
          'code' => '\'low\'',
          'attributes' => 
          array (
            'startLine' => 78,
            'endLine' => 78,
            'startTokenPos' => 109,
            'startFilePos' => 3330,
            'endTokenPos' => 109,
            'endFilePos' => 3334,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 78,
        'endLine' => 78,
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 72,
            'endLine' => 72,
            'startColumn' => 3,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'status' => 
          array (
            'name' => 'status',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 73,
            'endLine' => 73,
            'startColumn' => 3,
            'endColumn' => 33,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'signedDocumentRef' => 
          array (
            'name' => 'signedDocumentRef',
            'default' => NULL,
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 74,
            'endLine' => 74,
            'startColumn' => 3,
            'endColumn' => 45,
            'parameterIndex' => 2,
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 75,
            'endLine' => 75,
            'startColumn' => 3,
            'endColumn' => 33,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'signedAt' => 
          array (
            'name' => 'signedAt',
            'default' => NULL,
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 76,
            'endLine' => 76,
            'startColumn' => 3,
            'endColumn' => 36,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
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
            'startLine' => 77,
            'endLine' => 77,
            'startColumn' => 3,
            'endColumn' => 48,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
          'assuranceLevel' => 
          array (
            'name' => 'assuranceLevel',
            'default' => 
            array (
              'code' => '\'low\'',
              'attributes' => 
              array (
                'startLine' => 78,
                'endLine' => 78,
                'startTokenPos' => 109,
                'startFilePos' => 3330,
                'endTokenPos' => 109,
                'endFilePos' => 3334,
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
            'startLine' => 78,
            'endLine' => 78,
            'startColumn' => 3,
            'endColumn' => 49,
            'parameterIndex' => 6,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Construct the conclusion event.
 *
 * @param string $signingRequestId The concluded signing-request id
 * @param string $status Normalised outcome (signed|declined|expired|cancelled)
 * @param string|null $signedDocumentRef Reference to the signed document, when signed
 * @param array<int, mixed> $signers Resolved signers list
 * @param string|null $signedAt When the request concluded
 * @param SigningProvenance $provenance Source app, subject reference and consumer references
 * @param string $assuranceLevel Resolved eIDAS assurance (low|substantial|high,
 *                               signing-trust-rebuild REQ-DDSTR-010) — the
 *                               delegating consumer (e.g. decidesk\'s
 *                               `EIDASSignatureService::resolveSignatureStage()`)
 *                               maps this onto its own stage vocabulary. `low`
 *                               for the native SES provider today; broker-resolved
 *                               assurance is populated into the SAME field by the
 *                               `signer-identity-rails` change.
 *
 * @return void
 */',
        'startLine' => 71,
        'endLine' => 82,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
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
 * Get the concluded signing-request id.
 *
 * @return string The signing-request id.
 *
 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
 */',
        'startLine' => 91,
        'endLine' => 93,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'aliasName' => NULL,
      ),
      'getStatus' => 
      array (
        'name' => 'getStatus',
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
 * Get the normalised outcome status (signed|declined|expired|cancelled).
 *
 * @return string The status.
 *
 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
 */',
        'startLine' => 102,
        'endLine' => 104,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'aliasName' => NULL,
      ),
      'getSignedDocumentRef' => 
      array (
        'name' => 'getSignedDocumentRef',
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
 * Get the reference to the signed document, when signed.
 *
 * @return string|null The signed document reference, or null.
 *
 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
 */',
        'startLine' => 113,
        'endLine' => 115,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
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
 * Get the resolved signers list.
 *
 * @return array<int, mixed> The signers.
 *
 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
 */',
        'startLine' => 124,
        'endLine' => 126,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'aliasName' => NULL,
      ),
      'getSignedAt' => 
      array (
        'name' => 'getSignedAt',
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
 * Get when the request concluded.
 *
 * @return string|null The signed-at timestamp, or null.
 *
 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
 */',
        'startLine' => 135,
        'endLine' => 137,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
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
 *
 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
 */',
        'startLine' => 146,
        'endLine' => 148,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
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
 * Get the OpenRegister register of the originating object.
 *
 * @return string|null The subject register, or null.
 *
 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
 */',
        'startLine' => 157,
        'endLine' => 159,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
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
 * Get the OpenRegister schema of the originating object.
 *
 * @return string|null The subject schema, or null.
 *
 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
 */',
        'startLine' => 168,
        'endLine' => 170,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
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
 * Get the OpenRegister id of the originating object.
 *
 * @return string|null The subject id, or null.
 *
 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
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
        'declaringClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
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
 *
 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
 */',
        'startLine' => 190,
        'endLine' => 192,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
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
 * Get the correlation id from the request event.
 *
 * @return string The correlation id.
 *
 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
 */',
        'startLine' => 201,
        'endLine' => 203,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'aliasName' => NULL,
      ),
      'getAssuranceLevel' => 
      array (
        'name' => 'getAssuranceLevel',
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
 * Get the resolved eIDAS assurance level (signing-trust-rebuild REQ-DDSTR-010).
 *
 * @return string One of low|substantial|high.
 *
 * @spec openspec/specs/document-signing/spec.md
 */',
        'startLine' => 212,
        'endLine' => 214,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
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