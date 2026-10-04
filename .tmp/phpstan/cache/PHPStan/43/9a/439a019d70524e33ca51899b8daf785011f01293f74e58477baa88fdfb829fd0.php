<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/EntityConsolidationService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\EntityConsolidationService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-e72b9c35b4fb572729ef959746e432e4eb4ac80a3c6349e66d05448f059cbeda',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/EntityConsolidationService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
    'shortName' => 'EntityConsolidationService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Consolidates per-file entity detections into a unified batch-level list.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-1
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 46,
    'endLine' => 279,
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
      'HIGH_CONFIDENCE_THRESHOLD_KEY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'name' => 'HIGH_CONFIDENCE_THRESHOLD_KEY',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'prohibition.high_confidence_threshold\'',
          'attributes' => 
          array (
            'startLine' => 52,
            'endLine' => 52,
            'startTokenPos' => 60,
            'startFilePos' => 1812,
            'endTokenPos' => 60,
            'endFilePos' => 1850,
          ),
        ),
        'docComment' => '/**
 * App config key for the high-confidence threshold.
 *
 * Mirrors AnonymizationService::HIGH_CONFIDENCE_THRESHOLD_KEY.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 52,
        'endLine' => 52,
        'startColumn' => 2,
        'endColumn' => 87,
      ),
      'DEFAULT_HIGH_CONFIDENCE_THRESHOLD' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'name' => 'DEFAULT_HIGH_CONFIDENCE_THRESHOLD',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0.85',
          'attributes' => 
          array (
            'startLine' => 57,
            'endLine' => 57,
            'startTokenPos' => 73,
            'startFilePos' => 1965,
            'endTokenPos' => 73,
            'endFilePos' => 1968,
          ),
        ),
        'docComment' => '/**
 * Default threshold value (inclusive boundary).
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 2,
        'endColumn' => 56,
      ),
    ),
    'immediateProperties' => 
    array (
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
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
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'wooProfile' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'name' => 'wooProfile',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\WooProfileService',
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
        'endColumn' => 48,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'appManager' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'name' => 'appManager',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\App\\IAppManager',
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
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
        'startLine' => 76,
        'endLine' => 76,
        'startColumn' => 3,
        'endColumn' => 48,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'policyMatch' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'name' => 'policyMatch',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\PolicyMatchService',
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
        'endColumn' => 50,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'basesResolver' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'name' => 'basesResolver',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\BasesResolverService',
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
        'endColumn' => 54,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'appConfig' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
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
        'startLine' => 79,
        'endLine' => 79,
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
            'startLine' => 73,
            'endLine' => 73,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'wooProfile' => 
          array (
            'name' => 'wooProfile',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\WooProfileService',
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
            'endColumn' => 48,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'appManager' => 
          array (
            'name' => 'appManager',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\App\\IAppManager',
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
            'endColumn' => 42,
            'parameterIndex' => 2,
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
            'startLine' => 76,
            'endLine' => 76,
            'startColumn' => 3,
            'endColumn' => 48,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'policyMatch' => 
          array (
            'name' => 'policyMatch',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\PolicyMatchService',
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
            'endColumn' => 50,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'basesResolver' => 
          array (
            'name' => 'basesResolver',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\BasesResolverService',
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
            'endColumn' => 54,
            'parameterIndex' => 5,
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
            'startLine' => 79,
            'endLine' => 79,
            'startColumn' => 3,
            'endColumn' => 40,
            'parameterIndex' => 6,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor for EntityConsolidationService
 *
 * @param LoggerInterface $logger Logger for error reporting.
 * @param WooProfileService $wooProfile Profile service describing which entity types to anonymize.
 * @param IAppManager $appManager App manager used to check for OpenRegister availability.
 * @param ContainerInterface $container DI container used to resolve OpenRegister mappers at runtime.
 * @param PolicyMatchService $policyMatch Policy matcher for per-entity prohibition lookups.
 * @param BasesResolverService $basesResolver Resolver for suggested dossier bases.
 * @param IAppConfig $appConfig Tenant configuration (high-confidence threshold).
 *
 * @return void
 */',
        'startLine' => 72,
        'endLine' => 82,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'aliasName' => NULL,
      ),
      'consolidateEntities' => 
      array (
        'name' => 'consolidateEntities',
        'parameters' => 
        array (
          'batch' => 
          array (
            'name' => 'batch',
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
            'startLine' => 108,
            'endLine' => 108,
            'startColumn' => 38,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'minConfidence' => 
          array (
            'name' => 'minConfidence',
            'default' => 
            array (
              'code' => '0.0',
              'attributes' => 
              array (
                'startLine' => 108,
                'endLine' => 108,
                'startTokenPos' => 174,
                'startFilePos' => 4433,
                'endTokenPos' => 174,
                'endFilePos' => 4435,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'float',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 108,
            'endLine' => 108,
            'startColumn' => 52,
            'endColumn' => 77,
            'parameterIndex' => 1,
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
 * Consolidate entity detections across every extracted file in a batch.
 *
 * Entities below the supplied confidence threshold are kept in the
 * result but flagged as `included => false` so the UI can still show
 * them for manual review.
 *
 * Each returned entity also carries:
 *   - `prohibitionMatch` — `null` or `{ruleId, ruleName, highConfidence}`.
 *   - `suggestedBases`   — array of dossier-derived Woo Art. 5 grondslag
 *                          UUIDs/slugs (empty when no dossier is found).
 *
 * Both additions are non-breaking (strict superset of the pre-change shape).
 *
 * @param array<string, mixed> $batch Batch record whose file list should be consolidated.
 * @param float $minConfidence Minimum confidence required for an entity to be included by default.
 *
 * @return array<int, array<string, mixed>> Consolidated, confidence-sorted list of entities.
 *
 * @spec openspec/specs/anonymization-entity-review/spec.md#requirement-consolidated-entity-list-endpoint
 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-1
 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-2
 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-3
 */',
        'startLine' => 108,
        'endLine' => 142,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'aliasName' => NULL,
      ),
      'mergeEntity' => 
      array (
        'name' => 'mergeEntity',
        'parameters' => 
        array (
          'map' => 
          array (
            'name' => 'map',
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
            'startLine' => 157,
            'endLine' => 157,
            'startColumn' => 31,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'entity' => 
          array (
            'name' => 'entity',
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
            'startLine' => 157,
            'endLine' => 157,
            'startColumn' => 43,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Merge a single entity detection into the running consolidation map.
 *
 * Entries are keyed by lower-cased entity value. Duplicate detections
 * bump the file count and keep the highest confidence seen so far.
 *
 * @param array<string, array<string, mixed>> $map Running consolidation map keyed by lower-cased value.
 * @param mixed $entity Raw entity detection (object or array-like).
 *
 * @return array<string, array<string, mixed>> Updated consolidation map.
 *
 * @spec openspec/specs/anonymization-entity-review/spec.md#requirement-consolidated-entity-list-endpoint
 */',
        'startLine' => 157,
        'endLine' => 189,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'aliasName' => NULL,
      ),
      'computeProhibitionMatch' => 
      array (
        'name' => 'computeProhibitionMatch',
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
            'startLine' => 206,
            'endLine' => 206,
            'startColumn' => 43,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'threshold' => 
          array (
            'name' => 'threshold',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'float',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 206,
            'endLine' => 206,
            'startColumn' => 58,
            'endColumn' => 73,
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
 * Compute the `prohibitionMatch` value for a single consolidated entity.
 *
 * Uses `highestConfidence` (the worst-case confidence across the batch)
 * for the `highConfidence` flag so a single high-confidence detection in
 * any file marks the whole rollup as high-confidence.
 *
 * @param array<string, mixed> $entity Consolidated entity entry.
 * @param float $threshold High-confidence threshold (inclusive).
 *
 * @return array<string, mixed>|null Match object or null.
 *
 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-1
 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-2
 */',
        'startLine' => 206,
        'endLine' => 232,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
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
 * Mirrors AnonymizationService; uses the same config key so one admin
 * setting covers both surfaces.
 *
 * @return float Threshold value (inclusive boundary).
 *
 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-2
 */',
        'startLine' => 244,
        'endLine' => 251,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'aliasName' => NULL,
      ),
      'getEntitiesForFile' => 
      array (
        'name' => 'getEntitiesForFile',
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
            'startLine' => 266,
            'endLine' => 266,
            'startColumn' => 38,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Fetch the entity detections stored for a single file by OpenRegister.
 *
 * Returns an empty array and logs a warning when OpenRegister is not
 * installed or the lookup fails — callers treat missing entities as a
 * non-fatal condition.
 *
 * @param int $fileId Nextcloud file ID whose entities should be fetched.
 *
 * @return array<int, mixed> Raw entity detections, or an empty array on failure.
 *
 * @spec openspec/specs/anonymization-entity-review/spec.md#requirement-consolidated-entity-list-endpoint
 */',
        'startLine' => 266,
        'endLine' => 278,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\EntityConsolidationService',
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