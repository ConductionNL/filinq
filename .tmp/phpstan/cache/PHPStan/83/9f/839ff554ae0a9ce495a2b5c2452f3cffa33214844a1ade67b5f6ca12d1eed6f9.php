<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/CaseArchiveService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\CaseArchiveService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-e3615fd56c5d8909c108d7c092338f90607a75a2905ad739babd5743382d2bbc',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/CaseArchiveService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\CaseArchiveService',
    'shortName' => 'CaseArchiveService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Collects the files of one object into one bundle, and says what it holds.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 50,
    'endLine' => 293,
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
      'SCHEMA' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'name' => 'SCHEMA',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'archiveJob\'',
          'attributes' => 
          array (
            'startLine' => 57,
            'endLine' => 57,
            'startTokenPos' => 60,
            'startFilePos' => 1708,
            'endTokenPos' => 60,
            'endFilePos' => 1719,
          ),
        ),
        'docComment' => '/**
 * The schema holding the jobs.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 2,
        'endColumn' => 36,
      ),
      'DEFAULT_CEILING' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'name' => 'DEFAULT_CEILING',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '2147483648',
          'attributes' => 
          array (
            'startLine' => 64,
            'endLine' => 64,
            'startTokenPos' => 73,
            'startFilePos' => 1845,
            'endTokenPos' => 73,
            'endFilePos' => 1854,
          ),
        ),
        'docComment' => '/**
 * The ceiling used when an instance declared none, in bytes.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 64,
        'endLine' => 64,
        'startColumn' => 2,
        'endColumn' => 43,
      ),
      'REASON_CEILING' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'name' => 'REASON_CEILING',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'ceiling\'',
          'attributes' => 
          array (
            'startLine' => 71,
            'endLine' => 71,
            'startTokenPos' => 86,
            'startFilePos' => 1985,
            'endTokenPos' => 86,
            'endFilePos' => 1993,
          ),
        ),
        'docComment' => '/**
 * A file left out because the bundle would go over the ceiling.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 71,
        'endLine' => 71,
        'startColumn' => 2,
        'endColumn' => 41,
      ),
      'REASON_PERMISSION' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'name' => 'REASON_PERMISSION',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'permission\'',
          'attributes' => 
          array (
            'startLine' => 78,
            'endLine' => 78,
            'startTokenPos' => 99,
            'startFilePos' => 2124,
            'endTokenPos' => 99,
            'endFilePos' => 2135,
          ),
        ),
        'docComment' => '/**
 * A file left out because the person asking may not read it.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 78,
        'endLine' => 78,
        'startColumn' => 2,
        'endColumn' => 47,
      ),
    ),
    'immediateProperties' => 
    array (
      'files' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'name' => 'files',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\FlatFileListService',
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
        'endColumn' => 45,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'objectResolver' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'name' => 'objectResolver',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\DocumentObjectServiceResolver',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 92,
        'endLine' => 92,
        'startColumn' => 3,
        'endColumn' => 64,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'userSession' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
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
        'startLine' => 93,
        'endLine' => 93,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
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
        'startLine' => 94,
        'endLine' => 94,
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
          'files' => 
          array (
            'name' => 'files',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\FlatFileListService',
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
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'objectResolver' => 
          array (
            'name' => 'objectResolver',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\DocumentObjectServiceResolver',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 92,
            'endLine' => 92,
            'startColumn' => 3,
            'endColumn' => 64,
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
            'startLine' => 93,
            'endLine' => 93,
            'startColumn' => 3,
            'endColumn' => 44,
            'parameterIndex' => 2,
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
            'startLine' => 94,
            'endLine' => 94,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 3,
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
 * @param FlatFileListService $files Every file on the object.
 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister\'s ObjectService.
 * @param IUserSession $userSession The current session.
 * @param LoggerInterface $logger Logger for diagnostics.
 *
 * @return void
 */',
        'startLine' => 90,
        'endLine' => 97,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'aliasName' => NULL,
      ),
      'preflight' => 
      array (
        'name' => 'preflight',
        'parameters' => 
        array (
          'domain' => 
          array (
            'name' => 'domain',
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
            'startLine' => 112,
            'endLine' => 112,
            'startColumn' => 28,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'ceiling' => 
          array (
            'name' => 'ceiling',
            'default' => 
            array (
              'code' => 'self::DEFAULT_CEILING',
              'attributes' => 
              array (
                'startLine' => 112,
                'endLine' => 112,
                'startTokenPos' => 173,
                'startFilePos' => 3343,
                'endTokenPos' => 175,
                'endFilePos' => 3363,
              ),
            ),
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
            'startLine' => 112,
            'endLine' => 112,
            'startColumn' => 43,
            'endColumn' => 78,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * What this bundle would be, before anybody starts building it.
 *
 * The caller shows this to the person asking. Being told afterwards that
 * eleven documents did not fit is being told too late.
 *
 * @param array<string, mixed> $domain The object, as register, schema and id.
 * @param int $ceiling The ceiling in force, in bytes.
 *
 * @return array{files: int, bytes: int, ceilingBytes: int, exceedsCeiling: bool} The preflight.
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */',
        'startLine' => 112,
        'endLine' => 126,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'aliasName' => NULL,
      ),
      'manifestFor' => 
      array (
        'name' => 'manifestFor',
        'parameters' => 
        array (
          'domain' => 
          array (
            'name' => 'domain',
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
            'startLine' => 142,
            'endLine' => 142,
            'startColumn' => 30,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'ceiling' => 
          array (
            'name' => 'ceiling',
            'default' => 
            array (
              'code' => 'self::DEFAULT_CEILING',
              'attributes' => 
              array (
                'startLine' => 142,
                'endLine' => 142,
                'startTokenPos' => 302,
                'startFilePos' => 4389,
                'endTokenPos' => 304,
                'endFilePos' => 4409,
              ),
            ),
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
            'startLine' => 142,
            'endLine' => 142,
            'startColumn' => 45,
            'endColumn' => 80,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Build the manifest of one bundle: what goes in, and what does not.
 *
 * The files are taken in name order and added until the ceiling is reached.
 * Everything after that is left out with `ceiling` as its reason, so the
 * manifest says which documents are missing rather than only that some are.
 *
 * @param array<string, mixed> $domain The object.
 * @param int $ceiling The ceiling in force, in bytes.
 *
 * @return array{included: array<int, array<string, mixed>>, excluded: array<int, array<string, mixed>>, bytes: int} The manifest.
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */',
        'startLine' => 142,
        'endLine' => 181,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'aliasName' => NULL,
      ),
      'record' => 
      array (
        'name' => 'record',
        'parameters' => 
        array (
          'domain' => 
          array (
            'name' => 'domain',
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
            'startLine' => 195,
            'endLine' => 195,
            'startColumn' => 25,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'manifest' => 
          array (
            'name' => 'manifest',
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
            'startLine' => 195,
            'endLine' => 195,
            'startColumn' => 40,
            'endColumn' => 54,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'ceiling' => 
          array (
            'name' => 'ceiling',
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
            'startLine' => 195,
            'endLine' => 195,
            'startColumn' => 57,
            'endColumn' => 68,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'fileId' => 
          array (
            'name' => 'fileId',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 195,
                'endLine' => 195,
                'startTokenPos' => 706,
                'startFilePos' => 6109,
                'endTokenPos' => 706,
                'endFilePos' => 6109,
              ),
            ),
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
            'startLine' => 195,
            'endLine' => 195,
            'startColumn' => 71,
            'endColumn' => 85,
            'parameterIndex' => 3,
            'isOptional' => true,
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
 * Record one bundle as a job, so what was handed over can be checked later.
 *
 * @param array<string, mixed> $domain The object.
 * @param array<string, mixed> $manifest The manifest of what went in and what did not.
 * @param int $ceiling The ceiling in force.
 * @param int $fileId The archive\'s own file id, when one was written.
 *
 * @return array<string, mixed> The stored job.
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */',
        'startLine' => 195,
        'endLine' => 224,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'aliasName' => NULL,
      ),
      'readable' => 
      array (
        'name' => 'readable',
        'parameters' => 
        array (
          'domain' => 
          array (
            'name' => 'domain',
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
            'startLine' => 235,
            'endLine' => 235,
            'startColumn' => 28,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The files of the object this person can actually read.
 *
 * @param array<string, mixed> $domain The object.
 *
 * @return array<int, array<string, mixed>> The rows.
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */',
        'startLine' => 235,
        'endLine' => 240,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'aliasName' => NULL,
      ),
      'unreadable' => 
      array (
        'name' => 'unreadable',
        'parameters' => 
        array (
          'domain' => 
          array (
            'name' => 'domain',
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
            'startLine' => 255,
            'endLine' => 255,
            'startColumn' => 30,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The records of the object whose file this person cannot read.
 *
 * The flat list leaves those out, which is right for a list and wrong for a
 * manifest: a bundle has to say that something was left out on permission,
 * or the reader cannot tell a complete bundle from a filtered one.
 *
 * @param array<string, mixed> $domain The object.
 *
 * @return array<int, array<string, mixed>> The records whose file did not come back.
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */',
        'startLine' => 255,
        'endLine' => 275,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'aliasName' => NULL,
      ),
      'currentUserId' => 
      array (
        'name' => 'currentUserId',
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
 * The user id of the person at the keyboard.
 *
 * @return string The user id, or an empty string when there is no session.
 *
 * @spec exclude Session accessor with no behaviour of its own.
 */',
        'startLine' => 284,
        'endLine' => 292,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
        'currentClassName' => 'OCA\\Filinq\\Service\\CaseArchiveService',
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