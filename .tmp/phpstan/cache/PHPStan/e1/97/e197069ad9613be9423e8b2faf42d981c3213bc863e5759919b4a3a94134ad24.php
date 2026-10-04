<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Comparison/RedactionAnnotator.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Comparison\RedactionAnnotator
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-b0ce8fbb902809046334126c148866cb495dfd848936aa2e7c3382fbc6064314',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Comparison/RedactionAnnotator.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Comparison',
    'name' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
    'shortName' => 'RedactionAnnotator',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Annotates diff hunks with redaction metadata and completeness.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Comparison
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-comparison/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 48,
    'endLine' => 367,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
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
        'startLine' => 59,
        'endLine' => 59,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
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
        'startLine' => 60,
        'endLine' => 60,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
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
        'startLine' => 61,
        'endLine' => 61,
        'startColumn' => 3,
        'endColumn' => 48,
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
            'startLine' => 59,
            'endLine' => 59,
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
            'startLine' => 60,
            'endLine' => 60,
            'startColumn' => 3,
            'endColumn' => 42,
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
            'startLine' => 61,
            'endLine' => 61,
            'startColumn' => 3,
            'endColumn' => 48,
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
 * Constructor.
 *
 * @param LoggerInterface $logger Logger for diagnostics.
 * @param IAppManager $appManager App manager (OpenRegister availability check).
 * @param ContainerInterface $container DI container for lazy OR resolution.
 *
 * @return void
 */',
        'startLine' => 58,
        'endLine' => 64,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Comparison',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'currentClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'aliasName' => NULL,
      ),
      'annotate' => 
      array (
        'name' => 'annotate',
        'parameters' => 
        array (
          'hunks' => 
          array (
            'name' => 'hunks',
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
            'startLine' => 80,
            'endLine' => 80,
            'startColumn' => 27,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'sourceFileId' => 
          array (
            'name' => 'sourceFileId',
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
            'startLine' => 80,
            'endLine' => 80,
            'startColumn' => 41,
            'endColumn' => 57,
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
 * Annotate change hunks with redaction metadata + completeness signal.
 *
 * Falls back to a plain diff (status \'unavailable\') when OpenRegister is
 * absent, and \'none\' when no relations exist for the source file
 * (unrelated pair).
 *
 * @param array<int, array<string, mixed>> $hunks The diff hunks.
 * @param int $sourceFileId The source (left) file id.
 *
 * @return array{hunks: array<int, array<string, mixed>>, status: string, unredactedEntities: array<int, array<string, mixed>>}
 *
 * @spec openspec/specs/document-comparison/spec.md
 */',
        'startLine' => 80,
        'endLine' => 133,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Comparison',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'currentClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'aliasName' => NULL,
      ),
      'resolveMapper' => 
      array (
        'name' => 'resolveMapper',
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
 * Resolve the OpenRegister EntityRelationMapper, or null when unavailable.
 *
 * @return mixed The mapper or null.
 */',
        'startLine' => 140,
        'endLine' => 146,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Comparison',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'currentClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'aliasName' => NULL,
      ),
      'lookupRelations' => 
      array (
        'name' => 'lookupRelations',
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
            'startLine' => 157,
            'endLine' => 157,
            'startColumn' => 35,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'sourceFileId' => 
          array (
            'name' => 'sourceFileId',
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
            'startLine' => 157,
            'endLine' => 157,
            'startColumn' => 50,
            'endColumn' => 66,
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
 * Read the entity relations and joined entity rows for a source file.
 *
 * @param mixed $mapper The OpenRegister EntityRelationMapper.
 * @param int $sourceFileId The source (left) file id.
 *
 * @return array{relations: mixed, joined: mixed}|null The rows, or null when
 *                                                     the lookup failed.
 */',
        'startLine' => 157,
        'endLine' => 168,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Comparison',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'currentClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'aliasName' => NULL,
      ),
      'indexEntityMeta' => 
      array (
        'name' => 'indexEntityMeta',
        'parameters' => 
        array (
          'joined' => 
          array (
            'name' => 'joined',
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
            'startLine' => 177,
            'endLine' => 177,
            'startColumn' => 35,
            'endColumn' => 47,
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
 * Index entity metadata (type + canonical value/name) by entity id.
 *
 * @param mixed $joined The joined entity rows.
 *
 * @return array<int, array{entityType:string, entityName:string, value:string}>
 */',
        'startLine' => 177,
        'endLine' => 193,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Comparison',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'currentClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'aliasName' => NULL,
      ),
      'buildAnonymiseSet' => 
      array (
        'name' => 'buildAnonymiseSet',
        'parameters' => 
        array (
          'relations' => 
          array (
            'name' => 'relations',
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
            'startLine' => 202,
            'endLine' => 202,
            'startColumn' => 37,
            'endColumn' => 52,
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
 * Build the anonymise set: non-skip relations with their replacement keys.
 *
 * @param mixed $relations The EntityRelation rows.
 *
 * @return array<int, array{replacement:string, matched:bool}>
 */',
        'startLine' => 202,
        'endLine' => 217,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Comparison',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'currentClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'aliasName' => NULL,
      ),
      'unredactedEntities' => 
      array (
        'name' => 'unredactedEntities',
        'parameters' => 
        array (
          'anonymiseSet' => 
          array (
            'name' => 'anonymiseSet',
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
            'startLine' => 227,
            'endLine' => 227,
            'startColumn' => 38,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'entityMeta' => 
          array (
            'name' => 'entityMeta',
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
            'startLine' => 227,
            'endLine' => 227,
            'startColumn' => 59,
            'endColumn' => 75,
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
 * Collect the anonymise-set entities that matched zero hunks.
 *
 * @param array<int, array{replacement:string, matched:bool}> $anonymiseSet The anonymise set.
 * @param array<int, array{entityType:string, entityName:string, value:string}> $entityMeta Entity metadata.
 *
 * @return array<int, array<string, mixed>> The unredacted entities.
 */',
        'startLine' => 227,
        'endLine' => 239,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Comparison',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'currentClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'aliasName' => NULL,
      ),
      'matchHunkToEntity' => 
      array (
        'name' => 'matchHunkToEntity',
        'parameters' => 
        array (
          'hunk' => 
          array (
            'name' => 'hunk',
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
            'startLine' => 250,
            'endLine' => 250,
            'startColumn' => 37,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'anonymiseSet' => 
          array (
            'name' => 'anonymiseSet',
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
            'startLine' => 250,
            'endLine' => 250,
            'startColumn' => 50,
            'endColumn' => 68,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'entityMeta' => 
          array (
            'name' => 'entityMeta',
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
            'startLine' => 250,
            'endLine' => 250,
            'startColumn' => 71,
            'endColumn' => 87,
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
 * Match a hunk to an entity by replacement-key (insert) or value (delete).
 *
 * @param array<string, mixed> $hunk A diff hunk.
 * @param array<int, array{replacement:string, matched:bool}> $anonymiseSet The anonymise set.
 * @param array<int, array{entityType:string, entityName:string, value:string}> $entityMeta Entity metadata.
 *
 * @return array{entityId:int, entityType:string, matchedBy:string}|null The match or null.
 */',
        'startLine' => 250,
        'endLine' => 272,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Comparison',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'currentClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'aliasName' => NULL,
      ),
      'matchByReplacementKey' => 
      array (
        'name' => 'matchByReplacementKey',
        'parameters' => 
        array (
          'insertedText' => 
          array (
            'name' => 'insertedText',
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
            'startLine' => 283,
            'endLine' => 283,
            'startColumn' => 41,
            'endColumn' => 60,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'anonymiseSet' => 
          array (
            'name' => 'anonymiseSet',
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
            'startLine' => 283,
            'endLine' => 283,
            'startColumn' => 63,
            'endColumn' => 81,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'entityMeta' => 
          array (
            'name' => 'entityMeta',
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
            'startLine' => 283,
            'endLine' => 283,
            'startColumn' => 84,
            'endColumn' => 100,
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
 * Match an inserted span against the entities\' replacement keys.
 *
 * @param string $insertedText The inserted span.
 * @param array<int, array{replacement:string, matched:bool}> $anonymiseSet The anonymise set.
 * @param array<int, array{entityType:string, entityName:string, value:string}> $entityMeta Entity metadata.
 *
 * @return array{entityId:int, entityType:string, matchedBy:string}|null The match or null.
 */',
        'startLine' => 283,
        'endLine' => 299,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Comparison',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'currentClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'aliasName' => NULL,
      ),
      'matchByCanonicalValue' => 
      array (
        'name' => 'matchByCanonicalValue',
        'parameters' => 
        array (
          'removedText' => 
          array (
            'name' => 'removedText',
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
            'startLine' => 310,
            'endLine' => 310,
            'startColumn' => 41,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'anonymiseSet' => 
          array (
            'name' => 'anonymiseSet',
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
            'startLine' => 310,
            'endLine' => 310,
            'startColumn' => 62,
            'endColumn' => 80,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'entityMeta' => 
          array (
            'name' => 'entityMeta',
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
            'startLine' => 310,
            'endLine' => 310,
            'startColumn' => 83,
            'endColumn' => 99,
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
 * Match a removed span against the entities\' canonical values.
 *
 * @param string $removedText The removed span.
 * @param array<int, array{replacement:string, matched:bool}> $anonymiseSet The anonymise set.
 * @param array<int, array{entityType:string, entityName:string, value:string}> $entityMeta Entity metadata.
 *
 * @return array{entityId:int, entityType:string, matchedBy:string}|null The match or null.
 */',
        'startLine' => 310,
        'endLine' => 327,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Comparison',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'currentClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'aliasName' => NULL,
      ),
      'isSkipFlagged' => 
      array (
        'name' => 'isSkipFlagged',
        'parameters' => 
        array (
          'relation' => 
          array (
            'name' => 'relation',
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
            'startLine' => 336,
            'endLine' => 336,
            'startColumn' => 33,
            'endColumn' => 47,
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
 * Determine whether a relation is skip-flagged (operator-released override).
 *
 * @param mixed $relation The EntityRelation object.
 *
 * @return bool True when skip-flagged.
 */',
        'startLine' => 336,
        'endLine' => 342,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Comparison',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'currentClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'aliasName' => NULL,
      ),
      'isOpenRegisterAvailable' => 
      array (
        'name' => 'isOpenRegisterAvailable',
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
 * Whether OpenRegister is installed/available.
 *
 * @return bool True when available.
 */',
        'startLine' => 349,
        'endLine' => 351,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Comparison',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'currentClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'aliasName' => NULL,
      ),
      'tryGetEntityRelationMapper' => 
      array (
        'name' => 'tryGetEntityRelationMapper',
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
 * Try to resolve the OR EntityRelationMapper, returning null on failure.
 *
 * @return mixed The mapper or null.
 */',
        'startLine' => 358,
        'endLine' => 366,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Comparison',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
        'currentClassName' => 'OCA\\Filinq\\Service\\Comparison\\RedactionAnnotator',
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