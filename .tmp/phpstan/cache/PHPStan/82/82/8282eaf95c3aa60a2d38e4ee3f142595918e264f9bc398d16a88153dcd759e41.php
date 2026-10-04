<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DossierManagementService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\DossierManagementService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-16560125178fe2ca5e28cf44430ad1771b876d83855f088c74552a8dfe71540e',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DossierManagementService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\DossierManagementService',
    'shortName' => 'DossierManagementService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Aggregates and mutates dossiers for the dossier-management surface.
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) The aggregation deliberately
 *     wires existing capabilities (bases, folder enumeration, batch state,
 *     anonymisation links) rather than reimplementing any of them.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 53,
    'endLine' => 596,
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
      'REGISTER' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'name' => 'REGISTER',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\\OCA\\Filinq\\Service\\DossierObjectReader::REGISTER',
          'attributes' => 
          array (
            'startLine' => 64,
            'endLine' => 64,
            'startTokenPos' => 50,
            'startFilePos' => 2335,
            'endTokenPos' => 52,
            'endFilePos' => 2363,
          ),
        ),
        'docComment' => '/**
 * The register every filinq schema lives in.
 *
 * Aliased rather than repeated: DossierObjectReader owns the definition
 * now that three classes read it, and a second literal here is a second
 * thing to forget when the register moves.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 64,
        'endLine' => 64,
        'startColumn' => 2,
        'endColumn' => 56,
      ),
      'SCHEMA' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'name' => 'SCHEMA',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\\OCA\\Filinq\\Service\\DossierObjectReader::SCHEMA',
          'attributes' => 
          array (
            'startLine' => 71,
            'endLine' => 71,
            'startTokenPos' => 65,
            'startFilePos' => 2450,
            'endTokenPos' => 67,
            'endFilePos' => 2476,
          ),
        ),
        'docComment' => '/**
 * The dossier schema slug.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 71,
        'endLine' => 71,
        'startColumn' => 2,
        'endColumn' => 52,
      ),
      'DEFAULT_STATUS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'name' => 'DEFAULT_STATUS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\\OCA\\Filinq\\Service\\DossierObjectReader::DEFAULT_STATUS',
          'attributes' => 
          array (
            'startLine' => 81,
            'endLine' => 81,
            'startTokenPos' => 80,
            'startFilePos' => 2732,
            'endTokenPos' => 82,
            'endFilePos' => 2766,
          ),
        ),
        'docComment' => '/**
 * The status a dossier without one is read as.
 *
 * `status` is optional and existing objects were deliberately not
 * migrated, so absence is a value with a meaning, not missing data.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 81,
        'endLine' => 81,
        'startColumn' => 2,
        'endColumn' => 67,
      ),
      'TRANSITIONS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'name' => 'TRANSITIONS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'open\' => [\'in-review\'], \'in-review\' => [\'processed\', \'open\'], \'processed\' => [\'published\', \'closed\'], \'published\' => [\'closed\'], \'closed\' => []]',
          'attributes' => 
          array (
            'startLine' => 93,
            'endLine' => 99,
            'startTokenPos' => 95,
            'startFilePos' => 3220,
            'endTokenPos' => 147,
            'endFilePos' => 3379,
          ),
        ),
        'docComment' => '/**
 * The declared lifecycle, mirroring `x-openregister-lifecycle` on the schema.
 *
 * Mirrored, not owned: OpenRegister\'s guard is the authority and rejects an
 * out-of-order write regardless of what this map says. It exists so the UI
 * can offer only the legal targets instead of offering all five and letting
 * the operator discover the rule by being refused.
 *
 * @var array<string, list<string>>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 93,
        'endLine' => 99,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'repository' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
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
        'startLine' => 111,
        'endLine' => 111,
        'startColumn' => 3,
        'endColumn' => 54,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'files' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'name' => 'files',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\DossierFileService',
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
        'endColumn' => 44,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'context' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'name' => 'context',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\DossierContextService',
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
        'endColumn' => 49,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'reader' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'name' => 'reader',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\DossierObjectReader',
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
        'endColumn' => 46,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
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
        'startLine' => 115,
        'endLine' => 115,
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
            'startLine' => 111,
            'endLine' => 111,
            'startColumn' => 3,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'files' => 
          array (
            'name' => 'files',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\DossierFileService',
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
            'endColumn' => 44,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'context' => 
          array (
            'name' => 'context',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\DossierContextService',
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
            'endColumn' => 49,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'reader' => 
          array (
            'name' => 'reader',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\DossierObjectReader',
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
            'endColumn' => 46,
            'parameterIndex' => 3,
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
            'startLine' => 115,
            'endLine' => 115,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 4,
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
 * @param DossierObjectRepository $repository Dossier object + folder resolution.
 * @param DossierFileService $files The filesystem half: home folders and file nodes.
 * @param DossierContextService $context The live context a dossier is shown with.
 * @param DossierObjectReader $reader Object-shape reading.
 * @param LoggerInterface $logger Logger.
 */',
        'startLine' => 110,
        'endLine' => 118,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'aliasName' => NULL,
      ),
      'index' => 
      array (
        'name' => 'index',
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
 * Every dossier the caller can read, with its live context.
 *
 * @return array<int, array<string, mixed>> Index rows.
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 */',
        'startLine' => 127,
        'endLine' => 156,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'aliasName' => NULL,
      ),
      'detail' => 
      array (
        'name' => 'detail',
        'parameters' => 
        array (
          'dossierId' => 
          array (
            'name' => 'dossierId',
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
            'startLine' => 169,
            'endLine' => 169,
            'startColumn' => 25,
            'endColumn' => 41,
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
 * One dossier with its documents, grondslagen, batch runs and publication state.
 *
 * @param string $dossierId The dossier object UUID.
 *
 * @return array<string, mixed> The aggregated dossier.
 *
 * @throws RuntimeException When the caller cannot read the dossier.
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 */',
        'startLine' => 169,
        'endLine' => 200,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'aliasName' => NULL,
      ),
      'create' => 
      array (
        'name' => 'create',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
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
            'startLine' => 215,
            'endLine' => 215,
            'startColumn' => 25,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'description' => 
          array (
            'name' => 'description',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 215,
                'endLine' => 215,
                'startTokenPos' => 812,
                'startFilePos' => 7578,
                'endTokenPos' => 812,
                'endFilePos' => 7579,
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
            'startLine' => 215,
            'endLine' => 215,
            'startColumn' => 39,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'bases' => 
          array (
            'name' => 'bases',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 215,
                'endLine' => 215,
                'startTokenPos' => 821,
                'startFilePos' => 7597,
                'endTokenPos' => 822,
                'endFilePos' => 7598,
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
            'startLine' => 215,
            'endLine' => 215,
            'startColumn' => 65,
            'endColumn' => 81,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a dossier bound to a home folder.
 *
 * @param string $name The dossier name; also the home-folder name.
 * @param string $description Optional free text.
 * @param array<int, string> $bases Grondslag slugs.
 *
 * @return array<string, mixed> The created dossier\'s detail shape.
 *
 * @throws RuntimeException When the name is empty or the save fails.
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 */',
        'startLine' => 215,
        'endLine' => 251,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'aliasName' => NULL,
      ),
      'rename' => 
      array (
        'name' => 'rename',
        'parameters' => 
        array (
          'dossierId' => 
          array (
            'name' => 'dossierId',
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
            'startLine' => 270,
            'endLine' => 270,
            'startColumn' => 25,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'name' => 
          array (
            'name' => 'name',
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
            'startLine' => 270,
            'endLine' => 270,
            'startColumn' => 44,
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
 * Rename a dossier and keep its bound home folder in sync.
 *
 * The object rename ALWAYS succeeds; the folder rename is best-effort and
 * reports rather than blocks. A caller without write permission, or a
 * sibling already holding the target name, must not cost the operator
 * their rename — and a name collision must never merge two folders.
 *
 * @param string $dossierId The dossier object UUID.
 * @param string $name The new name.
 *
 * @return array<string, mixed> The detail shape plus a `folderWarning` key.
 *
 * @throws RuntimeException When the name is empty or the caller cannot read the dossier.
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 */',
        'startLine' => 270,
        'endLine' => 287,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'aliasName' => NULL,
      ),
      'transition' => 
      array (
        'name' => 'transition',
        'parameters' => 
        array (
          'dossierId' => 
          array (
            'name' => 'dossierId',
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
            'startLine' => 301,
            'endLine' => 301,
            'startColumn' => 29,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'status' => 
          array (
            'name' => 'status',
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
            'startLine' => 301,
            'endLine' => 301,
            'startColumn' => 48,
            'endColumn' => 61,
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
 * Move a dossier to a new lifecycle status.
 *
 * @param string $dossierId The dossier object UUID.
 * @param string $status The target status.
 *
 * @return array<string, mixed> The refreshed detail shape.
 *
 * @throws RuntimeException When the transition is not declared, or the save is refused.
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 */',
        'startLine' => 301,
        'endLine' => 329,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'aliasName' => NULL,
      ),
      'linkDocument' => 
      array (
        'name' => 'linkDocument',
        'parameters' => 
        array (
          'dossierId' => 
          array (
            'name' => 'dossierId',
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
            'startLine' => 346,
            'endLine' => 346,
            'startColumn' => 31,
            'endColumn' => 47,
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
            'startLine' => 346,
            'endLine' => 346,
            'startColumn' => 50,
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
 * Add an existing file to a dossier by reference, without moving it.
 *
 * Link semantics: the file stays where it is and the dossier gains a
 * reference, so the same document can be a member of several dossiers.
 *
 * @param string $dossierId The dossier object UUID.
 * @param int $fileId The Nextcloud file node id.
 *
 * @return array<string, mixed> The refreshed detail shape.
 *
 * @throws RuntimeException When the caller cannot read the dossier or the file.
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 */',
        'startLine' => 346,
        'endLine' => 362,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'aliasName' => NULL,
      ),
      'removeDocument' => 
      array (
        'name' => 'removeDocument',
        'parameters' => 
        array (
          'dossierId' => 
          array (
            'name' => 'dossierId',
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
            'startLine' => 386,
            'endLine' => 386,
            'startColumn' => 33,
            'endColumn' => 49,
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
            'startLine' => 386,
            'endLine' => 386,
            'startColumn' => 52,
            'endColumn' => 62,
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
 * Remove a document from a dossier.
 *
 * Two different operations behind one verb, and the caller is told which
 * one it will be by {@see self::removalMode()} before confirming:
 *
 *  - The file lives in this dossier\'s home folder and no other dossier
 *    references it → it is moved to the TRASHBIN, recoverable. Never a
 *    hard delete.
 *  - The file is a reference (lives elsewhere, or another dossier also
 *    holds it) → only this dossier\'s membership reference is dropped and
 *    the file is untouched.
 *
 * @param string $dossierId The dossier object UUID.
 * @param int $fileId The Nextcloud file node id.
 *
 * @return array<string, mixed> The refreshed detail shape.
 *
 * @throws RuntimeException When the caller cannot read the dossier.
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 */',
        'startLine' => 386,
        'endLine' => 406,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'aliasName' => NULL,
      ),
      'removalMode' => 
      array (
        'name' => 'removalMode',
        'parameters' => 
        array (
          'dossierId' => 
          array (
            'name' => 'dossierId',
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
            'startLine' => 422,
            'endLine' => 422,
            'startColumn' => 30,
            'endColumn' => 46,
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
            'startLine' => 422,
            'endLine' => 422,
            'startColumn' => 49,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Whether removing this file would trash it or merely unlink it.
 *
 * Exposed so the confirmation dialog can SAY which one it is. A confirm
 * that reads "remove?" for both is how an operator deletes a file they
 * meant to unlink.
 *
 * @param string $dossierId The dossier object UUID.
 * @param int $fileId The Nextcloud file node id.
 *
 * @return string Either \'trash\' or \'unlink\'.
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 */',
        'startLine' => 422,
        'endLine' => 446,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'aliasName' => NULL,
      ),
      'requireReadable' => 
      array (
        'name' => 'requireReadable',
        'parameters' => 
        array (
          'dossierId' => 
          array (
            'name' => 'dossierId',
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
            'startLine' => 473,
            'endLine' => 473,
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
            'name' => 'object',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The dossier object, or a refusal.
 *
 * ⚠️ The guard is only as strong as the schema\'s cascade. `dossier`
 * declares `read: ["authenticated"]`, so OpenRegister admits any
 * authenticated user in the organisation and this resolves to an existence
 * test. That is enforced HERE rather than bypassed: passing `_rbac: false`
 * would make the endpoint unconditionally open, and tightening the cascade
 * is a separate decision (ConductionNL/filinq#441). The FILE half is real —
 * every listing below runs through the caller\'s own view.
 *
 * @param string $dossierId The dossier object UUID.
 *
 * @return object The dossier object.
 *
 * @throws RuntimeException When it is absent or unreadable.
 */',
        'startLine' => 473,
        'endLine' => 488,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'aliasName' => NULL,
      ),
      'requireObjectService' => 
      array (
        'name' => 'requireObjectService',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'object',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * OpenRegister\'s object service, or a refusal.
 *
 * @return object The object service.
 *
 * @throws RuntimeException When OpenRegister is unavailable.
 */',
        'startLine' => 497,
        'endLine' => 505,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'aliasName' => NULL,
      ),
      'save' => 
      array (
        'name' => 'save',
        'parameters' => 
        array (
          'payload' => 
          array (
            'name' => 'payload',
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
            'startLine' => 522,
            'endLine' => 522,
            'startColumn' => 24,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'changes' => 
          array (
            'name' => 'changes',
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
            'startLine' => 522,
            'endLine' => 522,
            'startColumn' => 40,
            'endColumn' => 53,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Write changes onto a dossier as a FULL payload.
 *
 * THE PAYLOAD IS THE WHOLE INPUT. This used to take the object too and
 * never read it, which phpmd reports as an unused parameter and which cost
 * every call site a named argument that carried nothing: the register and
 * schema are constants and the identity travels inside `@self`.
 *
 * @param array<string, mixed> $payload Its current payload.
 * @param array<string, mixed> $changes The fields to change.
 *
 * @return void
 *
 * @throws RuntimeException When the save is refused.
 */',
        'startLine' => 522,
        'endLine' => 553,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'aliasName' => NULL,
      ),
      'referencedElsewhere' => 
      array (
        'name' => 'referencedElsewhere',
        'parameters' => 
        array (
          'dossierId' => 
          array (
            'name' => 'dossierId',
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
            'startLine' => 563,
            'endLine' => 563,
            'startColumn' => 39,
            'endColumn' => 55,
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
            'startLine' => 563,
            'endLine' => 563,
            'startColumn' => 58,
            'endColumn' => 68,
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
 * Whether another dossier also references this file.
 *
 * @param string $dossierId The dossier being removed from.
 * @param int $fileId The Nextcloud file node id.
 *
 * @return bool True when at least one other dossier references it.
 */',
        'startLine' => 563,
        'endLine' => 590,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierManagementService',
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