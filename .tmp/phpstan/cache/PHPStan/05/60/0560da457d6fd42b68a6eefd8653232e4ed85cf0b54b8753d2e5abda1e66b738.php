<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Portal/PortalAssertionVerifier.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Portal\PortalAssertionVerifier
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-6926a912da1b853c3c61a1338f787be83ce19b612777eb465a8052f614aa216f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Portal/PortalAssertionVerifier.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Portal',
    'name' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
    'shortName' => 'PortalAssertionVerifier',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Verifies portaliq\'s `X-Portal-Subject` HS256 assertion — fail-closed.
 *
 * `verify()` returns the full claims array only when EVERY check passes and
 * null on ANY failure; it never throws and never leaks the rejection reason
 * to the caller (debug log only). The receiving controller derives ALL
 * subject scope from the returned claims — never from request parameters
 * (portal-signing-actions REQ-DDPSA-003).
 *
 * @spec openspec/specs/portal-signing-actions/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 66,
    'endLine' => 300,
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
      'HEADER' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'name' => 'HEADER',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'X-Portal-Subject\'',
          'attributes' => 
          array (
            'startLine' => 70,
            'endLine' => 70,
            'startTokenPos' => 45,
            'startFilePos' => 2771,
            'endTokenPos' => 45,
            'endFilePos' => 2788,
          ),
        ),
        'docComment' => '/**
 * The header portaliq attaches the assertion to (contract v2, A6).
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 70,
        'endLine' => 70,
        'startColumn' => 2,
        'endColumn' => 42,
      ),
      'ALG' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'name' => 'ALG',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'HS256\'',
          'attributes' => 
          array (
            'startLine' => 76,
            'endLine' => 76,
            'startTokenPos' => 58,
            'startFilePos' => 2947,
            'endTokenPos' => 58,
            'endFilePos' => 2953,
          ),
        ),
        'docComment' => '/**
 * The only accepted JWS algorithm — exact-match kills `none` and any
 * RS/ES algorithm-confusion header in one check.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 76,
        'endLine' => 76,
        'startColumn' => 2,
        'endColumn' => 29,
      ),
      'HASH_FN' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'name' => 'HASH_FN',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'sha256\'',
          'attributes' => 
          array (
            'startLine' => 81,
            'endLine' => 81,
            'startTokenPos' => 71,
            'startFilePos' => 3036,
            'endTokenPos' => 71,
            'endFilePos' => 3043,
          ),
        ),
        'docComment' => '/**
 * Hash function name passed to hash_hmac.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 81,
        'endLine' => 81,
        'startColumn' => 2,
        'endColumn' => 34,
      ),
      'ISSUER' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'name' => 'ISSUER',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'portaliq\'',
          'attributes' => 
          array (
            'startLine' => 86,
            'endLine' => 86,
            'startTokenPos' => 84,
            'startFilePos' => 3148,
            'endTokenPos' => 84,
            'endFilePos' => 3157,
          ),
        ),
        'docComment' => '/**
 * The minting edge — portaliq stamps `iss` on every assertion.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 86,
        'endLine' => 86,
        'startColumn' => 2,
        'endColumn' => 35,
      ),
      'USE_ASSERTION' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'name' => 'USE_ASSERTION',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'assertion\'',
          'attributes' => 
          array (
            'startLine' => 94,
            'endLine' => 94,
            'startTokenPos' => 97,
            'startFilePos' => 3476,
            'endTokenPos' => 97,
            'endFilePos' => 3486,
          ),
        ),
        'docComment' => '/**
 * The `use` claim value marking an X-Portal-Subject assertion. The
 * receiver-side token-confusion guard (REQ-DDPSA-007): a portal SESSION
 * token (no `use` claim) can never drive this receiver, exactly as
 * portaliq rejects assertions presented as session bearers.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 94,
        'endLine' => 94,
        'startColumn' => 2,
        'endColumn' => 43,
      ),
      'PORTALIQ_APP_ID' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'name' => 'PORTALIQ_APP_ID',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'portaliq\'',
          'attributes' => 
          array (
            'startLine' => 100,
            'endLine' => 100,
            'startTokenPos' => 110,
            'startFilePos' => 3663,
            'endTokenPos' => 110,
            'endFilePos' => 3672,
          ),
        ),
        'docComment' => '/**
 * The app id whose config carries the signing secret (portaliq\'s, NOT
 * Filinq\'s — the receiver reads the minter\'s secret).
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 100,
        'endLine' => 100,
        'startColumn' => 2,
        'endColumn' => 44,
      ),
      'SECRET_KEY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'name' => 'SECRET_KEY',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'jwt_signing_secret\'',
          'attributes' => 
          array (
            'startLine' => 105,
            'endLine' => 105,
            'startTokenPos' => 123,
            'startFilePos' => 3784,
            'endTokenPos' => 123,
            'endFilePos' => 3803,
          ),
        ),
        'docComment' => '/**
 * The portaliq app-config key holding the dedicated signing secret.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 105,
        'endLine' => 105,
        'startColumn' => 2,
        'endColumn' => 49,
      ),
      'MIN_SECRET_LENGTH' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'name' => 'MIN_SECRET_LENGTH',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '16',
          'attributes' => 
          array (
            'startLine' => 111,
            'endLine' => 111,
            'startTokenPos' => 136,
            'startFilePos' => 3971,
            'endTokenPos' => 136,
            'endFilePos' => 3972,
          ),
        ),
        'docComment' => '/**
 * Minimum usable secret length — portaliq refuses to MINT with less; the
 * receiver refuses to ACCEPT with less.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 111,
        'endLine' => 111,
        'startColumn' => 2,
        'endColumn' => 38,
      ),
      'IAT_LEEWAY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'name' => 'IAT_LEEWAY',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '60',
          'attributes' => 
          array (
            'startLine' => 118,
            'endLine' => 118,
            'startTokenPos' => 149,
            'startFilePos' => 4221,
            'endTokenPos' => 149,
            'endFilePos' => 4222,
          ),
        ),
        'docComment' => '/**
 * Tolerated clock skew (seconds) when checking `iat` is not in the
 * future. The forward is an instance-local loopback hop, so real skew is
 * zero; the leeway only absorbs same-host second boundaries.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 118,
        'endLine' => 118,
        'startColumn' => 2,
        'endColumn' => 31,
      ),
    ),
    'immediateProperties' => 
    array (
      'config' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'name' => 'config',
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
                  'name' => 'OCP\\IConfig',
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
            'startLine' => 134,
            'endLine' => 134,
            'startTokenPos' => 172,
            'startFilePos' => 4895,
            'endTokenPos' => 172,
            'endFilePos' => 4898,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 134,
        'endLine' => 134,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'secretOverride' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'name' => 'secretOverride',
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
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 135,
            'endLine' => 135,
            'startTokenPos' => 186,
            'startFilePos' => 4946,
            'endTokenPos' => 186,
            'endFilePos' => 4949,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 135,
        'endLine' => 135,
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
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'name' => 'logger',
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
                  'name' => 'Psr\\Log\\LoggerInterface',
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
            'startLine' => 136,
            'endLine' => 136,
            'startTokenPos' => 200,
            'startFilePos' => 4998,
            'endTokenPos' => 200,
            'endFilePos' => 5001,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 136,
        'endLine' => 136,
        'startColumn' => 3,
        'endColumn' => 50,
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
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 134,
                'endLine' => 134,
                'startTokenPos' => 172,
                'startFilePos' => 4895,
                'endTokenPos' => 172,
                'endFilePos' => 4898,
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
                      'name' => 'OCP\\IConfig',
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
            'startLine' => 134,
            'endLine' => 134,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'secretOverride' => 
          array (
            'name' => 'secretOverride',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 135,
                'endLine' => 135,
                'startTokenPos' => 186,
                'startFilePos' => 4946,
                'endTokenPos' => 186,
                'endFilePos' => 4949,
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 135,
            'endLine' => 135,
            'startColumn' => 3,
            'endColumn' => 49,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'logger' => 
          array (
            'name' => 'logger',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 136,
                'endLine' => 136,
                'startTokenPos' => 200,
                'startFilePos' => 4998,
                'endTokenPos' => 200,
                'endFilePos' => 5001,
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
                      'name' => 'Psr\\Log\\LoggerInterface',
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
            'startLine' => 136,
            'endLine' => 136,
            'startColumn' => 3,
            'endColumn' => 50,
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
 * Constructor.
 *
 * Auto-wireable from IConfig alone (the DI container falls back to the
 * scalar defaults). Unit tests construct it with a plain secret instead:
 * `new PortalAssertionVerifier(config: null, secretOverride: \'sixteen-chars-min\')`.
 *
 * @param IConfig|null $config The configuration source for the secret derivation.
 * @param string|null $secretOverride Plain signing secret for tests (bypasses config).
 * @param LoggerInterface|null $logger Optional logger — rejection reasons at debug level only.
 *
 * @spec openspec/specs/portal-signing-actions/spec.md
 */',
        'startLine' => 133,
        'endLine' => 139,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Portal',
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'currentClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'aliasName' => NULL,
      ),
      'verify' => 
      array (
        'name' => 'verify',
        'parameters' => 
        array (
          'jwt' => 
          array (
            'name' => 'jwt',
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
            'startColumn' => 25,
            'endColumn' => 35,
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
 * Verify an `X-Portal-Subject` assertion and return its claims.
 *
 * Fail-closed on EVERYTHING (portal-signing-actions REQ-DDPSA-002) — the
 * claims array is returned only when all of the following hold, null
 * otherwise (no exception ever escapes):
 *
 *   1. compact JWS structure — exactly three non-empty segments;
 *   2. header `alg` is exactly `HS256` (rejects `none`, case tricks, and
 *      any asymmetric algorithm-confusion attempt);
 *   3. HMAC signature matches — hash_equals, constant time;
 *   4. claims decode to a JSON object;
 *   5. `use` is exactly `assertion` (session tokens are refused);
 *   6. `iss` is exactly `portaliq`;
 *   7. `exp` present, integer, strictly in the future;
 *   8. `iat` present, integer, not in the future (60s leeway), <= exp;
 *   9. `sub` present, non-empty string — an assertion without a subject
 *      can scope nothing, so it authorises nothing.
 *
 * @param string $jwt The raw header value (compact JWT).
 *
 * @return array<string, mixed>|null The verified claims, or null.
 *
 * @spec openspec/specs/portal-signing-actions/spec.md
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity) -- one fail-closed guard
 * per attack surface on an auth boundary (ADR-005); collapsing them would
 * trade auditability for a score.
 * @SuppressWarnings(PHPMD.NPathComplexity)      -- same rationale: the
 * guards are sequential early-returns, not combinatorial paths.
 */',
        'startLine' => 172,
        'endLine' => 225,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Portal',
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'currentClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'aliasName' => NULL,
      ),
      'secret' => 
      array (
        'name' => 'secret',
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
 * Derive the signing secret — EXACT copy of portaliq\'s derivation (see
 * the class docblock). Returns null when no usable (>= 16 chars) secret
 * exists, which makes verify() fail closed.
 *
 * @return string|null
 *
 * @spec openspec/specs/portal-signing-actions/spec.md
 */',
        'startLine' => 236,
        'endLine' => 256,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Portal',
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'currentClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'aliasName' => NULL,
      ),
      'reject' => 
      array (
        'name' => 'reject',
        'parameters' => 
        array (
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
            'startLine' => 266,
            'endLine' => 266,
            'startColumn' => 26,
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
            'name' => 'null',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Fail closed — never tell the caller (or an attacker probing the
 * endpoint) WHICH check failed; debug-level log only.
 *
 * @param string $reason The rejection reason for the debug log.
 *
 * @return null
 */',
        'startLine' => 266,
        'endLine' => 272,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Portal',
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'currentClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'aliasName' => NULL,
      ),
      'b64UrlEncode' => 
      array (
        'name' => 'b64UrlEncode',
        'parameters' => 
        array (
          'bytes' => 
          array (
            'name' => 'bytes',
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
            'startLine' => 281,
            'endLine' => 281,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Base64-url encode (no padding) — mirrors portaliq\'s encoding exactly.
 *
 * @param string $bytes Raw bytes.
 *
 * @return string
 */',
        'startLine' => 281,
        'endLine' => 283,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Portal',
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'currentClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'aliasName' => NULL,
      ),
      'b64UrlDecode' => 
      array (
        'name' => 'b64UrlDecode',
        'parameters' => 
        array (
          'encoded' => 
          array (
            'name' => 'encoded',
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
            'startLine' => 292,
            'endLine' => 292,
            'startColumn' => 32,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Base64-url decode — mirrors portaliq\'s decoding exactly.
 *
 * @param string $encoded Encoded string.
 *
 * @return string Raw bytes.
 */',
        'startLine' => 292,
        'endLine' => 299,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Portal',
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
        'currentClassName' => 'OCA\\Filinq\\Portal\\PortalAssertionVerifier',
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