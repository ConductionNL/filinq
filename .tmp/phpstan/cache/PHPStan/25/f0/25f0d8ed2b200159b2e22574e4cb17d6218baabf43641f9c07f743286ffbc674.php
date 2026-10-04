<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/EventListener/DossierCheckedOnListener.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\EventListener\DossierCheckedOnListener
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-00a9a23c02348441bb999739226a10f088e3c7f28cb55404cb4344f8a5302fcf',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/EventListener/DossierCheckedOnListener.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\EventListener',
    'name' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
    'shortName' => 'DossierCheckedOnListener',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Event listener that auto-regenerates the dossier grondslagen summary on checkedOn update
 *
 * @category EventListener
 * @package  OCA\\Filinq\\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-7
 *
 * @psalm-suppress MismatchingDocblockReturnType
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 52,
    'endLine' => 296,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'OCP\\EventDispatcher\\IEventListener',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'REGISTER' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'name' => 'REGISTER',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq\'',
          'attributes' => 
          array (
            'startLine' => 59,
            'endLine' => 59,
            'startTokenPos' => 64,
            'startFilePos' => 1826,
            'endTokenPos' => 64,
            'endFilePos' => 1833,
          ),
        ),
        'docComment' => '/**
 * Filinq register slug for dossier objects.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 59,
        'endLine' => 59,
        'startColumn' => 2,
        'endColumn' => 35,
      ),
      'DOSSIER_SCHEMA' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'name' => 'DOSSIER_SCHEMA',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'dossier\'',
          'attributes' => 
          array (
            'startLine' => 66,
            'endLine' => 66,
            'startTokenPos' => 77,
            'startFilePos' => 1924,
            'endTokenPos' => 77,
            'endFilePos' => 1932,
          ),
        ),
        'docComment' => '/**
 * Dossier schema slug.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 66,
        'endLine' => 66,
        'startColumn' => 2,
        'endColumn' => 42,
      ),
    ),
    'immediateProperties' => 
    array (
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
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
      'summaryService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'name' => 'summaryService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
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
        'endColumn' => 59,
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
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'summaryService' => 
          array (
            'name' => 'summaryService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
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
            'endColumn' => 59,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor for DossierCheckedOnListener.
 *
 * @param LoggerInterface $logger Logger for diagnostics
 * @param LegalBasesSummaryService $summaryService Dossier grondslagen summary renderer
 *
 * @return void
 *
 * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-7
 */',
        'startLine' => 78,
        'endLine' => 83,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'aliasName' => NULL,
      ),
      'handle' => 
      array (
        'name' => 'handle',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\EventDispatcher\\Event',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 25,
            'endColumn' => 36,
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
 * Handle an ObjectUpdatedEvent and trigger auto-regen when applicable.
 *
 * Regen fires iff:
 * 1. The updated object is a dossier (register=filinq, schema=dossier)
 * 2. `checkedOn` has changed between the old and new object data
 * 3. `configuration.grondslagen.autoRegenOnReview` is true (or absent, defaults to true)
 *
 * @param Event $event The dispatched event
 *
 * @return void
 *
 * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-7
 */',
        'startLine' => 99,
        'endLine' => 110,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'aliasName' => NULL,
      ),
      'processObjectUpdatedEvent' => 
      array (
        'name' => 'processObjectUpdatedEvent',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\OpenRegister\\Event\\ObjectUpdatedEvent',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 121,
            'endLine' => 121,
            'startColumn' => 45,
            'endColumn' => 69,
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
 * Process the ObjectUpdatedEvent for dossier checkedOn changes.
 *
 * @param ObjectUpdatedEvent $event The update event
 *
 * @return void
 *
 * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-7
 */',
        'startLine' => 121,
        'endLine' => 172,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'aliasName' => NULL,
      ),
      'isDossierObject' => 
      array (
        'name' => 'isDossierObject',
        'parameters' => 
        array (
          'object' => 
          array (
            'name' => 'object',
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
            'startLine' => 183,
            'endLine' => 183,
            'startColumn' => 35,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine whether the updated object is a dossier.
 *
 * Checks the object\'s register and schema slugs against Filinq constants.
 *
 * @param mixed $object The updated ObjectEntity
 *
 * @return bool True when this is a dossier object
 */',
        'startLine' => 183,
        'endLine' => 202,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'aliasName' => NULL,
      ),
      'matchesSlug' => 
      array (
        'name' => 'matchesSlug',
        'parameters' => 
        array (
          'candidate' => 
          array (
            'name' => 'candidate',
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
            'startLine' => 215,
            'endLine' => 215,
            'startColumn' => 31,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expected' => 
          array (
            'name' => 'expected',
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
            'startLine' => 215,
            'endLine' => 215,
            'startColumn' => 49,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine whether a register/schema reference matches an expected slug.
 *
 * Accepts both a bare slug string and a Register/Schema entity exposing
 * `getSlug()`, so numeric/UUID-keyed references resolve conservatively.
 *
 * @param mixed $candidate The register or schema reference from the object
 * @param string $expected The expected slug
 *
 * @return bool True when the reference matches the expected slug
 */',
        'startLine' => 215,
        'endLine' => 229,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'aliasName' => NULL,
      ),
      'hasCheckedOnChanged' => 
      array (
        'name' => 'hasCheckedOnChanged',
        'parameters' => 
        array (
          'newData' => 
          array (
            'name' => 'newData',
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
            'startLine' => 239,
            'endLine' => 239,
            'startColumn' => 39,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'oldData' => 
          array (
            'name' => 'oldData',
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
            'startLine' => 239,
            'endLine' => 239,
            'startColumn' => 55,
            'endColumn' => 68,
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
 * Determine whether `checkedOn` has changed between old and new object data.
 *
 * @param array<string, mixed> $newData New object data
 * @param array<string, mixed> $oldData Old object data (may be empty)
 *
 * @return bool True when checkedOn has changed or was added
 */',
        'startLine' => 239,
        'endLine' => 244,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'aliasName' => NULL,
      ),
      'isAutoRegenEnabled' => 
      array (
        'name' => 'isAutoRegenEnabled',
        'parameters' => 
        array (
          'data' => 
          array (
            'name' => 'data',
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
            'startColumn' => 38,
            'endColumn' => 48,
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
 * Read `configuration.grondslagen.autoRegenOnReview` from object data.
 *
 * Defaults to true when the field is absent.
 *
 * @param array<string, mixed> $data Object data
 *
 * @return bool True when auto-regen is enabled
 */',
        'startLine' => 255,
        'endLine' => 271,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'aliasName' => NULL,
      ),
      'logError' => 
      array (
        'name' => 'logError',
        'parameters' => 
        array (
          'exception' => 
          array (
            'name' => 'exception',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Throwable',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 282,
            'endLine' => 282,
            'startColumn' => 28,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'context' => 
          array (
            'name' => 'context',
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
            'startLine' => 282,
            'endLine' => 282,
            'startColumn' => 51,
            'endColumn' => 65,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'extra' => 
          array (
            'name' => 'extra',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 282,
                'endLine' => 282,
                'startTokenPos' => 1036,
                'startFilePos' => 8168,
                'endTokenPos' => 1037,
                'endFilePos' => 8169,
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
            'startLine' => 282,
            'endLine' => 282,
            'startColumn' => 68,
            'endColumn' => 84,
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
 * Log an error from the listener without throwing.
 *
 * @param \\Throwable $exception The caught exception
 * @param string $context Human-readable context label
 * @param array<string, mixed> $extra Optional additional context
 *
 * @return void
 */',
        'startLine' => 282,
        'endLine' => 295,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\DossierCheckedOnListener',
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