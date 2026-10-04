<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/BackgroundJob/UploadFragmentReaperJob.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\BackgroundJob\UploadFragmentReaperJob
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-e032795888863de3b87d07132efe0ec28940e22dd5f1b1378182c5245c7662c6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/BackgroundJob/UploadFragmentReaperJob.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\BackgroundJob',
    'name' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
    'shortName' => 'UploadFragmentReaperJob',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Sweeps every seen user\'s documents folder once a night.
 *
 * 🔑 THE AGE IS DECLARED, NOT HARDCODED. REQ-CDF-05 says "older than a declared
 * age", and an instance that uploads 4 GB scans over a slow line needs a longer
 * one than the default. A declared age of zero would reap a fragment the moment
 * it appeared, taking every in-flight upload with it, so the floor is one hour
 * and a lower declaration is raised to it rather than honoured.
 *
 * 🔑 THE TOTALS ARE ACROSS THE INSTANCE, and each user\'s sweep is also logged by
 * the reaper itself. One instance-wide line answers "is this worth running",
 * the per-user lines answer "whose upload keeps breaking".
 *
 * @category BackgroundJob
 * @package  OCA\\Filinq\\BackgroundJob
 * @author   Conduction Development Team <dev@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 57,
    'endLine' => 218,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'OCP\\BackgroundJob\\TimedJob',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'AGE_KEY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
        'name' => 'AGE_KEY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'uploadFragmentMaxAgeHours\'',
          'attributes' => 
          array (
            'startLine' => 64,
            'endLine' => 64,
            'startTokenPos' => 89,
            'startFilePos' => 2085,
            'endTokenPos' => 89,
            'endFilePos' => 2111,
          ),
        ),
        'docComment' => '/**
 * The app config key declaring how old a fragment must be, in hours.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 64,
        'endLine' => 64,
        'startColumn' => 2,
        'endColumn' => 52,
      ),
      'DEFAULT_AGE_HOURS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
        'name' => 'DEFAULT_AGE_HOURS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '24',
          'attributes' => 
          array (
            'startLine' => 71,
            'endLine' => 71,
            'startTokenPos' => 102,
            'startFilePos' => 2207,
            'endTokenPos' => 102,
            'endFilePos' => 2208,
          ),
        ),
        'docComment' => '/**
 * The default age, in hours.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 71,
        'endLine' => 71,
        'startColumn' => 2,
        'endColumn' => 37,
      ),
      'MINIMUM_AGE_HOURS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
        'name' => 'MINIMUM_AGE_HOURS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '1',
          'attributes' => 
          array (
            'startLine' => 78,
            'endLine' => 78,
            'startTokenPos' => 115,
            'startFilePos' => 2322,
            'endTokenPos' => 115,
            'endFilePos' => 2322,
          ),
        ),
        'docComment' => '/**
 * The shortest age that is honoured, in hours.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 78,
        'endLine' => 78,
        'startColumn' => 2,
        'endColumn' => 36,
      ),
      'DOCUMENTS_FOLDER' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
        'name' => 'DOCUMENTS_FOLDER',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'DocuDesk\'',
          'attributes' => 
          array (
            'startLine' => 89,
            'endLine' => 89,
            'startTokenPos' => 128,
            'startFilePos' => 2606,
            'endTokenPos' => 128,
            'endFilePos' => 2615,
          ),
        ),
        'docComment' => '/**
 * The folder filinq\'s documents live in.
 *
 * ⚠️ The folder name stays `DocuDesk` across the filinq rename; see
 * FileUploadService::getFilinqFolder() for why moving it orphans every
 * document silently.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 89,
        'endLine' => 89,
        'startColumn' => 2,
        'endColumn' => 44,
      ),
    ),
    'immediateProperties' => 
    array (
      'reaper' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
        'name' => 'reaper',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\UploadFragmentReaper',
            'isIdentifier' => false,
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
        'endColumn' => 47,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'rootFolder' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
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
      'userManager' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
        'name' => 'userManager',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IUserManager',
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
      'config' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
        'name' => 'config',
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
        'endColumn' => 37,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
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
          'time' => 
          array (
            'name' => 'time',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\AppFramework\\Utility\\ITimeFactory',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'reaper' => 
          array (
            'name' => 'reaper',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\UploadFragmentReaper',
                'isIdentifier' => false,
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
            'endColumn' => 47,
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
            'startLine' => 106,
            'endLine' => 106,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'userManager' => 
          array (
            'name' => 'userManager',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IUserManager',
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
            'parameterIndex' => 3,
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
            'endColumn' => 37,
            'parameterIndex' => 4,
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
            'parameterIndex' => 5,
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
 * @param ITimeFactory         $time        The clock the scheduler reads.
 * @param UploadFragmentReaper $reaper      Sweeps one tree.
 * @param IRootFolder          $rootFolder  The file tree.
 * @param IUserManager         $userManager Every user with a folder.
 * @param IAppConfig           $config      Where the age is declared.
 * @param LoggerInterface      $logger      Structured logger.
 *
 * @return void
 */',
        'startLine' => 103,
        'endLine' => 115,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\BackgroundJob',
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
        'currentClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
        'aliasName' => NULL,
      ),
      'maxAgeSeconds' => 
      array (
        'name' => 'maxAgeSeconds',
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
 * The declared age, in seconds, never below the floor.
 *
 * @return int The age in seconds.
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
        'startLine' => 124,
        'endLine' => 133,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\BackgroundJob',
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
        'currentClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
        'aliasName' => NULL,
      ),
      'run' => 
      array (
        'name' => 'run',
        'parameters' => 
        array (
          'argument' => 
          array (
            'name' => 'argument',
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
            'startLine' => 148,
            'endLine' => 148,
            'startColumn' => 25,
            'endColumn' => 39,
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
 * Sweep every seen user\'s documents folder once.
 *
 * @param mixed $argument Job arguments. The job is registered bare and takes none.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter) `$argument` is Nextcloud\'s
 * TimedJob signature, not a parameter this job chose. Dropping it changes the
 * override into a different method and the job stops running.
 *
 * @return void
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
        'startLine' => 148,
        'endLine' => 179,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'OCA\\Filinq\\BackgroundJob',
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
        'currentClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
        'aliasName' => NULL,
      ),
      'documentsFolder' => 
      array (
        'name' => 'documentsFolder',
        'parameters' => 
        array (
          'userId' => 
          array (
            'name' => 'userId',
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
            'startLine' => 190,
            'endLine' => 190,
            'startColumn' => 35,
            'endColumn' => 48,
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
                  'name' => 'OCP\\Files\\Folder',
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
 * One user\'s documents folder, or null when they have none.
 *
 * @param string $userId The user.
 *
 * @return Folder|null The folder.
 *
 * @spec exclude Lookup helper; the sweep\'s behaviour is in the reaper.
 */',
        'startLine' => 190,
        'endLine' => 217,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\BackgroundJob',
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
        'currentClassName' => 'OCA\\Filinq\\BackgroundJob\\UploadFragmentReaperJob',
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