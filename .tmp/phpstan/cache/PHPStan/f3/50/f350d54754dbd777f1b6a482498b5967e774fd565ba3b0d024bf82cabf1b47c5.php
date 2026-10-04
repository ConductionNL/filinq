<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/PolicyRuleNormaliser.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\PolicyRuleNormaliser
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-b07499056810c0721d75d7dc049fc8e56cc57ec9526b5253e86c14eca2bd7e33',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\PolicyRuleNormaliser',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/PolicyRuleNormaliser.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\PolicyRuleNormaliser',
    'shortName' => 'PolicyRuleNormaliser',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Admission + normalisation of stored policy rows.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/entity-publication-policies/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 48,
    'endLine' => 229,
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
    ),
    'immediateMethods' => 
    array (
      'normaliseRule' => 
      array (
        'name' => 'normaliseRule',
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
            'startLine' => 63,
            'endLine' => 63,
            'startColumn' => 32,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 63,
            'endLine' => 63,
            'startColumn' => 46,
            'endColumn' => 58,
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
 * Normalise a raw policy row into the matcher\'s cache shape.
 *
 * Applies the `active` and validity-window filters; returns null when the
 * row must not be matched (inactive, window closed, no usable match rules,
 * or no resolvable UUID).
 *
 * @param string $kind Rule kind, as recorded on the cache entry.
 * @param array<string, mixed> $object Raw object data.
 *
 * @return array<string, mixed>|null The cache entry, or null when inadmissible.
 *
 * @spec openspec/specs/entity-publication-policies/spec.md
 */',
        'startLine' => 63,
        'endLine' => 95,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyRuleNormaliser',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyRuleNormaliser',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyRuleNormaliser',
        'aliasName' => NULL,
      ),
      'flattenTranslatable' => 
      array (
        'name' => 'flattenTranslatable',
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
            'startLine' => 128,
            'endLine' => 128,
            'startColumn' => 39,
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
 * Reduce a possibly language-keyed value to a single display string.
 *
 * `publicationProhibition.primaryName` is declared `translatable: true` in
 * `lib/Settings/filinq_register.json`, and OpenRegister wraps a
 * translatable scalar under its default language on save
 * (`SaveObject::…` — "Normalize translatable properties (wrap simple values
 * under default language)"). So the stored value is `{"en": "…"}`, not a
 * bare string.
 *
 * The HTTP read path never showed this, because Filinq registers OR\'s
 * TranslationHandler and it resolves the map before the response is built.
 * PolicyMatchService does NOT go through that path — it calls
 * `searchObjectsBySlug()` directly — so the raw map reached a `(string)`
 * cast here and every consumer received the literal string "Array":
 * the prohibition rejection\'s `ruleName`, and the `ruleName` that
 * `anonymisation-prohibition-gate` REQUIRES on the anonymise gate\'s 422
 * body ("the prohibition rule\'s `primaryName`, included to help the
 * operator understand WHY the entity is required to be anonymised").
 *
 * Fallback chain matches TemplateLanguageService::resolveFieldValue():
 * nl → en → first available. That service is not reused here because it
 * resolves the *user\'s* preferred language, and the matcher also runs in
 * system contexts (cron, event listeners) where there is no user.
 *
 * @param mixed $value Raw value: a string, a language-keyed map, or null.
 *
 * @return string The display string, or \'\' when nothing usable is present.
 *
 * @spec openspec/specs/entity-publication-policies/spec.md
 */',
        'startLine' => 128,
        'endLine' => 146,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyRuleNormaliser',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyRuleNormaliser',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyRuleNormaliser',
        'aliasName' => NULL,
      ),
      'validityWindow' => 
      array (
        'name' => 'validityWindow',
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
            'startLine' => 156,
            'endLine' => 156,
            'startColumn' => 34,
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
 * Resolve the row\'s validity window, or null when it is currently closed.
 *
 * @param array<string, mixed> $object Raw object data.
 *
 * @return array{from: DateTimeImmutable|null, until: DateTimeImmutable|null}|null
 *                                                                                 The parsed bounds, or null when now falls outside them.
 */',
        'startLine' => 156,
        'endLine' => 174,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyRuleNormaliser',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyRuleNormaliser',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyRuleNormaliser',
        'aliasName' => NULL,
      ),
      'wellFormedRules' => 
      array (
        'name' => 'wellFormedRules',
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
            'startLine' => 183,
            'endLine' => 183,
            'startColumn' => 35,
            'endColumn' => 51,
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
 * Keep only the `{type, value}` entries of a raw matchRules array.
 *
 * @param array<mixed> $matchRules Raw match rules as stored.
 *
 * @return array<int, array<string, mixed>> The well-formed rules, re-indexed.
 */',
        'startLine' => 183,
        'endLine' => 192,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyRuleNormaliser',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyRuleNormaliser',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyRuleNormaliser',
        'aliasName' => NULL,
      ),
      'readUuid' => 
      array (
        'name' => 'readUuid',
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
            'startLine' => 201,
            'endLine' => 201,
            'startColumn' => 28,
            'endColumn' => 40,
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
 * Read a row\'s UUID from its `@self` envelope or its top-level keys.
 *
 * @param array<string, mixed> $object Raw object data.
 *
 * @return string The UUID, or an empty string when the row carries none.
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyRuleNormaliser',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyRuleNormaliser',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyRuleNormaliser',
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
            'startLine' => 217,
            'endLine' => 217,
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
 * Parse an ISO-8601 string into DateTimeImmutable; null on failure.
 *
 * @param string $value The raw value (may be empty).
 *
 * @return DateTimeImmutable|null The parsed instant, or null.
 */',
        'startLine' => 217,
        'endLine' => 228,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyRuleNormaliser',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyRuleNormaliser',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyRuleNormaliser',
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