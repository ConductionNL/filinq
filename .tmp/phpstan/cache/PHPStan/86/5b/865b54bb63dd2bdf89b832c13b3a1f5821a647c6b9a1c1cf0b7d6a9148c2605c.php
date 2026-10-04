<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/PolicyRetroactiveService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\PolicyRetroactiveService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-a9ba00c8053823d54b1147dcb711ce604720308442bb4f20662e5001b99c6077',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/PolicyRetroactiveService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
    'shortName' => 'PolicyRetroactiveService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Retroactive rule-mutation handler.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 53,
    'endLine' => 445,
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
      'IN_FLIGHT_STATUSES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'name' => 'IN_FLIGHT_STATUSES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'pending\', \'consent_given\', \'objection_received\', \'no_response\']',
          'attributes' => 
          array (
            'startLine' => 65,
            'endLine' => 70,
            'startTokenPos' => 55,
            'startFilePos' => 2534,
            'endTokenPos' => 69,
            'endFilePos' => 2610,
          ),
        ),
        'docComment' => '/**
 * Statuses considered "in-flight" — eligible for retroactive force-resolve.
 *
 * `anonymized` is terminal (the goal state); excluded so we don\'t re-write
 * already-resolved records. `published_*` derivatives, if they existed, would
 * also be terminal — the current schema does not surface them as `consentStatus`
 * values, so we don\'t filter on them here.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 65,
        'endLine' => 70,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
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
        'startLine' => 81,
        'endLine' => 81,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'policyMatcher' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'name' => 'policyMatcher',
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
        'startLine' => 82,
        'endLine' => 82,
        'startColumn' => 3,
        'endColumn' => 52,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'objectService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'name' => 'objectService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\OpenRegister\\Contract\\ObjectServiceInterface',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 83,
        'endLine' => 83,
        'startColumn' => 3,
        'endColumn' => 56,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'resultExtractor' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'name' => 'resultExtractor',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\ObjectResultExtractor',
            'isIdentifier' => false,
          ),
        ),
        'default' => 
        array (
          'code' => 'new \\OCA\\Filinq\\Service\\ObjectResultExtractor()',
          'attributes' => 
          array (
            'startLine' => 84,
            'endLine' => 84,
            'startTokenPos' => 118,
            'startFilePos' => 3224,
            'endTokenPos' => 122,
            'endFilePos' => 3250,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 84,
        'endLine' => 84,
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
            'startLine' => 81,
            'endLine' => 81,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'policyMatcher' => 
          array (
            'name' => 'policyMatcher',
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
            'startLine' => 82,
            'endLine' => 82,
            'startColumn' => 3,
            'endColumn' => 52,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'objectService' => 
          array (
            'name' => 'objectService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\OpenRegister\\Contract\\ObjectServiceInterface',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 3,
            'endColumn' => 56,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'resultExtractor' => 
          array (
            'name' => 'resultExtractor',
            'default' => 
            array (
              'code' => 'new \\OCA\\Filinq\\Service\\ObjectResultExtractor()',
              'attributes' => 
              array (
                'startLine' => 84,
                'endLine' => 84,
                'startTokenPos' => 118,
                'startFilePos' => 3224,
                'endTokenPos' => 122,
                'endFilePos' => 3250,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\ObjectResultExtractor',
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
 * Constructor.
 *
 * @param LoggerInterface $logger Structured log sink.
 * @param PolicyMatchService $policyMatcher Reusable rule-evaluation primitives.
 * @param ObjectServiceInterface $objectService OpenRegister\'s published object contract (ADR-084).
 * @param ObjectResultExtractor $resultExtractor Coerces OpenRegister results to plain rows.
 */',
        'startLine' => 80,
        'endLine' => 87,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'aliasName' => NULL,
      ),
      'applyProhibitionMutation' => 
      array (
        'name' => 'applyProhibitionMutation',
        'parameters' => 
        array (
          'prohibition' => 
          array (
            'name' => 'prohibition',
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
            'startLine' => 100,
            'endLine' => 100,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Apply a prohibition mutation retroactively to in-flight workflow records.
 *
 * Call this after a `publicationProhibition` has been created OR updated to
 * `active: true` OR had its `matchRules` widened OR had `validUntil` extended
 * — the caller decides which updates qualify; this method is the worker.
 *
 * @param array<string, mixed> $prohibition The prohibition record\'s plain data.
 *
 * @return int Number of in-flight records that were force-resolved.
 */',
        'startLine' => 100,
        'endLine' => 137,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'aliasName' => NULL,
      ),
      'sweepInFlightRecords' => 
      array (
        'name' => 'sweepInFlightRecords',
        'parameters' => 
        array (
          'matchRules' => 
          array (
            'name' => 'matchRules',
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
            'startLine' => 149,
            'endLine' => 149,
            'startColumn' => 3,
            'endColumn' => 19,
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
            'startLine' => 150,
            'endLine' => 150,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'prohibitionUuid' => 
          array (
            'name' => 'prohibitionUuid',
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
            'startLine' => 151,
            'endLine' => 151,
            'startColumn' => 3,
            'endColumn' => 25,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Force-resolve every in-flight record the given rules now match.
 *
 * @param array<int, array<string, mixed>> $matchRules The prohibition\'s match rules.
 * @param string $entityType Entity type the prohibition targets.
 * @param string $prohibitionUuid UUID recorded on each resolved record.
 *
 * @return int Number of records that were successfully force-resolved.
 */',
        'startLine' => 148,
        'endLine' => 170,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'aliasName' => NULL,
      ),
      'candidateMatches' => 
      array (
        'name' => 'candidateMatches',
        'parameters' => 
        array (
          'candidate' => 
          array (
            'name' => 'candidate',
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
            'startLine' => 180,
            'endLine' => 180,
            'startColumn' => 36,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'matchRules' => 
          array (
            'name' => 'matchRules',
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
            'startLine' => 180,
            'endLine' => 180,
            'startColumn' => 54,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Test one in-flight record against the prohibition\'s match rules.
 *
 * @param array<string, mixed> $candidate The in-flight record.
 * @param array<int, array<string, mixed>> $matchRules The prohibition\'s match rules.
 *
 * @return bool True when the record\'s entity is covered by the rules.
 */',
        'startLine' => 180,
        'endLine' => 192,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'aliasName' => NULL,
      ),
      'readRecordUuid' => 
      array (
        'name' => 'readRecordUuid',
        'parameters' => 
        array (
          'record' => 
          array (
            'name' => 'record',
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
            'startLine' => 201,
            'endLine' => 201,
            'startColumn' => 34,
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
 * Read a record\'s UUID from its `@self` envelope or its top-level keys.
 *
 * @param array<string, mixed> $record The record\'s plain data.
 *
 * @return string The UUID, or an empty string when the record carries none.
 */',
        'startLine' => 201,
        'endLine' => 208,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'aliasName' => NULL,
      ),
      'applyStandingConsentMutation' => 
      array (
        'name' => 'applyStandingConsentMutation',
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
 * Standing-consent mutations are intentionally NOT applied retroactively.
 *
 * Future detections benefit; past detections retain their already-decided
 * outcomes (spec §5.2). The only effect of this method is cache invalidation.
 *
 * @return void
 */',
        'startLine' => 218,
        'endLine' => 221,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'aliasName' => NULL,
      ),
      'applyRuleRemoval' => 
      array (
        'name' => 'applyRuleRemoval',
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
 * Rule deletions / deactivations / expiries do NOT modify past records.
 *
 * Past `publicationConsent` records keep their final state and their
 * `policyMatch` reference (which becomes dangling). Spec §5.3. Cache is
 * invalidated so future detections see the rule absence.
 *
 * @return void
 */',
        'startLine' => 232,
        'endLine' => 235,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'aliasName' => NULL,
      ),
      'isProhibitionEligible' => 
      array (
        'name' => 'isProhibitionEligible',
        'parameters' => 
        array (
          'prohibition' => 
          array (
            'name' => 'prohibition',
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
            'startLine' => 247,
            'endLine' => 247,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Decide whether a prohibition is eligible to drive a retroactive sweep.
 *
 * Inactive rules and rules outside their validity window are skipped — we
 * only sweep when the rule would also produce new matches.
 *
 * @param array<string, mixed> $prohibition The prohibition\'s plain data.
 *
 * @return bool
 */',
        'startLine' => 247,
        'endLine' => 265,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'aliasName' => NULL,
      ),
      'loadInFlightDocumentRecords' => 
      array (
        'name' => 'loadInFlightDocumentRecords',
        'parameters' => 
        array (
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
            'startLine' => 278,
            'endLine' => 278,
            'startColumn' => 47,
            'endColumn' => 64,
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
 * Load in-flight `scope: "document"` `publicationConsent` records.
 *
 * Filters: scope=document, consentStatus in IN_FLIGHT_STATUSES, policyMatch
 * not already set (records pre-empted by a different policy stay as-is and
 * are not re-pointed by this routine).
 *
 * @param string $entityType Optional entity-type filter.
 *
 * @return array<int, array<string, mixed>>
 */',
        'startLine' => 278,
        'endLine' => 333,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'aliasName' => NULL,
      ),
      'forceResolveToAnonymized' => 
      array (
        'name' => 'forceResolveToAnonymized',
        'parameters' => 
        array (
          'record' => 
          array (
            'name' => 'record',
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
            'startLine' => 348,
            'endLine' => 348,
            'startColumn' => 44,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'prohibitionUuid' => 
          array (
            'name' => 'prohibitionUuid',
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
            'startLine' => 348,
            'endLine' => 348,
            'startColumn' => 59,
            'endColumn' => 81,
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
 * Persist the force-resolved state on a single record.
 *
 * Preserves: `notificationSentAt`, `objectionReceivedAt`, all entity fields,
 * and the rest of the original payload. Sets: `consentStatus: "anonymized"`,
 * `notificationStatus: "skipped"`, `publicationDecision: "anonymize"`,
 * `objectionDeadline: null`, `policyMatch: <prohibition uuid>`.
 *
 * @param array<string, mixed> $record The current record data.
 * @param string $prohibitionUuid UUID of the prohibition that triggered this.
 *
 * @return bool True on success, false on write failure.
 */',
        'startLine' => 348,
        'endLine' => 391,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'aliasName' => NULL,
      ),
      'extractIdentifiers' => 
      array (
        'name' => 'extractIdentifiers',
        'parameters' => 
        array (
          'object' => 
          array (
            'name' => 'object',
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
            'startLine' => 404,
            'endLine' => 404,
            'startColumn' => 38,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Pull `bsn` / `kvk` identifiers from a record if present.
 *
 * The schema does not currently formalise these fields on
 * `publicationConsent`, but seed data and future extensions may. This
 * method is forward-compatible — empty result is fine.
 *
 * @param array<string, mixed> $object The record.
 *
 * @return array<string, string>
 */',
        'startLine' => 404,
        'endLine' => 424,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'aliasName' => NULL,
      ),
      'parseDateTime' => 
      array (
        'name' => 'parseDateTime',
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
            'startLine' => 433,
            'endLine' => 433,
            'startColumn' => 33,
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
                  'name' => 'DateTimeImmutable',
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Parse ISO-8601 to DateTimeImmutable; null on failure.
 *
 * @param string $value Raw value.
 *
 * @return DateTimeImmutable|null
 */',
        'startLine' => 433,
        'endLine' => 444,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
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