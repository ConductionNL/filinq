<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Signing/ValidSignProvider.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Signing\ValidSignProvider
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-2b40643e484f80df8f643de7d2f652a45914b72bc28877b94e406a3172bbc920',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Signing/ValidSignProvider.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Signing',
    'name' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
    'shortName' => 'ValidSignProvider',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * ValidSign signing provider
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#2-3
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 39,
    'endLine' => 245,
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
    ),
    'immediateProperties' => 
    array (
      'config' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
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
        'startLine' => 48,
        'endLine' => 48,
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
            'startLine' => 48,
            'endLine' => 48,
            'startColumn' => 3,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
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
 * @param IAppConfig $config The app config
 *
 * @return void
 */',
        'startLine' => 47,
        'endLine' => 51,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
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
 * @spec openspec/changes/digital-signing-integration/tasks.md#2-3
 */',
        'startLine' => 60,
        'endLine' => 62,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
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
            'startLine' => 80,
            'endLine' => 80,
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
            'startLine' => 81,
            'endLine' => 81,
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
            'startLine' => 82,
            'endLine' => 82,
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
            'startLine' => 83,
            'endLine' => 83,
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
                'startLine' => 84,
                'endLine' => 84,
                'startTokenPos' => 127,
                'startFilePos' => 2216,
                'endTokenPos' => 128,
                'endFilePos' => 2217,
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
            'startLine' => 84,
            'endLine' => 84,
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
 * Initiate a ValidSign signing flow (stub)
 *
 * @param string $documentPath Path to the document
 * @param string $documentName Display name of the document
 * @param array<string, mixed> $signers Signer data array
 * @param string $level Signature level
 * @param array<string, mixed> $options Additional options
 *
 * @return array<string, mixed> Result with ValidSign package ID
 *
 * @throws RuntimeException If provider is not configured
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#2-3
 */',
        'startLine' => 79,
        'endLine' => 102,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
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
            'startLine' => 120,
            'endLine' => 120,
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
 * Check status of a ValidSign signing flow (stub)
 *
 * Orphan-auth seam (hydra gate-6): a provider-contract status *read*, not
 * an authorization guard. No native caller — this is the external-provider
 * status-poll extension point (SigningProviderInterface::checkStatus),
 * currently a stub pending ValidSign integration; the live status surface
 * is the signing request read via `SigningController::showRequest`. Classified as
 * a legit plugin seam in openspec/changes/orphan-auth-remediation/design.md.
 *
 * @param string $externalId The ValidSign package identifier
 *
 * @return array<string, mixed> The signing flow status
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#2-3
 */',
        'startLine' => 120,
        'endLine' => 127,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
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
            'startLine' => 140,
            'endLine' => 140,
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
 * Download the signed document from ValidSign (stub)
 *
 * @param string $externalId The ValidSign package identifier
 *
 * @return string The signed document content
 *
 * @throws RuntimeException Always throws - not yet implemented
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#2-3
 */',
        'startLine' => 140,
        'endLine' => 145,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
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
            'startLine' => 174,
            'endLine' => 174,
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
 * Withdraw a ValidSign signing flow — NOT IMPLEMENTED, and it says so.
 *
 * This method previously was, in full, `return true;`. No call to ValidSign.
 * Nothing in this app invoked it, so the lie was dormant — but connected to a
 * cancel button it would have told a user their request was withdrawn while it
 * stayed live at ValidSign, with signatories still able to open it and produce a
 * legally valid signature. Filinq would have shown no trace of the
 * discrepancy.
 *
 * It now throws. Throwing is not a regression from `return true`: it is the
 * difference between a user who knows they must withdraw the request in
 * ValidSign\'s own interface, and a user who believes it is already done.
 *
 * ValidSign\'s cancellation API has not been integrated. Per the direction set
 * 2026-08-16, ValidSign is not the strategic provider — an own signing service
 * via Portaliq is — so this is deliberately left unimplemented rather than
 * half-built against an API we intend to stop using.
 *
 * @param string $externalId The ValidSign package identifier.
 *
 * @return void
 *
 * @throws SigningCancellationNotSupportedException Always.
 *
 * @spec openspec/changes/signing-cancellation/specs/signing-cancellation/spec.md
 */',
        'startLine' => 174,
        'endLine' => 179,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
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
            'startLine' => 190,
            'endLine' => 190,
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
 * @return bool True if supported
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#2-3
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
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
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
            'startLine' => 211,
            'endLine' => 211,
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
            'startLine' => 211,
            'endLine' => 211,
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
 * Produce a signed artifact (not yet implemented — honest-completion gate).
 *
 * The ValidSign external integration cannot yet return a signed file, so
 * this throws rather than presenting the unsigned original as signed. A QES
 * request routed here fails the honest-completion gate loudly (issue #304
 * scope: an unfinished provider must never mislabel the original).
 *
 * @param string $documentContent The original document bytes.
 * @param array<string, mixed> $context Signing context.
 *
 * @return string The signed document bytes.
 *
 * @throws RuntimeException Always — the ValidSign artifact flow is not wired.
 *
 * @spec openspec/specs/document-signing/spec.md
 */',
        'startLine' => 211,
        'endLine' => 228,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
        'aliasName' => NULL,
      ),
      'getProviderConfig' => 
      array (
        'name' => 'getProviderConfig',
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
 * Get the provider configuration from app config
 *
 * @return array<string, mixed> The provider configuration
 */',
        'startLine' => 235,
        'endLine' => 244,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\ValidSignProvider',
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