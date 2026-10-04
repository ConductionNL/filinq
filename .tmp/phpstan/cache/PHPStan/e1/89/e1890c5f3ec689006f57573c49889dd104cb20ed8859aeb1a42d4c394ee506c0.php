<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/AnonymizationService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\AnonymizationService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-678f4a2c0071afe6683cde42c150128d1bee025255426a7d3b3f85bdb9ddc80d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/AnonymizationService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\AnonymizationService',
    'shortName' => 'AnonymizationService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service for orchestrating the document anonymization pipeline
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/anonymization/spec.md
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) The collaborator that took this
 * over the line is RedactionOutputGuard: nothing may be written until a person
 * has checked the detection run. The guard is asked here because this is the
 * class that writes; asking it anywhere else would leave a write path that does
 * not ask.
 *
 * @SuppressWarnings(PHPMD.ExcessiveParameterList) Same collaborator, same
 * constructor. Every parameter is an injected service, and grouping them behind
 * a bag would hide which of them a given instance actually needs.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 71,
    'endLine' => 625,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
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
        'startLine' => 106,
        'endLine' => 106,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
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
        'startLine' => 107,
        'endLine' => 107,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
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
        'startLine' => 108,
        'endLine' => 108,
        'startColumn' => 3,
        'endColumn' => 54,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'entityDetection' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'name' => 'entityDetection',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\EntityDetectionService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 109,
        'endLine' => 109,
        'startColumn' => 3,
        'endColumn' => 58,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'dictionaryRunner' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'name' => 'dictionaryRunner',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\CustomDictionaryDetectionRunner',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 110,
        'endLine' => 110,
        'startColumn' => 3,
        'endColumn' => 68,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'fileEntityStats' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'name' => 'fileEntityStats',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\FileEntityStatsService',
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
        'endColumn' => 58,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'confidentialityLabel' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'name' => 'confidentialityLabel',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
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
        'endColumn' => 68,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'prohibitionPolicy' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'name' => 'prohibitionPolicy',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
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
        'endColumn' => 62,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'anonymizeRunner' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'name' => 'anonymizeRunner',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
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
        'endColumn' => 59,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'reviewGuard' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'name' => 'reviewGuard',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Redaction\\RedactionOutputGuard',
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
        'endColumn' => 52,
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
            'startLine' => 106,
            'endLine' => 106,
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
            'startLine' => 107,
            'endLine' => 107,
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
            'startLine' => 108,
            'endLine' => 108,
            'startColumn' => 3,
            'endColumn' => 54,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'entityDetection' => 
          array (
            'name' => 'entityDetection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\EntityDetectionService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 109,
            'endLine' => 109,
            'startColumn' => 3,
            'endColumn' => 58,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'dictionaryRunner' => 
          array (
            'name' => 'dictionaryRunner',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\CustomDictionaryDetectionRunner',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 110,
            'endLine' => 110,
            'startColumn' => 3,
            'endColumn' => 68,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'fileEntityStats' => 
          array (
            'name' => 'fileEntityStats',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\FileEntityStatsService',
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
            'endColumn' => 58,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
          'confidentialityLabel' => 
          array (
            'name' => 'confidentialityLabel',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
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
            'endColumn' => 68,
            'parameterIndex' => 6,
            'isOptional' => false,
          ),
          'prohibitionPolicy' => 
          array (
            'name' => 'prohibitionPolicy',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\ProhibitionPolicyService',
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
            'endColumn' => 62,
            'parameterIndex' => 7,
            'isOptional' => false,
          ),
          'anonymizeRunner' => 
          array (
            'name' => 'anonymizeRunner',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\DocumentAnonymizeRunner',
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
            'endColumn' => 59,
            'parameterIndex' => 8,
            'isOptional' => false,
          ),
          'reviewGuard' => 
          array (
            'name' => 'reviewGuard',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Redaction\\RedactionOutputGuard',
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
            'endColumn' => 52,
            'parameterIndex' => 9,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor for AnonymizationService
 *
 * @param LoggerInterface $logger Logger for error reporting
 * @param ContainerInterface $container Container for dependency injection
 * @param OpenRegisterServiceLocator $locator Resolver for OpenRegister services and
 *                                            mappers.
 * @param EntityDetectionService $entityDetection Entity detection and mapping service
 * @param CustomDictionaryDetectionRunner $dictionaryRunner The custom-dictionary detection pass
 *                                                          (custom-dictionary-recognition
 *                                                          design.md §D3). Best-effort — it
 *                                                          returns a warning string rather than
 *                                                          throwing, so OpenRegister\'s own
 *                                                          detections always survive.
 * @param FileEntityStatsService $fileEntityStats Service for entity statistics and risk
 *                                                levels.
 * @param ConfidentialityLabelService $confidentialityLabel Reads a file\'s existing
 *                                                          files_confidential TSCP/BAILS
 *                                                          classification (availability-guarded;
 *                                                          null when absent) so it can be
 *                                                          surfaced alongside detected entities
 *                                                          and risk (files-confidential-labels).
 * @param ProhibitionPolicyService $prohibitionPolicy Publication-policy decisions on detected
 *                                                    entities and skip requests, plus the
 *                                                    pre-anonymise prohibition gate.
 * @param DocumentAnonymizeRunner $anonymizeRunner The per-document anonymise pipeline.
 * @param RedactionOutputGuard $reviewGuard The human review gate. It sits in the service
 *                                          rather than on the screen, so the API, the batch
 *                                          path and the folder job all reach the same
 *                                          refusal.
 *
 * @return void
 */',
        'startLine' => 105,
        'endLine' => 118,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'aliasName' => NULL,
      ),
      'extractAndDetectEntities' => 
      array (
        'name' => 'extractAndDetectEntities',
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
            'startLine' => 141,
            'endLine' => 141,
            'startColumn' => 43,
            'endColumn' => 53,
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
 * Extract text from a file and detect entities, resuming from cache.
 *
 * This is the resume-friendly, DB-cached lookup: when the file is unchanged,
 * OpenRegister\'s `isSourceUpToDate` short-circuit returns the existing chunks
 * and `EntityRelation` rows (with their skip/bases decisions) instead of
 * re-detecting, so re-opening a concept picks up where the operator left off
 * and does not append duplicate relations. The file\'s mtime already triggers a
 * re-extract when the source itself changed; see
 * {@see reExtractAndDetectEntities()} for an explicit re-analysis.
 *
 * @param int $fileId The Nextcloud file ID
 *
 * @return array<string, mixed> Extraction result with entities, entityCount, riskLevel
 *
 * @throws Exception If extraction or detection fails
 *
 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-5
 * @spec openspec/specs/anonymization/spec.md
 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md#requirement-surface-the-label-in-the-document-report-and-entity-review-context-req-ddfcl-002
 */',
        'startLine' => 141,
        'endLine' => 143,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'aliasName' => NULL,
      ),
      'reExtractAndDetectEntities' => 
      array (
        'name' => 'reExtractAndDetectEntities',
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
            'startLine' => 161,
            'endLine' => 161,
            'startColumn' => 45,
            'endColumn' => 55,
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
 * Force a fresh extraction + detection even when the file is unchanged.
 *
 * Identical to {@see extractAndDetectEntities()} except that OpenRegister\'s
 * `isSourceUpToDate` short-circuit is bypassed, so the document is re-chunked
 * and re-detected from scratch.
 *
 * @param int $fileId The Nextcloud file ID
 *
 * @return array<string, mixed> Extraction result with entities, entityCount, riskLevel
 *
 * @throws Exception If extraction or detection fails
 *
 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-5
 * @spec openspec/specs/anonymization/spec.md
 */',
        'startLine' => 161,
        'endLine' => 163,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'aliasName' => NULL,
      ),
      'runExtraction' => 
      array (
        'name' => 'runExtraction',
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
            'startLine' => 197,
            'endLine' => 197,
            'startColumn' => 33,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
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
            'startLine' => 197,
            'endLine' => 197,
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
 * Shared implementation behind both extraction entry points.
 *
 * Each entity in the response includes a `prohibitionMatch` field: null when
 * no publication-prohibition rule matches, or an object with ruleId, ruleName,
 * and highConfidence (score >= configured threshold, inclusive).
 *
 * The response also includes a `riskLevel` field derived from OpenRegister\'s
 * RiskLevelService, or \'none\' when that service is unavailable.
 *
 * The response also includes a `customDictionaryWarning` field: null when the
 * custom-dictionary matching pass (organisation-managed term lists,
 * `CUSTOM_DICTIONARY` entities) ran without error, or a human-readable warning
 * string when it failed. OpenRegister\'s own detections are always returned
 * regardless — the pass is best-effort (custom-dictionary-recognition §D3).
 *
 * When the file carries a `files_confidential` TSCP/BAILS confidentiality label
 * (availability-guarded — see ConfidentialityLabelService), the response also
 * includes `confidentialityLabel` (display string) and `confidentialityLevel`
 * (normalised int). Both are omitted when no label resolves — a read-only
 * signal, never a block/gate/redaction (files-confidential-labels).
 *
 * @param int $fileId The Nextcloud file ID
 * @param array<string, mixed> $options Run options; `force` (bool) bypasses
 *                                      OpenRegister\'s up-to-date short-circuit.
 *
 * @return array<string, mixed> Extraction result with entities, entityCount, riskLevel
 *
 * @throws Exception If extraction or detection fails
 *
 * @spec openspec/specs/anonymization/spec.md
 */',
        'startLine' => 197,
        'endLine' => 276,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'aliasName' => NULL,
      ),
      'buildExtractionResult' => 
      array (
        'name' => 'buildExtractionResult',
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
            'startLine' => 297,
            'endLine' => 297,
            'startColumn' => 3,
            'endColumn' => 13,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'normalized' => 
          array (
            'name' => 'normalized',
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
            'startLine' => 298,
            'endLine' => 298,
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'entityCount' => 
          array (
            'name' => 'entityCount',
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
            'startLine' => 299,
            'endLine' => 299,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'dictionaryWarning' => 
          array (
            'name' => 'dictionaryWarning',
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
            'startLine' => 300,
            'endLine' => 300,
            'startColumn' => 3,
            'endColumn' => 28,
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
 * Assemble the extraction response payload.
 *
 * Adds the risk level derived from OpenRegister\'s RiskLevelService and, when
 * the file carries one, the read-only `files_confidential` TSCP/BAILS
 * confidentiality signal. Both are surfaced alongside entities, never
 * blocking detection (files-confidential-labels, design.md D2).
 *
 * @param int $fileId The Nextcloud file ID.
 * @param array<int, array<string, mixed>> $normalized Normalized, policy-decorated entities.
 * @param int $entityCount Raw detected-entity count.
 * @param string|null $dictionaryWarning Warning from the best-effort
 *                                       custom-dictionary pass, or null.
 *
 * @return array<string, mixed> The extraction result payload.
 *
 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md#requirement-surface-the-label-in-the-document-report-and-entity-review-context-req-ddfcl-002
 */',
        'startLine' => 296,
        'endLine' => 326,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
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
            'startLine' => 345,
            'endLine' => 345,
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
            'startLine' => 345,
            'endLine' => 345,
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
            'startLine' => 345,
            'endLine' => 345,
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
            'startLine' => 345,
            'endLine' => 345,
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
 * Setting `skipAnonymization = true` on a prohibition-matched relation is
 * guarded per {@see ProhibitionSkipTier::classify}; include / non-skip
 * decisions are always allowed. A blocked decision performs no OpenRegister
 * write.
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
        'startLine' => 345,
        'endLine' => 353,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
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
            'startLine' => 367,
            'endLine' => 367,
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
 * Returns any prohibition-matched occurrence at confidence >= threshold that
 * is being left un-redacted (skipped).
 *
 * @param int $fileId The Nextcloud file id.
 *
 * @return array<int, array<string, mixed>> Absolute-tier violations (may be empty).
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-7
 */',
        'startLine' => 367,
        'endLine' => 369,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
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
            'startLine' => 383,
            'endLine' => 383,
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
 * Returns an array of violation records (one per matching entity). An empty
 * array means no violations — all entries may proceed to consent creation.
 *
 * @param array<int, array<string, mixed>> $unredactedEntities Entries from the request\'s unredactedEntities field
 *
 * @return array<int, array<string, mixed>> Violation records: [{entityId, entityText, ruleId, ruleName}]
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-2
 */',
        'startLine' => 383,
        'endLine' => 388,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'aliasName' => NULL,
      ),
      'runProhibitionGate' => 
      array (
        'name' => 'runProhibitionGate',
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
            'startLine' => 412,
            'endLine' => 412,
            'startColumn' => 3,
            'endColumn' => 13,
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
            'startLine' => 413,
            'endLine' => 413,
            'startColumn' => 3,
            'endColumn' => 24,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'overrides' => 
          array (
            'name' => 'overrides',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 414,
                'endLine' => 414,
                'startTokenPos' => 1025,
                'startFilePos' => 18664,
                'endTokenPos' => 1026,
                'endFilePos' => 18665,
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
            'startLine' => 414,
            'endLine' => 414,
            'startColumn' => 3,
            'endColumn' => 23,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'userId' => 
          array (
            'name' => 'userId',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 415,
                'endLine' => 415,
                'startTokenPos' => 1035,
                'startFilePos' => 18687,
                'endTokenPos' => 1035,
                'endFilePos' => 18688,
              ),
            ),
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
            'startLine' => 415,
            'endLine' => 415,
            'startColumn' => 3,
            'endColumn' => 21,
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
 * Matches the file\'s detected entities against the active prohibition rules,
 * validates acknowledged overrides, requires every high-confidence match to be
 * in the to-be-anonymised set, and commits the validated overrides.
 *
 * @param int $fileId Nextcloud file ID.
 * @param array<int, array<string, mixed>> $requestEntities User-submitted entities to anonymize.
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
        'startLine' => 411,
        'endLine' => 424,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'aliasName' => NULL,
      ),
      'anonymizeDocument' => 
      array (
        'name' => 'anonymizeDocument',
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
            'startLine' => 484,
            'endLine' => 484,
            'startColumn' => 3,
            'endColumn' => 13,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 485,
            'endLine' => 485,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'outputFormat' => 
          array (
            'name' => 'outputFormat',
            'default' => 
            array (
              'code' => '\'pdf-only\'',
              'attributes' => 
              array (
                'startLine' => 486,
                'endLine' => 486,
                'startTokenPos' => 1106,
                'startFilePos' => 22487,
                'endTokenPos' => 1106,
                'endFilePos' => 22496,
              ),
            ),
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
            'startLine' => 486,
            'endLine' => 486,
            'startColumn' => 3,
            'endColumn' => 35,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'unredactedEntities' => 
          array (
            'name' => 'unredactedEntities',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 487,
                'endLine' => 487,
                'startTokenPos' => 1115,
                'startFilePos' => 22529,
                'endTokenPos' => 1116,
                'endFilePos' => 22530,
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
            'startLine' => 487,
            'endLine' => 487,
            'startColumn' => 3,
            'endColumn' => 32,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'overrides' => 
          array (
            'name' => 'overrides',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 488,
                'endLine' => 488,
                'startTokenPos' => 1125,
                'startFilePos' => 22554,
                'endTokenPos' => 1126,
                'endFilePos' => 22555,
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
            'startLine' => 488,
            'endLine' => 488,
            'startColumn' => 3,
            'endColumn' => 23,
            'parameterIndex' => 4,
            'isOptional' => true,
          ),
          'userId' => 
          array (
            'name' => 'userId',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 489,
                'endLine' => 489,
                'startTokenPos' => 1135,
                'startFilePos' => 22577,
                'endTokenPos' => 1135,
                'endFilePos' => 22578,
              ),
            ),
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
            'startLine' => 489,
            'endLine' => 489,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 5,
            'isOptional' => true,
          ),
          'scope' => 
          array (
            'name' => 'scope',
            'default' => 
            array (
              'code' => '\'document\'',
              'attributes' => 
              array (
                'startLine' => 490,
                'endLine' => 490,
                'startTokenPos' => 1144,
                'startFilePos' => 22599,
                'endTokenPos' => 1144,
                'endFilePos' => 22608,
              ),
            ),
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
            'startLine' => 490,
            'endLine' => 490,
            'startColumn' => 3,
            'endColumn' => 28,
            'parameterIndex' => 6,
            'isOptional' => true,
          ),
          'dossierKey' => 
          array (
            'name' => 'dossierKey',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 491,
                'endLine' => 491,
                'startTokenPos' => 1154,
                'startFilePos' => 22635,
                'endTokenPos' => 1154,
                'endFilePos' => 22638,
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
            'startLine' => 491,
            'endLine' => 491,
            'startColumn' => 3,
            'endColumn' => 28,
            'parameterIndex' => 7,
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
 * Anonymize entities in a document.
 *
 * No grondslagen summary is produced; see
 * {@see anonymizeDocumentWithBasisSummary()} for the summary-producing variant.
 *
 * When outputFormat is "pdf-only" (default) or "pdf", the anonymised
 * intermediate is run through the PdfConversionService cascade and replaced
 * with the PDF; on cascade failure the intermediate is rolled back
 * (best-effort) and a ConversionFailedException is thrown for the controller
 * to surface as HTTP 422. "pdf-only" additionally best-effort deletes the
 * native anonymised intermediate after a successful conversion so only the PDF
 * remains; "pdf" keeps it too; "preserve" skips conversion entirely.
 *
 * EML inputs are routed to OpenRegister\'s dedicated anonymise-EML API and
 * assembled into a PDF/A-3b by EmlPdfAssemblyService (OR\'s anonymizeDocument
 * throws on message/rfc822); "preserve" is overridden to PDF for EML.
 *
 * When unredactedEntities is non-empty, a publicationConsent record is
 * created for each entry AFTER the anonymise pipeline succeeds. The
 * `createdConsents` field in the response aggregates the resulting records.
 *
 * @param int $fileId The Nextcloud file ID
 * @param array<array<string, mixed>> $entities The entities to anonymize
 * @param string $outputFormat Output format: \'pdf-only\' (default), \'pdf\'
 *                             or \'preserve\'
 * @param array<int, array<string, mixed>> $unredactedEntities Entities to publish unredacted with consent
 *                                                             creation
 * @param array<int, array<string, mixed>> $overrides Acknowledged override entries {ruleId,
 *                                                    entityId, reason?} that release
 *                                                    low-confidence prohibition matches.
 * @param string $userId UID of the acting user (for override audit
 *                       entries).
 * @param string $scope Placeholder-numbering scope forwarded to
 *                      OpenRegister: \'document\' (default) or
 *                      \'dossier\' (consistent numbering across the
 *                      dossier folder).
 * @param string|null $dossierKey Stable folder id for the dossier when
 *                                $scope=\'dossier\'; null lets OpenRegister
 *                                fall back to the file\'s parent folder.
 *
 * @return array<string, mixed> Anonymization result with optional warning/createdConsents fields
 *
 * @throws Exception If anonymization fails.
 * @throws ConversionFailedException When `$outputFormat` requests a PDF and the cascade could
 *                                   not convert the anonymised intermediate. The intermediate
 *                                   is deleted (best-effort) before the exception propagates.
 * @throws ProhibitionGateException When the prohibition gate fires (high-confidence matches
 *                                  missing or invalid overrides for high-confidence matches).
 * @throws RedactionNotReviewedException When nobody has checked this detection run yet.
 *
 * @spec openspec/specs/anonymization/spec.md
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-4
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-4
 */',
        'startLine' => 483,
        'endLine' => 507,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'aliasName' => NULL,
      ),
      'anonymizeDocumentWithBasisSummary' => 
      array (
        'name' => 'anonymizeDocumentWithBasisSummary',
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
            'startLine' => 544,
            'endLine' => 544,
            'startColumn' => 3,
            'endColumn' => 13,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 545,
            'endLine' => 545,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'outputFormat' => 
          array (
            'name' => 'outputFormat',
            'default' => 
            array (
              'code' => '\'pdf-only\'',
              'attributes' => 
              array (
                'startLine' => 546,
                'endLine' => 546,
                'startTokenPos' => 1268,
                'startFilePos' => 25006,
                'endTokenPos' => 1268,
                'endFilePos' => 25015,
              ),
            ),
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
            'startLine' => 546,
            'endLine' => 546,
            'startColumn' => 3,
            'endColumn' => 35,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'unredactedEntities' => 
          array (
            'name' => 'unredactedEntities',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 547,
                'endLine' => 547,
                'startTokenPos' => 1277,
                'startFilePos' => 25048,
                'endTokenPos' => 1278,
                'endFilePos' => 25049,
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
            'startLine' => 547,
            'endLine' => 547,
            'startColumn' => 3,
            'endColumn' => 32,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'overrides' => 
          array (
            'name' => 'overrides',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 548,
                'endLine' => 548,
                'startTokenPos' => 1287,
                'startFilePos' => 25073,
                'endTokenPos' => 1288,
                'endFilePos' => 25074,
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
            'startLine' => 548,
            'endLine' => 548,
            'startColumn' => 3,
            'endColumn' => 23,
            'parameterIndex' => 4,
            'isOptional' => true,
          ),
          'userId' => 
          array (
            'name' => 'userId',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 549,
                'endLine' => 549,
                'startTokenPos' => 1297,
                'startFilePos' => 25096,
                'endTokenPos' => 1297,
                'endFilePos' => 25097,
              ),
            ),
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
            'startLine' => 549,
            'endLine' => 549,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 5,
            'isOptional' => true,
          ),
          'scope' => 
          array (
            'name' => 'scope',
            'default' => 
            array (
              'code' => '\'document\'',
              'attributes' => 
              array (
                'startLine' => 550,
                'endLine' => 550,
                'startTokenPos' => 1306,
                'startFilePos' => 25118,
                'endTokenPos' => 1306,
                'endFilePos' => 25127,
              ),
            ),
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
            'startLine' => 550,
            'endLine' => 550,
            'startColumn' => 3,
            'endColumn' => 28,
            'parameterIndex' => 6,
            'isOptional' => true,
          ),
          'dossierKey' => 
          array (
            'name' => 'dossierKey',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 551,
                'endLine' => 551,
                'startTokenPos' => 1316,
                'startFilePos' => 25154,
                'endTokenPos' => 1316,
                'endFilePos' => 25157,
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
            'startLine' => 551,
            'endLine' => 551,
            'startColumn' => 3,
            'endColumn' => 28,
            'parameterIndex' => 7,
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
 * Anonymize entities in a document AND append the grondslagen summary.
 *
 * Identical to {@see anonymizeDocument()} except that LegalBasesSummaryService
 * is invoked after the anonymised file has been written. For PDF output the
 * summary is appended as an extra page; otherwise a separate
 * `<base>_grondslagen.pdf` is written alongside. Summary failure is non-fatal:
 * the anonymised file is always preserved and a `warning` field is added to the
 * response instead (HTTP 200).
 *
 * @param int $fileId The Nextcloud file ID
 * @param array<array<string, mixed>> $entities The entities to anonymize
 * @param string $outputFormat Output format: \'pdf-only\' (default), \'pdf\'
 *                             or \'preserve\'
 * @param array<int, array<string, mixed>> $unredactedEntities Entities to publish unredacted with consent
 *                                                             creation
 * @param array<int, array<string, mixed>> $overrides Acknowledged override entries {ruleId,
 *                                                    entityId, reason?}.
 * @param string $userId UID of the acting user.
 * @param string $scope Placeholder-numbering scope forwarded to
 *                      OpenRegister.
 * @param string|null $dossierKey Stable folder id for the dossier.
 *
 * @return array<string, mixed> Anonymization result with optional
 *                              warning/summaryFileId/createdConsents fields
 *
 * @throws Exception If anonymization fails.
 * @throws ConversionFailedException When the PDF cascade is exhausted.
 * @throws ProhibitionGateException When the prohibition gate fires.
 * @throws RedactionNotReviewedException When nobody has checked this detection run yet.
 *
 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-2
 * @spec openspec/specs/anonymization/spec.md
 */',
        'startLine' => 543,
        'endLine' => 567,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'aliasName' => NULL,
      ),
      'runAnonymize' => 
      array (
        'name' => 'runAnonymize',
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
            'startLine' => 595,
            'endLine' => 595,
            'startColumn' => 3,
            'endColumn' => 13,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 596,
            'endLine' => 596,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 1,
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
            'startLine' => 597,
            'endLine' => 597,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 2,
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
            'startLine' => 598,
            'endLine' => 598,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
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
            'startLine' => 599,
            'endLine' => 599,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 4,
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
 * Shared implementation behind both anonymise entry points.
 *
 * The prohibition gate runs BEFORE any OpenRegister interaction; the pipeline
 * itself is owned by DocumentAnonymizeRunner.
 *
 * @param int $fileId The Nextcloud file ID.
 * @param array<array<string, mixed>> $entities The entities to anonymize.
 * @param string $userId UID of the acting user.
 * @param array<int, array<string, mixed>> $overrides Acknowledged override entries.
 * @param array<string, mixed> $options Run options forwarded to the runner
 *                                      (appendBasisSummary, outputFormat,
 *                                      unredactedEntities, scope, dossierKey).
 *
 * @return array<string, mixed> The anonymisation result.
 *
 * @throws Exception If anonymization fails.
 * @throws ConversionFailedException When the PDF cascade is exhausted.
 * @throws ProhibitionGateException When the prohibition gate fires.
 * @throws RedactionNotReviewedException When nobody has checked this detection run yet.
 *
 * @spec openspec/specs/anonymization/spec.md
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */',
        'startLine' => 594,
        'endLine' => 624,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymizationService',
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