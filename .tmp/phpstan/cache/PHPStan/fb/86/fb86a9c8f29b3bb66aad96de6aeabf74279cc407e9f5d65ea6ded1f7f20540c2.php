<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Signing/SigningCancellationService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Signing\SigningCancellationService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-6c32f79ef9ebb9026e0182fe6a83a4efcc74d0a52dcc4ec7b6af075834c742b2',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Signing\\SigningCancellationService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Signing/SigningCancellationService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Signing',
    'name' => 'OCA\\Filinq\\Service\\Signing\\SigningCancellationService',
    'shortName' => 'SigningCancellationService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * The one place a signing request is withdrawn.
 *
 * ## Only the creator, decided 2026-08-16
 *
 * Not an app administrator. Not a holder of write access to the document.
 *
 * Write permission on a file is not authority to withdraw a legal process from
 * every signatory. The two coincide often, which is exactly what makes the
 * conflation easy and wrong. An administrator administers an application; they are
 * not a party to an agreement between a requester and its signatories.
 *
 * **The accepted consequence:** a creator who has left the organisation
 * permanently blocks cancellation of their requests. There is no in-app override,
 * deliberately. The refusal names the creator so a blocked user knows who to ask
 * rather than concluding the feature is broken. An "absent creator" escape hatch is
 * a separate change with its own authorisation argument — adding one here on
 * operational grounds is how the administrator path returns through the back door.
 *
 * ## Order of checks is load-bearing
 *
 * Authorisation runs before the provider is contacted (so no partial cancellation)
 * AND before the request id is resolved — otherwise an unauthorised caller could
 * distinguish "no such request" from "not allowed" and enumerate valid ids from the
 * error text.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Signing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://filinq.app
 *
 * @spec openspec/changes/signing-cancellation/specs/signing-cancellation/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 72,
    'endLine' => 258,
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
      'providers' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningCancellationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningCancellationService',
        'name' => 'providers',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Signing\\SigningProviderFactory',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 84,
        'endLine' => 84,
        'startColumn' => 3,
        'endColumn' => 52,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'requests' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningCancellationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningCancellationService',
        'name' => 'requests',
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
        'startLine' => 85,
        'endLine' => 85,
        'startColumn' => 3,
        'endColumn' => 43,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningCancellationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningCancellationService',
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
        'startLine' => 86,
        'endLine' => 86,
        'startColumn' => 3,
        'endColumn' => 42,
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
          'providers' => 
          array (
            'name' => 'providers',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Signing\\SigningProviderFactory',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 84,
            'endLine' => 84,
            'startColumn' => 3,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'requests' => 
          array (
            'name' => 'requests',
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
            'startLine' => 85,
            'endLine' => 85,
            'startColumn' => 3,
            'endColumn' => 43,
            'parameterIndex' => 1,
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
            'startLine' => 86,
            'endLine' => 86,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 2,
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
 * @param SigningProviderFactory $providers Resolves the configured provider.
 * @param SigningService $requests Signing request lookup.
 * @param LoggerInterface $logger The logger.
 *
 * @return void
 */',
        'startLine' => 83,
        'endLine' => 88,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningCancellationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningCancellationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningCancellationService',
        'aliasName' => NULL,
      ),
      'cancel' => 
      array (
        'name' => 'cancel',
        'parameters' => 
        array (
          'uid' => 
          array (
            'name' => 'uid',
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
            'startColumn' => 25,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 111,
            'endLine' => 111,
            'startColumn' => 38,
            'endColumn' => 54,
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
 * Withdraw a signing request.
 *
 * @param string $uid The acting user id.
 * @param string $requestId The signing request id.
 *
 * @return array{requestId: string, status: string, alreadyCancelled: bool}
 *
 * @throws RuntimeException When the actor is not the creator, or the withdrawal fails.
 *
 * @spec openspec/changes/signing-cancellation/specs/signing-cancellation/spec.md
 *
 * @orphaned-write-capability exclude shipped ahead of its caller. The
 * HTTP route signing#cancelRequest still runs the older path
 * (SigningService::cancelRequest) with its authorization inline in the
 * controller, so nothing reaches this service yet. Rewiring is NOT
 * mechanical and is deliberately not done here: this service refuses
 * every non-creator, while the controller currently lets an ADMIN cancel
 * any request, so swapping them silently removes an admin capability.
 * Tracked in filinq#628.
 */',
        'startLine' => 111,
        'endLine' => 184,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningCancellationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningCancellationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningCancellationService',
        'aliasName' => NULL,
      ),
      'isCreator' => 
      array (
        'name' => 'isCreator',
        'parameters' => 
        array (
          'uid' => 
          array (
            'name' => 'uid',
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
            'startLine' => 198,
            'endLine' => 198,
            'startColumn' => 29,
            'endColumn' => 39,
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
            'startLine' => 198,
            'endLine' => 198,
            'startColumn' => 42,
            'endColumn' => 55,
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
 * Whether the actor created this request.
 *
 * The ONLY authorisation predicate. Kept as one method so a second caller
 * cannot introduce a second, laxer rule — and so that widening it is a visible
 * edit to a named thing rather than a condition drifting at a call site.
 *
 * @param string $uid The acting user id.
 * @param array $request The signing request.
 *
 * @return bool True when the actor is the creator.
 */',
        'startLine' => 198,
        'endLine' => 202,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningCancellationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningCancellationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningCancellationService',
        'aliasName' => NULL,
      ),
      'refusalMessage' => 
      array (
        'name' => 'refusalMessage',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
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
            'startLine' => 216,
            'endLine' => 216,
            'startColumn' => 34,
            'endColumn' => 48,
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
 * Build the refusal.
 *
 * Names the creator when there is one. A bare "not permitted" leaves the user
 * concluding the feature is broken, when what they need is to know who to ask —
 * which matters more here than usual, because an absent creator blocks
 * cancellation permanently and by design.
 *
 * @param array|null $request The request, or null when it does not exist.
 *
 * @return string The refusal message.
 */',
        'startLine' => 216,
        'endLine' => 231,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningCancellationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningCancellationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningCancellationService',
        'aliasName' => NULL,
      ),
      'recordAttempt' => 
      array (
        'name' => 'recordAttempt',
        'parameters' => 
        array (
          'uid' => 
          array (
            'name' => 'uid',
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
            'startLine' => 246,
            'endLine' => 246,
            'startColumn' => 33,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 246,
            'endLine' => 246,
            'startColumn' => 46,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'outcome' => 
          array (
            'name' => 'outcome',
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
            'startLine' => 246,
            'endLine' => 246,
            'startColumn' => 65,
            'endColumn' => 79,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'detail' => 
          array (
            'name' => 'detail',
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
            'startLine' => 246,
            'endLine' => 246,
            'startColumn' => 82,
            'endColumn' => 95,
            'parameterIndex' => 3,
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
 * Record an attempt and its outcome.
 *
 * Every attempt, not only the successes. A withdrawn signing process is exactly
 * the event someone will later need to reconstruct.
 *
 * @param string $uid The acting user id.
 * @param string $requestId The signing request id.
 * @param string $outcome What happened.
 * @param string $detail Why, when it failed.
 *
 * @return void
 */',
        'startLine' => 246,
        'endLine' => 257,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningCancellationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningCancellationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\SigningCancellationService',
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