<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/LegalBasisCatalog.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\LegalBasisCatalog
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-82ab2f0158afb3b6c8739c85c7e944ef2348a0a72c6d73e7aefc3c22ef2b9fc4',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/LegalBasisCatalog.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
    'shortName' => 'LegalBasisCatalog',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Resolves OpenRegister collaborators and reads `base` (grondslag) records.
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
    'startLine' => 50,
    'endLine' => 282,
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
      'OPENREGISTER_APP_ID' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
        'name' => 'OPENREGISTER_APP_ID',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'openregister\'',
          'attributes' => 
          array (
            'startLine' => 57,
            'endLine' => 57,
            'startTokenPos' => 55,
            'startFilePos' => 1835,
            'endTokenPos' => 55,
            'endFilePos' => 1848,
          ),
        ),
        'docComment' => '/**
 * The OpenRegister app id.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 2,
        'endColumn' => 52,
      ),
    ),
    'immediateProperties' => 
    array (
      'appManager' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
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
        'startLine' => 69,
        'endLine' => 69,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
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
        'startLine' => 70,
        'endLine' => 70,
        'startColumn' => 3,
        'endColumn' => 48,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
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
        'startLine' => 71,
        'endLine' => 71,
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
            'startLine' => 69,
            'endLine' => 69,
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
            'startLine' => 70,
            'endLine' => 70,
            'startColumn' => 3,
            'endColumn' => 48,
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
            'startLine' => 71,
            'endLine' => 71,
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
 * Constructor for LegalBasisCatalog.
 *
 * @param IAppManager $appManager App manager (OpenRegister availability).
 * @param ContainerInterface $container DI container resolving OpenRegister services at runtime.
 * @param LoggerInterface $logger Logger for best-effort diagnostics.
 *
 * @return void
 */',
        'startLine' => 68,
        'endLine' => 74,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
        'aliasName' => NULL,
      ),
      'resolve' => 
      array (
        'name' => 'resolve',
        'parameters' => 
        array (
          'className' => 
          array (
            'name' => 'className',
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
            'startLine' => 88,
            'endLine' => 88,
            'startColumn' => 26,
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
            'name' => 'mixed',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolve an OpenRegister service/mapper by class name, or null.
 *
 * Returns null (best-effort) when OpenRegister is not installed or the
 * container cannot resolve the class — callers degrade gracefully.
 *
 * @param string $className Fully-qualified OpenRegister class name.
 *
 * @return mixed The resolved instance, or null.
 *
 * @spec openspec/changes/propose-grondslag-per-entity-type/specs/grondslag-proposal/spec.md#requirement-grondslag-selector-in-admin-settings
 */',
        'startLine' => 88,
        'endLine' => 103,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
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
 *
 * @spec openspec/changes/propose-grondslag-per-entity-type/specs/grondslag-proposal/spec.md#requirement-grondslag-selector-in-admin-settings
 */',
        'startLine' => 117,
        'endLine' => 148,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
        'aliasName' => NULL,
      ),
      'toBaseRecord' => 
      array (
        'name' => 'toBaseRecord',
        'parameters' => 
        array (
          'base' => 
          array (
            'name' => 'base',
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
            'startLine' => 160,
            'endLine' => 160,
            'startColumn' => 32,
            'endColumn' => 42,
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
 * Convert one raw `base` object into the selector record shape.
 *
 * Returns null when the object is not usable as an option — it is not an
 * array, or it lacks either a slug or a name.
 *
 * @param mixed $base One raw `base` object from OpenRegister.
 *
 * @return array{slug: string, name: string, description: string}|null The record, or null.
 */',
        'startLine' => 160,
        'endLine' => 191,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
        'aliasName' => NULL,
      ),
      'asString' => 
      array (
        'name' => 'asString',
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
            'startLine' => 208,
            'endLine' => 208,
            'startColumn' => 35,
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
 * Coerce an OpenRegister property to a string without warning on arrays.
 *
 * OR object properties are `mixed`: a field may be a scalar, null, or an
 * array (multi-value / nested object). A bare `(string) $value` cast on an
 * array raises "Array to string conversion" — harmless-looking, but on the
 * object-write path it fires once per field per object and buries the
 * request (and the log) under thousands of warnings.
 *
 * @param mixed $value The raw property value.
 *
 * @return string The scalar rendering, or \'\' when the value is not scalar.
 *
 * @psalm-pure
 */',
        'startLine' => 208,
        'endLine' => 214,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
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
            'startLine' => 227,
            'endLine' => 227,
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
 * Normalise a raw OpenRegister search result into a plain list of objects.
 *
 * The result may be a paginated envelope (`[\'results\' => [...]]`) or a
 * bare iterable, and each item may be an `ObjectEntity` rather than an
 * array.
 *
 * @param mixed $result The raw search result.
 *
 * @return array<int, array<string, mixed>> The list of object arrays.
 */',
        'startLine' => 227,
        'endLine' => 245,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
        'aliasName' => NULL,
      ),
      'normaliseSearchItem' => 
      array (
        'name' => 'normaliseSearchItem',
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
            'startLine' => 257,
            'endLine' => 257,
            'startColumn' => 39,
            'endColumn' => 49,
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
 * Normalise a single search-result item to an array, or null to skip it.
 *
 * Items may be plain arrays or `ObjectEntity` objects; the latter are
 * flattened via `jsonSerialize()` to the `@self` + property payload.
 *
 * @param mixed $item One search-result item.
 *
 * @return array<string, mixed>|null The flattened object, or null.
 */',
        'startLine' => 257,
        'endLine' => 281,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
        'implementingClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
        'currentClassName' => 'OCA\\Filinq\\Service\\LegalBasisCatalog',
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