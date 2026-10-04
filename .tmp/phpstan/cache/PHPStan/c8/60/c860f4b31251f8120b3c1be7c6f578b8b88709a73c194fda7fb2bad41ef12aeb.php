<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Conversion/OfficeAppBackend.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Conversion\OfficeAppBackend
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-32b9ab4b9e63dbbe587e499a65219304ebb5aac879614722536a51ce4d843526',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Conversion/OfficeAppBackend.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Conversion',
    'name' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
    'shortName' => 'OfficeAppBackend',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Uses Nextcloud\'s IConversionManager (NC 31+) to route conversions
 * through whichever Office app integration is installed.
 *
 * @category  Service
 * @package   OCA\\Filinq\\Service\\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 55,
    'endLine' => 386,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'OCA\\Filinq\\Service\\Conversion\\ConversionBackendInterface',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'ENABLED_KEY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'name' => 'ENABLED_KEY',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq.conversion.backends.office_app_enabled\'',
          'attributes' => 
          array (
            'startLine' => 62,
            'endLine' => 62,
            'startTokenPos' => 84,
            'startFilePos' => 2097,
            'endTokenPos' => 84,
            'endFilePos' => 2143,
          ),
        ),
        'docComment' => '/**
 * App config key controlling whether this backend is attempted.
 * Default true; tenants disable for air-gapped installs that
 * don\'t want HTTP probing into Office app endpoints.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 2,
        'endColumn' => 77,
      ),
      'APP_ID' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'name' => 'APP_ID',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq\'',
          'attributes' => 
          array (
            'startLine' => 67,
            'endLine' => 67,
            'startTokenPos' => 97,
            'startFilePos' => 2234,
            'endTokenPos' => 97,
            'endFilePos' => 2241,
          ),
        ),
        'docComment' => '/**
 * App identifier used for IAppConfig reads/writes.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 67,
        'endLine' => 67,
        'startColumn' => 2,
        'endColumn' => 33,
      ),
      'TARGET_MIME' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'name' => 'TARGET_MIME',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'application/pdf\'',
          'attributes' => 
          array (
            'startLine' => 72,
            'endLine' => 72,
            'startTokenPos' => 110,
            'startFilePos' => 2337,
            'endTokenPos' => 110,
            'endFilePos' => 2353,
          ),
        ),
        'docComment' => '/**
 * Target MIME for all conversions in this cascade.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 72,
        'endLine' => 72,
        'startColumn' => 2,
        'endColumn' => 47,
      ),
    ),
    'immediateProperties' => 
    array (
      'hasProvidersCache' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'name' => 'hasProvidersCache',
        'modifiers' => 4,
        'type' => 
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
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 81,
            'endLine' => 81,
            'startTokenPos' => 124,
            'startFilePos' => 2582,
            'endTokenPos' => 124,
            'endFilePos' => 2585,
          ),
        ),
        'docComment' => '/**
 * Cached `hasProviders()` result per request to avoid repeated
 * HTTP probing across multiple isAvailable() calls within one
 * conversion attempt.
 *
 * @var boolean|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 81,
        'endLine' => 81,
        'startColumn' => 2,
        'endColumn' => 41,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'providersCache' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'name' => 'providersCache',
        'modifiers' => 4,
        'type' => 
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
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 88,
            'endLine' => 88,
            'startTokenPos' => 138,
            'startFilePos' => 2746,
            'endTokenPos' => 138,
            'endFilePos' => 2749,
          ),
        ),
        'docComment' => '/**
 * Cached provider list per request.
 *
 * @var array<int, \\OCP\\Files\\Conversion\\ConversionMimeProvider>|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 88,
        'endLine' => 88,
        'startColumn' => 2,
        'endColumn' => 39,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'conversionManager' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'name' => 'conversionManager',
        'modifiers' => 132,
        'type' => 
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
                  'name' => 'OCP\\Files\\Conversion\\IConversionManager',
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
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 105,
        'endLine' => 105,
        'startColumn' => 3,
        'endColumn' => 57,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'rootFolder' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
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
        'startLine' => 106,
        'endLine' => 106,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
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
        'startLine' => 107,
        'endLine' => 107,
        'startColumn' => 3,
        'endColumn' => 44,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'appConfig' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'name' => 'appConfig',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IAppConfig',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 108,
        'endLine' => 108,
        'startColumn' => 3,
        'endColumn' => 40,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
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
        'startLine' => 109,
        'endLine' => 109,
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
          'conversionManager' => 
          array (
            'name' => 'conversionManager',
            'default' => NULL,
            'type' => 
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
                      'name' => 'OCP\\Files\\Conversion\\IConversionManager',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 105,
            'endLine' => 105,
            'startColumn' => 3,
            'endColumn' => 57,
            'parameterIndex' => 0,
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
            'startLine' => 106,
            'endLine' => 106,
            'startColumn' => 3,
            'endColumn' => 42,
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
            'startLine' => 107,
            'endLine' => 107,
            'startColumn' => 3,
            'endColumn' => 44,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'appConfig' => 
          array (
            'name' => 'appConfig',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IAppConfig',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 108,
            'endLine' => 108,
            'startColumn' => 3,
            'endColumn' => 40,
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
            'startLine' => 109,
            'endLine' => 109,
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
 * IConversionManager and IRootFolder are nullable so the backend
 * degrades cleanly on Nextcloud versions older than 31 (interface
 * not present) — `isAvailable()` returns false rather than crashing
 * at autowire time.
 *
 * @param IConversionManager|null $conversionManager NC\'s unified converter (31+).
 * @param IRootFolder $rootFolder For looking up the converted-file path.
 * @param IUserSession $userSession Active session — providers expect a user context.
 * @param IAppConfig $appConfig Tenant configuration.
 * @param LoggerInterface $logger Diagnostics.
 */',
        'startLine' => 104,
        'endLine' => 112,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'aliasName' => NULL,
      ),
      'name' => 
      array (
        'name' => 'name',
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
 * Backend identifier surfaced in the 422 body\'s `conversionAttempts[].name`.
 *
 * @return string
 */',
        'startLine' => 119,
        'endLine' => 121,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'aliasName' => NULL,
      ),
      'isAvailable' => 
      array (
        'name' => 'isAvailable',
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
 * Available iff:
 *   - tenant flag is `true`
 *   - IConversionManager was bound (NC ≥ 31 with at least one
 *     conversion provider app installed)
 *   - the manager reports at least one registered provider
 *
 * @return bool
 */',
        'startLine' => 132,
        'endLine' => 155,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'aliasName' => NULL,
      ),
      'canHandle' => 
      array (
        'name' => 'canHandle',
        'parameters' => 
        array (
          'mimeType' => 
          array (
            'name' => 'mimeType',
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
            'startLine' => 168,
            'endLine' => 168,
            'startColumn' => 28,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'extension' => 
          array (
            'name' => 'extension',
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
            'startLine' => 168,
            'endLine' => 168,
            'startColumn' => 46,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Declare whether any registered conversion provider can map this source MIME to PDF.
 *
 * @param string $mimeType Source MIME.
 * @param string $extension Source extension (lowercased, no dot). Matching is
 *                          MIME-driven — providers advertise MIME tuples, not
 *                          extensions — so this is carried into the decline
 *                          diagnostics only.
 *
 * @return bool True iff some registered provider can convert this MIME → application/pdf.
 */',
        'startLine' => 168,
        'endLine' => 191,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'aliasName' => NULL,
      ),
      'convert' => 
      array (
        'name' => 'convert',
        'parameters' => 
        array (
          'source' => 
          array (
            'name' => 'source',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Files\\File',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 205,
            'endLine' => 205,
            'startColumn' => 26,
            'endColumn' => 37,
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
            'name' => 'OCP\\Files\\File',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Delegate to IConversionManager. The provider writes the converted
 * file into the user\'s Files area at the destination path we
 * supply; we then resolve that path back to a File node for the
 * caller.
 *
 * @param File $source Source file node.
 *
 * @return File Newly written PDF file node.
 *
 * @throws ConversionFailedException On manager failure or path resolution failure.
 */',
        'startLine' => 205,
        'endLine' => 275,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'aliasName' => NULL,
      ),
      'resolveConvertedFile' => 
      array (
        'name' => 'resolveConvertedFile',
        'parameters' => 
        array (
          'sourceFolder' => 
          array (
            'name' => 'sourceFolder',
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
            'startLine' => 294,
            'endLine' => 294,
            'startColumn' => 3,
            'endColumn' => 22,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'targetBaseName' => 
          array (
            'name' => 'targetBaseName',
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
            'startLine' => 295,
            'endLine' => 295,
            'startColumn' => 3,
            'endColumn' => 24,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'destFullPath' => 
          array (
            'name' => 'destFullPath',
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
            'startLine' => 296,
            'endLine' => 296,
            'startColumn' => 3,
            'endColumn' => 22,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'writtenPath' => 
          array (
            'name' => 'writtenPath',
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
            'startLine' => 297,
            'endLine' => 297,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\Files\\File',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolve the file the conversion manager reported it wrote.
 *
 * The manager returns a path relative to the user folder OR an absolute
 * one depending on the provider, so the lookup is normalised by going
 * through the source\'s parent folder plus the target basename.
 *
 * @param Folder $sourceFolder Folder that holds the source (and the output).
 * @param string $targetBaseName Basename of the expected PDF.
 * @param string $destFullPath Full destination path handed to the manager.
 * @param mixed $writtenPath Whatever the manager returned, for diagnostics.
 *
 * @return File The converted PDF node.
 *
 * @throws ConversionFailedException When the node cannot be found or is not a file.
 */',
        'startLine' => 293,
        'endLine' => 339,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'aliasName' => NULL,
      ),
      'getProvidersCached' => 
      array (
        'name' => 'getProvidersCached',
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
 * Cached accessor for the provider list. Memoised per request to
 * avoid hitting the manager\'s `getProviders()` repeatedly during
 * a single cascade walk.
 *
 * @return array<int, \\OCP\\Files\\Conversion\\ConversionMimeProvider>
 */',
        'startLine' => 348,
        'endLine' => 369,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'aliasName' => NULL,
      ),
      'stripExtension' => 
      array (
        'name' => 'stripExtension',
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
            'startLine' => 378,
            'endLine' => 378,
            'startColumn' => 34,
            'endColumn' => 45,
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
 * Return $name without its trailing `.ext`.
 *
 * @param string $name File name with extension.
 *
 * @return string
 */',
        'startLine' => 378,
        'endLine' => 385,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\OfficeAppBackend',
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