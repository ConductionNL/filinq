<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/BackgroundJob/BatchCorrespondenceJob.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\BackgroundJob\BatchCorrespondenceJob
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-b51293145aa317e7a87b5b78807b0f58ea6478e2c86b96b1ae79a181b6d60330',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\BackgroundJob\\BatchCorrespondenceJob',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/BackgroundJob/BatchCorrespondenceJob.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\BackgroundJob',
    'name' => 'OCA\\Filinq\\BackgroundJob\\BatchCorrespondenceJob',
    'shortName' => 'BatchCorrespondenceJob',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Queued job for batch correspondence generation
 *
 * @category BackgroundJob
 * @package  OCA\\Filinq\\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-2
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 45,
    'endLine' => 229,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'OCP\\BackgroundJob\\QueuedJob',
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
      'corrSvc' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\BatchCorrespondenceJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\BatchCorrespondenceJob',
        'name' => 'corrSvc',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\CorrespondenceService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 3,
        'endColumn' => 49,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\BatchCorrespondenceJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\BatchCorrespondenceJob',
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
        'startLine' => 58,
        'endLine' => 58,
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
            'startLine' => 56,
            'endLine' => 56,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'corrSvc' => 
          array (
            'name' => 'corrSvc',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\CorrespondenceService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 57,
            'endLine' => 57,
            'startColumn' => 3,
            'endColumn' => 49,
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
            'startLine' => 58,
            'endLine' => 58,
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
 * Constructor for BatchCorrespondenceJob
 *
 * @param ITimeFactory $time Time factory
 * @param CorrespondenceService $corrSvc Correspondence generation service
 * @param LoggerInterface $logger Logger for error reporting
 *
 * @return void
 */',
        'startLine' => 55,
        'endLine' => 62,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\BackgroundJob',
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\BatchCorrespondenceJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\BatchCorrespondenceJob',
        'currentClassName' => 'OCA\\Filinq\\BackgroundJob\\BatchCorrespondenceJob',
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
            'startLine' => 78,
            'endLine' => 78,
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
 * Run the batch correspondence generation
 *
 * Processes each recipient individually. Updates job status after each
 * recipient so progress can be tracked. Individual failures do not
 * abort the batch.
 *
 * @param array $argument Job arguments containing jobId, templateId,
 *                        recipientIds, and options
 *
 * @return void
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
 */',
        'startLine' => 78,
        'endLine' => 104,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'OCA\\Filinq\\BackgroundJob',
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\BatchCorrespondenceJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\BatchCorrespondenceJob',
        'currentClassName' => 'OCA\\Filinq\\BackgroundJob\\BatchCorrespondenceJob',
        'aliasName' => NULL,
      ),
      'initializeJobStatus' => 
      array (
        'name' => 'initializeJobStatus',
        'parameters' => 
        array (
          'jobId' => 
          array (
            'name' => 'jobId',
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
            'startLine' => 117,
            'endLine' => 117,
            'startColumn' => 39,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'total' => 
          array (
            'name' => 'total',
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
            'startLine' => 117,
            'endLine' => 117,
            'startColumn' => 54,
            'endColumn' => 63,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'ownerUserId' => 
          array (
            'name' => 'ownerUserId',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 117,
                'endLine' => 117,
                'startTokenPos' => 349,
                'startFilePos' => 3736,
                'endTokenPos' => 349,
                'endFilePos' => 3737,
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
            'startLine' => 117,
            'endLine' => 117,
            'startColumn' => 66,
            'endColumn' => 89,
            'parameterIndex' => 2,
            'isOptional' => true,
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
 * Initialize job status to processing
 *
 * @param string $jobId The job UUID
 * @param int $total Total number of recipients
 * @param string $ownerUserId UID of the user who dispatched the job (SB1 fix)
 *
 * @return void
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
 */',
        'startLine' => 117,
        'endLine' => 130,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\BackgroundJob',
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\BatchCorrespondenceJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\BatchCorrespondenceJob',
        'currentClassName' => 'OCA\\Filinq\\BackgroundJob\\BatchCorrespondenceJob',
        'aliasName' => NULL,
      ),
      'processRecipients' => 
      array (
        'name' => 'processRecipients',
        'parameters' => 
        array (
          'jobId' => 
          array (
            'name' => 'jobId',
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
            'startColumn' => 3,
            'endColumn' => 15,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'templateId' => 
          array (
            'name' => 'templateId',
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
            'startLine' => 147,
            'endLine' => 147,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'recipientIds' => 
          array (
            'name' => 'recipientIds',
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
            'startLine' => 148,
            'endLine' => 148,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
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
            'startLine' => 149,
            'endLine' => 149,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'ownerUserId' => 
          array (
            'name' => 'ownerUserId',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 150,
                'endLine' => 150,
                'startTokenPos' => 461,
                'startFilePos' => 4600,
                'endTokenPos' => 461,
                'endFilePos' => 4601,
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
            'startLine' => 150,
            'endLine' => 150,
            'startColumn' => 3,
            'endColumn' => 26,
            'parameterIndex' => 4,
            'isOptional' => true,
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
 * Process all recipients in the batch
 *
 * @param string $jobId The job UUID
 * @param string $templateId The template UUID
 * @param array $recipientIds Array of recipient UUIDs
 * @param array $options Generation options
 * @param string $ownerUserId UID of the user who dispatched the job (SB1 fix)
 *
 * @return void
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
 */',
        'startLine' => 145,
        'endLine' => 228,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\BackgroundJob',
        'declaringClassName' => 'OCA\\Filinq\\BackgroundJob\\BatchCorrespondenceJob',
        'implementingClassName' => 'OCA\\Filinq\\BackgroundJob\\BatchCorrespondenceJob',
        'currentClassName' => 'OCA\\Filinq\\BackgroundJob\\BatchCorrespondenceJob',
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