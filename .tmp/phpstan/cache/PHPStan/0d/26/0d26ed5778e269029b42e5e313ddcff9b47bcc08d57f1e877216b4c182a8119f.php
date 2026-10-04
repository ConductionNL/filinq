<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/BackgroundJob/DomainFolderReconciliationJob.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\BackgroundJob\DomainFolderReconciliationJob
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-f6a18aaafac11790c61fcf820e7a580f94ffab728be3b2bd1b131f6a5a3865f5',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\BackgroundJob\\DomainFolderReconciliationJob',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/BackgroundJob/DomainFolderReconciliationJob.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\BackgroundJob',
    'name' => 'OCA\\Filinq\\BackgroundJob\\DomainFolderReconciliationJob',
    'shortName' => 'DomainFolderReconciliationJob',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Runs the reconciler once a night.
 *
 * 🔑 THE JOB IS A CLOCK, NOTHING MORE. Every decision about what a drifted
 * folder means lives in DomainFolderReconciler, where it can be tested without
 * a scheduler; the job exists to say "once a night" and to make sure a throw
 * inside one night does not take the scheduler\'s whole run down with it.
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
    'startLine' => 47,
    'endLine' => 98,
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
    ),
    'immediateProperties' => 
    array (
      'reconciler' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\DomainFolderReconciliationJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\DomainFolderReconciliationJob',
        'name' => 'reconciler',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\DomainFolderReconciler',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 60,
        'endLine' => 60,
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
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\DomainFolderReconciliationJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\DomainFolderReconciliationJob',
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
        'startLine' => 61,
        'endLine' => 61,
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
            'startLine' => 59,
            'endLine' => 59,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'reconciler' => 
          array (
            'name' => 'reconciler',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\DomainFolderReconciler',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 60,
            'endLine' => 60,
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
            'startLine' => 61,
            'endLine' => 61,
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
 * @param ITimeFactory           $time       The clock the scheduler reads.
 * @param DomainFolderReconciler $reconciler Does the night\'s work.
 * @param LoggerInterface        $logger     Structured logger.
 *
 * @return void
 */',
        'startLine' => 58,
        'endLine' => 67,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\BackgroundJob',
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\DomainFolderReconciliationJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\DomainFolderReconciliationJob',
        'currentClassName' => 'OCA\\Filinq\\BackgroundJob\\DomainFolderReconciliationJob',
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
            'startLine' => 82,
            'endLine' => 82,
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
 * Reconcile every domain folder once.
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
        'startLine' => 82,
        'endLine' => 97,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'OCA\\Filinq\\BackgroundJob',
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\DomainFolderReconciliationJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\DomainFolderReconciliationJob',
        'currentClassName' => 'OCA\\Filinq\\BackgroundJob\\DomainFolderReconciliationJob',
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