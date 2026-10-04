<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/LegalBasisProposalService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\LegalBasisProposalService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-5c7462ed29aa8b5a835c11be242dbf0e1adad44b9436252def5faa7f1a7a582b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/LegalBasisProposalService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
    'shortName' => 'LegalBasisProposalService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Proposes grondslagen per entity type and pre-fills them at detection time.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/propose-grondslag-per-entity-type/specs/grondslag-proposal/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 56,
    'endLine' => 425,
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
      'CONFIG_KEY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'name' => 'CONFIG_KEY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq.grondslagen.entity_type_bases\'',
          'attributes' => 
          array (
            'startLine' => 63,
            'endLine' => 63,
            'startTokenPos' => 60,
            'startFilePos' => 2260,
            'endTokenPos' => 60,
            'endFilePos' => 2297,
          ),
        ),
        'docComment' => '/**
 * App config key holding the entity-type → base-slug[] mapping.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 63,
        'endLine' => 63,
        'startColumn' => 2,
        'endColumn' => 66,
      ),
      'ENABLED_TYPES_CONFIG_KEY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'name' => 'ENABLED_TYPES_CONFIG_KEY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq.anonymisation.enabled_entity_types\'',
          'attributes' => 
          array (
            'startLine' => 71,
            'endLine' => 71,
            'startTokenPos' => 73,
            'startFilePos' => 2520,
            'endTokenPos' => 73,
            'endFilePos' => 2562,
          ),
        ),
        'docComment' => '/**
 * App config key holding the JSON array of entity types left enabled for
 * automatic detection. Unset or empty means "all types" (the default).
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 71,
        'endLine' => 71,
        'startColumn' => 2,
        'endColumn' => 85,
      ),
      'APP_ID' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'name' => 'APP_ID',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq\'',
          'attributes' => 
          array (
            'startLine' => 78,
            'endLine' => 78,
            'startTokenPos' => 86,
            'startFilePos' => 2677,
            'endTokenPos' => 86,
            'endFilePos' => 2684,
          ),
        ),
        'docComment' => '/**
 * The Filinq app id, used as the app-config namespace.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 78,
        'endLine' => 78,
        'startColumn' => 2,
        'endColumn' => 33,
      ),
      'CURATED_ENTITY_TYPES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'name' => 'CURATED_ENTITY_TYPES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[
    // Classic/light flavor — verified against live detections.
    \'PERSON\',
    \'LOCATION\',
    \'ORGANIZATION\',
    \'EMAIL\',
    \'PHONE\',
    \'DATE\',
    \'BSN\',
    \'IBAN\',
    \'KENTEKEN\',
    // Additional GLiNER targets emitted by the GPU flavor.
    \'STREET_ADDRESS\',
    \'NORP\',
    \'POLITICAL_PARTY\',
    \'INCOME\',
    \'EDUCATION_LEVEL\',
    // Dynamic, per-organisation-list type contributed by
    // CustomDictionaryMatchService (custom-dictionary-recognition,
    // design.md §D4) — distinct from the fixed backend-emitted types
    // above: this one toggle enables/disables ALL active custom
    // dictionaries\' automatic detection at once.
    \'CUSTOM_DICTIONARY\',
]',
          'attributes' => 
          array (
            'startLine' => 93,
            'endLine' => 116,
            'startTokenPos' => 99,
            'startFilePos' => 3364,
            'endTokenPos' => 160,
            'endFilePos' => 4009,
          ),
        ),
        'docComment' => '/**
 * Curated list of entity types offered in the settings selector (v1).
 *
 * The identifiers MUST match the `entity_type` strings the backend emits
 * on detections, so the mapping keys line up at pre-fill time. The first
 * group is verified against this deployment\'s classic/light flavor
 * (spaCy NER + Dutch regex). The second group are the additional GLiNER
 * targets the GPU flavor emits (kept so the same instance works against
 * either flavor). This constant is the single seam to replace when the
 * backend exposes a supported-types endpoint (deferred — see design.md D4).
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 93,
        'endLine' => 116,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'baseCatalog' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'name' => 'baseCatalog',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * OpenRegister-facing reads (service resolution + `base` records).
 *
 * @var LegalBasisCatalog
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 123,
        'endLine' => 123,
        'startColumn' => 2,
        'endColumn' => 49,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'config' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
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
        'startLine' => 141,
        'endLine' => 141,
        'startColumn' => 3,
        'endColumn' => 37,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
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
        'startLine' => 144,
        'endLine' => 144,
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
            'startLine' => 141,
            'endLine' => 141,
            'startColumn' => 3,
            'endColumn' => 37,
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 142,
            'endLine' => 142,
            'startColumn' => 3,
            'endColumn' => 25,
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 143,
            'endLine' => 143,
            'startColumn' => 3,
            'endColumn' => 31,
            'parameterIndex' => 2,
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
            'startLine' => 144,
            'endLine' => 144,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'baseCatalog' => 
          array (
            'name' => 'baseCatalog',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 145,
                'endLine' => 145,
                'startTokenPos' => 218,
                'startFilePos' => 5089,
                'endTokenPos' => 218,
                'endFilePos' => 5092,
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
                      'name' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 145,
            'endLine' => 145,
            'startColumn' => 3,
            'endColumn' => 40,
            'parameterIndex' => 4,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor for LegalBasisProposalService.
 *
 * The catalog is an injected collaborator; the null default keeps the
 * historical four-argument signature usable, in which case an equivalent
 * catalog is built from the same three dependencies.
 *
 * @param IAppConfig $config App configuration (mapping storage).
 * @param IAppManager $appManager App manager (OpenRegister availability).
 * @param ContainerInterface $container DI container resolving OpenRegister services at runtime.
 * @param LoggerInterface $logger Logger for best-effort diagnostics.
 * @param LegalBasisCatalog|null $baseCatalog OpenRegister-facing reads; built from the above when null.
 *
 * @return void
 */',
        'startLine' => 140,
        'endLine' => 149,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'aliasName' => NULL,
      ),
      'getSelectableEntityTypes' => 
      array (
        'name' => 'getSelectableEntityTypes',
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
 * Return the entity types selectable in the settings UI.
 *
 * Version 1 returns the curated constant. This is the single seam to swap
 * for a live backend-sourced list when that endpoint exists.
 *
 * @return array<int, string> Entity type identifiers.
 */',
        'startLine' => 159,
        'endLine' => 161,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'aliasName' => NULL,
      ),
      'getEnabledEntityTypes' => 
      array (
        'name' => 'getEnabledEntityTypes',
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
 * Return the entity types currently enabled for automatic detection.
 *
 * Backs the settings selector, which is all-on by default: when nothing has
 * been stored yet every curated type is returned so the UI shows them all
 * checked. A stored selection is sanitised against the curated list so an
 * unknown/stale type can never surface in the UI.
 *
 * @return array<int, string> Enabled entity type identifiers.
 */',
        'startLine' => 173,
        'endLine' => 180,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'aliasName' => NULL,
      ),
      'getEntityTypeWhitelist' => 
      array (
        'name' => 'getEntityTypeWhitelist',
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
 * Return the entity-type whitelist to hand to OpenRegister\'s analysis call.
 *
 * Null means "do not constrain detection" (detect every type) and is
 * returned whenever the operator has everything enabled — either because
 * nothing was stored or because the stored selection covers the full
 * curated set. Only a genuine subset yields a whitelist, keeping the
 * default behaviour identical to today and avoiding a filter that would
 * needlessly narrow the detector\'s vocabulary.
 *
 * @return array<int, string>|null Entity type whitelist, or null for "all".
 */',
        'startLine' => 194,
        'endLine' => 201,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'aliasName' => NULL,
      ),
      'readEnabledEntityTypes' => 
      array (
        'name' => 'readEnabledEntityTypes',
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
 * Read and sanitise the stored enabled-types selection.
 *
 * Returns null when unset, empty, malformed, or reduced to nothing after
 * sanitisation — every one of those cases means "all types". Otherwise the
 * stored ids are intersected with the curated list and returned in curated
 * order, dropping unknown/stale/duplicate entries.
 *
 * @return array<int, string>|null Sanitised enabled subset, or null for "all".
 */',
        'startLine' => 213,
        'endLine' => 238,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'aliasName' => NULL,
      ),
      'getMapping' => 
      array (
        'name' => 'getMapping',
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
 * Return the configured entity-type → base-slug[] mapping.
 *
 * @return array<string, array<int, string>> Mapping; empty when unset or malformed.
 */',
        'startLine' => 245,
        'endLine' => 271,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'aliasName' => NULL,
      ),
      'getAvailableBases' => 
      array (
        'name' => 'getAvailableBases',
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
 * List the available `base` (grondslag) records for the settings selector.
 *
 * Returns slug + name + description per base so the UI can offer them as
 * options. Operator-added bases appear automatically. Best-effort: an
 * empty array is returned when OpenRegister is unavailable or the lookup
 * fails — the settings page must still render.
 *
 * @return array<int, array{slug: string, name: string, description: string}> Available bases.
 */',
        'startLine' => 283,
        'endLine' => 285,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'aliasName' => NULL,
      ),
      'applyProposals' => 
      array (
        'name' => 'applyProposals',
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
            'startLine' => 301,
            'endLine' => 301,
            'startColumn' => 33,
            'endColumn' => 43,
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
 * Pre-fill proposed bases onto a file\'s freshly-detected entity relations.
 *
 * For each EntityRelation of the file whose `bases` is empty, sets the
 * configured base slug(s) for that entity\'s type. Relations with existing
 * bases are skipped (non-clobber); entity types with no mapping are left
 * empty. Best-effort and idempotent — safe to call after every detection.
 *
 * @param int $fileId Nextcloud file id whose relations were just detected.
 *
 * @return int Number of relations a proposal was written to.
 *
 * @spec openspec/changes/propose-grondslag-per-entity-type/specs/grondslag-proposal/spec.md#requirement-proposed-grondslag-pre-filled-at-detection-time
 */',
        'startLine' => 301,
        'endLine' => 333,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'aliasName' => NULL,
      ),
      'proposeForDetection' => 
      array (
        'name' => 'proposeForDetection',
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
            'startLine' => 348,
            'endLine' => 348,
            'startColumn' => 39,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'mapping' => 
          array (
            'name' => 'mapping',
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
            'startColumn' => 54,
            'endColumn' => 67,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'detection' => 
          array (
            'name' => 'detection',
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
            'startLine' => 348,
            'endLine' => 348,
            'startColumn' => 70,
            'endColumn' => 85,
            'parameterIndex' => 2,
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
            'startLine' => 348,
            'endLine' => 348,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Pre-fill the proposed bases for a single detection, when applicable.
 *
 * Returns 1 when a proposal was written, 0 otherwise (unmapped type,
 * missing relation id, already-filled relation, or a best-effort failure).
 *
 * @param mixed $mapper The EntityRelationMapper.
 * @param array<string, array<int, string>> $mapping Entity-type → base-slug[] mapping.
 * @param mixed $detection One detection row from findEntitiesForFile.
 * @param int $fileId File id (for log context).
 *
 * @return int 1 if a proposal was written, 0 otherwise.
 */',
        'startLine' => 348,
        'endLine' => 377,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'aliasName' => NULL,
      ),
      'enrichEntitiesWithBases' => 
      array (
        'name' => 'enrichEntitiesWithBases',
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
            'startLine' => 395,
            'endLine' => 395,
            'startColumn' => 42,
            'endColumn' => 56,
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
            'startLine' => 395,
            'endLine' => 395,
            'startColumn' => 59,
            'endColumn' => 69,
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
 * Merge each relation\'s current `bases` into the detection rows.
 *
 * `findEntitiesForFile` does not select `bases`, so callers that surface
 * detections for review (e.g. the anonymisation sidebar) enrich them here
 * after {@see applyProposals} — otherwise the proposed grondslag would be
 * stored but invisible in the review UI. Best-effort: the rows are
 * returned unchanged when OpenRegister is unavailable or the lookup fails.
 *
 * @param array<int, mixed> $entities Detection rows (each carrying relation_id).
 * @param int $fileId File whose relations supply the bases.
 *
 * @return array<int, mixed> The detection rows with a `bases` key populated.
 *
 * @spec openspec/changes/propose-grondslag-per-entity-type/specs/grondslag-proposal/spec.md#requirement-proposed-grondslag-pre-filled-at-detection-time
 */',
        'startLine' => 395,
        'endLine' => 424,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasisProposalService',
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