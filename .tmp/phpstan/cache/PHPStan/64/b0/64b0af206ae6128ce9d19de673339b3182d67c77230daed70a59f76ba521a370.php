<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Sections/FilinqAdmin.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Sections\FilinqAdmin
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-c86bef6e4ae0f3b71e6af46ba5ae1b15d30d5b3554febe654710a3d7b284382c',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Sections\\FilinqAdmin',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Sections/FilinqAdmin.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Sections',
    'name' => 'OCA\\Filinq\\Sections\\FilinqAdmin',
    'shortName' => 'FilinqAdmin',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Admin section for Filinq settings
 *
 * This class defines the admin section where Filinq settings will appear
 * in the Nextcloud admin panel.
 *
 * @category Sections
 * @package  OCA\\Filinq\\Sections
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2
 * @link     https://github.com/conductionnl/filinq
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 38,
    'endLine' => 123,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'OCP\\Settings\\IIconSection',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'l10n' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Sections\\FilinqAdmin',
        'implementingClassName' => 'OCA\\Filinq\\Sections\\FilinqAdmin',
        'name' => 'l10n',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IL10N',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * L10N service for translations
 *
 * @var IL10N
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 45,
        'endLine' => 45,
        'startColumn' => 2,
        'endColumn' => 21,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'urlGenerator' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Sections\\FilinqAdmin',
        'implementingClassName' => 'OCA\\Filinq\\Sections\\FilinqAdmin',
        'name' => 'urlGenerator',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IURLGenerator',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * URL generator for creating URLs
 *
 * @var IURLGenerator
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 52,
        'endLine' => 52,
        'startColumn' => 2,
        'endColumn' => 37,
        'isPromoted' => false,
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
          'l10n' => 
          array (
            'name' => 'l10n',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IL10N',
                'isIdentifier' => false,
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
            'startColumn' => 30,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'urlGenerator' => 
          array (
            'name' => 'urlGenerator',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IURLGenerator',
                'isIdentifier' => false,
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
            'startColumn' => 43,
            'endColumn' => 69,
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
 * Constructor for FilinqAdmin section
 *
 * @param IL10N $l10n L10N service for translations
 * @param IURLGenerator $urlGenerator URL generator service
 *
 * @return void
 */',
        'startLine' => 62,
        'endLine' => 66,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Sections',
        'declaringClassName' => 'OCA\\Filinq\\Sections\\FilinqAdmin',
        'implementingClassName' => 'OCA\\Filinq\\Sections\\FilinqAdmin',
        'currentClassName' => 'OCA\\Filinq\\Sections\\FilinqAdmin',
        'aliasName' => NULL,
      ),
      'getIcon' => 
      array (
        'name' => 'getIcon',
        'parameters' => 
        array (
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
 * Get the icon for the admin section
 *
 * @return string URL to the section icon
 *
 * @psalm-return   string
 * @phpstan-return string
 *
 * @spec openspec/specs/admin-settings/spec.md
 */',
        'startLine' => 78,
        'endLine' => 80,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Sections',
        'declaringClassName' => 'OCA\\Filinq\\Sections\\FilinqAdmin',
        'implementingClassName' => 'OCA\\Filinq\\Sections\\FilinqAdmin',
        'currentClassName' => 'OCA\\Filinq\\Sections\\FilinqAdmin',
        'aliasName' => NULL,
      ),
      'getID' => 
      array (
        'name' => 'getID',
        'parameters' => 
        array (
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
 * Get the ID of the admin section
 *
 * @return string The section ID
 *
 * @psalm-return   string
 * @phpstan-return string
 *
 * @spec openspec/specs/admin-settings/spec.md
 */',
        'startLine' => 92,
        'endLine' => 94,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Sections',
        'declaringClassName' => 'OCA\\Filinq\\Sections\\FilinqAdmin',
        'implementingClassName' => 'OCA\\Filinq\\Sections\\FilinqAdmin',
        'currentClassName' => 'OCA\\Filinq\\Sections\\FilinqAdmin',
        'aliasName' => NULL,
      ),
      'getName' => 
      array (
        'name' => 'getName',
        'parameters' => 
        array (
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
 * Get the name of the admin section
 *
 * @return string The translated section name
 *
 * @psalm-return   string
 * @phpstan-return string
 *
 * @spec openspec/specs/admin-settings/spec.md
 */',
        'startLine' => 106,
        'endLine' => 108,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Sections',
        'declaringClassName' => 'OCA\\Filinq\\Sections\\FilinqAdmin',
        'implementingClassName' => 'OCA\\Filinq\\Sections\\FilinqAdmin',
        'currentClassName' => 'OCA\\Filinq\\Sections\\FilinqAdmin',
        'aliasName' => NULL,
      ),
      'getPriority' => 
      array (
        'name' => 'getPriority',
        'parameters' => 
        array (
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
 * Get the priority of the admin section
 *
 * @return int The section priority (0-100)
 *
 * @psalm-return   int
 * @phpstan-return int
 *
 * @spec openspec/specs/admin-settings/spec.md
 */',
        'startLine' => 120,
        'endLine' => 122,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Sections',
        'declaringClassName' => 'OCA\\Filinq\\Sections\\FilinqAdmin',
        'implementingClassName' => 'OCA\\Filinq\\Sections\\FilinqAdmin',
        'currentClassName' => 'OCA\\Filinq\\Sections\\FilinqAdmin',
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