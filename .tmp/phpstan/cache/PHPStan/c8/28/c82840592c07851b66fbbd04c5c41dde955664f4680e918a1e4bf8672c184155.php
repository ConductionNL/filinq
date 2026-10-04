<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/ProhibitionGateService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\ProhibitionGateService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-d7b0dae64166ea1eba6ecec2410c7ee69e12ad66c2a1bd760410a0f22860c783',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/ProhibitionGateService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
    'shortName' => 'ProhibitionGateService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Enforces the publication-prohibition gate before an anonymise call.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 53,
    'endLine' => 614,
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
      'DEFAULT_HIGH_CONFIDENCE_THRESHOLD' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'name' => 'DEFAULT_HIGH_CONFIDENCE_THRESHOLD',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0.85',
          'attributes' => 
          array (
            'startLine' => 59,
            'endLine' => 59,
            'startTokenPos' => 60,
            'startFilePos' => 1994,
            'endTokenPos' => 60,
            'endFilePos' => 1997,
          ),
        ),
        'docComment' => '/**
 * Default prohibition high-confidence threshold (inclusive)
 *
 * @var float
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 59,
        'endLine' => 59,
        'startColumn' => 2,
        'endColumn' => 56,
      ),
      'HIGH_CONFIDENCE_THRESHOLD_KEY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'name' => 'HIGH_CONFIDENCE_THRESHOLD_KEY',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'prohibition.high_confidence_threshold\'',
          'attributes' => 
          array (
            'startLine' => 66,
            'endLine' => 66,
            'startTokenPos' => 73,
            'startFilePos' => 2131,
            'endTokenPos' => 73,
            'endFilePos' => 2169,
          ),
        ),
        'docComment' => '/**
 * App config key for the high-confidence threshold
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 66,
        'endLine' => 66,
        'startColumn' => 2,
        'endColumn' => 87,
      ),
      'FAIL_CLOSED_KEY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'name' => 'FAIL_CLOSED_KEY',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'prohibition.fail_closed\'',
          'attributes' => 
          array (
            'startLine' => 84,
            'endLine' => 84,
            'startTokenPos' => 86,
            'startFilePos' => 2860,
            'endTokenPos' => 86,
            'endFilePos' => 2884,
          ),
        ),
        'docComment' => '/**
 * App config key controlling the gate\'s fail-mode for backend errors.
 *
 * When `true` (default) any backend error inside the prohibition gate
 * (PolicyMatchService unavailable, EntityRelationMapper lookup throws,
 * per-entity matchProhibition throws) is treated as gate-firing: the
 * call is rejected via ProhibitionGateException. This is the safety-
 * critical default for a gate protecting witness/undercover-officer
 * identities — silent fail-open would let any service outage disable
 * the gate.
 *
 * Set to `false` to opt into the legacy fail-open behaviour for non-
 * production environments.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 84,
        'endLine' => 84,
        'startColumn' => 2,
        'endColumn' => 59,
      ),
      'DEFAULT_FAIL_CLOSED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'name' => 'DEFAULT_FAIL_CLOSED',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => 'true',
          'attributes' => 
          array (
            'startLine' => 91,
            'endLine' => 91,
            'startTokenPos' => 99,
            'startFilePos' => 2991,
            'endTokenPos' => 99,
            'endFilePos' => 2994,
          ),
        ),
        'docComment' => '/**
 * Default for the fail-closed flag.
 *
 * @var bool
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 91,
        'endLine' => 91,
        'startColumn' => 2,
        'endColumn' => 42,
      ),
    ),
    'immediateProperties' => 
    array (
      'committer' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'name' => 'committer',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\ProhibitionOverrideCommitter',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Writes the audit entry + OpenRegister skip flag for released overrides.
 *
 * @var ProhibitionOverrideCommitter
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 98,
        'endLine' => 98,
        'startColumn' => 2,
        'endColumn' => 58,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
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
        'startLine' => 111,
        'endLine' => 111,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'appConfig' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'name' => 'appConfig',
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
        'startLine' => 112,
        'endLine' => 112,
        'startColumn' => 3,
        'endColumn' => 40,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'container' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'name' => 'container',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Psr\\Container\\ContainerInterface',
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
        'endColumn' => 48,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'locator' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'name' => 'locator',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\OpenRegisterServiceLocator',
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
        'endColumn' => 54,
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
            'startLine' => 111,
            'endLine' => 111,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'appConfig' => 
          array (
            'name' => 'appConfig',
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
            'startLine' => 112,
            'endLine' => 112,
            'startColumn' => 3,
            'endColumn' => 40,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'container' => 
          array (
            'name' => 'container',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Psr\\Container\\ContainerInterface',
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
            'endColumn' => 48,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'locator' => 
          array (
            'name' => 'locator',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\OpenRegisterServiceLocator',
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
            'endColumn' => 54,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor for ProhibitionGateService
 *
 * @param LoggerInterface $logger Logger for gate decisions and backend outages.
 * @param IAppConfig $appConfig App configuration for threshold + fail-mode settings.
 * @param ContainerInterface $container Container the PolicyMatchService is resolved from.
 * @param OpenRegisterServiceLocator $locator Resolver for OpenRegister services and mappers.
 *
 * @return void
 */',
        'startLine' => 110,
        'endLine' => 121,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'aliasName' => NULL,
      ),
      'run' => 
      array (
        'name' => 'run',
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
            'startLine' => 146,
            'endLine' => 146,
            'startColumn' => 22,
            'endColumn' => 32,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'requestEntities' => 
          array (
            'name' => 'requestEntities',
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
            'startLine' => 146,
            'endLine' => 146,
            'startColumn' => 35,
            'endColumn' => 56,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'overrides' => 
          array (
            'name' => 'overrides',
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
            'startLine' => 146,
            'endLine' => 146,
            'startColumn' => 59,
            'endColumn' => 74,
            'parameterIndex' => 2,
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
            'startLine' => 146,
            'endLine' => 146,
            'startColumn' => 77,
            'endColumn' => 90,
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
 * Run the prohibition gate before forwarding to OpenRegister.
 *
 * Resolves detected entities for the file, matches each against active
 * prohibition rules, validates overrides, checks that high-confidence
 * matches are present in the to-be-anonymised set, and commits validated
 * overrides (Filinq audit entry + OR PATCH). Throws
 * ProhibitionGateException when the gate blocks the call.
 *
 * @param int $fileId Nextcloud file ID.
 * @param array<int, array<string, mixed>> $requestEntities User-submitted entities[] to anonymize.
 * @param array<int, array<string, mixed>> $overrides Override entries {ruleId, entityId, reason?}.
 * @param string $userId UID of the acting user.
 *
 * @return void
 *
 * @throws ProhibitionGateException When the gate blocks the call.
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-4
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-7
 */',
        'startLine' => 146,
        'endLine' => 201,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'aliasName' => NULL,
      ),
      'getHighConfidenceThreshold' => 
      array (
        'name' => 'getHighConfidenceThreshold',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'float',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Read the high-confidence threshold from app config.
 *
 * Default 0.85; configurable via filinq.prohibition.high_confidence_threshold.
 *
 * @return float Threshold value (inclusive boundary).
 *
 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-7
 */',
        'startLine' => 212,
        'endLine' => 219,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'aliasName' => NULL,
      ),
      'isFailClosed' => 
      array (
        'name' => 'isFailClosed',
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
 * Read the fail-closed flag from app config.
 *
 * Defaults to TRUE — the gate fails closed by default for any backend
 * outage path. Operators can flip to false for non-production via
 * filinq.prohibition.fail_closed.
 *
 * @return bool True when a backend outage must reject the call.
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
 */',
        'startLine' => 232,
        'endLine' => 239,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'aliasName' => NULL,
      ),
      'resolvePolicyService' => 
      array (
        'name' => 'resolvePolicyService',
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
            'startLine' => 258,
            'endLine' => 258,
            'startColumn' => 40,
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
            'name' => 'mixed',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolve PolicyMatchService, honouring the configured fail-mode.
 *
 * PolicyMatchService not available — fail-CLOSED by default for a
 * privacy-critical safety gate. Silent fail-open would let any service
 * outage disable witness/undercover-officer protection. Operators can opt
 * into legacy fail-open via filinq.prohibition.fail_closed=false in
 * non-production environments.
 *
 * @param int $fileId Nextcloud file ID (for the log context).
 *
 * @return mixed The PolicyMatchService, or null when the gate must fail open.
 *
 * @throws ProhibitionGateException When the gate is fail-closed and the service is absent.
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
 */',
        'startLine' => 258,
        'endLine' => 283,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'aliasName' => NULL,
      ),
      'loadFileEntities' => 
      array (
        'name' => 'loadFileEntities',
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
            'startLine' => 296,
            'endLine' => 296,
            'startColumn' => 36,
            'endColumn' => 46,
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
 * Load the file\'s detected entity relations, honouring the fail-mode.
 *
 * @param int $fileId Nextcloud file ID.
 *
 * @return array<int, mixed>|null The raw relation rows, or null when the gate must fail open.
 *
 * @throws ProhibitionGateException When the gate is fail-closed and the lookup fails.
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
 */',
        'startLine' => 296,
        'endLine' => 323,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'aliasName' => NULL,
      ),
      'buildProhibitionMatches' => 
      array (
        'name' => 'buildProhibitionMatches',
        'parameters' => 
        array (
          'rawEntities' => 
          array (
            'name' => 'rawEntities',
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
            'startLine' => 341,
            'endLine' => 341,
            'startColumn' => 43,
            'endColumn' => 60,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'policyService' => 
          array (
            'name' => 'policyService',
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
            'startLine' => 341,
            'endLine' => 341,
            'startColumn' => 63,
            'endColumn' => 82,
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
 * Build prohibition matches from raw EntityRelation data.
 *
 * For each raw entity, calls PolicyMatchService::matchProhibition and
 * collects matches into a structured list.
 *
 * @param array<int, mixed> $rawEntities Raw EntityRelation rows from findEntitiesForFile.
 * @param mixed $policyService PolicyMatchService instance.
 *
 * @return array<int, array<string, mixed>> Match entries with ruleId, ruleName, entityId,
 *                                          entityRelationId, confidence, entityValue.
 *
 * @throws ProhibitionGateException When fail-closed and a per-entity match throws.
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
 */',
        'startLine' => 341,
        'endLine' => 381,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'aliasName' => NULL,
      ),
      'matchOrEscalate' => 
      array (
        'name' => 'matchOrEscalate',
        'parameters' => 
        array (
          'policyService' => 
          array (
            'name' => 'policyService',
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
            'startLine' => 403,
            'endLine' => 403,
            'startColumn' => 3,
            'endColumn' => 22,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'entityType' => 
          array (
            'name' => 'entityType',
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
            'startLine' => 404,
            'endLine' => 404,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'entityValue' => 
          array (
            'name' => 'entityValue',
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
            'startLine' => 405,
            'endLine' => 405,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'entityId' => 
          array (
            'name' => 'entityId',
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
            'startLine' => 406,
            'endLine' => 406,
            'startColumn' => 3,
            'endColumn' => 15,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'failClosed' => 
          array (
            'name' => 'failClosed',
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
            'startLine' => 407,
            'endLine' => 407,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 4,
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
 * Match one entity against the prohibition rules, escalating when fail-closed.
 *
 * A per-entity match failure under fail-closed is escalated so run() surfaces
 * a 422/503 rather than silently skipping the entity (which would allow the
 * anonymise call to proceed without a check).
 *
 * @param mixed $policyService PolicyMatchService instance.
 * @param string $entityType The occurrence\'s entity type.
 * @param string $entityValue The occurrence\'s detected text.
 * @param int $entityId The global entity id (for the log context).
 * @param bool $failClosed Whether a backend error must reject the call.
 *
 * @return array<string, mixed>|null The rule match, or null when there is none.
 *
 * @throws ProhibitionGateException When fail-closed and the match throws.
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
 */',
        'startLine' => 402,
        'endLine' => 439,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'aliasName' => NULL,
      ),
      'splitOverrides' => 
      array (
        'name' => 'splitOverrides',
        'parameters' => 
        array (
          'matches' => 
          array (
            'name' => 'matches',
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
            'startLine' => 457,
            'endLine' => 457,
            'startColumn' => 34,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'overrides' => 
          array (
            'name' => 'overrides',
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
            'startLine' => 457,
            'endLine' => 457,
            'startColumn' => 50,
            'endColumn' => 65,
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
 * Split acknowledged overrides into released and rejected sets.
 *
 * A non-matching (ruleId, entityId) combination is silently ignored; an
 * override on a high-confidence match is rejected; an override on a
 * low-confidence match releases that match.
 *
 * @param array<int, array<string, mixed>> $matches Prohibition matches for the file.
 * @param array<int, array<string, mixed>> $overrides Override entries {ruleId, entityId, reason?}.
 *
 * @return array{released: array<string, array<string, mixed>>,
 *               rejected: array<int, array<string, mixed>>} Released overrides keyed by
 *                                                           \'ruleId|entityId\', plus the rejections.
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
 */',
        'startLine' => 457,
        'endLine' => 495,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'aliasName' => NULL,
      ),
      'findMatch' => 
      array (
        'name' => 'findMatch',
        'parameters' => 
        array (
          'matches' => 
          array (
            'name' => 'matches',
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
            'startLine' => 508,
            'endLine' => 508,
            'startColumn' => 29,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'ruleId' => 
          array (
            'name' => 'ruleId',
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
            'startLine' => 508,
            'endLine' => 508,
            'startColumn' => 45,
            'endColumn' => 58,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'entityId' => 
          array (
            'name' => 'entityId',
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
            'startLine' => 508,
            'endLine' => 508,
            'startColumn' => 61,
            'endColumn' => 73,
            'parameterIndex' => 2,
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
 * Find the prohibition match for a (ruleId, entityId) pair.
 *
 * @param array<int, array<string, mixed>> $matches Prohibition matches for the file.
 * @param string $ruleId The rule id being looked up.
 * @param int $entityId The entity id being looked up.
 *
 * @return array<string, mixed>|null The match, or null when the pair does not occur.
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
 */',
        'startLine' => 508,
        'endLine' => 516,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'aliasName' => NULL,
      ),
      'collectMissingMatches' => 
      array (
        'name' => 'collectMissingMatches',
        'parameters' => 
        array (
          'matches' => 
          array (
            'name' => 'matches',
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
            'startLine' => 529,
            'endLine' => 529,
            'startColumn' => 41,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'released' => 
          array (
            'name' => 'released',
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
            'startLine' => 529,
            'endLine' => 529,
            'startColumn' => 57,
            'endColumn' => 71,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'requestEntities' => 
          array (
            'name' => 'requestEntities',
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
            'startLine' => 529,
            'endLine' => 529,
            'startColumn' => 74,
            'endColumn' => 95,
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
 * Identify high-confidence matches missing from the to-be-anonymised set.
 *
 * @param array<int, array<string, mixed>> $matches Prohibition matches for the file.
 * @param array<string, array<string, mixed>> $released Overrides released by splitOverrides().
 * @param array<int, array<string, mixed>> $requestEntities User-submitted entities[].
 *
 * @return array<int, array<string, mixed>> Missing entries for the 422 body.
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-4
 */',
        'startLine' => 529,
        'endLine' => 572,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'aliasName' => NULL,
      ),
      'tryGetEntityCanonicalName' => 
      array (
        'name' => 'tryGetEntityCanonicalName',
        'parameters' => 
        array (
          'entityId' => 
          array (
            'name' => 'entityId',
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
            'startLine' => 587,
            'endLine' => 587,
            'startColumn' => 45,
            'endColumn' => 57,
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
 * Try to get the canonical name of an OR Entity record.
 *
 * Best-effort: returns empty string when OR is unavailable or the entity
 * has no canonical name field. The gate falls back to the detected text
 * when this returns empty.
 *
 * @param int $entityId OR Entity record ID.
 *
 * @return string Canonical name, or empty string on failure.
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-4
 */',
        'startLine' => 587,
        'endLine' => 613,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
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