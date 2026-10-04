<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Controller/PortalSigningReceiverController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Controller\PortalSigningReceiverController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-cba1991eb2c4ff61c2036e20c4abdc5ea7a45458b28e9c55c86d4b3934322c85',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Controller/PortalSigningReceiverController.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Controller',
    'name' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
    'shortName' => 'PortalSigningReceiverController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Receives portaliq\'s forwarded signing actions on the `signer` audience.
 *
 * @spec openspec/specs/portal-signing-actions/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 64,
    'endLine' => 568,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'OCP\\AppFramework\\Controller',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'THROTTLE_ACTION' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'name' => 'THROTTLE_ACTION',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq_portal_signing_assertion\'',
          'attributes' => 
          array (
            'startLine' => 74,
            'endLine' => 74,
            'startTokenPos' => 119,
            'startFilePos' => 2894,
            'endTokenPos' => 119,
            'endFilePos' => 2926,
          ),
        ),
        'docComment' => '/**
 * Brute-force throttler action for rejected portal signing assertions.
 *
 * One action across sign / decline / viewDocument: they share
 * `authoriseAct()`, so a caller must not be able to spread guesses over
 * three endpoints to stay under a per-endpoint ceiling.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 74,
        'endLine' => 74,
        'startColumn' => 2,
        'endColumn' => 67,
      ),
      'MIN_TRUST' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'name' => 'MIN_TRUST',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'substantial\'',
          'attributes' => 
          array (
            'startLine' => 81,
            'endLine' => 81,
            'startTokenPos' => 132,
            'startFilePos' => 3180,
            'endTokenPos' => 132,
            'endFilePos' => 3192,
          ),
        ),
        'docComment' => '/**
 * Minimum eIDAS-aligned portal trust required to act (mirrors
 * `PortalContributionProvider::SIGNING_MIN_TRUST` — re-checked here as
 * defence in depth; the receiver MUST NOT rely on portaliq\'s own gate).
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 81,
        'endLine' => 81,
        'startColumn' => 2,
        'endColumn' => 41,
      ),
      'TRUST_ORDER' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'name' => 'TRUST_ORDER',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'low\', \'substantial\', \'high\']',
          'attributes' => 
          array (
            'startLine' => 88,
            'endLine' => 88,
            'startTokenPos' => 145,
            'startFilePos' => 3325,
            'endTokenPos' => 153,
            'endFilePos' => 3354,
          ),
        ),
        'docComment' => '/**
 * Ordered trust levels, low to high, for the `>=` comparison.
 *
 * @var list<string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 88,
        'endLine' => 88,
        'startColumn' => 2,
        'endColumn' => 60,
      ),
      'AUDIENCE_SIGNER' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'name' => 'AUDIENCE_SIGNER',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'signer\'',
          'attributes' => 
          array (
            'startLine' => 93,
            'endLine' => 93,
            'startTokenPos' => 166,
            'startFilePos' => 3445,
            'endTokenPos' => 166,
            'endFilePos' => 3452,
          ),
        ),
        'docComment' => '/**
 * The only audience this receiver serves.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 93,
        'endLine' => 93,
        'startColumn' => 2,
        'endColumn' => 42,
      ),
    ),
    'immediateProperties' => 
    array (
      'verifier' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'name' => 'verifier',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 113,
        'endLine' => 113,
        'startColumn' => 3,
        'endColumn' => 52,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'signingService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'name' => 'signingService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\SigningService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 114,
        'endLine' => 114,
        'startColumn' => 3,
        'endColumn' => 49,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'settingsService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
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
        'startLine' => 115,
        'endLine' => 115,
        'startColumn' => 3,
        'endColumn' => 51,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'registerResolver' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'name' => 'registerResolver',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 116,
        'endLine' => 116,
        'startColumn' => 3,
        'endColumn' => 57,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
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
        'startLine' => 117,
        'endLine' => 117,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'documentResolver' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'name' => 'documentResolver',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\PortalSigningDocumentResolver',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 118,
        'endLine' => 118,
        'startColumn' => 3,
        'endColumn' => 66,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'throttler' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'name' => 'throttler',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\Security\\Bruteforce\\IThrottler',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 119,
        'endLine' => 119,
        'startColumn' => 3,
        'endColumn' => 40,
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
          'appName' => 
          array (
            'name' => 'appName',
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
            'startLine' => 111,
            'endLine' => 111,
            'startColumn' => 3,
            'endColumn' => 17,
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
                'name' => 'OCP\\IRequest',
                'isIdentifier' => false,
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
            'endColumn' => 19,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'verifier' => 
          array (
            'name' => 'verifier',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 113,
            'endLine' => 113,
            'startColumn' => 3,
            'endColumn' => 52,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'signingService' => 
          array (
            'name' => 'signingService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\SigningService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 114,
            'endLine' => 114,
            'startColumn' => 3,
            'endColumn' => 49,
            'parameterIndex' => 3,
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
            'startLine' => 115,
            'endLine' => 115,
            'startColumn' => 3,
            'endColumn' => 51,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'registerResolver' => 
          array (
            'name' => 'registerResolver',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 116,
            'endLine' => 116,
            'startColumn' => 3,
            'endColumn' => 57,
            'parameterIndex' => 5,
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
            'startLine' => 117,
            'endLine' => 117,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 6,
            'isOptional' => false,
          ),
          'documentResolver' => 
          array (
            'name' => 'documentResolver',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\PortalSigningDocumentResolver',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 118,
            'endLine' => 118,
            'startColumn' => 3,
            'endColumn' => 66,
            'parameterIndex' => 7,
            'isOptional' => false,
          ),
          'throttler' => 
          array (
            'name' => 'throttler',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Security\\Bruteforce\\IThrottler',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 119,
            'endLine' => 119,
            'startColumn' => 3,
            'endColumn' => 40,
            'parameterIndex' => 8,
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
 * @param string $appName App name.
 * @param IRequest $request Request object.
 * @param PortalAssertionVerifier $verifier Verifies the X-Portal-Subject assertion.
 * @param SigningService $signingService The honest signing primitive.
 * @param SettingsService $settingsService Settings service (resolves OR\'s ObjectService).
 * @param OpenRegisterResolver $registerResolver Resolves register/schema bindings, failing closed.
 * @param LoggerInterface $logger Logger.
 * @param PortalSigningDocumentResolver $documentResolver Resolves the target document for viewDocument.
 * @param IThrottler $throttler Brute-force throttler; rejected portal assertions are registered against it.
 *
 * @return void
 */',
        'startLine' => 110,
        'endLine' => 123,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'aliasName' => NULL,
      ),
      'signDocument' => 
      array (
        'name' => 'signDocument',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\PublicPage',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
          1 => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\NoCSRFRequired',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
          2 => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\AnonRateLimit',
            'isRepeated' => false,
            'arguments' => 
            array (
              'limit' => 
              array (
                'code' => '20',
                'attributes' => 
                array (
                  'startLine' => 143,
                  'endLine' => 143,
                  'startTokenPos' => 291,
                  'startFilePos' => 5491,
                  'endTokenPos' => 291,
                  'endFilePos' => 5492,
                ),
              ),
              'period' => 
              array (
                'code' => '60',
                'attributes' => 
                array (
                  'startLine' => 143,
                  'endLine' => 143,
                  'startTokenPos' => 297,
                  'startFilePos' => 5503,
                  'endTokenPos' => 297,
                  'endFilePos' => 5504,
                ),
              ),
            ),
          ),
          3 => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\BruteForceProtection',
            'isRepeated' => false,
            'arguments' => 
            array (
              'action' => 
              array (
                'code' => 'self::THROTTLE_ACTION',
                'attributes' => 
                array (
                  'startLine' => 144,
                  'endLine' => 144,
                  'startTokenPos' => 307,
                  'startFilePos' => 5540,
                  'endTokenPos' => 309,
                  'endFilePos' => 5560,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * POST /apps/filinq/api/portal/signing/sign
 *
 * The signDocument act (portal-signing-actions REQ-DDPSA-005,
 * portal-signing-surface REQ-DDPSS-002): records the signer\'s consent
 * confirmation + optional drawn-signature payload, then drives
 * `SigningService::sign()` acting as the resolved, verified external
 * signer. A refusal by the guardian consent rule answers 403
 * `signing_refused` rather than 502 (signer-identity-rails REQ-DDSIR-008).
 *
 * @return JSONResponse
 *
 * @spec openspec/specs/portal-signing-actions/spec.md
 * @spec openspec/specs/portal-signing-surface/spec.md
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */',
        'startLine' => 141,
        'endLine' => 187,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'aliasName' => NULL,
      ),
      'declineDocument' => 
      array (
        'name' => 'declineDocument',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\PublicPage',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
          1 => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\NoCSRFRequired',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
          2 => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\AnonRateLimit',
            'isRepeated' => false,
            'arguments' => 
            array (
              'limit' => 
              array (
                'code' => '20',
                'attributes' => 
                array (
                  'startLine' => 204,
                  'endLine' => 204,
                  'startTokenPos' => 659,
                  'startFilePos' => 7527,
                  'endTokenPos' => 659,
                  'endFilePos' => 7528,
                ),
              ),
              'period' => 
              array (
                'code' => '60',
                'attributes' => 
                array (
                  'startLine' => 204,
                  'endLine' => 204,
                  'startTokenPos' => 665,
                  'startFilePos' => 7539,
                  'endTokenPos' => 665,
                  'endFilePos' => 7540,
                ),
              ),
            ),
          ),
          3 => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\BruteForceProtection',
            'isRepeated' => false,
            'arguments' => 
            array (
              'action' => 
              array (
                'code' => 'self::THROTTLE_ACTION',
                'attributes' => 
                array (
                  'startLine' => 205,
                  'endLine' => 205,
                  'startTokenPos' => 675,
                  'startFilePos' => 7576,
                  'endTokenPos' => 677,
                  'endFilePos' => 7596,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * POST /apps/filinq/api/portal/signing/decline
 *
 * The declineDocument act (portal-signing-actions REQ-DDPSA-005,
 * portal-signing-surface REQ-DDPSS-003): records the client-supplied
 * reason, then drives `SigningService::decline()` acting as the resolved,
 * verified external signer.
 *
 * @return JSONResponse
 *
 * @spec openspec/specs/portal-signing-actions/spec.md
 * @spec openspec/specs/portal-signing-surface/spec.md
 */',
        'startLine' => 202,
        'endLine' => 233,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'aliasName' => NULL,
      ),
      'viewDocument' => 
      array (
        'name' => 'viewDocument',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\PublicPage',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
          1 => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\NoCSRFRequired',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
          2 => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\AnonRateLimit',
            'isRepeated' => false,
            'arguments' => 
            array (
              'limit' => 
              array (
                'code' => '60',
                'attributes' => 
                array (
                  'startLine' => 251,
                  'endLine' => 251,
                  'startTokenPos' => 898,
                  'startFilePos' => 8934,
                  'endTokenPos' => 898,
                  'endFilePos' => 8935,
                ),
              ),
              'period' => 
              array (
                'code' => '60',
                'attributes' => 
                array (
                  'startLine' => 251,
                  'endLine' => 251,
                  'startTokenPos' => 904,
                  'startFilePos' => 8946,
                  'endTokenPos' => 904,
                  'endFilePos' => 8947,
                ),
              ),
            ),
          ),
          3 => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\BruteForceProtection',
            'isRepeated' => false,
            'arguments' => 
            array (
              'action' => 
              array (
                'code' => 'self::THROTTLE_ACTION',
                'attributes' => 
                array (
                  'startLine' => 252,
                  'endLine' => 252,
                  'startTokenPos' => 914,
                  'startFilePos' => 8983,
                  'endTokenPos' => 916,
                  'endFilePos' => 9003,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * GET /apps/filinq/api/portal/signing/viewDocument
 *
 * The viewDocument act (portal-signing-actions REQ-DDPSA-006): lets the
 * verified, invited signer read the target document BEFORE signing.
 * Scoped by the IDENTICAL invited-signer guard as sign/decline.
 * portaliq\'s A6 forward relays a decoded JSON body only, so the document
 * is returned as `{documentName, mimeType, contentBase64}` inside the
 * single JSON hop.
 *
 * @return JSONResponse
 *
 * @spec openspec/specs/portal-signing-actions/spec.md
 */',
        'startLine' => 249,
        'endLine' => 291,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'aliasName' => NULL,
      ),
      'authoriseAct' => 
      array (
        'name' => 'authoriseAct',
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
                  'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
                  'isIdentifier' => false,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Verify the assertion, derive identity, validate the target, and resolve
 * the invited signer — the SAME guard for sign/decline/viewDocument
 * (portal-signing-actions REQ-DDPSA-002/003/004).
 *
 * @return array{0: array<string, mixed>, 1: array<string, mixed>}|JSONResponse
 *                                                                              On success: `[verifiedActor, signerRecord]`. On any
 *                                                                              failure: the fail-closed JSONResponse to return directly.
 */',
        'startLine' => 302,
        'endLine' => 369,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'aliasName' => NULL,
      ),
      'resolveInvitedSigner' => 
      array (
        'name' => 'resolveInvitedSigner',
        'parameters' => 
        array (
          'email' => 
          array (
            'name' => 'email',
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
            'startLine' => 384,
            'endLine' => 384,
            'startColumn' => 40,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 384,
            'endLine' => 384,
            'startColumn' => 55,
            'endColumn' => 78,
            'parameterIndex' => 1,
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
 * Resolve the invited signerRecord for the given email + target request.
 *
 * The anti-IDOR boundary (REQ-DDPSA-004): a row is returned ONLY when a
 * `signerRecord` exists whose `email` equals the verified assertion email
 * AND whose `signingRequestId` equals the target. Wrong email, wrong
 * request, or a non-existent request id all return null identically.
 *
 * @param string $email The verified assertion\'s signer email.
 * @param string $signingRequestId The client-supplied (opaque) target request id.
 *
 * @return array<string, mixed>|null The resolved signerRecord, or null.
 */',
        'startLine' => 384,
        'endLine' => 446,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'aliasName' => NULL,
      ),
      'normalise' => 
      array (
        'name' => 'normalise',
        'parameters' => 
        array (
          'row' => 
          array (
            'name' => 'row',
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
            'startLine' => 455,
            'endLine' => 455,
            'startColumn' => 29,
            'endColumn' => 38,
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
 * Normalise an OpenRegister result (array or ObjectEntity) to an array.
 *
 * @param mixed $row The fetched object.
 *
 * @return array<string, mixed>|null
 */',
        'startLine' => 455,
        'endLine' => 468,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'aliasName' => NULL,
      ),
      'isValidOpaqueId' => 
      array (
        'name' => 'isValidOpaqueId',
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
            'startLine' => 481,
            'endLine' => 481,
            'startColumn' => 35,
            'endColumn' => 46,
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
 * Check the client-supplied target is an opaque id — never a URL/path.
 *
 * SSRF hardening (REQ-DDPSA-004): accepts only the character set OR
 * ids/uuids actually use; anything containing a scheme, host, or path
 * separator is rejected before any lookup.
 *
 * @param mixed $value The candidate signingRequestId.
 *
 * @return bool True when the value is a safe opaque identifier.
 */',
        'startLine' => 481,
        'endLine' => 487,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'aliasName' => NULL,
      ),
      'trustAtLeast' => 
      array (
        'name' => 'trustAtLeast',
        'parameters' => 
        array (
          'trust' => 
          array (
            'name' => 'trust',
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
            'startLine' => 497,
            'endLine' => 497,
            'startColumn' => 32,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'minimum' => 
          array (
            'name' => 'minimum',
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
            'startLine' => 497,
            'endLine' => 497,
            'startColumn' => 47,
            'endColumn' => 61,
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
 * Compare a trust level against a minimum on the ordered trust scale.
 *
 * @param string $trust The candidate trust level.
 * @param string $minimum The minimum required trust level.
 *
 * @return bool True when the candidate is at or above the minimum.
 */',
        'startLine' => 497,
        'endLine' => 506,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'aliasName' => NULL,
      ),
      'forbidden' => 
      array (
        'name' => 'forbidden',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The uniform not-authorised response — identical for wrong audience,
 * insufficient trust, no signer-identifying claim, a malformed target, a
 * foreign request, and a non-existent request (no existence oracle,
 * REQ-DDPSA-004/007).
 *
 * @return JSONResponse
 */',
        'startLine' => 516,
        'endLine' => 523,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'aliasName' => NULL,
      ),
      'registerRejectedAssertion' => 
      array (
        'name' => 'registerRejectedAssertion',
        'parameters' => 
        array (
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
 * Record a rejected assertion with the brute-force throttler.
 *
 * This is the half that COUNTS. The half that ENFORCES is the
 * `#[BruteForceProtection]` attribute on sign / decline / viewDocument —
 * `BruteForceMiddleware` only calls `sleepDelayOrThrowOnMax()` when that
 * attribute is present, so registering without it writes a counter nothing
 * ever reads. Both are required; see ADR-082.
 *
 * @return void
 */',
        'startLine' => 536,
        'endLine' => 549,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'aliasName' => NULL,
      ),
      'downstreamFailure' => 
      array (
        'name' => 'downstreamFailure',
        'parameters' => 
        array (
          'context' => 
          array (
            'name' => 'context',
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
            'startLine' => 560,
            'endLine' => 560,
            'startColumn' => 37,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'exception' => 
          array (
            'name' => 'exception',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Throwable',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 560,
            'endLine' => 560,
            'startColumn' => 54,
            'endColumn' => 73,
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
            'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Relay a downstream/OpenRegister failure as 502 — never leaking
 * transport or exception internals (REQ-DDPSA-005/007).
 *
 * @param string $context Log context (which act failed).
 * @param Throwable $exception The caught failure.
 *
 * @return JSONResponse
 */',
        'startLine' => 560,
        'endLine' => 567,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\PortalSigningReceiverController',
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