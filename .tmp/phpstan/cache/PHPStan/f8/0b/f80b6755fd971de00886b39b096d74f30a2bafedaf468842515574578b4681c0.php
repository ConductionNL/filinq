<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/SigningVerificationService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\SigningVerificationService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-0361d0c443e210f0697c18a0e174e61f002bcc474cf20dba444bba9e6d3419c8',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/SigningVerificationService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\SigningVerificationService',
    'shortName' => 'SigningVerificationService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service for verifying document signatures
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#5-1
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 43,
    'endLine' => 460,
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
      'rootFolder' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'name' => 'rootFolder',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\Files\\IRootFolder',
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
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'config' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
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
        'startLine' => 55,
        'endLine' => 55,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
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
            'startLine' => 56,
            'endLine' => 56,
            'startTokenPos' => 97,
            'startFilePos' => 1542,
            'endTokenPos' => 101,
            'endFilePos' => 1569,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 56,
        'endLine' => 56,
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
          'rootFolder' => 
          array (
            'name' => 'rootFolder',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Files\\IRootFolder',
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
            'endColumn' => 42,
            'parameterIndex' => 0,
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
            'startLine' => 55,
            'endLine' => 55,
            'startColumn' => 3,
            'endColumn' => 37,
            'parameterIndex' => 1,
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
                'startLine' => 56,
                'endLine' => 56,
                'startTokenPos' => 97,
                'startFilePos' => 1542,
                'endTokenPos' => 101,
                'endFilePos' => 1569,
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
            'startLine' => 56,
            'endLine' => 56,
            'startColumn' => 3,
            'endColumn' => 87,
            'parameterIndex' => 2,
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
 * @param IRootFolder $rootFolder Root folder
 * @param IAppConfig $config App config
 * @param AssertionCanonicalizer $canonicalizer Canonical-JSON encoder shared with the writer
 *
 * @return void
 */',
        'startLine' => 53,
        'endLine' => 59,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'aliasName' => NULL,
      ),
      'verifyDocument' => 
      array (
        'name' => 'verifyDocument',
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
            'startLine' => 73,
            'endLine' => 73,
            'startColumn' => 33,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'userId' => 
          array (
            'name' => 'userId',
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
            'startLine' => 73,
            'endLine' => 73,
            'startColumn' => 46,
            'endColumn' => 59,
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
 * Verify all signatures in a document
 *
 * @param int $fileId The Nextcloud file ID
 * @param string $userId The user ID requesting verification
 *
 * @return array<string, mixed> Verification result
 *
 * @throws RuntimeException If file cannot be accessed
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#5-1
 */',
        'startLine' => 73,
        'endLine' => 98,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'aliasName' => NULL,
      ),
      'computeVerdict' => 
      array (
        'name' => 'computeVerdict',
        'parameters' => 
        array (
          'signatures' => 
          array (
            'name' => 'signatures',
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
            'startLine' => 115,
            'endLine' => 115,
            'startColumn' => 34,
            'endColumn' => 50,
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
 * Compute the document-level tri-state verdict from per-signature status.
 *
 * `isValid` keeps its strict pre-existing meaning (at least one signature,
 * all `verified`); `verdict` additionally distinguishes tampering from
 * mere inability to verify (signing-trust-rebuild REQ-DDSTR-005): all
 * signatures `verified` -> `verified`; all `invalid` -> `tampered`; all
 * `unverifiable` -> `unverifiable`; any other combination -> `mixed`.
 *
 * @param array<int, array<string, mixed>> $signatures The per-signature results.
 *
 * @return string One of verified|tampered|unverifiable|mixed.
 *
 * @spec openspec/specs/document-signing/spec.md
 */',
        'startLine' => 115,
        'endLine' => 131,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'aliasName' => NULL,
      ),
      'extractSignatures' => 
      array (
        'name' => 'extractSignatures',
        'parameters' => 
        array (
          'pdfContent' => 
          array (
            'name' => 'pdfContent',
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
            'startLine' => 153,
            'endLine' => 153,
            'startColumn' => 37,
            'endColumn' => 54,
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
 * Extract signature information from a PDF document
 *
 * Security note (finding #284 / signing-trust-rebuild REQ-DDSTR-005): the
 * embedded `/DocuDesk-Signature(...)` blob is entirely attacker-controlled
 * (anyone can append it to a PDF), so its mere presence proves nothing.
 * This verifier is therefore FAIL-CLOSED and reports one of three honest
 * states per signature: `verified` (v2 MAC recomputed and matches),
 * `invalid` (a v2 marker whose MAC fails — tamper evidence), or
 * `unverifiable` (a legacy v1 marker, or a genuine external `/Type /Sig`
 * signature Filinq cannot yet cryptographically validate). `valid` is
 * retained as the derived boolean `status === \'verified\'` for
 * response-shape backward compatibility.
 *
 * @param string $pdfContent The PDF file content
 *
 * @return array<int, array<string, mixed>> List of signature records
 *
 * @spec openspec/specs/document-signing/spec.md
 */',
        'startLine' => 153,
        'endLine' => 232,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'aliasName' => NULL,
      ),
      'verifyAssertion' => 
      array (
        'name' => 'verifyAssertion',
        'parameters' => 
        array (
          'assertion' => 
          array (
            'name' => 'assertion',
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
            'startLine' => 255,
            'endLine' => 255,
            'startColumn' => 35,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'pdfContent' => 
          array (
            'name' => 'pdfContent',
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
            'startLine' => 255,
            'endLine' => 255,
            'startColumn' => 53,
            'endColumn' => 70,
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
 * Cryptographically verify a self-asserted Filinq signature blob (v2)
 *
 * V2 assertions carry `v: 2` and a MAC computed as `HMAC-SHA256(secret,
 * sha256(canonical-document) . "\\n" . canonical-JSON(assertion-minus-mac))`
 * — the identity fields (`signer`, `timestamp`, `level`, `method`, `ip`,
 * and any bound portal-identity claims) are inside the MAC input, so
 * rewriting any of them while keeping the original `mac` recomputes to a
 * DIFFERENT value and reports `invalid` (closes the #284 residual /
 * portaliq#3 forgeable-signer class, signing-trust-rebuild REQ-DDSTR-001).
 * An assertion without `v: 2` or without `mac` is a legacy v1 artifact (or
 * malformed) and reports `unverifiable`/`legacy-assertion-v1` — it MUST
 * NEVER be reported `verified` (fail-closed).
 *
 * @param array<string, mixed> $assertion The decoded signature blob
 * @param string $pdfContent The full PDF content
 *
 * @return array{status: string, reason: string, checked: string, keyId: ?string, means: string} The result and its account of itself.
 *
 * @spec openspec/specs/document-signing/spec.md
 */',
        'startLine' => 255,
        'endLine' => 308,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'aliasName' => NULL,
      ),
      'account' => 
      array (
        'name' => 'account',
        'parameters' => 
        array (
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 336,
            'endLine' => 336,
            'startColumn' => 27,
            'endColumn' => 40,
            'parameterIndex' => 0,
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
            'startLine' => 336,
            'endLine' => 336,
            'startColumn' => 43,
            'endColumn' => 56,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'checked' => 
          array (
            'name' => 'checked',
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
            'startLine' => 336,
            'endLine' => 336,
            'startColumn' => 59,
            'endColumn' => 73,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'secret' => 
          array (
            'name' => 'secret',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 336,
                'endLine' => 336,
                'startTokenPos' => 1441,
                'startFilePos' => 13473,
                'endTokenPos' => 1441,
                'endFilePos' => 13476,
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
            'startLine' => 336,
            'endLine' => 336,
            'startColumn' => 76,
            'endColumn' => 97,
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
 * Say what was checked, against which key, and what that proves.
 *
 * 🔴 `reason` IS A SLUG, AND A SLUG IS NOT AN ANSWER. `mac-mismatch` on a
 * screen tells a person nothing they can act on, and the one thing they
 * actually need to know is what a green tick does NOT mean. A verified MAC
 * proves this server produced the artifact and that nobody has changed the
 * bytes since. It does not prove who the signer is, and it is not a
 * qualified electronic signature.
 *
 * 🔴 THE KEY IS NAMED, NEVER SHOWN. "Verified" is meaningless without
 * saying against WHAT — an instance whose secret was rotated will report
 * `invalid` for every older artifact, and without a key id that reads as
 * mass tampering. The id is an HMAC of a fixed label under the secret,
 * truncated: it changes when the secret changes, identifies nothing else,
 * and cannot be turned back into the secret.
 *
 * @param string      $status  The tri-state status.
 * @param string      $reason  The machine-readable reason slug.
 * @param string      $checked What was covered by the check.
 * @param string|null $secret  The signing secret, when there was one.
 *
 * @return array{status: string, reason: string, checked: string, keyId: ?string, means: string} The account.
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */',
        'startLine' => 336,
        'endLine' => 358,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'aliasName' => NULL,
      ),
      'keyId' => 
      array (
        'name' => 'keyId',
        'parameters' => 
        array (
          'secret' => 
          array (
            'name' => 'secret',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 367,
            'endLine' => 367,
            'startColumn' => 25,
            'endColumn' => 39,
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
 * A stable, non-reversible name for the key a check ran against.
 *
 * @param string|null $secret The signing secret, or null when there is none.
 *
 * @return string|null The key id, or null when no key was involved.
 */',
        'startLine' => 367,
        'endLine' => 377,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'aliasName' => NULL,
      ),
      'canonicaliseForAssertion' => 
      array (
        'name' => 'canonicaliseForAssertion',
        'parameters' => 
        array (
          'pdfContent' => 
          array (
            'name' => 'pdfContent',
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
            'startLine' => 393,
            'endLine' => 393,
            'startColumn' => 44,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Blank every Filinq signature marker payload to recover the canonical form
 *
 * The signed artifact is the original document plus a
 * `/DocuDesk-Signature(base64-json)` marker. The marker\'s own `mac` field
 * cannot be part of the hashed content, so verification (and the writer)
 * hash the document with every marker payload emptied. This yields the exact
 * bytes the writer hashed before it knew the mac, making writer and verifier
 * symmetric.
 *
 * @param string $pdfContent The full PDF content
 *
 * @return string The content with all marker payloads blanked
 */',
        'startLine' => 393,
        'endLine' => 400,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'aliasName' => NULL,
      ),
      'stripAssertionMac' => 
      array (
        'name' => 'stripAssertionMac',
        'parameters' => 
        array (
          'pdfContent' => 
          array (
            'name' => 'pdfContent',
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
            'startLine' => 410,
            'endLine' => 410,
            'startColumn' => 37,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'mac' => 
          array (
            'name' => 'mac',
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
            'startLine' => 410,
            'endLine' => 410,
            'startColumn' => 57,
            'endColumn' => 67,
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
 * Remove the asserted MAC value from the PDF byte stream before hashing
 *
 * @param string $pdfContent The full PDF content
 * @param string $mac The asserted MAC value to strip
 *
 * @return string The PDF content with the MAC value removed
 */',
        'startLine' => 410,
        'endLine' => 414,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'aliasName' => NULL,
      ),
      'getSigningSecret' => 
      array (
        'name' => 'getSigningSecret',
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
 * Get the server-held signing secret used to verify assertions.
 *
 * Returns null — not \'\' — when the secret is unset, so "not configured" is
 * representable in the type rather than being a magic empty string that
 * happens to be falsy. The single caller already fails closed on it, but an
 * empty string is a VALID HMAC key: `hash_hmac(\'sha256\', $payload, \'\')`
 * computes a perfectly well-formed MAC that any attacker can also compute.
 * A future caller that forgets the check would therefore not crash — it
 * would verify signatures against a publicly-derivable key and report them
 * genuine. A null cannot be passed to hash_hmac() by accident.
 *
 * @return string|null The configured secret, or null when unset.
 */',
        'startLine' => 430,
        'endLine' => 437,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'aliasName' => NULL,
      ),
      'allSignaturesValid' => 
      array (
        'name' => 'allSignaturesValid',
        'parameters' => 
        array (
          'signatures' => 
          array (
            'name' => 'signatures',
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
            'startLine' => 446,
            'endLine' => 446,
            'startColumn' => 38,
            'endColumn' => 54,
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
 * Check if all signatures are valid
 *
 * @param array<int, array<string, mixed>> $signatures The signatures
 *
 * @return bool True if all valid
 */',
        'startLine' => 446,
        'endLine' => 459,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningVerificationService',
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