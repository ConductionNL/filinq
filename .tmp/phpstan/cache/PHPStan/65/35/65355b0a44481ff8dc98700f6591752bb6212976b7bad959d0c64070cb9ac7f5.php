<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Controller/ScanIntakeController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Controller\ScanIntakeController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-e73363d18f41dc5cc2e4b26d4b4428305a06f7ebbd0917d75c6cba5a00b7a065',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Controller/ScanIntakeController.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Controller',
    'name' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
    'shortName' => 'ScanIntakeController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Controller for separator sheets, scan profiles and batch splitting.
 *
 * @category Controller
 * @package  OCA\\Filinq\\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) The thirteenth type is
 * OCP\\Files\\File, named so the batch id is checked to be a file before it
 * reaches the reader. Without that check a folder id passes as a batch with no
 * pages, which is the silent version of this refusal.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 59,
    'endLine' => 284,
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
      'sheets' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
        'name' => 'sheets',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\SeparatorSheetService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 78,
        'endLine' => 78,
        'startColumn' => 3,
        'endColumn' => 48,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'profiles' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
        'name' => 'profiles',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\ScanProfileService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 79,
        'endLine' => 79,
        'startColumn' => 3,
        'endColumn' => 47,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'batches' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
        'name' => 'batches',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\ScanBatchService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 80,
        'endLine' => 80,
        'startColumn' => 3,
        'endColumn' => 44,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'store' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
        'name' => 'store',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\ScanBatchRepository',
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
        'endColumn' => 45,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'rootFolder' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
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
      'userSession' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
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
        'startLine' => 83,
        'endLine' => 83,
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
          'appName' => 
          array (
            'name' => 'appName',
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
            'startLine' => 76,
            'endLine' => 76,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 77,
            'endLine' => 77,
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'sheets' => 
          array (
            'name' => 'sheets',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\SeparatorSheetService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 78,
            'endLine' => 78,
            'startColumn' => 3,
            'endColumn' => 48,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'profiles' => 
          array (
            'name' => 'profiles',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\ScanProfileService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 79,
            'endLine' => 79,
            'startColumn' => 3,
            'endColumn' => 47,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'batches' => 
          array (
            'name' => 'batches',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\ScanBatchService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 80,
            'endLine' => 80,
            'startColumn' => 3,
            'endColumn' => 44,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'store' => 
          array (
            'name' => 'store',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\ScanBatchRepository',
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
            'endColumn' => 45,
            'parameterIndex' => 5,
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
            'startLine' => 82,
            'endLine' => 82,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 6,
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
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 3,
            'endColumn' => 44,
            'parameterIndex' => 7,
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
 * @param string $appName The app name.
 * @param IRequest $request The request.
 * @param SeparatorSheetService $sheets The sheets filinq prints and reads.
 * @param ScanProfileService $profiles The declared scan profiles.
 * @param ScanBatchService $batches The splitter.
 * @param ScanBatchRepository $store The batch store.
 * @param IRootFolder $rootFolder The file tree.
 * @param IUserSession $userSession The current session.
 *
 * @return void
 */',
        'startLine' => 75,
        'endLine' => 87,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
        'aliasName' => NULL,
      ),
      'separators' => 
      array (
        'name' => 'separators',
        'parameters' => 
        array (
          'profileId' => 
          array (
            'name' => 'profileId',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 108,
                'endLine' => 108,
                'startTokenPos' => 219,
                'startFilePos' => 3905,
                'endTokenPos' => 219,
                'endFilePos' => 3906,
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
            'startLine' => 108,
            'endLine' => 108,
            'startColumn' => 29,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'caseNumbers' => 
          array (
            'name' => 'caseNumbers',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 108,
                'endLine' => 108,
                'startTokenPos' => 228,
                'startFilePos' => 3930,
                'endTokenPos' => 229,
                'endFilePos' => 3931,
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
            'startLine' => 108,
            'endLine' => 108,
            'startColumn' => 53,
            'endColumn' => 75,
            'parameterIndex' => 1,
            'isOptional' => true,
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
                  'name' => 'OCP\\AppFramework\\Http\\DataDownloadResponse',
                  'isIdentifier' => false,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'OCP\\AppFramework\\Http\\JSONResponse',
                  'isIdentifier' => false,
                ),
              ),
            ),
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
 * Print separator sheets, one page per case number.
 *
 * @param string $profileId The scan profile the sheets belong to.
 * @param array<int, string> $caseNumbers The cases to print a sheet for.
 *
 * @return DataDownloadResponse|JSONResponse The sheets, or the refusal.
 *
 * @no-admin-idor-exempt this endpoint reads no stored object, so there is
 *       nothing to scope to the caller. `render()` encodes the profile id and
 *       the case numbers the caller typed into a barcode payload and renders
 *       them to a PDF; the profile is instance-wide configuration that every
 *       clerk may print a sheet for, and no record, folder or file is looked
 *       up from either argument. Verified by reading SeparatorSheetService::
 *       render(), which touches the profile store and the PDF writer only.
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 */',
        'startLine' => 107,
        'endLine' => 127,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
        'aliasName' => NULL,
      ),
      'listProfiles' => 
      array (
        'name' => 'listProfiles',
        'parameters' => 
        array (
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
 * The declared scan profiles.
 *
 * @return JSONResponse The profiles.
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 */',
        'startLine' => 136,
        'endLine' => 152,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
        'aliasName' => NULL,
      ),
      'declareProfiles' => 
      array (
        'name' => 'declareProfiles',
        'parameters' => 
        array (
          'profiles' => 
          array (
            'name' => 'profiles',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 172,
                'endLine' => 172,
                'startTokenPos' => 518,
                'startFilePos' => 5972,
                'endTokenPos' => 519,
                'endFilePos' => 5973,
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
            'startLine' => 172,
            'endLine' => 172,
            'startColumn' => 34,
            'endColumn' => 53,
            'parameterIndex' => 0,
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
        ),
        'docComment' => '/**
 * Declare the scan profiles.
 *
 * Administrative: a profile points a scanner\'s output at a folder and says
 * how what lands there is cut, which is an instance-wide decision.
 *
 * @param array<int, array<string, mixed>> $profiles The profiles.
 *
 * @return JSONResponse What was stored, or the refusal.
 *
 * @auth admin-only a profile decides which folder a scanner writes into and how
 *       everything landing there is cut, for the whole instance; the admin posture
 *       is the absence of NoAdminRequired, and adding that attribute to satisfy a
 *       gate would hand every authenticated user the setting this method exists to
 *       keep administrative.
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 */',
        'startLine' => 172,
        'endLine' => 185,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
        'aliasName' => NULL,
      ),
      'split' => 
      array (
        'name' => 'split',
        'parameters' => 
        array (
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
            'startLine' => 198,
            'endLine' => 198,
            'startColumn' => 24,
            'endColumn' => 34,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'profileId' => 
          array (
            'name' => 'profileId',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 198,
                'endLine' => 198,
                'startTokenPos' => 643,
                'startFilePos' => 6747,
                'endTokenPos' => 643,
                'endFilePos' => 6748,
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
            'startLine' => 198,
            'endLine' => 198,
            'startColumn' => 37,
            'endColumn' => 58,
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
 * Cut one delivered batch into documents.
 *
 * @param int $fileId The batch the scanner wrote.
 * @param string $profileId The profile to cut it with; when empty, the folder it sits in decides.
 *
 * @return JSONResponse The batch, split or failed.
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 */',
        'startLine' => 197,
        'endLine' => 260,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
        'aliasName' => NULL,
      ),
      'resolveProfile' => 
      array (
        'name' => 'resolveProfile',
        'parameters' => 
        array (
          'profileId' => 
          array (
            'name' => 'profileId',
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
            'startLine' => 276,
            'endLine' => 276,
            'startColumn' => 34,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 276,
            'endLine' => 276,
            'startColumn' => 53,
            'endColumn' => 64,
            'parameterIndex' => 1,
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The profile to cut a batch with.
 *
 * @param string $profileId The profile the caller named, if any.
 * @param string $path The path the batch sits at.
 *
 * @return array<string, mixed>|null The profile, or null when nobody watches this folder.
 *
 * @throws Throwable When the profile store cannot be read. The caller, split(),
 *                   catches it and answers the refusal, so a store that is down
 *                   is reported rather than read as "nobody watches this folder".
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 */',
        'startLine' => 276,
        'endLine' => 283,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Controller',
        'declaringClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
        'implementingClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
        'currentClassName' => 'OCA\\Filinq\\Controller\\ScanIntakeController',
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