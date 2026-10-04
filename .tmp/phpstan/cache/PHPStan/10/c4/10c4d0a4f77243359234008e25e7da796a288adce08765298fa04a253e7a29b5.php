<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/ProhibitionPolicyService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\ProhibitionPolicyService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-756a1279448c7da5e5db642c8e48595e74f92e116e10c5e9b7f41abb0b6bae07',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/ProhibitionPolicyService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
    'shortName' => 'ProhibitionPolicyService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Applies publication policy to detected entities and guards skip decisions.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-5
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 51,
    'endLine' => 438,
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
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
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
        'startLine' => 72,
        'endLine' => 72,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'container' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
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
        'startLine' => 73,
        'endLine' => 73,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
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
        'startLine' => 74,
        'endLine' => 74,
        'startColumn' => 3,
        'endColumn' => 54,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'gate' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'name' => 'gate',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
            'isIdentifier' => false,
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
        'endColumn' => 47,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'skipDecisions' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'name' => 'skipDecisions',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\RelationSkipDecisionService',
            'isIdentifier' => false,
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
        'endColumn' => 61,
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
            'startLine' => 72,
            'endLine' => 72,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 0,
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
            'startLine' => 73,
            'endLine' => 73,
            'startColumn' => 3,
            'endColumn' => 48,
            'parameterIndex' => 1,
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
            'startLine' => 74,
            'endLine' => 74,
            'startColumn' => 3,
            'endColumn' => 54,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'gate' => 
          array (
            'name' => 'gate',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\ProhibitionGateService',
                'isIdentifier' => false,
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
            'endColumn' => 47,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'skipDecisions' => 
          array (
            'name' => 'skipDecisions',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\RelationSkipDecisionService',
                'isIdentifier' => false,
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
            'endColumn' => 61,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor for ProhibitionPolicyService
 *
 * `$skipDecisions` is INJECTED rather than constructed here. It used to be
 * `new`ed in this constructor, which meant every dependency it needed had to
 * be threaded through this class as well — adding its authorisation
 * collaborators (IUserSession + IRootFolder) that way pushed this class over
 * PHPMD\'s CouplingBetweenObjects limit for dependencies it never uses.
 * Letting the container build it keeps that coupling where it belongs.
 *
 * @param LoggerInterface $logger Logger for best-effort policy failures.
 * @param ContainerInterface $container Container the PolicyMatchService is resolved from.
 * @param OpenRegisterServiceLocator $locator Resolver for OpenRegister services and mappers.
 * @param ProhibitionGateService $gate The gate that runs before any OpenRegister
 *                                     interaction on an anonymise call.
 * @param RelationSkipDecisionService $skipDecisions Guard + apply for the per-relation skip decision.
 *
 * @return void
 */',
        'startLine' => 71,
        'endLine' => 78,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'aliasName' => NULL,
      ),
      'runGate' => 
      array (
        'name' => 'runGate',
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
            'startLine' => 94,
            'endLine' => 94,
            'startColumn' => 26,
            'endColumn' => 36,
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
            'startLine' => 94,
            'endLine' => 94,
            'startColumn' => 39,
            'endColumn' => 60,
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
            'startLine' => 94,
            'endLine' => 94,
            'startColumn' => 63,
            'endColumn' => 78,
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
            'startLine' => 94,
            'endLine' => 94,
            'startColumn' => 81,
            'endColumn' => 94,
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
 * Run the publication-prohibition gate for an anonymise call.
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
 */',
        'startLine' => 94,
        'endLine' => 102,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'aliasName' => NULL,
      ),
      'applyPolicyDecisions' => 
      array (
        'name' => 'applyPolicyDecisions',
        'parameters' => 
        array (
          'entities' => 
          array (
            'name' => 'entities',
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
            'startLine' => 124,
            'endLine' => 124,
            'startColumn' => 39,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'entityRelationMapper' => 
          array (
            'name' => 'entityRelationMapper',
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
            'startLine' => 124,
            'endLine' => 124,
            'startColumn' => 56,
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
 * Apply publication policy to freshly-detected, normalized entities.
 *
 * Runs `PolicyMatchService::match()` (prohibition precedence) per entity:
 *  - a standing-consent winner is auto-skipped (`skip_anonymization = true`
 *    on the relation, via OpenRegister) unless it is already skipped;
 *  - a prohibition winner gets a read-only `prohibitionMatch`
 *    (`{ruleId, ruleName, highConfidence}`) for the review UI and is never
 *    auto-skipped.
 *
 * Every returned entity gains a `prohibitionMatch` key (null when none).
 * Best-effort: policy failures are logged and never block detection.
 *
 * @param array<int, array<string, mixed>> $entities Normalized entities.
 * @param mixed $entityRelationMapper OpenRegister EntityRelationMapper (DI).
 *
 * @return array<int, array<string, mixed>> Entities with `prohibitionMatch` attached.
 *
 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-7
 */',
        'startLine' => 124,
        'endLine' => 146,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'aliasName' => NULL,
      ),
      'applyRelationSkipDecision' => 
      array (
        'name' => 'applyRelationSkipDecision',
        'parameters' => 
        array (
          'relationId' => 
          array (
            'name' => 'relationId',
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
            'startLine' => 160,
            'endLine' => 160,
            'startColumn' => 44,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'skip' => 
          array (
            'name' => 'skip',
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
            'startLine' => 160,
            'endLine' => 160,
            'startColumn' => 61,
            'endColumn' => 70,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'bases' => 
          array (
            'name' => 'bases',
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
            'startLine' => 160,
            'endLine' => 160,
            'startColumn' => 73,
            'endColumn' => 85,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'force' => 
          array (
            'name' => 'force',
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
            'startLine' => 160,
            'endLine' => 160,
            'startColumn' => 88,
            'endColumn' => 98,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Guard + apply a per-relation skip/include decision from the review UI.
 *
 * @param int $relationId The EntityRelation id.
 * @param bool $skip The requested skipAnonymization value.
 * @param array|null $bases Optional bases to set alongside the decision.
 * @param bool $force Release a sub-threshold prohibition match.
 *
 * @return array{status: 200|404|422, body: array<string, mixed>} HTTP status + response body.
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
 */',
        'startLine' => 160,
        'endLine' => 168,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'aliasName' => NULL,
      ),
      'absoluteProhibitionViolations' => 
      array (
        'name' => 'absoluteProhibitionViolations',
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
            'startLine' => 188,
            'endLine' => 188,
            'startColumn' => 48,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Defence-in-depth backstop: absolute prohibition matches left un-redacted.
 *
 * OpenRegister\'s generic relation PATCH stays open, so a caller could skip a
 * prohibited relation directly, bypassing the Filinq skip endpoint. Before
 * redaction, this returns any prohibition-matched occurrence at confidence
 * >= threshold that is being left un-redacted (skipped). Only the absolute
 * tier is enforced here — the primary decision-time guard covers the rest.
 *
 * "Skipped" = detected for the file but absent from the anonymise set
 * (`findEntitiesForAnonymization`, which already excludes skipAnonymization).
 *
 * @param int $fileId The Nextcloud file id.
 *
 * @return array<int, array<string, mixed>> Absolute-tier violations (may be empty).
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-7
 */',
        'startLine' => 188,
        'endLine' => 239,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'aliasName' => NULL,
      ),
      'checkUnredactedProhibitions' => 
      array (
        'name' => 'checkUnredactedProhibitions',
        'parameters' => 
        array (
          'unredactedEntities' => 
          array (
            'name' => 'unredactedEntities',
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
            'startColumn' => 46,
            'endColumn' => 70,
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
 * Check unredacted entities against publication-prohibition rules.
 *
 * Returns an array of violation records (one per matching entity).
 * An empty array means no violations — all entries may proceed to consent creation.
 * Uses PolicyMatchService at any confidence (operator made an explicit decision;
 * the 0.85-threshold logic of the regular gate does NOT apply here — D2).
 *
 * @param array<int, array<string, mixed>> $unredactedEntities Entries from the unredactedEntities[] payload field.
 *
 * @return array<int, array<string, mixed>> Violation records: [{entityId, entityText, ruleId, ruleName}]
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-2
 */',
        'startLine' => 255,
        'endLine' => 289,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'aliasName' => NULL,
      ),
      'tryGetPolicyMatchService' => 
      array (
        'name' => 'tryGetPolicyMatchService',
        'parameters' => 
        array (
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
 * Try to get PolicyMatchService from the container without throwing.
 *
 * Returns null when the service is not registered (before anonymisation-prohibition-gate lands).
 *
 * @return mixed PolicyMatchService instance or null.
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-2
 */',
        'startLine' => 300,
        'endLine' => 307,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'aliasName' => NULL,
      ),
      'resolvePolicyMatcher' => 
      array (
        'name' => 'resolvePolicyMatcher',
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
 * Resolve the policy matcher together with its high-confidence threshold.
 *
 * Both the container lookup and the threshold read share one guard, so a
 * failure in either degrades the whole policy pass to a no-op rather than
 * bubbling out of detection.
 *
 * @return array{matcher: mixed, threshold: float}|null The matcher + threshold, or null when
 *                                                      the policy pass must be skipped.
 *
 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-7
 */',
        'startLine' => 321,
        'endLine' => 336,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'aliasName' => NULL,
      ),
      'applyPolicyToEntity' => 
      array (
        'name' => 'applyPolicyToEntity',
        'parameters' => 
        array (
          'entity' => 
          array (
            'name' => 'entity',
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
            'startLine' => 349,
            'endLine' => 349,
            'startColumn' => 39,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'policy' => 
          array (
            'name' => 'policy',
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
            'startLine' => 349,
            'endLine' => 349,
            'startColumn' => 54,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'entityRelationMapper' => 
          array (
            'name' => 'entityRelationMapper',
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
            'startLine' => 349,
            'endLine' => 349,
            'startColumn' => 69,
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
 * Apply the policy decision for one normalized entity.
 *
 * @param array<string, mixed> $entity The normalized entity.
 * @param array{matcher: mixed, threshold: float} $policy The resolved matcher + threshold.
 * @param mixed $entityRelationMapper OpenRegister EntityRelationMapper (DI).
 *
 * @return array<string, mixed> The entity with `prohibitionMatch` (and possibly the auto-skip) applied.
 *
 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-7
 */',
        'startLine' => 349,
        'endLine' => 387,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'aliasName' => NULL,
      ),
      'autoSkipStandingConsent' => 
      array (
        'name' => 'autoSkipStandingConsent',
        'parameters' => 
        array (
          'entity' => 
          array (
            'name' => 'entity',
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
            'startLine' => 399,
            'endLine' => 399,
            'startColumn' => 43,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'entityRelationMapper' => 
          array (
            'name' => 'entityRelationMapper',
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
            'startLine' => 399,
            'endLine' => 399,
            'startColumn' => 58,
            'endColumn' => 84,
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
 * Auto-skip a standing-consent occurrence unless it is already skipped.
 *
 * @param array<string, mixed> $entity The normalized entity.
 * @param mixed $entityRelationMapper OpenRegister EntityRelationMapper (DI).
 *
 * @return array<string, mixed> The entity, with `skipAnonymization` set when the write succeeded.
 *
 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-7
 */',
        'startLine' => 399,
        'endLine' => 418,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'aliasName' => NULL,
      ),
      'collectRedactionRelationIds' => 
      array (
        'name' => 'collectRedactionRelationIds',
        'parameters' => 
        array (
          'mapper' => 
          array (
            'name' => 'mapper',
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
            'startLine' => 430,
            'endLine' => 430,
            'startColumn' => 47,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 430,
            'endLine' => 430,
            'startColumn' => 62,
            'endColumn' => 72,
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
 * Collect the relation ids that are actually going to be redacted.
 *
 * @param mixed $mapper OpenRegister EntityRelationMapper.
 * @param int $fileId The Nextcloud file id.
 *
 * @return array<int, bool> Relation ids present in the anonymise set, keyed for isset() lookup.
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-7
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
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