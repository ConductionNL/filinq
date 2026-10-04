<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Controller/PreferencesController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Controller\PreferencesController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-d9782cf23e23445a0d31a480ef29013e166f8bcfc9881077e7e7658109818084',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Controller\\PreferencesController',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Controller/PreferencesController.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Controller',
    'name' => 'OCA\\Filinq\\Controller\\PreferencesController',
    'shortName' => 'PreferencesController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Serves `GET|PUT /api/preferences/{key}` for Filinq.
 *
 * Behaviour (auth posture, key sanitisation, per-user scoping) is a
 * byte-for-byte reimplementation of OpenRegister\'s generic. `#[NoAdminRequired]`
 * was previously INHERITED from the generic; it is declared explicitly on both
 * methods here so the auth posture is unchanged by dropping the inheritance —
 * any logged-in user may read and write their OWN preferences, and the
 * `$user === null` guard still rejects anonymous callers with 401.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 78,
    'endLine' => 191,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'OCP\\AppFramework\\Controller',
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
      'config' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PreferencesController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PreferencesController',
        'name' => 'config',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IConfig',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 90,
        'endLine' => 90,
        'startColumn' => 3,
        'endColumn' => 34,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'userSession' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PreferencesController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PreferencesController',
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
        'startLine' => 91,
        'endLine' => 91,
        'startColumn' => 3,
        'endColumn' => 44,
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
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IRequest',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 89,
            'endLine' => 89,
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'config' => 
          array (
            'name' => 'config',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IConfig',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 90,
            'endLine' => 90,
            'startColumn' => 3,
            'endColumn' => 34,
            'parameterIndex' => 1,
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
            'startLine' => 91,
            'endLine' => 91,
            'startColumn' => 3,
            'endColumn' => 44,
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
 * @param IRequest $request The request object.
 * @param IConfig $config Nextcloud config service (per-user values).
 * @param IUserSession $userSession The current user session.
 *
 * @return void
 */',
        'startLine' => 88,
        'endLine' => 95,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PreferencesController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PreferencesController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\PreferencesController',
        'aliasName' => NULL,
      ),
      'getPreference' => 
      array (
        'name' => 'getPreference',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
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
            'startLine' => 107,
            'endLine' => 107,
            'startColumn' => 32,
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
            'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\NoAdminRequired',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * GET /api/preferences/{key} — read one preference for the current user.
 *
 * @param string $key The preference key (sanitised before use).
 *
 * @return JSONResponse `{value: string|null}`, or 401/400 on a bad caller/key.
 *
 * @spec openspec/specs/preferences-api/spec.md
 */',
        'startLine' => 106,
        'endLine' => 131,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PreferencesController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PreferencesController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\PreferencesController',
        'aliasName' => NULL,
      ),
      'setPreference' => 
      array (
        'name' => 'setPreference',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
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
            'startLine' => 146,
            'endLine' => 146,
            'startColumn' => 32,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 146,
                'endLine' => 146,
                'startTokenPos' => 379,
                'startFilePos' => 5420,
                'endTokenPos' => 379,
                'endFilePos' => 5421,
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
            'startLine' => 146,
            'endLine' => 146,
            'startColumn' => 45,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'OCP\\AppFramework\\Http\\Attribute\\NoAdminRequired',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * PUT /api/preferences/{key} — write (or clear) one preference.
 *
 * An empty `$value` deletes the stored preference, matching the generic.
 *
 * @param string $key The preference key (sanitised before use).
 * @param string $value The value to store; empty string clears it.
 *
 * @return JSONResponse `{value: string|null}`, or 401/400 on a bad caller/key.
 *
 * @spec openspec/specs/preferences-api/spec.md
 */',
        'startLine' => 145,
        'endLine' => 175,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PreferencesController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PreferencesController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\PreferencesController',
        'aliasName' => NULL,
      ),
      'sanitizeKey' => 
      array (
        'name' => 'sanitizeKey',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
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
            'startLine' => 186,
            'endLine' => 186,
            'startColumn' => 31,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Reduce a caller-supplied key to a safe, bounded storage key.
 *
 * Lowercased, restricted to `[a-z0-9-]`, capped at 64 characters.
 *
 * @param string $key The raw key from the URL.
 *
 * @return string The sanitised key, or \'\' when nothing survives.
 */',
        'startLine' => 186,
        'endLine' => 190,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\PreferencesController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\PreferencesController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\PreferencesController',
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