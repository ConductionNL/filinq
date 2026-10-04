<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Signing/SigningProviderInterface.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Signing\SigningProviderInterface
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-9fc27b1d790ef4eba7e6822bedbc404b306047d9dadbf848ea1d038b33ef5065',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Signing\\SigningProviderInterface',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Signing/SigningProviderInterface.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Signing',
    'name' => 'OCA\\Filinq\\Service\\Signing\\SigningProviderInterface',
    'shortName' => 'SigningProviderInterface',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Interface for signing providers
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 36,
    'endLine' => 163,
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
    ),
    'immediateMethods' => 
    array (
      'getIdentifier' => 
      array (
        'name' => 'getIdentifier',
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
 * Get the unique identifier for this provider
 *
 * @return string The provider identifier
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */',
        'startLine' => 44,
        'endLine' => 44,
        'startColumn' => 2,
        'endColumn' => 41,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningProviderInterface',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningProviderInterface',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningProviderInterface',
        'aliasName' => NULL,
      ),
      'initiateSigning' => 
      array (
        'name' => 'initiateSigning',
        'parameters' => 
        array (
          'documentPath' => 
          array (
            'name' => 'documentPath',
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
            'startLine' => 60,
            'endLine' => 60,
            'startColumn' => 3,
            'endColumn' => 22,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'documentName' => 
          array (
            'name' => 'documentName',
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
            'startLine' => 61,
            'endLine' => 61,
            'startColumn' => 3,
            'endColumn' => 22,
            'parameterIndex' => 1,
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
            'startLine' => 62,
            'endLine' => 62,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'level' => 
          array (
            'name' => 'level',
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
            'startLine' => 63,
            'endLine' => 63,
            'startColumn' => 3,
            'endColumn' => 15,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 64,
                'endLine' => 64,
                'startTokenPos' => 84,
                'startFilePos' => 1868,
                'endTokenPos' => 85,
                'endFilePos' => 1869,
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
            'startLine' => 64,
            'endLine' => 64,
            'startColumn' => 3,
            'endColumn' => 21,
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
 * Initiate a signing flow for a document
 *
 * @param string $documentPath The Nextcloud file path
 * @param string $documentName The document display name
 * @param array<string, mixed> $signers Array of signer data
 * @param string $level Signature level (SES, AdES, QES)
 * @param array<string, mixed> $options Additional options
 *
 * @return array<string, mixed> Result with keys: success, externalId, message
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#2-1
 */',
        'startLine' => 59,
        'endLine' => 65,
        'startColumn' => 2,
        'endColumn' => 10,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningProviderInterface',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningProviderInterface',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningProviderInterface',
        'aliasName' => NULL,
      ),
      'checkStatus' => 
      array (
        'name' => 'checkStatus',
        'parameters' => 
        array (
          'externalId' => 
          array (
            'name' => 'externalId',
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
            'startLine' => 84,
            'endLine' => 84,
            'startColumn' => 30,
            'endColumn' => 47,
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
 * Check the status of an ongoing signing flow
 *
 * Orphan-auth seam (hydra gate-6): this is a provider-contract status
 * *read*, not an authorization guard. It intentionally has no native
 * caller — the async external-provider status-poll leg is a pluggable
 * extension point implemented by external providers (e.g. ValidSign) and
 * invoked through the provider flow, not the live signing-request status
 * path (`SigningController::showRequest`). Classified as a legit plugin
 * seam in openspec/changes/orphan-auth-remediation/design.md.
 *
 * @param string $externalId The external signing flow identifier
 *
 * @return array<string, mixed> Status with keys: status, signers, completedAt
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#2-1
 */',
        'startLine' => 84,
        'endLine' => 84,
        'startColumn' => 2,
        'endColumn' => 56,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningProviderInterface',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningProviderInterface',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningProviderInterface',
        'aliasName' => NULL,
      ),
      'downloadSignedDocument' => 
      array (
        'name' => 'downloadSignedDocument',
        'parameters' => 
        array (
          'externalId' => 
          array (
            'name' => 'externalId',
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
            'startLine' => 95,
            'endLine' => 95,
            'startColumn' => 41,
            'endColumn' => 58,
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
 * Download the signed document from the provider
 *
 * @param string $externalId The external signing flow identifier
 *
 * @return string The signed document content
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#2-1
 */',
        'startLine' => 95,
        'endLine' => 95,
        'startColumn' => 2,
        'endColumn' => 68,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningProviderInterface',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningProviderInterface',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningProviderInterface',
        'aliasName' => NULL,
      ),
      'cancelSigning' => 
      array (
        'name' => 'cancelSigning',
        'parameters' => 
        array (
          'externalId' => 
          array (
            'name' => 'externalId',
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
            'startLine' => 122,
            'endLine' => 122,
            'startColumn' => 32,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Withdraw an ongoing signing flow.
 *
 * VOID OR THROW, deliberately, and not `bool`.
 *
 * The previous `: bool` contract is what allowed `ValidSignProvider` to ship
 * `return true;` with no call to ValidSign at all — an implementation-shaped
 * statement that a user\'s request had been withdrawn when it was still live and
 * still signable. A boolean invites one caller to write `if ($ok)` and the next
 * to ignore it, and neither is wrong under the type.
 *
 * An implementation MUST either complete the withdrawal against its backend or
 * raise. A provider with no cancellation capability MUST throw
 * {@see SigningCancellationNotSupportedException} rather than return, so the
 * caller can tell the user the truth.
 *
 * @param string $externalId The external signing flow identifier.
 *
 * @return void
 *
 * @throws SigningCancellationNotSupportedException When the provider cannot cancel at all.
 * @throws RuntimeException When the provider could be reached but refused or failed.
 *
 * @spec openspec/changes/signing-cancellation/specs/signing-cancellation/spec.md
 */',
        'startLine' => 122,
        'endLine' => 122,
        'startColumn' => 2,
        'endColumn' => 57,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningProviderInterface',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningProviderInterface',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningProviderInterface',
        'aliasName' => NULL,
      ),
      'supportsLevel' => 
      array (
        'name' => 'supportsLevel',
        'parameters' => 
        array (
          'level' => 
          array (
            'name' => 'level',
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
            'startLine' => 133,
            'endLine' => 133,
            'startColumn' => 32,
            'endColumn' => 44,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Check if this provider supports a given signature level
 *
 * @param string $level The signature level (SES, AdES, QES)
 *
 * @return bool True if the level is supported
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#2-1
 */',
        'startLine' => 133,
        'endLine' => 133,
        'startColumn' => 2,
        'endColumn' => 52,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningProviderInterface',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningProviderInterface',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningProviderInterface',
        'aliasName' => NULL,
      ),
      'produceSignedArtifact' => 
      array (
        'name' => 'produceSignedArtifact',
        'parameters' => 
        array (
          'documentContent' => 
          array (
            'name' => 'documentContent',
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
            'startLine' => 162,
            'endLine' => 162,
            'startColumn' => 40,
            'endColumn' => 62,
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
            'startLine' => 162,
            'endLine' => 162,
            'startColumn' => 65,
            'endColumn' => 78,
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
 * Produce a verifiable signed artifact from the original document bytes.
 *
 * Given the original document content (already a writeable PDF), the
 * provider returns the signed document bytes. The native provider embeds a
 * `/DocuDesk-Signature(...)` marker carrying an HMAC over the document
 * content-hash so the artifact passes
 * `SigningVerificationService::verifyDocument()`. External providers return
 * the file their remote signing service produced.
 *
 * Honest-completion gate: a provider that cannot currently produce a signed
 * artifact (native writer disabled, `signing_verification_secret` unset, or
 * an external provider unconfigured/stubbed) MUST throw a descriptive
 * exception rather than return the unsigned original — so the completing
 * signature fails loudly instead of mislabelling the original as signed.
 *
 * @param string $documentContent The original document bytes.
 * @param array<string, mixed> $context Signing context: signer,
 *                                      signers, timestamp, ip,
 *                                      level.
 *
 * @return string The signed document bytes.
 *
 * @throws \\RuntimeException When no signed artifact can be produced.
 *
 * @spec openspec/specs/document-signing/spec.md
 */',
        'startLine' => 162,
        'endLine' => 162,
        'startColumn' => 2,
        'endColumn' => 88,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningProviderInterface',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningProviderInterface',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningProviderInterface',
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