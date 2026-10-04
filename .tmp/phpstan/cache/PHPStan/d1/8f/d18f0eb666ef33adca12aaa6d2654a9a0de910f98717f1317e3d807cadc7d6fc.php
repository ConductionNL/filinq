<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/NextcloudMountCapabilityProbe.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\NextcloudMountCapabilityProbe
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-ea181b6c8be932e7ac04ddf49ff105af46442f4ea2f03189f7f36a61a636cbc3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\NextcloudMountCapabilityProbe',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/NextcloudMountCapabilityProbe.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\NextcloudMountCapabilityProbe',
    'shortName' => 'NextcloudMountCapabilityProbe',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * The one probe that talks to a real mount.
 *
 * 🔴 IT ANSWERS `null` FOR EVERYTHING NEXTCLOUD DOES NOT EXPOSE, AND THAT IS
 * THE HONEST ANSWER, NOT A STUB. There is no public API that asks a storage
 * whether it holds a permission per group, keeps a version history, or can be
 * written to behind the app\'s back. Answering `true` would tell an
 * administrator their external store keeps the promises filinq makes, on no
 * evidence at all; answering `false` would cry wolf about every mount whose
 * driver simply does not say. `ExternalMountValidator` reports a `null` as
 * `unknown`, apart from `cannot`, which is exactly what this situation is.
 *
 * 🔑 A HOME MOUNT IS THE ONE CASE THAT CAN BE ANSWERED. When the folder sits on
 * the user\'s own home storage, permissions, versions and the write path are
 * Nextcloud\'s own and filinq\'s promises hold. Everything else, files_external
 * included, is reported as unconfirmed rather than guessed at. That is the
 * difference the requirement\'s scenario turns on: setup must say what it cannot
 * meet before a document is stored, and "nobody has checked this store" is a
 * thing it must be able to say.
 *
 * 🔑 NOT COVERED BY UNIT TESTS, DELIBERATELY AND NARROWLY.
 * `OCP\\Files\\Mount\\IMountPoint` does not exist in this repository\'s unit test
 * environment, measured with `interface_exists()` under
 * `tests/bootstrap-unit.php`, so a double of it cannot be built here. The
 * decisions therefore live in ExternalMountValidator, which is tested; this
 * class holds only the lookup and the mount-type reading, and is kept small
 * enough that reading it is the review.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 62,
    'endLine' => 218,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'OCA\\Filinq\\Service\\MountCapabilityProbe',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'rootFolder' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\NextcloudMountCapabilityProbe',
        'implementingClassName' => 'OCA\\Filinq\\Service\\NextcloudMountCapabilityProbe',
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
      'directory' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\NextcloudMountCapabilityProbe',
        'implementingClassName' => 'OCA\\Filinq\\Service\\NextcloudMountCapabilityProbe',
        'name' => 'directory',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\DomainDirectory',
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
        'endColumn' => 45,
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
            'startLine' => 73,
            'endLine' => 73,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'directory' => 
          array (
            'name' => 'directory',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\DomainDirectory',
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
            'endColumn' => 45,
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
 * Collaborators.
 *
 * @param IRootFolder    $rootFolder The file tree.
 * @param DomainDirectory $directory  Names the user whose storage holds the folders.
 *
 * @return void
 */',
        'startLine' => 72,
        'endLine' => 77,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\NextcloudMountCapabilityProbe',
        'implementingClassName' => 'OCA\\Filinq\\Service\\NextcloudMountCapabilityProbe',
        'currentClassName' => 'OCA\\Filinq\\Service\\NextcloudMountCapabilityProbe',
        'aliasName' => NULL,
      ),
      'isWritable' => 
      array (
        'name' => 'isWritable',
        'parameters' => 
        array (
          'path' => 
          array (
            'name' => 'path',
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
            'startColumn' => 29,
            'endColumn' => 40,
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
                  'name' => 'bool',
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
 * Whether the mount accepts a write.
 *
 * @param string $path The folder the domain would live in.
 *
 * @return bool|null True, false, or null when it could not be found out.
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
        'startLine' => 88,
        'endLine' => 102,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\NextcloudMountCapabilityProbe',
        'implementingClassName' => 'OCA\\Filinq\\Service\\NextcloudMountCapabilityProbe',
        'currentClassName' => 'OCA\\Filinq\\Service\\NextcloudMountCapabilityProbe',
        'aliasName' => NULL,
      ),
      'supportsPerGroupPermissions' => 
      array (
        'name' => 'supportsPerGroupPermissions',
        'parameters' => 
        array (
          'path' => 
          array (
            'name' => 'path',
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
            'startLine' => 113,
            'endLine' => 113,
            'startColumn' => 46,
            'endColumn' => 57,
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
                  'name' => 'bool',
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
 * Whether the mount can hold a permission per group.
 *
 * @param string $path The folder the domain would live in.
 *
 * @return bool|null True on a home mount, null anywhere else.
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
        'startLine' => 113,
        'endLine' => 116,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\NextcloudMountCapabilityProbe',
        'implementingClassName' => 'OCA\\Filinq\\Service\\NextcloudMountCapabilityProbe',
        'currentClassName' => 'OCA\\Filinq\\Service\\NextcloudMountCapabilityProbe',
        'aliasName' => NULL,
      ),
      'supportsVersions' => 
      array (
        'name' => 'supportsVersions',
        'parameters' => 
        array (
          'path' => 
          array (
            'name' => 'path',
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
            'startLine' => 127,
            'endLine' => 127,
            'startColumn' => 35,
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
                  'name' => 'bool',
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
 * Whether the mount keeps a version history.
 *
 * @param string $path The folder the domain would live in.
 *
 * @return bool|null True on a home mount, null anywhere else.
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
        'startLine' => 127,
        'endLine' => 130,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\NextcloudMountCapabilityProbe',
        'implementingClassName' => 'OCA\\Filinq\\Service\\NextcloudMountCapabilityProbe',
        'currentClassName' => 'OCA\\Filinq\\Service\\NextcloudMountCapabilityProbe',
        'aliasName' => NULL,
      ),
      'writesPassThroughFilinq' => 
      array (
        'name' => 'writesPassThroughFilinq',
        'parameters' => 
        array (
          'path' => 
          array (
            'name' => 'path',
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
            'startLine' => 147,
            'endLine' => 147,
            'startColumn' => 42,
            'endColumn' => 53,
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
                  'name' => 'bool',
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
 * Whether every write to this mount passes through filinq.
 *
 * 🔴 THIS IS `null` EVEN ON A HOME MOUNT, and that is not an oversight. A
 * home folder is reachable over WebDAV and the Files app by the person who
 * owns it, so "every write passes filinq" is false there in the strictest
 * reading and true in the reading the requirement means. Saying so either
 * way would be filinq deciding what an administrator\'s threat model is.
 *
 * @param string $path The folder the domain would live in.
 *
 * @return bool|null Always null: nothing Nextcloud exposes answers this.
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
        'startLine' => 147,
        'endLine' => 152,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\NextcloudMountCapabilityProbe',
        'implementingClassName' => 'OCA\\Filinq\\Service\\NextcloudMountCapabilityProbe',
        'currentClassName' => 'OCA\\Filinq\\Service\\NextcloudMountCapabilityProbe',
        'aliasName' => NULL,
      ),
      'homeMount' => 
      array (
        'name' => 'homeMount',
        'parameters' => 
        array (
          'path' => 
          array (
            'name' => 'path',
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
            'startLine' => 163,
            'endLine' => 163,
            'startColumn' => 29,
            'endColumn' => 40,
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
                  'name' => 'bool',
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
 * Whether the folder sits on the owner\'s own home storage.
 *
 * @param string $path The folder.
 *
 * @return bool|null True on a home mount, null when it is anything else or could not be read.
 *
 * @spec exclude Mount-type lookup; the decisions it feeds live in ExternalMountValidator.
 */',
        'startLine' => 163,
        'endLine' => 187,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\NextcloudMountCapabilityProbe',
        'implementingClassName' => 'OCA\\Filinq\\Service\\NextcloudMountCapabilityProbe',
        'currentClassName' => 'OCA\\Filinq\\Service\\NextcloudMountCapabilityProbe',
        'aliasName' => NULL,
      ),
      'node' => 
      array (
        'name' => 'node',
        'parameters' => 
        array (
          'path' => 
          array (
            'name' => 'path',
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
            'startLine' => 198,
            'endLine' => 198,
            'startColumn' => 24,
            'endColumn' => 35,
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
                  'name' => 'OCP\\Files\\Node',
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
 * The node at a path, or null when it is not there or cannot be reached.
 *
 * @param string $path The path, relative to the folder owner\'s files.
 *
 * @return \\OCP\\Files\\Node|null The node.
 *
 * @spec exclude Lookup helper.
 */',
        'startLine' => 198,
        'endLine' => 217,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\NextcloudMountCapabilityProbe',
        'implementingClassName' => 'OCA\\Filinq\\Service\\NextcloudMountCapabilityProbe',
        'currentClassName' => 'OCA\\Filinq\\Service\\NextcloudMountCapabilityProbe',
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