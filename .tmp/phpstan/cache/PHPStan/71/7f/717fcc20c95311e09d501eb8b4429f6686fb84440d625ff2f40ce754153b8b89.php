<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/BaseLabelResolver.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\BaseLabelResolver
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-bb3bc31910cb0191a2c3b5a660e433a8aec1e475e2a4a0518b5e69b8f0bdc82d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/BaseLabelResolver.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
    'shortName' => 'BaseLabelResolver',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Resolves `base` references to display names and toelichtingen.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 40,
    'endLine' => 340,
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
      'OBJECT_ENTITY_CLASS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'name' => 'OBJECT_ENTITY_CLASS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'\\OCA\\OpenRegister\\Db\\ObjectEntity\'',
          'attributes' => 
          array (
            'startLine' => 50,
            'endLine' => 50,
            'startTokenPos' => 45,
            'startFilePos' => 1487,
            'endTokenPos' => 45,
            'endFilePos' => 1521,
          ),
        ),
        'docComment' => '/**
 * Fully-qualified name of OpenRegister\'s ObjectEntity.
 *
 * Referenced as a string, not as a `::class` constant: OpenRegister is an
 * optional dependency and must never be autoloaded by a type reference.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 50,
        'endLine' => 50,
        'startColumn' => 2,
        'endColumn' => 73,
      ),
    ),
    'immediateProperties' => 
    array (
      'repository' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'name' => 'repository',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 61,
        'endLine' => 61,
        'startColumn' => 3,
        'endColumn' => 54,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
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
        'startLine' => 62,
        'endLine' => 62,
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
          'repository' => 
          array (
            'name' => 'repository',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 61,
            'endLine' => 61,
            'startColumn' => 3,
            'endColumn' => 54,
            'parameterIndex' => 0,
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
            'startLine' => 62,
            'endLine' => 62,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 1,
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
 * @param DossierObjectRepository $repository OpenRegister object access.
 * @param LoggerInterface $logger Structured logger.
 *
 * @return void
 */',
        'startLine' => 60,
        'endLine' => 65,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'aliasName' => NULL,
      ),
      'resolve' => 
      array (
        'name' => 'resolve',
        'parameters' => 
        array (
          'baseRefs' => 
          array (
            'name' => 'baseRefs',
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
            'startLine' => 88,
            'endLine' => 88,
            'startColumn' => 26,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolve a list of `base` references (slugs or UUIDs) to human-readable labels.
 *
 * Looks each reference up in the dossier register\'s `base` schema and
 * returns a map `{ref => {name, description}}`. Unresolved references
 * (rule deleted, malformed reference, etc.) resolve to the raw reference
 * so the rendered report flags the data gap rather than silently dropping
 * the row.
 *
 * Wave 1.1\'s `add-dossier-schema` ships `bases` as plain slug strings
 * (per the v1 trade-off documented in its design.md §D1), so this method
 * primarily resolves by slug; UUID fallback is supported for
 * forward-compatibility with a future `$ref` enforcement story.
 *
 * @param array<int, string> $baseRefs Slugs or UUIDs of base records.
 *
 * @return array<string, array{name: string, description: string}> Map from each
 *                                                                 reference to its
 *                                                                 display name and
 *                                                                 Woo Art. 5 toelichting.
 */',
        'startLine' => 88,
        'endLine' => 110,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'aliasName' => NULL,
      ),
      'groupByName' => 
      array (
        'name' => 'groupByName',
        'parameters' => 
        array (
          'detail' => 
          array (
            'name' => 'detail',
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
            'startLine' => 123,
            'endLine' => 123,
            'startColumn' => 30,
            'endColumn' => 42,
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
 * Group resolved base labels by display name for the report legend.
 *
 * Distinct references may resolve to the same grondslag, so entries are
 * deduplicated by name; nameless entries are dropped and the first
 * non-empty description wins.
 *
 * @param array<string, array{name: string, description: string}> $detail Output of {@see resolve}.
 *
 * @return array<int, array{name: string, description: string}> Distinct bases, sorted by name.
 */',
        'startLine' => 123,
        'endLine' => 144,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'aliasName' => NULL,
      ),
      'rawLabels' => 
      array (
        'name' => 'rawLabels',
        'parameters' => 
        array (
          'baseRefs' => 
          array (
            'name' => 'baseRefs',
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
            'startLine' => 153,
            'endLine' => 153,
            'startColumn' => 29,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Best-effort labels used when OpenRegister cannot be consulted.
 *
 * @param array<int, string> $baseRefs The references to label.
 *
 * @return array<string, array{name: string, description: string}> Raw-reference labels.
 */',
        'startLine' => 153,
        'endLine' => 160,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'aliasName' => NULL,
      ),
      'loadBaseLookups' => 
      array (
        'name' => 'loadBaseLookups',
        'parameters' => 
        array (
          'objectService' => 
          array (
            'name' => 'objectService',
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
            'startLine' => 180,
            'endLine' => 180,
            'startColumn' => 35,
            'endColumn' => 54,
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
 * Build the slug→ and uuid→ lookups from the register\'s `base` objects.
 *
 * Pulls every `base` object in one shot — the canonical set is six Woo
 * Art. 5 grondslagen plus any tenant-added entries; very small
 * cardinality. Both lookup shapes are built so the resolver works
 * regardless of which reference shape the `bases` column carries.
 *
 * `searchObjectsBySlug` is the path that resolves slug filters to numeric
 * IDs and reaches the magic-mapped `dossier` register; `findAll` with slug
 * filters returns nothing because the magic tables aren\'t visible to the
 * generic getHandler path.
 *
 * @param mixed $objectService OpenRegister\'s ObjectService.
 *
 * @return array<string, array<string,string>> Keyed `slugToName`, `uuidToName`,
 *                                             `slugToDesc` and `uuidToDesc`.
 */',
        'startLine' => 180,
        'endLine' => 208,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'aliasName' => NULL,
      ),
      'withBase' => 
      array (
        'name' => 'withBase',
        'parameters' => 
        array (
          'lookups' => 
          array (
            'name' => 'lookups',
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
            'startLine' => 218,
            'endLine' => 218,
            'startColumn' => 28,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'base' => 
          array (
            'name' => 'base',
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
            'startLine' => 218,
            'endLine' => 218,
            'startColumn' => 44,
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
 * Fold one `base` object into the lookups.
 *
 * @param array<string, array<string,string>> $lookups The lookups so far.
 * @param array<string, mixed> $base One `base` object payload.
 *
 * @return array<string, array<string,string>> The updated lookups.
 */',
        'startLine' => 218,
        'endLine' => 248,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'aliasName' => NULL,
      ),
      'labelFor' => 
      array (
        'name' => 'labelFor',
        'parameters' => 
        array (
          'refString' => 
          array (
            'name' => 'refString',
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
            'startLine' => 258,
            'endLine' => 258,
            'startColumn' => 28,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'lookups' => 
          array (
            'name' => 'lookups',
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
            'startLine' => 258,
            'endLine' => 258,
            'startColumn' => 47,
            'endColumn' => 60,
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
 * Resolve one reference against the lookups, falling back to the raw ref.
 *
 * @param string $refString The reference.
 * @param array<string, array<string,string>> $lookups The lookups.
 *
 * @return array{name: string, description: string} The label.
 */',
        'startLine' => 258,
        'endLine' => 274,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'aliasName' => NULL,
      ),
      'extractObjects' => 
      array (
        'name' => 'extractObjects',
        'parameters' => 
        array (
          'result' => 
          array (
            'name' => 'result',
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
            'startLine' => 286,
            'endLine' => 286,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Coerce an ObjectService search result into a plain array of object payloads.
 *
 * The result may be ObjectEntity instances, plain associative arrays, or a
 * `{results: [...]}` envelope depending on the path that served it.
 *
 * @param mixed $result The raw search return value.
 *
 * @return array<int, array<string, mixed>> The normalised payloads.
 */',
        'startLine' => 286,
        'endLine' => 304,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'aliasName' => NULL,
      ),
      'normaliseItem' => 
      array (
        'name' => 'normaliseItem',
        'parameters' => 
        array (
          'item' => 
          array (
            'name' => 'item',
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
            'startLine' => 318,
            'endLine' => 318,
            'startColumn' => 33,
            'endColumn' => 43,
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
 * Normalise a single search-result item to a payload array, or null.
 *
 * `ObjectEntity::jsonSerialize()` returns a flat payload that includes a
 * synthetic `@self` block (id, slug, register, schema, …) reconstructed
 * from the entity\'s columns. That\'s the shape {@see resolve} needs, so it
 * is preferred when the item is a real ObjectEntity.
 *
 * @param mixed $item One search-result item.
 *
 * @return array<string, mixed>|null The payload, or null to skip the item.
 */',
        'startLine' => 318,
        'endLine' => 339,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
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