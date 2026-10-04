<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Settings/FilinqAdmin.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Settings\FilinqAdmin
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-fb2648b519e9e29a215be4ad6a4604722dedd40f8294704876ebc8af9d4a262a',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Settings\\FilinqAdmin',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Settings/FilinqAdmin.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Settings',
    'name' => 'OCA\\Filinq\\Settings\\FilinqAdmin',
    'shortName' => 'FilinqAdmin',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Admin settings for Filinq
 *
 * This class handles the admin settings page for Filinq.
 *
 * @category Settings
 * @package  OCA\\Filinq\\Settings
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2
 * @link     https://github.com/conductionnl/filinq
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 52,
    'endLine' => 162,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'OCP\\Settings\\IDelegatedSettings',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'appManager' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Settings\\FilinqAdmin',
        'implementingClassName' => 'OCA\\Filinq\\Settings\\FilinqAdmin',
        'name' => 'appManager',
        'modifiers' => 4,
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
        'docComment' => '/**
 * App manager for retrieving app version
 *
 * @var IAppManager $appManager
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 59,
        'endLine' => 59,
        'startColumn' => 2,
        'endColumn' => 33,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'initialState' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Settings\\FilinqAdmin',
        'implementingClassName' => 'OCA\\Filinq\\Settings\\FilinqAdmin',
        'name' => 'initialState',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\AppFramework\\Services\\IInitialState',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Initial state service for passing data to the frontend
 *
 * @var IInitialState $initialState
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 66,
        'endLine' => 66,
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
            'startLine' => 76,
            'endLine' => 76,
            'startColumn' => 30,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'initialState' => 
          array (
            'name' => 'initialState',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\AppFramework\\Services\\IInitialState',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 76,
            'endLine' => 76,
            'startColumn' => 55,
            'endColumn' => 81,
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
 * Constructor for FilinqAdmin
 *
 * @param IAppManager $appManager App manager for retrieving app version
 * @param IInitialState $initialState Initial state service for the frontend
 *
 * @return void
 */',
        'startLine' => 76,
        'endLine' => 80,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Settings',
        'declaringClassName' => 'OCA\\Filinq\\Settings\\FilinqAdmin',
        'implementingClassName' => 'OCA\\Filinq\\Settings\\FilinqAdmin',
        'currentClassName' => 'OCA\\Filinq\\Settings\\FilinqAdmin',
        'aliasName' => NULL,
      ),
      'getForm' => 
      array (
        'name' => 'getForm',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\TemplateResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the admin settings form
 *
 * @return TemplateResponse The template response for the admin settings
 *
 * @psalm-return   TemplateResponse
 * @phpstan-return TemplateResponse
 *
 * @spec openspec/specs/admin-settings/spec.md
 */',
        'startLine' => 92,
        'endLine' => 104,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Settings',
        'declaringClassName' => 'OCA\\Filinq\\Settings\\FilinqAdmin',
        'implementingClassName' => 'OCA\\Filinq\\Settings\\FilinqAdmin',
        'currentClassName' => 'OCA\\Filinq\\Settings\\FilinqAdmin',
        'aliasName' => NULL,
      ),
      'getSection' => 
      array (
        'name' => 'getSection',
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
 * Get the section ID for the admin settings
 *
 * @return string The section ID
 *
 * @psalm-return   string
 * @phpstan-return string
 *
 * @spec openspec/specs/admin-settings/spec.md
 */',
        'startLine' => 116,
        'endLine' => 118,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Settings',
        'declaringClassName' => 'OCA\\Filinq\\Settings\\FilinqAdmin',
        'implementingClassName' => 'OCA\\Filinq\\Settings\\FilinqAdmin',
        'currentClassName' => 'OCA\\Filinq\\Settings\\FilinqAdmin',
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
 * Get the priority for the admin settings
 *
 * @return int The priority (0-100)
 *
 * @psalm-return   int
 * @phpstan-return int
 *
 * @spec openspec/specs/admin-settings/spec.md
 */',
        'startLine' => 130,
        'endLine' => 132,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Settings',
        'declaringClassName' => 'OCA\\Filinq\\Settings\\FilinqAdmin',
        'implementingClassName' => 'OCA\\Filinq\\Settings\\FilinqAdmin',
        'currentClassName' => 'OCA\\Filinq\\Settings\\FilinqAdmin',
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
                  'name' => 'string',
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
 * Get the name of this delegated settings section.
 *
 * Returns null so only the section name is displayed in the UI.
 *
 * @return string|null The display name, or null to use the section name only.
 *
 * @psalm-return   string|null
 * @phpstan-return string|null
 */',
        'startLine' => 144,
        'endLine' => 146,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Settings',
        'declaringClassName' => 'OCA\\Filinq\\Settings\\FilinqAdmin',
        'implementingClassName' => 'OCA\\Filinq\\Settings\\FilinqAdmin',
        'currentClassName' => 'OCA\\Filinq\\Settings\\FilinqAdmin',
        'aliasName' => NULL,
      ),
      'getAuthorizedAppConfig' => 
      array (
        'name' => 'getAuthorizedAppConfig',
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
 * Get the list of authorized app config keys for this settings section.
 *
 * Filinq admin settings are full-admin-only; no delegated config keys
 * are granted.
 *
 * @return array<string,mixed> Empty array — no delegated config keys.
 *
 * @psalm-return   array<string,mixed>
 * @phpstan-return array<string,mixed>
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
        'namespace' => 'OCA\\Filinq\\Settings',
        'declaringClassName' => 'OCA\\Filinq\\Settings\\FilinqAdmin',
        'implementingClassName' => 'OCA\\Filinq\\Settings\\FilinqAdmin',
        'currentClassName' => 'OCA\\Filinq\\Settings\\FilinqAdmin',
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