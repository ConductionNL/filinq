<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Signing/NativeSigningProvider.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Signing\NativeSigningProvider
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-4ba99faeed1a90a138a8497cb622f20bacb0b14f328420ae7c50840dbe6f6a22',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Signing/NativeSigningProvider.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Signing',
    'name' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
    'shortName' => 'NativeSigningProvider',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Native signing provider for SES-level signatures
 *
 * Sessions are persisted as OpenRegister objects (register/schema configured
 * via `signingSession_register` / `signingSession_schema` in IAppConfig).
 * Previously they lived only in a per-request `$sessions` array, so the
 * `initiateSigning()` HTTP request created a record that `checkStatus()`,
 * `downloadSignedDocument()` and `cancelSigning()` in subsequent requests
 * could never see (issue #287).
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#2-2
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 50,
    'endLine' => 582,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'OCA\\Filinq\\Service\\Signing\\SigningProviderInterface',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'STATUS_CANCELLED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'name' => 'STATUS_CANCELLED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'cancelled\'',
          'attributes' => 
          array (
            'startLine' => 57,
            'endLine' => 57,
            'startTokenPos' => 74,
            'startFilePos' => 1699,
            'endTokenPos' => 74,
            'endFilePos' => 1709,
          ),
        ),
        'docComment' => '/**
 * A withdrawn signing session.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 2,
        'endColumn' => 45,
      ),
      'STATUS_COMPLETED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'name' => 'STATUS_COMPLETED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'completed\'',
          'attributes' => 
          array (
            'startLine' => 64,
            'endLine' => 64,
            'startTokenPos' => 87,
            'startFilePos' => 1826,
            'endTokenPos' => 87,
            'endFilePos' => 1836,
          ),
        ),
        'docComment' => '/**
 * A signing session every signatory has signed.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 64,
        'endLine' => 64,
        'startColumn' => 2,
        'endColumn' => 45,
      ),
    ),
    'immediateProperties' => 
    array (
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
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
        'startLine' => 77,
        'endLine' => 77,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
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
      'config' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
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
        'startLine' => 79,
        'endLine' => 79,
        'startColumn' => 3,
        'endColumn' => 37,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'canonicalizer' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'name' => 'canonicalizer',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Signing\\AssertionCanonicalizer',
            'isIdentifier' => false,
          ),
        ),
        'default' => 
        array (
          'code' => 'new \\OCA\\Filinq\\Service\\Signing\\AssertionCanonicalizer()',
          'attributes' => 
          array (
            'startLine' => 80,
            'endLine' => 80,
            'startTokenPos' => 136,
            'startFilePos' => 2430,
            'endTokenPos' => 140,
            'endFilePos' => 2457,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 80,
        'endLine' => 80,
        'startColumn' => 3,
        'endColumn' => 87,
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
            'startLine' => 77,
            'endLine' => 77,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 0,
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
            'startLine' => 78,
            'endLine' => 78,
            'startColumn' => 3,
            'endColumn' => 51,
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
            'startLine' => 79,
            'endLine' => 79,
            'startColumn' => 3,
            'endColumn' => 37,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'canonicalizer' => 
          array (
            'name' => 'canonicalizer',
            'default' => 
            array (
              'code' => 'new \\OCA\\Filinq\\Service\\Signing\\AssertionCanonicalizer()',
              'attributes' => 
              array (
                'startLine' => 80,
                'endLine' => 80,
                'startTokenPos' => 136,
                'startFilePos' => 2430,
                'endTokenPos' => 140,
                'endFilePos' => 2457,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Signing\\AssertionCanonicalizer',
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
            'endColumn' => 87,
            'parameterIndex' => 3,
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
 * @param LoggerInterface $logger Logger interface
 * @param SettingsService $settingsService Settings service (provides OR ObjectService)
 * @param IAppConfig $config App config (resolves session register/schema)
 * @param AssertionCanonicalizer $canonicalizer Canonical-JSON encoder shared with the verifier
 *
 * @return void
 */',
        'startLine' => 76,
        'endLine' => 83,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'aliasName' => NULL,
      ),
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
 * Get provider identifier
 *
 * @return string The provider identifier
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#2-2
 */',
        'startLine' => 92,
        'endLine' => 94,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
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
            'startLine' => 112,
            'endLine' => 112,
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
            'startLine' => 113,
            'endLine' => 113,
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
            'startLine' => 114,
            'endLine' => 114,
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
            'startLine' => 115,
            'endLine' => 115,
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
                'startLine' => 116,
                'endLine' => 116,
                'startTokenPos' => 208,
                'startFilePos' => 3417,
                'endTokenPos' => 209,
                'endFilePos' => 3418,
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
            'startLine' => 116,
            'endLine' => 116,
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
 * Initiate a native SES signing flow
 *
 * @param string $documentPath Path to the document
 * @param string $documentName Display name of the document
 * @param array<string, mixed> $signers Signer data array
 * @param string $level Signature level
 * @param array<string, mixed> $options Additional options
 *
 * @return array<string, mixed> Result with signing session identifier
 *
 * @throws RuntimeException If the signature level is not supported
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#2-2
 */',
        'startLine' => 111,
        'endLine' => 152,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
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
            'startLine' => 172,
            'endLine' => 172,
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
 * Check status of a native signing session
 *
 * Orphan-auth seam (hydra gate-6): a provider-contract status *read*, not
 * an authorization guard. No native caller — the async status-poll leg is
 * a pluggable extension point (see SigningProviderInterface::checkStatus);
 * the live status surface is the signing request read via
 * `SigningController::showRequest`. Classified as a legit plugin seam in
 * openspec/changes/orphan-auth-remediation/design.md.
 *
 * @param string $externalId The signing session identifier
 *
 * @return array<string, mixed> The session status
 *
 * @throws RuntimeException If session not found
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#2-2
 */',
        'startLine' => 172,
        'endLine' => 182,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
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
            'startLine' => 204,
            'endLine' => 204,
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
 * Download the signed document
 *
 * Fail-closed session download (signing-trust-rebuild REQ-DDSTR-004,
 * closing issue #287\'s residual): returns the persisted
 * `signedDocumentPath` ONLY when the session is `completed`, the path is
 * non-empty, AND `markerEmbedded === true`. In every other case this
 * throws — the unsigned original `documentPath` is NEVER returned as if
 * it were the signed document. This extends the honest-completion gate
 * (issue #304) to the pluggable session-download seam.
 *
 * @param string $externalId The signing session identifier
 *
 * @return string The signed document path
 *
 * @throws RuntimeException If the session is not found, not completed, or
 *                          has no embedded, marker-verified artifact.
 *
 * @spec openspec/specs/document-signing/spec.md
 */',
        'startLine' => 204,
        'endLine' => 226,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
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
            'startLine' => 244,
            'endLine' => 244,
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
 * Withdraw a native signing session.
 *
 * Idempotent on an already-cancelled session — a double-click, not an error.
 * Refuses a COMPLETED one: the signatures exist and the process is over, so
 * accepting it would let the UI show "cancelled" over a document that is in fact
 * signed, which is a claim the system cannot make good on.
 *
 * @param string $externalId The signing session identifier.
 *
 * @return void
 *
 * @throws RuntimeException If the session is not found, or is already completed.
 *
 * @spec openspec/changes/signing-cancellation/specs/signing-cancellation/spec.md
 */',
        'startLine' => 244,
        'endLine' => 264,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
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
            'startLine' => 275,
            'endLine' => 275,
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
 * Check if this provider supports the given signature level
 *
 * @param string $level The signature level to check
 *
 * @return bool True if SES level
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#2-2
 */',
        'startLine' => 275,
        'endLine' => 277,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
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
            'startLine' => 326,
            'endLine' => 326,
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
            'startLine' => 326,
            'endLine' => 326,
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
 * Produce a verifiable, identity-bound native SES signed artifact (v2).
 *
 * Embeds a `/DocuDesk-Signature(base64-json)` marker binding the signer
 * identity, timestamp, level, method and IP. The assertion carries a
 * version discriminator `v: 2` and a MAC computed as
 * `HMAC-SHA256(secret, sha256(canonical-document) . "\\n" .
 * canonical-JSON(assertion-minus-mac))` — the identity fields are now
 * INSIDE the MAC input, so rewriting any of them (signer name, level,
 * timestamp, method) while keeping the original `mac` fails verification
 * (signing-trust-rebuild REQ-DDSTR-001, closing the #284 residual where
 * the v1 MAC covered only the content-hash and left every assertion
 * field forgeable). `SigningVerificationService::verifyAssertion()`
 * recomputes the identical value and validates the artifact.
 *
 * When the completing act is portal-originated (`portal-signing-actions` /
 * `portal-signing-surface`), the receiver-resolved portal subject claims
 * (`portalSubjectRef`, `portalIdentityRef`, `portalTrust`, `portalJti`) are
 * threaded in via `$context` and folded into the SAME assertion — and
 * therefore the SAME MAC — before it is computed, so the portal signer\'s
 * identity is cryptographically bound too (portal-signing-surface
 * REQ-DDPSS-004, closing the portaliq#3 forgeable-signer class for the
 * portal seam). Those fields are present only when the caller (always
 * `SigningService`, sourced only from the verified assertion — never
 * client input) supplies them.
 *
 * Honest-completion gates: (1) an unset signing secret and (2) a
 * requested level this provider does not support (`supportsLevel()`,
 * REQ-DDSTR-002 point 3, defence in depth alongside the request-creation
 * and completion-resolution gates in `SigningService`) both throw rather
 * than emit an unverifiable or mislabelled artifact.
 *
 * @param string $documentContent The original document bytes.
 * @param array<string, mixed> $context Signing context: signer,
 *                                      signers, timestamp, ip,
 *                                      level, and optionally the
 *                                      portal* identity claims.
 *
 * @return string The signed document bytes.
 *
 * @throws RuntimeException When the signing secret is unset or the
 *                          requested level is not SES.
 *
 * @spec openspec/specs/document-signing/spec.md
 * @spec openspec/specs/portal-signing-surface/spec.md
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */',
        'startLine' => 326,
        'endLine' => 385,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'aliasName' => NULL,
      ),
      'assembleSignedBytes' => 
      array (
        'name' => 'assembleSignedBytes',
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
            'startLine' => 399,
            'endLine' => 399,
            'startColumn' => 39,
            'endColumn' => 61,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'payload' => 
          array (
            'name' => 'payload',
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
            'startLine' => 399,
            'endLine' => 399,
            'startColumn' => 64,
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
 * Assemble the signed document bytes with the given marker payload.
 *
 * Appending the marker as a trailing PDF object keeps the original bytes
 * intact and lets the verifier recover the canonical form by blanking the
 * marker payload. An empty payload yields the canonical (hashed) form.
 *
 * @param string $documentContent The original document bytes.
 * @param string $payload The base64 marker payload (\'\' for canonical).
 *
 * @return string The assembled bytes.
 */',
        'startLine' => 399,
        'endLine' => 412,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'aliasName' => NULL,
      ),
      'persistSession' => 
      array (
        'name' => 'persistSession',
        'parameters' => 
        array (
          'session' => 
          array (
            'name' => 'session',
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
            'startLine' => 429,
            'endLine' => 429,
            'startColumn' => 34,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Persist a signing session as an OpenRegister object
 *
 * Honours `externalId` as the natural key — when a session with the
 * same externalId already exists its `id`/`uuid` is preserved so OR
 * updates the existing row instead of creating a duplicate. Uses the
 * canonical OR ObjectService surface (`saveObject(object, extend,
 * register, schema, uuid)`).
 *
 * @param array<string, mixed> $session The session data
 *
 * @return void
 *
 * @throws RuntimeException If OR is unavailable
 */',
        'startLine' => 429,
        'endLine' => 460,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'aliasName' => NULL,
      ),
      'loadSessionByExternalId' => 
      array (
        'name' => 'loadSessionByExternalId',
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
            'startLine' => 471,
            'endLine' => 471,
            'startColumn' => 43,
            'endColumn' => 60,
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
 * Load a session by externalId, throwing if missing
 *
 * @param string $externalId The externalId to look up
 *
 * @return array<string, mixed> The session row
 *
 * @throws RuntimeException If the session is not found
 */',
        'startLine' => 471,
        'endLine' => 478,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'aliasName' => NULL,
      ),
      'loadRawSessionByExternalId' => 
      array (
        'name' => 'loadRawSessionByExternalId',
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
            'startLine' => 491,
            'endLine' => 491,
            'startColumn' => 46,
            'endColumn' => 63,
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
 * Load a session by externalId, returning null if missing
 *
 * Uses OR\'s findAll(config) facade with a filter on externalId so the
 * call goes through the canonical zoeken-filteren pipeline rather than
 * a non-existent `getObjects($register, $schema)` shortcut.
 *
 * @param string $externalId The externalId to look up
 *
 * @return array<string, mixed>|null The session row or null
 */',
        'startLine' => 491,
        'endLine' => 534,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'aliasName' => NULL,
      ),
      'normaliseEntry' => 
      array (
        'name' => 'normaliseEntry',
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
            'startLine' => 543,
            'endLine' => 543,
            'startColumn' => 34,
            'endColumn' => 45,
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
 * Normalise an OR entry (ObjectEntity or array) into a plain array
 *
 * @param mixed $entry The raw entry from findAll()
 *
 * @return array<string, mixed>|null The normalised row, or null on failure
 */',
        'startLine' => 543,
        'endLine' => 563,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'aliasName' => NULL,
      ),
      'resolveSessionRegisterSchema' => 
      array (
        'name' => 'resolveSessionRegisterSchema',
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
 * Resolve the OR register/schema pair used to persist sessions
 *
 * The fallback is `filinq`, not `signing`. It is reached whenever the
 * app-config key is absent — a fresh install before the import has run, or
 * an install where it was never written — and a default is exactly where a
 * stale register slug survives a sweep: it never appears in a call, it just
 * quietly sends the write to a register nothing reads.
 *
 * @return array{0:string,1:string} [register, schema]
 */',
        'startLine' => 576,
        'endLine' => 581,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\NativeSigningProvider',
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