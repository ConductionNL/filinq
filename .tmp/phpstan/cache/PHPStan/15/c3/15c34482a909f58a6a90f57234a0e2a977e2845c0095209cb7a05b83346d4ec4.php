<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DossierPlaceholderRanker.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\DossierPlaceholderRanker
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-f2b7cf324c0e357d0d63de72c9a742fc723f05b93b725cd58686e81d3327f810',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DossierPlaceholderRanker.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
    'shortName' => 'DossierPlaceholderRanker',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Ranks a dossier\'s entity ids by first appearance, mirroring OpenRegister.
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
    'startLine' => 47,
    'endLine' => 236,
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
      'PLACEHOLDER_TRANSLATOR_CLASS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
        'name' => 'PLACEHOLDER_TRANSLATOR_CLASS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'\\OCA\\OpenRegister\\Service\\File\\PlaceholderIdTranslator\'',
          'attributes' => 
          array (
            'startLine' => 59,
            'endLine' => 59,
            'startTokenPos' => 55,
            'startFilePos' => 2021,
            'endTokenPos' => 55,
            'endFilePos' => 2076,
          ),
        ),
        'docComment' => '/**
 * Fully-qualified name of OpenRegister\'s placeholder-id translator.
 *
 * Referenced as a string, not as a `::class` constant: OpenRegister is an
 * optional dependency, its translator has a private constructor (so it
 * cannot be injected), and a hard reference would autoload a class that
 * may not exist on this instance.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 59,
        'endLine' => 59,
        'startColumn' => 2,
        'endColumn' => 103,
      ),
      'OUTPUT_FOLDER_NAMES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
        'name' => 'OUTPUT_FOLDER_NAMES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'anonymised\', \'anonymized\', \'redacted\']',
          'attributes' => 
          array (
            'startLine' => 66,
            'endLine' => 70,
            'startTokenPos' => 68,
            'startFilePos' => 2237,
            'endTokenPos' => 79,
            'endFilePos' => 2286,
          ),
        ),
        'docComment' => '/**
 * Folder names holding redacted OUTPUT, excluded from the source-file walk.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 66,
        'endLine' => 70,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'repository' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
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
        'startLine' => 81,
        'endLine' => 81,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
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
        'startLine' => 82,
        'endLine' => 82,
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
            'startLine' => 81,
            'endLine' => 81,
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
            'startLine' => 82,
            'endLine' => 82,
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
        'startLine' => 80,
        'endLine' => 85,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
        'aliasName' => NULL,
      ),
      'rank' => 
      array (
        'name' => 'rank',
        'parameters' => 
        array (
          'folder' => 
          array (
            'name' => 'folder',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Files\\Folder',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 23,
            'endColumn' => 36,
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
 * Rank the dossier\'s entity ids by first appearance.
 *
 * Ranks distinct entity ids by first appearance under the total order
 * (file_id, position_start, entity_id), reusing OpenRegister\'s own
 * `rankByFirstAppearance` so the ranking can never drift from the
 * anonymise path. Returns empty sets when OpenRegister is absent or too
 * old; callers then fall back to the global-id behaviour.
 *
 * @param Folder $folder The dossier folder.
 *
 * @return array{ranks: array<array-key, int>, types: array<string, string>} Ranks per entity
 *                                                                           id, and each id\'s
 *                                                                           entity TYPE.
 *
 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md#requirement-the-per-dossier-summary-must-aggregate-per-document-and-per-grondslag
 */',
        'startLine' => 104,
        'endLine' => 138,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
        'aliasName' => NULL,
      ),
      'sortKey' => 
      array (
        'name' => 'sortKey',
        'parameters' => 
        array (
          'placeholder' => 
          array (
            'name' => 'placeholder',
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
            'startColumn' => 26,
            'endColumn' => 44,
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
 * Sort key for a `[<TYPE>: <number>]` placeholder: [type, number] so a
 * spaceship compare orders by type alphabetically then by number
 * NUMERICALLY ascending (1,2,…,10,11 — not the lexical 1,10,11,2). A
 * placeholder that doesn\'t match the shape sorts last, by its raw string.
 *
 * @param string $placeholder The placeholder string.
 *
 * @return array{0: string, 1: int} The [type, number] sort key.
 */',
        'startLine' => 150,
        'endLine' => 156,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
        'aliasName' => NULL,
      ),
      'collectFileIds' => 
      array (
        'name' => 'collectFileIds',
        'parameters' => 
        array (
          'folder' => 
          array (
            'name' => 'folder',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Files\\Folder',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 169,
            'endLine' => 169,
            'startColumn' => 33,
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
 * Collect the descendant file ids of a dossier folder (recursive), skipping
 * the redacted-output subfolders — mirrors the entity walk so the
 * recompute ranks over the same source-file set the rows come from.
 *
 * @param Folder $folder The dossier folder.
 *
 * @return array<int, int> Distinct descendant source file ids.
 *
 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md#requirement-the-per-dossier-summary-must-aggregate-per-document-and-per-grondslag
 */',
        'startLine' => 169,
        'endLine' => 197,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
        'aliasName' => NULL,
      ),
      'supportsRanking' => 
      array (
        'name' => 'supportsRanking',
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
            'startLine' => 206,
            'endLine' => 206,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Whether OpenRegister exposes everything the recompute needs.
 *
 * @param mixed $mapper The EntityRelationMapper, or null.
 *
 * @return bool True when the ranking can be reproduced.
 */',
        'startLine' => 206,
        'endLine' => 212,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
        'aliasName' => NULL,
      ),
      'entityTypes' => 
      array (
        'name' => 'entityTypes',
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
            'startLine' => 222,
            'endLine' => 222,
            'startColumn' => 31,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'fileIds' => 
          array (
            'name' => 'fileIds',
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
            'startLine' => 222,
            'endLine' => 222,
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
 * Resolve each entity id\'s TYPE from the per-file value→{id,type} maps.
 *
 * @param mixed $mapper The EntityRelationMapper.
 * @param array<int, int> $fileIds The dossier\'s source file ids.
 *
 * @return array<string, string> Map of entity id → entity TYPE.
 */',
        'startLine' => 222,
        'endLine' => 235,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
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