<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/MountCapabilityProbe.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\MountCapabilityProbe
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-f1deef635ff314f3193d5714690bccac0f41727b052fdcef15a4e3187835f8ee',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\MountCapabilityProbe',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/MountCapabilityProbe.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\MountCapabilityProbe',
    'shortName' => 'MountCapabilityProbe',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * One narrow question per capability filinq depends on.
 *
 * 🔑 WHY THIS IS A SEAM RATHER THAN A DIRECT READ OF THE STORAGE. Answering
 * these questions means `OCP\\Files\\Mount\\IMountPoint` and
 * `OCP\\Files\\Storage\\IStorage`, and NEITHER EXISTS in this repository\'s unit
 * test environment, measured with `interface_exists()` under
 * `tests/bootstrap-unit.php`. A validator written straight against them could
 * be typed but neither run nor tested here, and an untestable guard is the one
 * that quietly stops guarding. So the validator reasons about an answer and the
 * adapter that gets the answer from a real mount is named as still open.
 *
 * 🔴 EVERY ANSWER IS THREE-VALUED, NEVER TWO. `null` means the probe could not
 * find out, and that is not the same as "no". A false collapses into "the mount
 * cannot do this, name it in the report"; a null must read as "nobody knows
 * whether this mount can do this", which is a WORSE answer than no and has to
 * reach the person setting the mount up as its own line.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction Development Team <dev@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 51,
    'endLine' => 94,
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
            'startLine' => 62,
            'endLine' => 62,
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
 * Whether the mount can hold a permission per group, which reconciliation needs.
 *
 * @param string $path The folder the domain would live in.
 *
 * @return bool|null True, false, or null when it could not be found out.
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 2,
        'endColumn' => 66,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\MountCapabilityProbe',
        'implementingClassName' => 'OCA\\Filinq\\Service\\MountCapabilityProbe',
        'currentClassName' => 'OCA\\Filinq\\Service\\MountCapabilityProbe',
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
            'startLine' => 73,
            'endLine' => 73,
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
 * Whether every write to this mount passes through filinq, which the upload policy needs.
 *
 * @param string $path The folder the domain would live in.
 *
 * @return bool|null True, false, or null when it could not be found out.
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 2,
        'endColumn' => 62,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\MountCapabilityProbe',
        'implementingClassName' => 'OCA\\Filinq\\Service\\MountCapabilityProbe',
        'currentClassName' => 'OCA\\Filinq\\Service\\MountCapabilityProbe',
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
            'startLine' => 84,
            'endLine' => 84,
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
 * Whether the mount keeps a version history, which a corrected document needs.
 *
 * @param string $path The folder the domain would live in.
 *
 * @return bool|null True, false, or null when it could not be found out.
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
        'startLine' => 84,
        'endLine' => 84,
        'startColumn' => 2,
        'endColumn' => 55,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\MountCapabilityProbe',
        'implementingClassName' => 'OCA\\Filinq\\Service\\MountCapabilityProbe',
        'currentClassName' => 'OCA\\Filinq\\Service\\MountCapabilityProbe',
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
            'startLine' => 93,
            'endLine' => 93,
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
 * Whether the mount is writable at all.
 *
 * @param string $path The folder the domain would live in.
 *
 * @return bool|null True, false, or null when it could not be found out.
 */',
        'startLine' => 93,
        'endLine' => 93,
        'startColumn' => 2,
        'endColumn' => 49,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\MountCapabilityProbe',
        'implementingClassName' => 'OCA\\Filinq\\Service\\MountCapabilityProbe',
        'currentClassName' => 'OCA\\Filinq\\Service\\MountCapabilityProbe',
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