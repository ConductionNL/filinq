<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DossierObjectRepository.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\DossierObjectRepository
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-58d4bf06da559d4fe04d6dc9f9267d6dca1a3707f12213c27b9f680be9307a8f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DossierObjectRepository.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
    'shortName' => 'DossierObjectRepository',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Reads and writes the OpenRegister dossier objects the report depends on.
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
    'startLine' => 49,
    'endLine' => 462,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'name' => 'OBJECT_ENTITY_CLASS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'\\OCA\\OpenRegister\\Db\\ObjectEntity\'',
          'attributes' => 
          array (
            'startLine' => 59,
            'endLine' => 59,
            'startTokenPos' => 80,
            'startFilePos' => 1850,
            'endTokenPos' => 80,
            'endFilePos' => 1884,
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
        'startLine' => 59,
        'endLine' => 59,
        'startColumn' => 2,
        'endColumn' => 73,
      ),
    ),
    'immediateProperties' => 
    array (
      'appManager' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
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
        'startLine' => 73,
        'endLine' => 73,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
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
        'startLine' => 74,
        'endLine' => 74,
        'startColumn' => 3,
        'endColumn' => 48,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'rootFolder' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'name' => 'rootFolder',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\Files\\IRootFolder',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 75,
        'endLine' => 75,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'userSession' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'name' => 'userSession',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IUserSession',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 76,
        'endLine' => 76,
        'startColumn' => 3,
        'endColumn' => 44,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
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
        'startLine' => 77,
        'endLine' => 77,
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
            'startLine' => 73,
            'endLine' => 73,
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
            'startLine' => 74,
            'endLine' => 74,
            'startColumn' => 3,
            'endColumn' => 48,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'rootFolder' => 
          array (
            'name' => 'rootFolder',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Files\\IRootFolder',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 75,
            'endLine' => 75,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'userSession' => 
          array (
            'name' => 'userSession',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IUserSession',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 76,
            'endLine' => 76,
            'startColumn' => 3,
            'endColumn' => 44,
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
            'startLine' => 77,
            'endLine' => 77,
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
 * @param IAppManager $appManager App-availability check for OpenRegister.
 * @param ContainerInterface $container DI container for OpenRegister-side services.
 * @param IRootFolder $rootFolder Nextcloud file API entry point.
 * @param IUserSession $userSession Session-user lookup.
 * @param LoggerInterface $logger Structured logger.
 *
 * @return void
 */',
        'startLine' => 72,
        'endLine' => 80,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'aliasName' => NULL,
      ),
      'resolveDossierFolder' => 
      array (
        'name' => 'resolveDossierFolder',
        'parameters' => 
        array (
          'folderRef' => 
          array (
            'name' => 'folderRef',
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
            'startLine' => 91,
            'endLine' => 91,
            'startColumn' => 39,
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
            'name' => 'OCP\\Files\\Folder',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolve the dossier\'s `@self.folder` reference to a Nextcloud Folder node.
 *
 * @param mixed $folderRef The raw reference value — typically a file-node id (int/string).
 *
 * @return Folder The dossier\'s folder.
 *
 * @throws RuntimeException When the reference cannot be resolved.
 */',
        'startLine' => 91,
        'endLine' => 124,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'aliasName' => NULL,
      ),
      'objectService' => 
      array (
        'name' => 'objectService',
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
                  'name' => 'object',
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
 * Get the OpenRegister ObjectService, or null when unavailable.
 *
 * @return object|null The ObjectService instance, or null.
 *
 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md#requirement-a-per-dossier-summary-endpoint-must-exist
 */',
        'startLine' => 133,
        'endLine' => 148,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'aliasName' => NULL,
      ),
      'entityRelationMapper' => 
      array (
        'name' => 'entityRelationMapper',
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
                  'name' => 'object',
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
 * Get the OpenRegister EntityRelationMapper, or null when unavailable.
 *
 * @return object|null The EntityRelationMapper instance, or null.
 *
 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md#requirement-a-per-dossier-summary-endpoint-must-exist
 */',
        'startLine' => 157,
        'endLine' => 172,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'aliasName' => NULL,
      ),
      'loadDossierContext' => 
      array (
        'name' => 'loadDossierContext',
        'parameters' => 
        array (
          'dossierUuid' => 
          array (
            'name' => 'dossierUuid',
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
            'startLine' => 183,
            'endLine' => 183,
            'startColumn' => 37,
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
 * Load the minimum dossier context the renderer needs.
 *
 * @param string $dossierUuid The OR object UUID.
 *
 * @return array<string, mixed> `{name, description, checkedOn, folderRef, configuration}`.
 *
 * @throws RuntimeException When the dossier cannot be resolved.
 */',
        'startLine' => 183,
        'endLine' => 195,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'aliasName' => NULL,
      ),
      'updateDossierConfiguration' => 
      array (
        'name' => 'updateDossierConfiguration',
        'parameters' => 
        array (
          'dossierUuid' => 
          array (
            'name' => 'dossierUuid',
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
            'startLine' => 211,
            'endLine' => 211,
            'startColumn' => 45,
            'endColumn' => 63,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'summaryFileId' => 
          array (
            'name' => 'summaryFileId',
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
            'startLine' => 211,
            'endLine' => 211,
            'startColumn' => 66,
            'endColumn' => 83,
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
 * Update the dossier object\'s `configuration.grondslagen.{fileId, lastGeneratedAt}`.
 *
 * Failure is logged but does not roll back the rendered file — the PDF
 * is on disk and the operator can find it; the metadata refresh is
 * convenience for the dossier UI\'s freshness-badge.
 *
 * @param string $dossierUuid The OR dossier object UUID.
 * @param int $summaryFileId The newly-written summary file\'s NC node id.
 *
 * @return void
 *
 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md#requirement-a-per-dossier-summary-endpoint-must-exist
 */',
        'startLine' => 211,
        'endLine' => 257,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'aliasName' => NULL,
      ),
      'findDossier' => 
      array (
        'name' => 'findDossier',
        'parameters' => 
        array (
          'dossierUuid' => 
          array (
            'name' => 'dossierUuid',
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
            'startLine' => 268,
            'endLine' => 268,
            'startColumn' => 31,
            'endColumn' => 49,
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
 * Resolve one dossier object with RBAC disabled, or throw.
 *
 * @param string $dossierUuid The OR object UUID.
 *
 * @return mixed The resolved dossier object (never null).
 *
 * @throws RuntimeException When OR is unavailable, the lookup fails, or nothing matches.
 */',
        'startLine' => 268,
        'endLine' => 294,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'aliasName' => NULL,
      ),
      'payloadOf' => 
      array (
        'name' => 'payloadOf',
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
            'startLine' => 303,
            'endLine' => 303,
            'startColumn' => 29,
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
 * Reduce an OR object to its schema-typed payload array.
 *
 * @param mixed $object The raw ObjectService result.
 *
 * @return array<string, mixed> The payload.
 */',
        'startLine' => 303,
        'endLine' => 309,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'aliasName' => NULL,
      ),
      'folderRefOf' => 
      array (
        'name' => 'folderRefOf',
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
            'startLine' => 327,
            'endLine' => 327,
            'startColumn' => 31,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 327,
            'endLine' => 327,
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
            'name' => 'mixed',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolve the dossier\'s `@self.folder` reference.
 *
 * The `@self.folder` reference is stored on the ObjectEntity\'s `folder`
 * column, NOT inside the schema-typed payload returned by `getObject()`.
 * OR\'s renderer reconstructs the `@self` block from the entity\'s columns
 * when serialising for the API, but in-process callers must read the
 * columns directly. Read the entity-level getter first; fall back to a
 * payload-embedded `@self.folder` for future-compat in case the renderer
 * ever inlines it.
 *
 * @param mixed $object The raw ObjectService result.
 * @param array<string, mixed> $payload The schema-typed payload.
 *
 * @return mixed The folder reference, or null.
 */',
        'startLine' => 327,
        'endLine' => 336,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'aliasName' => NULL,
      ),
      'entityFolderRef' => 
      array (
        'name' => 'entityFolderRef',
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
            'startLine' => 351,
            'endLine' => 351,
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
            'name' => 'mixed',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Read the `folder` column off an OpenRegister ObjectEntity.
 *
 * `getFolder` is a magic method on Nextcloud\'s `Entity` base class
 * (auto-generated via `__call`, declared only as `@method`), so
 * `method_exists` returns false even when the call works. Probe via
 * `ObjectEntity` instanceof, then invoke directly. OpenRegister\'s lib is
 * an optional dependency, so the class is probed by name.
 *
 * @param mixed $object The raw ObjectService result.
 *
 * @return mixed The folder reference, or null when unavailable.
 */',
        'startLine' => 351,
        'endLine' => 366,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'aliasName' => NULL,
      ),
      'withGrondslagenConfiguration' => 
      array (
        'name' => 'withGrondslagenConfiguration',
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
            'startLine' => 376,
            'endLine' => 376,
            'startColumn' => 48,
            'endColumn' => 61,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'summaryFileId' => 
          array (
            'name' => 'summaryFileId',
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
            'startLine' => 376,
            'endLine' => 376,
            'startColumn' => 64,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Merge the grondslagen freshness metadata into a dossier payload.
 *
 * @param array<string, mixed> $payload The dossier payload.
 * @param int $summaryFileId The summary file\'s NC node id.
 *
 * @return array<string, mixed> The payload with `configuration.grondslagen` set.
 */',
        'startLine' => 376,
        'endLine' => 393,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'aliasName' => NULL,
      ),
      'withPreservedFolderRef' => 
      array (
        'name' => 'withPreservedFolderRef',
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
            'startLine' => 417,
            'endLine' => 417,
            'startColumn' => 42,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 417,
            'endLine' => 417,
            'startColumn' => 57,
            'endColumn' => 70,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'dossierUuid' => 
          array (
            'name' => 'dossierUuid',
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
            'startLine' => 417,
            'endLine' => 417,
            'startColumn' => 73,
            'endColumn' => 91,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Re-inject the dossier\'s existing `@self.folder` before saving.
 *
 * `getObject()` returns the schema-typed payload only — the `folder`
 * column lives on the ObjectEntity itself. Without explicit
 * re-injection, OR\'s save path sees no folder ref on the incoming
 * payload, hands the object to `ensureObjectFolderExists`, and that
 * helper auto-creates a brand-new folder under the register\'s storage
 * tree — overwriting `_folder` with the auto-folder\'s id. Operators see
 * their original dossier folder mysteriously replaced by a generated one
 * in OR\'s `Open Registers` folder.
 *
 * OR\'s `setSelfMetadata` reads `@self.folder` and re-applies it via
 * `setFolder()` on save (per the `validate-self-folder-access` change),
 * so the original folder binding is preserved.
 *
 * @param mixed $object The raw ObjectService result.
 * @param array<string, mixed> $payload The payload about to be saved.
 * @param string $dossierUuid The dossier UUID (log context).
 *
 * @return array<string, mixed> The payload, with `@self.folder` preserved when readable.
 */',
        'startLine' => 417,
        'endLine' => 452,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
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
 * True when the OpenRegister app is installed and enabled.
 *
 * @return bool
 */',
        'startLine' => 459,
        'endLine' => 461,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
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