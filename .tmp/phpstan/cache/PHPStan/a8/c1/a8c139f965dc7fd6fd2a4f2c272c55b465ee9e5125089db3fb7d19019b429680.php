<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DomainFolderReconciler.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\DomainFolderReconciler
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-89a87c68fbcb47e42bdf9e6fab8b8ba313da2b7438a4a18848dbdaab24172ea1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\DomainFolderReconciler',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DomainFolderReconciler.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\DomainFolderReconciler',
    'shortName' => 'DomainFolderReconciler',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Reconciles every domain folder and reports both halves of the night.
 *
 * 🔴 THE REPORT HAS TWO HALVES AND NEITHER MAY BE DROPPED. REQ-CDF-02 asks the
 * nightly job to report the drift it corrected AND the drift it could not, and
 * a report that carries only the first is the one somebody reads as a clean
 * night. So `corrected` and `refused` are separate lists, both always present,
 * and the summary counts them apart.
 *
 * 🔴 A DOMAIN THAT THREW IS REFUSED, NOT SKIPPED. A reconciler that swallows a
 * throw and moves to the next domain reports a shorter run rather than a
 * failure, and the folder it never reached looks exactly like a folder that was
 * already right.
 *
 * 🔑 THE JOB REPORTS, IT DOES NOT DECIDE. Nothing here retries, backs off or
 * escalates: what to do about a folder that refuses a revoke for the fourth
 * night running is a decision a person makes with the report in front of them.
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
    'startLine' => 54,
    'endLine' => 257,
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
      'directory' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainFolderReconciler',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainFolderReconciler',
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
        'startLine' => 66,
        'endLine' => 66,
        'startColumn' => 3,
        'endColumn' => 45,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'folderService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainFolderReconciler',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainFolderReconciler',
        'name' => 'folderService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\DomainFolderService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 67,
        'endLine' => 67,
        'startColumn' => 3,
        'endColumn' => 53,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainFolderReconciler',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainFolderReconciler',
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
        'startLine' => 68,
        'endLine' => 68,
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
            'startLine' => 66,
            'endLine' => 66,
            'startColumn' => 3,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'folderService' => 
          array (
            'name' => 'folderService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\DomainFolderService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 3,
            'endColumn' => 53,
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
            'startLine' => 68,
            'endLine' => 68,
            'startColumn' => 3,
            'endColumn' => 42,
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
 * Collaborators.
 *
 * @param DomainDirectory     $directory     Where the domains come from.
 * @param DomainFolderService $folderService Reconciles one folder.
 * @param LoggerInterface     $logger        Structured logger.
 *
 * @return void
 */',
        'startLine' => 65,
        'endLine' => 71,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainFolderReconciler',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainFolderReconciler',
        'currentClassName' => 'OCA\\Filinq\\Service\\DomainFolderReconciler',
        'aliasName' => NULL,
      ),
      'run' => 
      array (
        'name' => 'run',
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
 * Reconcile every domain folder once.
 *
 * @return array<string, mixed> The report: what was corrected, what was refused, what was pinned, what was already right.
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
        'startLine' => 80,
        'endLine' => 177,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainFolderReconciler',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainFolderReconciler',
        'currentClassName' => 'OCA\\Filinq\\Service\\DomainFolderReconciler',
        'aliasName' => NULL,
      ),
      'announce' => 
      array (
        'name' => 'announce',
        'parameters' => 
        array (
          'report' => 
          array (
            'name' => 'report',
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
            'startLine' => 188,
            'endLine' => 188,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Put the night in the log, loudly for the half that failed.
 *
 * @param array<string, mixed> $report The report.
 *
 * @return void
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
        'startLine' => 188,
        'endLine' => 214,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainFolderReconciler',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainFolderReconciler',
        'currentClassName' => 'OCA\\Filinq\\Service\\DomainFolderReconciler',
        'aliasName' => NULL,
      ),
      'report' => 
      array (
        'name' => 'report',
        'parameters' => 
        array (
          'skipped' => 
          array (
            'name' => 'skipped',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 237,
                'endLine' => 237,
                'startTokenPos' => 955,
                'startFilePos' => 8086,
                'endTokenPos' => 955,
                'endFilePos' => 8090,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 237,
            'endLine' => 237,
            'startColumn' => 3,
            'endColumn' => 23,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'reason' => 
          array (
            'name' => 'reason',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 238,
                'endLine' => 238,
                'startTokenPos' => 964,
                'startFilePos' => 8112,
                'endTokenPos' => 964,
                'endFilePos' => 8113,
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
            'startLine' => 238,
            'endLine' => 238,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'corrected' => 
          array (
            'name' => 'corrected',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 239,
                'endLine' => 239,
                'startTokenPos' => 973,
                'startFilePos' => 8137,
                'endTokenPos' => 974,
                'endFilePos' => 8138,
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
            'startLine' => 239,
            'endLine' => 239,
            'startColumn' => 3,
            'endColumn' => 23,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'refused' => 
          array (
            'name' => 'refused',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 240,
                'endLine' => 240,
                'startTokenPos' => 983,
                'startFilePos' => 8160,
                'endTokenPos' => 984,
                'endFilePos' => 8161,
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
            'startLine' => 240,
            'endLine' => 240,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'pinned' => 
          array (
            'name' => 'pinned',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 241,
                'endLine' => 241,
                'startTokenPos' => 993,
                'startFilePos' => 8182,
                'endTokenPos' => 994,
                'endFilePos' => 8183,
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
            'startLine' => 241,
            'endLine' => 241,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 4,
            'isOptional' => true,
          ),
          'inStep' => 
          array (
            'name' => 'inStep',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 242,
                'endLine' => 242,
                'startTokenPos' => 1003,
                'startFilePos' => 8202,
                'endTokenPos' => 1003,
                'endFilePos' => 8202,
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
            'startLine' => 242,
            'endLine' => 242,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 5,
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
 * One report, in the one shape every caller reads.
 *
 * @param bool                                $skipped   Whether the run never started.
 * @param string                              $reason    Why it never started.
 * @param array<int, array<string, mixed>>    $corrected Folders brought back into step.
 * @param array<int, array<string, mixed>>    $refused   Folders that could not be, with the reason.
 * @param array<int, array<string, mixed>>    $pinned    Folders left alone on purpose, with the reason.
 * @param int                                 $inStep    How many needed nothing.
 *
 * @return array<string, mixed> The report.
 *
 * @spec exclude Shape helper; the behaviour is in run().
 *
 * @SuppressWarnings(PHPMD.BooleanArgumentFlag) `$skipped` names the one report
 * that has no folders in it, because the run never started. It is a private
 * shape helper with one caller per branch, not a switch on behaviour, and a
 * second method would duplicate the eight-key shape this exists to keep in
 * one place.
 */',
        'startLine' => 236,
        'endLine' => 256,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainFolderReconciler',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainFolderReconciler',
        'currentClassName' => 'OCA\\Filinq\\Service\\DomainFolderReconciler',
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