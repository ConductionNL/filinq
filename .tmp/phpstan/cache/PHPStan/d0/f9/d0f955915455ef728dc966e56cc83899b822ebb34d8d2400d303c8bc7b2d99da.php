<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Conversion/LibreOfficeHeadlessBackend.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-43f393312a4bdcdbd561c65ddcd8303b9890eeff4f67386850effd27f1e38790',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Conversion/LibreOfficeHeadlessBackend.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Conversion',
    'name' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
    'shortName' => 'LibreOfficeHeadlessBackend',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Converts documents to PDF/A-3b via LibreOffice headless (`soffice --headless`).
 *
 * The conversion is performed by writing the source file to a temp path,
 * invoking soffice with `proc_open`, and collecting the emitted `.pdf`
 * file. A global ILockingProvider lock serialises concurrent calls;
 * a `proc_open` + stream-select loop enforces the configured timeout.
 *
 * @category  Service
 * @package   OCA\\Filinq\\Service\\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/pdf-conversion-service/tasks.md#task-6
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 62,
    'endLine' => 602,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'name' => 'ENABLED_KEY',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq.conversion.backends.libreoffice_enabled\'',
          'attributes' => 
          array (
            'startLine' => 67,
            'endLine' => 67,
            'startTokenPos' => 74,
            'startFilePos' => 2359,
            'endTokenPos' => 74,
            'endFilePos' => 2406,
          ),
        ),
        'docComment' => '/**
 * App config key controlling whether this backend is attempted.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 67,
        'endLine' => 67,
        'startColumn' => 2,
        'endColumn' => 78,
      ),
      'BINARY_KEY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'name' => 'BINARY_KEY',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq.conversion.libreoffice_binary_path\'',
          'attributes' => 
          array (
            'startLine' => 72,
            'endLine' => 72,
            'startTokenPos' => 87,
            'startFilePos' => 2503,
            'endTokenPos' => 87,
            'endFilePos' => 2545,
          ),
        ),
        'docComment' => '/**
 * App config key for the path to the soffice binary.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 72,
        'endLine' => 72,
        'startColumn' => 2,
        'endColumn' => 72,
      ),
      'TIMEOUT_KEY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'name' => 'TIMEOUT_KEY',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq.conversion.timeout_seconds\'',
          'attributes' => 
          array (
            'startLine' => 77,
            'endLine' => 77,
            'startTokenPos' => 100,
            'startFilePos' => 2642,
            'endTokenPos' => 100,
            'endFilePos' => 2676,
          ),
        ),
        'docComment' => '/**
 * App config key for conversion timeout in seconds.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 77,
        'endLine' => 77,
        'startColumn' => 2,
        'endColumn' => 65,
      ),
      'LOCK_KEY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'name' => 'LOCK_KEY',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'soffice:headless:convert\'',
          'attributes' => 
          array (
            'startLine' => 82,
            'endLine' => 82,
            'startTokenPos' => 113,
            'startFilePos' => 2794,
            'endTokenPos' => 113,
            'endFilePos' => 2819,
          ),
        ),
        'docComment' => '/**
 * ILockingProvider lock key — one concurrent soffice process per NC host.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 82,
        'endLine' => 82,
        'startColumn' => 2,
        'endColumn' => 53,
      ),
      'APP_ID' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'name' => 'APP_ID',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq\'',
          'attributes' => 
          array (
            'startLine' => 87,
            'endLine' => 87,
            'startTokenPos' => 126,
            'startFilePos' => 2903,
            'endTokenPos' => 126,
            'endFilePos' => 2910,
          ),
        ),
        'docComment' => '/**
 * App identifier used for IAppConfig reads.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 87,
        'endLine' => 87,
        'startColumn' => 2,
        'endColumn' => 33,
      ),
      'SUPPORTED_MIMES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'name' => 'SUPPORTED_MIMES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'application/vnd.openxmlformats-officedocument.wordprocessingml.document\' => true, \'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet\' => true, \'application/vnd.openxmlformats-officedocument.presentationml.presentation\' => true, \'application/msword\' => true, \'application/vnd.ms-excel\' => true, \'application/vnd.ms-powerpoint\' => true, \'application/vnd.oasis.opendocument.text\' => true, \'application/vnd.oasis.opendocument.spreadsheet\' => true, \'application/vnd.oasis.opendocument.presentation\' => true, \'application/rtf\' => true, \'text/rtf\' => true, \'text/html\' => true, \'text/plain\' => true, \'image/png\' => true, \'image/jpeg\' => true]',
          'attributes' => 
          array (
            'startLine' => 96,
            'endLine' => 112,
            'startTokenPos' => 139,
            'startFilePos' => 3143,
            'endTokenPos' => 246,
            'endFilePos' => 3829,
          ),
        ),
        'docComment' => '/**
 * MIME types LibreOffice can convert to PDF. Only common document
 * formats are listed; the Office-app backend handles these first
 * when present.
 *
 * @var array<string, true>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 96,
        'endLine' => 112,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
      'SUPPORTED_EXTENSIONS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'name' => 'SUPPORTED_EXTENSIONS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'doc\' => true, \'docx\' => true, \'xls\' => true, \'xlsx\' => true, \'ppt\' => true, \'pptx\' => true, \'odt\' => true, \'ods\' => true, \'odp\' => true, \'rtf\' => true, \'html\' => true, \'htm\' => true, \'txt\' => true, \'png\' => true, \'jpg\' => true, \'jpeg\' => true]',
          'attributes' => 
          array (
            'startLine' => 120,
            'endLine' => 137,
            'startTokenPos' => 259,
            'startFilePos' => 4044,
            'endTokenPos' => 373,
            'endFilePos' => 4324,
          ),
        ),
        'docComment' => '/**
 * File extensions that LibreOffice handles. Used as fallback when the
 * MIME type is generic (e.g. application/octet-stream).
 *
 * @var array<string, true>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 120,
        'endLine' => 137,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'processRunner' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'name' => 'processRunner',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Conversion\\SofficeProcessRunner',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Runs and supervises the headless soffice subprocess.
 *
 * @var SofficeProcessRunner
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 144,
        'endLine' => 144,
        'startColumn' => 2,
        'endColumn' => 54,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'appConfig' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
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
        'startLine' => 157,
        'endLine' => 157,
        'startColumn' => 3,
        'endColumn' => 40,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'lockingProvider' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'name' => 'lockingProvider',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\Lock\\ILockingProvider',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 158,
        'endLine' => 158,
        'startColumn' => 3,
        'endColumn' => 52,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
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
        'startLine' => 159,
        'endLine' => 159,
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
            'startLine' => 157,
            'endLine' => 157,
            'startColumn' => 3,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'lockingProvider' => 
          array (
            'name' => 'lockingProvider',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Lock\\ILockingProvider',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 158,
            'endLine' => 158,
            'startColumn' => 3,
            'endColumn' => 52,
            'parameterIndex' => 1,
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
            'startLine' => 159,
            'endLine' => 159,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'processRunner' => 
          array (
            'name' => 'processRunner',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 160,
                'endLine' => 160,
                'startTokenPos' => 430,
                'startFilePos' => 5202,
                'endTokenPos' => 430,
                'endFilePos' => 5205,
              ),
            ),
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
                      'name' => 'OCA\\Filinq\\Service\\Conversion\\SofficeProcessRunner',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 160,
            'endLine' => 160,
            'startColumn' => 3,
            'endColumn' => 45,
            'parameterIndex' => 3,
            'isOptional' => true,
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
 * @param IAppConfig $appConfig Tenant configuration provider.
 * @param ILockingProvider $lockingProvider Nextcloud locking for soffice serialisation.
 * @param LoggerInterface $logger Logger for diagnostics.
 * @param SofficeProcessRunner|null $processRunner Subprocess runner; autowired in
 *                                                 production, defaulted here so existing
 *                                                 call sites stay source-compatible.
 */',
        'startLine' => 156,
        'endLine' => 164,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
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
 * Backend identifier used in attempt records and diagnostics.
 *
 * @return string
 *
 * @spec openspec/changes/pdf-conversion-service/tasks.md#task-6
 */',
        'startLine' => 173,
        'endLine' => 175,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
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
 * Returns true iff:
 *  - the tenant flag is enabled (default true)
 *  - the configured soffice binary exists and is executable
 *
 * @return bool
 *
 * @spec openspec/changes/pdf-conversion-service/tasks.md#task-6
 */',
        'startLine' => 186,
        'endLine' => 194,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
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
            'startLine' => 206,
            'endLine' => 206,
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
            'startLine' => 206,
            'endLine' => 206,
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
 * Returns true when the MIME type or extension is in the supported set.
 *
 * @param string $mimeType Source MIME.
 * @param string $extension Lowercased extension without dot.
 *
 * @return bool
 *
 * @spec openspec/changes/pdf-conversion-service/tasks.md#task-6
 */',
        'startLine' => 206,
        'endLine' => 212,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
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
            'startLine' => 229,
            'endLine' => 229,
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
 * Convert via LibreOffice headless. Acquires the global lock to
 * serialise concurrent soffice calls, writes the source to a temp
 * directory, invokes soffice with `proc_open`, enforces the
 * configured timeout, and resolves the output PDF back to a
 * Nextcloud File node.
 *
 * @param File $source Source file node.
 *
 * @return File Newly written PDF file node.
 *
 * @throws ConversionFailedException On lock failure, timeout, or non-zero exit.
 *
 * @spec openspec/changes/pdf-conversion-service/tasks.md#task-6
 */',
        'startLine' => 229,
        'endLine' => 266,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'aliasName' => NULL,
      ),
      'runConversion' => 
      array (
        'name' => 'runConversion',
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
            'startLine' => 280,
            'endLine' => 280,
            'startColumn' => 33,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'binary' => 
          array (
            'name' => 'binary',
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
            'startLine' => 280,
            'endLine' => 280,
            'startColumn' => 47,
            'endColumn' => 60,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'timeout' => 
          array (
            'name' => 'timeout',
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
            'startLine' => 280,
            'endLine' => 280,
            'startColumn' => 63,
            'endColumn' => 74,
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
            'name' => 'OCP\\Files\\File',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Write source to a temp dir, invoke soffice, wait (with timeout),
 * then write the emitted PDF to Nextcloud Files beside the source.
 *
 * @param File $source Source file node.
 * @param string $binary Path to the soffice binary.
 * @param int $timeout Timeout in seconds.
 *
 * @return File Newly written PDF file node.
 *
 * @throws ConversionFailedException On soffice failure, timeout, or file I/O error.
 */',
        'startLine' => 280,
        'endLine' => 339,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'aliasName' => NULL,
      ),
      'writeSourceBytes' => 
      array (
        'name' => 'writeSourceBytes',
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
            'startLine' => 351,
            'endLine' => 351,
            'startColumn' => 36,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'srcPath' => 
          array (
            'name' => 'srcPath',
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
            'startLine' => 351,
            'endLine' => 351,
            'startColumn' => 50,
            'endColumn' => 64,
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
 * Copy the node\'s bytes to the temp path soffice will read.
 *
 * @param File $source Source file node.
 * @param string $srcPath Temp path to write the source bytes to.
 *
 * @return void
 *
 * @throws ConversionFailedException When the node yields no readable content.
 */',
        'startLine' => 351,
        'endLine' => 369,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'aliasName' => NULL,
      ),
      'buildArgv' => 
      array (
        'name' => 'buildArgv',
        'parameters' => 
        array (
          'binary' => 
          array (
            'name' => 'binary',
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
            'startLine' => 385,
            'endLine' => 385,
            'startColumn' => 29,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'tmpDir' => 
          array (
            'name' => 'tmpDir',
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
            'startLine' => 385,
            'endLine' => 385,
            'startColumn' => 45,
            'endColumn' => 58,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'srcPath' => 
          array (
            'name' => 'srcPath',
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
            'startLine' => 385,
            'endLine' => 385,
            'startColumn' => 61,
            'endColumn' => 75,
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
 * Build the soffice argv for a PDF/A-3b conversion.
 *
 * The array form of proc_open avoids the `/bin/sh -c` layer entirely —
 * strictly safer than the string form even with escapeshellarg().
 * `--norestore` and `--nofirststartwizard` keep soffice from trying to
 * bring up its on-disk profile UI under headless.
 *
 * @param string $binary Path to the soffice binary.
 * @param string $tmpDir Temp directory soffice writes its output into.
 * @param string $srcPath Path of the materialised source document.
 *
 * @return array<int, string> Process argv (argv[0] = binary).
 */',
        'startLine' => 385,
        'endLine' => 401,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'aliasName' => NULL,
      ),
      'readEmittedPdf' => 
      array (
        'name' => 'readEmittedPdf',
        'parameters' => 
        array (
          'tmpDir' => 
          array (
            'name' => 'tmpDir',
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
            'startLine' => 419,
            'endLine' => 419,
            'startColumn' => 34,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'baseName' => 
          array (
            'name' => 'baseName',
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
            'startLine' => 419,
            'endLine' => 419,
            'startColumn' => 50,
            'endColumn' => 65,
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
 * Locate, containment-check, and read the PDF soffice emitted.
 *
 * Soffice emits the file with the source basename + ".pdf". Even though
 * $baseName is derived from basename($source->getName()), the resolved
 * output path is realpath\'d and checked to stay inside $tmpDir before it
 * is read — that closes any remaining TOCTOU / symlink window.
 *
 * @param string $tmpDir Temp directory soffice wrote its output into.
 * @param string $baseName Source basename without extension.
 *
 * @return string Non-empty PDF bytes.
 *
 * @throws ConversionFailedException When the output is missing, escapes
 *                                   the sandbox, or is empty.
 */',
        'startLine' => 419,
        'endLine' => 470,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'aliasName' => NULL,
      ),
      'extractExtension' => 
      array (
        'name' => 'extractExtension',
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
            'startLine' => 480,
            'endLine' => 480,
            'startColumn' => 36,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Return the lowercased extension of $name without the leading dot.
 *
 * @param string $name File name, with or without an extension.
 *
 * @return string Lowercased extension, or an empty string when the name
 *                carries no dot.
 */',
        'startLine' => 480,
        'endLine' => 487,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
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
            'startLine' => 496,
            'endLine' => 496,
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
 * Return $name without its trailing `.ext` suffix.
 *
 * @param string $name File name with or without an extension.
 *
 * @return string Name without extension.
 */',
        'startLine' => 496,
        'endLine' => 503,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'aliasName' => NULL,
      ),
      'resolveBinaryPath' => 
      array (
        'name' => 'resolveBinaryPath',
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
 * Read and resolve the configured soffice binary path.
 * Defaults to `"soffice"` (resolved via PATH).
 *
 * @return string Binary path.
 */',
        'startLine' => 511,
        'endLine' => 518,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'aliasName' => NULL,
      ),
      'resolveTimeout' => 
      array (
        'name' => 'resolveTimeout',
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
 * Read and resolve the configured conversion timeout.
 * Defaults to 60 seconds; minimum clamped to 1.
 *
 * @return int Timeout in seconds.
 */',
        'startLine' => 526,
        'endLine' => 530,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'aliasName' => NULL,
      ),
      'isBinaryExecutable' => 
      array (
        'name' => 'isBinaryExecutable',
        'parameters' => 
        array (
          'binary' => 
          array (
            'name' => 'binary',
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
            'startLine' => 540,
            'endLine' => 540,
            'startColumn' => 38,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Check whether the given binary path is executable.
 * Wraps the `is_executable` filesystem check.
 *
 * @param string $binary Path to check.
 *
 * @return bool True when the path resolves to an executable file.
 */',
        'startLine' => 540,
        'endLine' => 566,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'aliasName' => NULL,
      ),
      'cleanupDir' => 
      array (
        'name' => 'cleanupDir',
        'parameters' => 
        array (
          'dir' => 
          array (
            'name' => 'dir',
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
            'startLine' => 575,
            'endLine' => 575,
            'startColumn' => 30,
            'endColumn' => 40,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Recursively delete a temp directory and its contents.
 *
 * @param string $dir Directory path.
 *
 * @return void
 */',
        'startLine' => 575,
        'endLine' => 601,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\LibreOfficeHeadlessBackend',
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