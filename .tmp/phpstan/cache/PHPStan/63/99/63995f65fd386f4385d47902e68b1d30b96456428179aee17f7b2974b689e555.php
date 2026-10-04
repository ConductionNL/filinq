<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/PolicyMatchService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\PolicyMatchService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-84dc2671742ca1d61ec3f31427400c97842e5c3a3be52cce1e75af30d58fe732',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/PolicyMatchService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\PolicyMatchService',
    'shortName' => 'PolicyMatchService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Detection-time policy matcher.
 *
 * @spec openspec/specs/entity-publication-policies/spec.md
 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-1
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 58,
    'endLine' => 508,
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
      'APP_ID' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'name' => 'APP_ID',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq\'',
          'attributes' => 
          array (
            'startLine' => 63,
            'endLine' => 63,
            'startTokenPos' => 60,
            'startFilePos' => 2235,
            'endTokenPos' => 60,
            'endFilePos' => 2242,
          ),
        ),
        'docComment' => '/**
 * The Filinq app id, used as the app-config namespace.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 63,
        'endLine' => 63,
        'startColumn' => 2,
        'endColumn' => 33,
      ),
      'KIND_PROHIBITION' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'name' => 'KIND_PROHIBITION',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'prohibition\'',
          'attributes' => 
          array (
            'startLine' => 68,
            'endLine' => 68,
            'startTokenPos' => 73,
            'startFilePos' => 2346,
            'endTokenPos' => 73,
            'endFilePos' => 2358,
          ),
        ),
        'docComment' => '/**
 * Match result kind — prohibition (force anonymise).
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 68,
        'endLine' => 68,
        'startColumn' => 2,
        'endColumn' => 47,
      ),
      'KIND_STANDING_CONSENT' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'name' => 'KIND_STANDING_CONSENT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'standing_consent\'',
          'attributes' => 
          array (
            'startLine' => 73,
            'endLine' => 73,
            'startTokenPos' => 86,
            'startFilePos' => 2474,
            'endTokenPos' => 86,
            'endFilePos' => 2491,
          ),
        ),
        'docComment' => '/**
 * Match result kind — standing consent (allow publication).
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 2,
        'endColumn' => 57,
      ),
    ),
    'immediateProperties' => 
    array (
      'rulesCache' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'name' => 'rulesCache',
        'modifiers' => 4,
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
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 90,
            'endLine' => 90,
            'startTokenPos' => 100,
            'startFilePos' => 3056,
            'endTokenPos' => 100,
            'endFilePos' => 3059,
          ),
        ),
        'docComment' => '/**
 * Lazily-built cache: list of normalised rule records.
 *
 * Each entry: [
 *   \'uuid\'        => string,
 *   \'kind\'        => self::KIND_PROHIBITION | self::KIND_STANDING_CONSENT,
 *   \'entityType\'  => \'PERSON\' | \'ORGANIZATION\' | \'OTHER\',
 *   \'matchRules\'  => array<int, array{type: string, value: string}>,
 *   \'validFrom\'   => ?DateTimeInterface,
 *   \'validUntil\'  => ?DateTimeInterface,
 *   \'primaryName\' => string (for response/audit context),
 * ]
 *
 * @var array<int, array<string, mixed>>|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 90,
        'endLine' => 90,
        'startColumn' => 2,
        'endColumn' => 35,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
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
        'startLine' => 104,
        'endLine' => 104,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'appManager' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
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
        'startLine' => 105,
        'endLine' => 105,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
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
        'startLine' => 106,
        'endLine' => 106,
        'startColumn' => 3,
        'endColumn' => 37,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'objectService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
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
        'startLine' => 107,
        'endLine' => 107,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
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
            'startLine' => 108,
            'endLine' => 108,
            'startTokenPos' => 158,
            'startFilePos' => 3957,
            'endTokenPos' => 162,
            'endFilePos' => 3983,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 108,
        'endLine' => 108,
        'startColumn' => 3,
        'endColumn' => 87,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'textNormaliser' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'name' => 'textNormaliser',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\TextNormaliser',
            'isIdentifier' => false,
          ),
        ),
        'default' => 
        array (
          'code' => 'new \\OCA\\Filinq\\Service\\TextNormaliser()',
          'attributes' => 
          array (
            'startLine' => 109,
            'endLine' => 109,
            'startTokenPos' => 175,
            'startFilePos' => 4038,
            'endTokenPos' => 179,
            'endFilePos' => 4057,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 109,
        'endLine' => 109,
        'startColumn' => 3,
        'endColumn' => 72,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'ruleNormaliser' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'name' => 'ruleNormaliser',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\PolicyRuleNormaliser',
            'isIdentifier' => false,
          ),
        ),
        'default' => 
        array (
          'code' => 'new \\OCA\\Filinq\\Service\\PolicyRuleNormaliser()',
          'attributes' => 
          array (
            'startLine' => 110,
            'endLine' => 110,
            'startTokenPos' => 192,
            'startFilePos' => 4118,
            'endTokenPos' => 196,
            'endFilePos' => 4143,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 110,
        'endLine' => 110,
        'startColumn' => 3,
        'endColumn' => 84,
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
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 0,
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
            'startLine' => 105,
            'endLine' => 105,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 1,
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
            'startLine' => 106,
            'endLine' => 106,
            'startColumn' => 3,
            'endColumn' => 37,
            'parameterIndex' => 2,
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
            'startLine' => 107,
            'endLine' => 107,
            'startColumn' => 3,
            'endColumn' => 56,
            'parameterIndex' => 3,
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
                'startLine' => 108,
                'endLine' => 108,
                'startTokenPos' => 158,
                'startFilePos' => 3957,
                'endTokenPos' => 162,
                'endFilePos' => 3983,
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
            'startLine' => 108,
            'endLine' => 108,
            'startColumn' => 3,
            'endColumn' => 87,
            'parameterIndex' => 4,
            'isOptional' => true,
          ),
          'textNormaliser' => 
          array (
            'name' => 'textNormaliser',
            'default' => 
            array (
              'code' => 'new \\OCA\\Filinq\\Service\\TextNormaliser()',
              'attributes' => 
              array (
                'startLine' => 109,
                'endLine' => 109,
                'startTokenPos' => 175,
                'startFilePos' => 4038,
                'endTokenPos' => 179,
                'endFilePos' => 4057,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\TextNormaliser',
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
            'endColumn' => 72,
            'parameterIndex' => 5,
            'isOptional' => true,
          ),
          'ruleNormaliser' => 
          array (
            'name' => 'ruleNormaliser',
            'default' => 
            array (
              'code' => 'new \\OCA\\Filinq\\Service\\PolicyRuleNormaliser()',
              'attributes' => 
              array (
                'startLine' => 110,
                'endLine' => 110,
                'startTokenPos' => 192,
                'startFilePos' => 4118,
                'endTokenPos' => 196,
                'endFilePos' => 4143,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\PolicyRuleNormaliser',
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
            'endColumn' => 84,
            'parameterIndex' => 6,
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
 * @param IAppManager $appManager App manager (used to confirm OR is installed).
 * @param IAppConfig $config App config (prohibition high-confidence threshold).
 * @param ObjectServiceInterface $objectService OpenRegister\'s published object contract (ADR-084).
 * @param ObjectResultExtractor $resultExtractor Coerces OpenRegister results to plain rows.
 * @param TextNormaliser $textNormaliser Accent-stripping text normaliser.
 * @param PolicyRuleNormaliser $ruleNormaliser Admission + normalisation of stored policy rows.
 */',
        'startLine' => 103,
        'endLine' => 113,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'aliasName' => NULL,
      ),
      'highConfidenceThreshold' => 
      array (
        'name' => 'highConfidenceThreshold',
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
 * The confidence at or above which a prohibition match is "absolute".
 *
 * A prohibition match with detection confidence >= this threshold cannot be
 * released by `force`; below it, `force` may release the skip. Read at call
 * time so a runtime app-config change propagates without a restart. Same
 * threshold governs `highConfidence` in the extract response and the gate.
 *
 * @return float The configured threshold (default 0.85).
 *
 * @spec openspec/specs/anonymisation-prohibition-gate/spec.md#requirement-overrides-must-only-release-low-confidence-prohibition-matches
 */',
        'startLine' => 127,
        'endLine' => 134,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'aliasName' => NULL,
      ),
      'match' => 
      array (
        'name' => 'match',
        'parameters' => 
        array (
          'entityText' => 
          array (
            'name' => 'entityText',
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
            'startLine' => 157,
            'endLine' => 157,
            'startColumn' => 3,
            'endColumn' => 20,
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
            'startLine' => 158,
            'endLine' => 158,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'resolvedIdentifiers' => 
          array (
            'name' => 'resolvedIdentifiers',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 159,
                'endLine' => 159,
                'startTokenPos' => 272,
                'startFilePos' => 5900,
                'endTokenPos' => 273,
                'endFilePos' => 5901,
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
            'startLine' => 159,
            'endLine' => 159,
            'startColumn' => 3,
            'endColumn' => 33,
            'parameterIndex' => 2,
            'isOptional' => true,
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
 * Match a detected entity against the policy layer.
 *
 * @param string $entityText Detected entity text (e.g. "Pieter de Vries").
 * @param string $entityType \'PERSON\', \'ORGANIZATION\', or \'OTHER\'.
 * @param array<string, mixed> $resolvedIdentifiers Optional structured identifiers
 *                                                  attached to the entity (BSN, KvK, etc.).
 *                                                  Shape: `[\'bsn\' => \'123456789\', \'kvk\' => \'12345678\']`.
 *
 * @return array<string, mixed>|null Match data, or null when no rule matches.
 *
 * @phpstan-return null|array{
 *   uuid: string,
 *   kind: \'prohibition\'|\'standing_consent\',
 *   entityType: string,
 *   primaryName: string
 * }
 *
 * @spec openspec/specs/entity-publication-policies/spec.md
 */',
        'startLine' => 156,
        'endLine' => 184,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'aliasName' => NULL,
      ),
      'matchProhibition' => 
      array (
        'name' => 'matchProhibition',
        'parameters' => 
        array (
          'entityText' => 
          array (
            'name' => 'entityText',
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
            'startLine' => 203,
            'endLine' => 203,
            'startColumn' => 3,
            'endColumn' => 20,
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
            'startLine' => 204,
            'endLine' => 204,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'resolvedIdentifiers' => 
          array (
            'name' => 'resolvedIdentifiers',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 205,
                'endLine' => 205,
                'startTokenPos' => 428,
                'startFilePos' => 7363,
                'endTokenPos' => 429,
                'endFilePos' => 7364,
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
            'startLine' => 205,
            'endLine' => 205,
            'startColumn' => 3,
            'endColumn' => 33,
            'parameterIndex' => 2,
            'isOptional' => true,
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
 * Match a detected entity against the prohibition layer only.
 *
 * Unlike {@see match}, this never returns a standing-consent match: the
 * prohibition gate (anonymisation-prohibition-gate) is read-only safety
 * layered on generic anonymisation and MUST NOT consult standing consents.
 * Same return shape as {@see match}.
 *
 * @param string $entityText Detected entity text.
 * @param string $entityType \'PERSON\', \'ORGANIZATION\', or \'OTHER\'.
 * @param array<string, mixed> $resolvedIdentifiers Optional structured identifiers (BSN, KvK).
 *
 * @return array<string, mixed>|null Prohibition match, or null when none matches.
 *
 * @phpstan-return null|array{uuid: string, kind: \'prohibition\', entityType: string, primaryName: string}
 */',
        'startLine' => 202,
        'endLine' => 215,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'aliasName' => NULL,
      ),
      'firstMatchOf' => 
      array (
        'name' => 'firstMatchOf',
        'parameters' => 
        array (
          'kind' => 
          array (
            'name' => 'kind',
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
            'startLine' => 232,
            'endLine' => 232,
            'startColumn' => 3,
            'endColumn' => 14,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'rules' => 
          array (
            'name' => 'rules',
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
            'startLine' => 233,
            'endLine' => 233,
            'startColumn' => 3,
            'endColumn' => 14,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'entityText' => 
          array (
            'name' => 'entityText',
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
            'startLine' => 234,
            'endLine' => 234,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 2,
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
            'startLine' => 235,
            'endLine' => 235,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'resolvedIdentifiers' => 
          array (
            'name' => 'resolvedIdentifiers',
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
            'startLine' => 236,
            'endLine' => 236,
            'startColumn' => 3,
            'endColumn' => 28,
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
 * Find the first rule of the given kind that matches the entity.
 *
 * Sorts candidates by UUID lexicographically so multi-match resolution
 * is deterministic.
 *
 * @param string $kind Rule kind to scan.
 * @param array<int, array<string,mixed>> $rules Cached rule list.
 * @param string $entityText Entity literal text.
 * @param string $entityType Entity type.
 * @param array<string, mixed> $resolvedIdentifiers Structured identifiers.
 *
 * @return array<string, mixed>|null
 */',
        'startLine' => 231,
        'endLine' => 276,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'aliasName' => NULL,
      ),
      'entityMatchesAnyRule' => 
      array (
        'name' => 'entityMatchesAnyRule',
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
            'startLine' => 294,
            'endLine' => 294,
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'entityText' => 
          array (
            'name' => 'entityText',
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
            'startLine' => 295,
            'endLine' => 295,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'resolvedIdentifiers' => 
          array (
            'name' => 'resolvedIdentifiers',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 296,
                'endLine' => 296,
                'startTokenPos' => 856,
                'startFilePos' => 10018,
                'endTokenPos' => 857,
                'endFilePos' => 10019,
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
            'startLine' => 296,
            'endLine' => 296,
            'startColumn' => 3,
            'endColumn' => 33,
            'parameterIndex' => 2,
            'isOptional' => true,
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
 * Test whether any rule in a `matchRules` array matches the given entity.
 *
 * Public so the retroactive layer (`PolicyRetroactiveService`) can ask the
 * inverse question — "does this new rule match this existing entity?" —
 * without duplicating the type-by-type semantics.
 *
 * @param array<int, array<string, mixed>> $matchRules List of {type, value} rules.
 * @param string $entityText Entity literal text.
 * @param array<string, mixed> $resolvedIdentifiers Structured identifiers (BSN, KvK).
 *
 * @return bool
 *
 * @spec openspec/specs/entity-publication-policies/spec.md
 */',
        'startLine' => 293,
        'endLine' => 313,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'aliasName' => NULL,
      ),
      'ruleMatches' => 
      array (
        'name' => 'ruleMatches',
        'parameters' => 
        array (
          'type' => 
          array (
            'name' => 'type',
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
            'startLine' => 327,
            'endLine' => 327,
            'startColumn' => 3,
            'endColumn' => 14,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 328,
            'endLine' => 328,
            'startColumn' => 3,
            'endColumn' => 15,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'entityText' => 
          array (
            'name' => 'entityText',
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
            'startLine' => 329,
            'endLine' => 329,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'entityTextNormalised' => 
          array (
            'name' => 'entityTextNormalised',
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
            'startLine' => 330,
            'endLine' => 330,
            'startColumn' => 3,
            'endColumn' => 30,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'resolvedIdentifiers' => 
          array (
            'name' => 'resolvedIdentifiers',
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
            'startLine' => 331,
            'endLine' => 331,
            'startColumn' => 3,
            'endColumn' => 28,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Test a single match rule against an entity.
 *
 * @param string $type Match type (\'exact\', \'normalized\', \'bsn\', \'kvk\').
 * @param string $value Match value (literal or wildcard).
 * @param string $entityText Entity literal text.
 * @param string $entityTextNormalised Lower-cased + accent-stripped text.
 * @param array<string, mixed> $resolvedIdentifiers Structured identifiers (BSN, KvK).
 *
 * @return bool
 */',
        'startLine' => 326,
        'endLine' => 358,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'aliasName' => NULL,
      ),
      'loadRules' => 
      array (
        'name' => 'loadRules',
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
 * Load both rule sources and normalise into a single cache.
 *
 * @return array<int, array<string, mixed>>
 */',
        'startLine' => 365,
        'endLine' => 386,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'aliasName' => NULL,
      ),
      'loadProhibitions' => 
      array (
        'name' => 'loadProhibitions',
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
 * Load active prohibition records.
 *
 * @return array<int, array<string, mixed>>
 */',
        'startLine' => 393,
        'endLine' => 421,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'aliasName' => NULL,
      ),
      'loadStandingConsents' => 
      array (
        'name' => 'loadStandingConsents',
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
 * Load active standing-consent records (scope=entity).
 *
 * @return array<int, array<string, mixed>>
 */',
        'startLine' => 428,
        'endLine' => 463,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'aliasName' => NULL,
      ),
      'matchProhibitionHint' => 
      array (
        'name' => 'matchProhibitionHint',
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
            'startLine' => 480,
            'endLine' => 480,
            'startColumn' => 39,
            'endColumn' => 56,
            'parameterIndex' => 0,
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
            'startLine' => 480,
            'endLine' => 480,
            'startColumn' => 59,
            'endColumn' => 77,
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
 * Match a detected entity against prohibition rules only.
 *
 * Convenience wrapper used by the extract and consolidated-entities
 * endpoints. Returns only prohibition matches (standing-consent matches
 * are excluded).
 *
 * @param string $entityType \'PERSON\', \'ORGANIZATION\', or \'OTHER\'.
 * @param string $entityValue Detected entity text (e.g. "Pieter de Vries").
 *
 * @return array<string, mixed>|null `{ruleId, ruleName}` when a prohibition
 *                                   rule matches, null otherwise.
 *
 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-1
 */',
        'startLine' => 480,
        'endLine' => 491,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'aliasName' => NULL,
      ),
      'invalidateCache' => 
      array (
        'name' => 'invalidateCache',
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
 * Invalidate the in-memory rule cache.
 *
 * Public so an external event subscriber can call it when policy records
 * change. Until that wiring lands (task 3.5), the cache is naturally
 * stable within a single request and rebuilt on the next one.
 *
 * @return void
 *
 * @spec openspec/specs/entity-publication-policies/spec.md
 */',
        'startLine' => 504,
        'endLine' => 507,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyMatchService',
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