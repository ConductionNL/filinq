<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/BackgroundJob/MergeDocumentsJob.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\BackgroundJob\MergeDocumentsJob
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-7938e96e634ccffdcbc5d9f947a0014e029d007a21d87a0ed46ae36b042f3b23',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\BackgroundJob\\MergeDocumentsJob',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/BackgroundJob/MergeDocumentsJob.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\BackgroundJob',
    'name' => 'OCA\\Filinq\\BackgroundJob\\MergeDocumentsJob',
    'shortName' => 'MergeDocumentsJob',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Picks up queued merges and runs them.
 *
 * @category BackgroundJob
 * @package  OCA\\Filinq\\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 54,
    'endLine' => 127,
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
      'INTERVAL' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\MergeDocumentsJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\MergeDocumentsJob',
        'name' => 'INTERVAL',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '300',
          'attributes' => 
          array (
            'startLine' => 61,
            'endLine' => 61,
            'startTokenPos' => 79,
            'startFilePos' => 1911,
            'endTokenPos' => 79,
            'endFilePos' => 1913,
          ),
        ),
        'docComment' => '/**
 * How often the queue is looked at, in seconds.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 61,
        'endLine' => 61,
        'startColumn' => 2,
        'endColumn' => 30,
      ),
    ),
    'immediateProperties' => 
    array (
      'jobs' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\MergeDocumentsJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\MergeDocumentsJob',
        'name' => 'jobs',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\MergeJobRepository',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 76,
        'endLine' => 76,
        'startColumn' => 3,
        'endColumn' => 43,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'merges' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\MergeDocumentsJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\MergeDocumentsJob',
        'name' => 'merges',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\DocumentMergeService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 77,
        'endLine' => 77,
        'startColumn' => 3,
        'endColumn' => 47,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'userSession' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\MergeDocumentsJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\MergeDocumentsJob',
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
        'startLine' => 78,
        'endLine' => 78,
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
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\MergeDocumentsJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\MergeDocumentsJob',
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
        'startLine' => 79,
        'endLine' => 79,
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
            'startLine' => 75,
            'endLine' => 75,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'jobs' => 
          array (
            'name' => 'jobs',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\MergeJobRepository',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 76,
            'endLine' => 76,
            'startColumn' => 3,
            'endColumn' => 43,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'merges' => 
          array (
            'name' => 'merges',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\DocumentMergeService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 77,
            'endLine' => 77,
            'startColumn' => 3,
            'endColumn' => 47,
            'parameterIndex' => 2,
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
            'startLine' => 78,
            'endLine' => 78,
            'startColumn' => 3,
            'endColumn' => 44,
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
            'startLine' => 79,
            'endLine' => 79,
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
 * @param ITimeFactory $time The clock the job runner uses.
 * @param MergeJobRepository $jobs The job store.
 * @param DocumentMergeService $merges The merge itself.
 * @param IUserSession $userSession The session the merge runs as.
 * @param LoggerInterface $logger Logger for diagnostics.
 *
 * @return void
 */',
        'startLine' => 74,
        'endLine' => 84,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\BackgroundJob',
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\MergeDocumentsJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\MergeDocumentsJob',
        'currentClassName' => 'OCA\\Filinq\\BackgroundJob\\MergeDocumentsJob',
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
            'startLine' => 109,
            'endLine' => 109,
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
 * Run every queued merge.
 *
 * One failing job does not stop the rest of the queue: a batch that stopped
 * at the first bad input would leave every later merge waiting with nothing
 * saying why.
 *
 * A queue that could not be READ is the other case, and it is deliberately
 * not caught. Swallowing it would end the run looking exactly like a run
 * over an empty queue, which is what hid an unreachable store here before.
 *
 * @param mixed $argument Job arguments. The job is registered bare and takes none.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter) `$argument` is Nextcloud\'s
 * TimedJob signature, not a parameter this job chose. Dropping it changes the
 * override into a different method and the job stops running.
 *
 * @return void
 *
 * @throws MergeJobStoreUnreadableException When the queue could not be read at all.
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */',
        'startLine' => 109,
        'endLine' => 126,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'OCA\\Filinq\\BackgroundJob',
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\MergeDocumentsJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\MergeDocumentsJob',
        'currentClassName' => 'OCA\\Filinq\\BackgroundJob\\MergeDocumentsJob',
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