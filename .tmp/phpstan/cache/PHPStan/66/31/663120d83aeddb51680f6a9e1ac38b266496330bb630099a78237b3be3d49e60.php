<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/EventListener/FilinqEventHandler.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\EventListener\FilinqEventHandler
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-f3177e7db56423c1015f75688ec7bec9f3bde9e6bfd35d58508a50d643c16971',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/EventListener/FilinqEventHandler.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\EventListener',
    'name' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
    'shortName' => 'FilinqEventHandler',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Handles OpenRegister object events for Filinq metadata enrichment
 *
 * @category EventListener
 * @package  OCA\\Filinq\\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 47,
    'endLine' => 506,
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
      'container' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'name' => 'container',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Psr\\Container\\ContainerInterface',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 59,
        'endLine' => 59,
        'startColumn' => 3,
        'endColumn' => 48,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'enrichmentRunner' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'name' => 'enrichmentRunner',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\EventListener\\EnrichmentRunner',
            'isIdentifier' => false,
          ),
        ),
        'default' => 
        array (
          'code' => 'new \\OCA\\Filinq\\EventListener\\EnrichmentRunner()',
          'attributes' => 
          array (
            'startLine' => 60,
            'endLine' => 60,
            'startTokenPos' => 98,
            'startFilePos' => 2051,
            'endTokenPos' => 102,
            'endFilePos' => 2072,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 60,
        'endLine' => 60,
        'startColumn' => 3,
        'endColumn' => 78,
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
          'container' => 
          array (
            'name' => 'container',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Psr\\Container\\ContainerInterface',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 59,
            'endLine' => 59,
            'startColumn' => 3,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'enrichmentRunner' => 
          array (
            'name' => 'enrichmentRunner',
            'default' => 
            array (
              'code' => 'new \\OCA\\Filinq\\EventListener\\EnrichmentRunner()',
              'attributes' => 
              array (
                'startLine' => 60,
                'endLine' => 60,
                'startTokenPos' => 98,
                'startFilePos' => 2051,
                'endTokenPos' => 102,
                'endFilePos' => 2072,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\EventListener\\EnrichmentRunner',
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
            'endColumn' => 78,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor for FilinqEventHandler
 *
 * @param ContainerInterface $container App container the legal-bases summary service is resolved from.
 * @param EnrichmentRunner $enrichmentRunner The enrichment runner. Defaults to a
 *                                           fresh stateless instance; injectable
 *                                           so tests can substitute a double.
 *
 * @return void
 */',
        'startLine' => 58,
        'endLine' => 63,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'aliasName' => NULL,
      ),
      'handleObjectCreated' => 
      array (
        'name' => 'handleObjectCreated',
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
                'name' => 'OCA\\OpenRegister\\Event\\ObjectCreatedEvent',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 81,
            'endLine' => 81,
            'startColumn' => 3,
            'endColumn' => 27,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'metadataService' => 
          array (
            'name' => 'metadataService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\MetadataService',
                'isIdentifier' => false,
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
            'startColumn' => 3,
            'endColumn' => 34,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'settingsService' => 
          array (
            'name' => 'settingsService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\SettingsService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 3,
            'endColumn' => 34,
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 84,
            'endLine' => 84,
            'startColumn' => 3,
            'endColumn' => 25,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'retroactive' => 
          array (
            'name' => 'retroactive',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 85,
            'endLine' => 85,
            'startColumn' => 3,
            'endColumn' => 39,
            'parameterIndex' => 4,
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
 * Handles object creation events
 *
 * @param ObjectCreatedEvent $event The creation event
 * @param MetadataService $metadataService The metadata service
 * @param SettingsService $settingsService The settings service
 * @param LoggerInterface $logger The logger instance
 * @param PolicyRetroactiveService $retroactive Retroactive policy applicator
 *                                              (injected here, not via
 *                                              service-locator).
 *
 * @return void
 *
 * @spec openspec/specs/metadata-enrichment/spec.md
 */',
        'startLine' => 80,
        'endLine' => 108,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'aliasName' => NULL,
      ),
      'handleObjectUpdated' => 
      array (
        'name' => 'handleObjectUpdated',
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
            'startLine' => 124,
            'endLine' => 124,
            'startColumn' => 3,
            'endColumn' => 27,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'metadataService' => 
          array (
            'name' => 'metadataService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\MetadataService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 125,
            'endLine' => 125,
            'startColumn' => 3,
            'endColumn' => 34,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'settingsService' => 
          array (
            'name' => 'settingsService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\SettingsService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 126,
            'endLine' => 126,
            'startColumn' => 3,
            'endColumn' => 34,
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 127,
            'endLine' => 127,
            'startColumn' => 3,
            'endColumn' => 25,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'retroactive' => 
          array (
            'name' => 'retroactive',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 128,
            'endLine' => 128,
            'startColumn' => 3,
            'endColumn' => 39,
            'parameterIndex' => 4,
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
 * Handles object update events
 *
 * @param ObjectUpdatedEvent $event The update event
 * @param MetadataService $metadataService The metadata service
 * @param SettingsService $settingsService The settings service
 * @param LoggerInterface $logger The logger instance
 * @param PolicyRetroactiveService $retroactive Retroactive policy applicator.
 *
 * @return void
 *
 * @spec openspec/specs/metadata-enrichment/spec.md
 */',
        'startLine' => 123,
        'endLine' => 178,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'aliasName' => NULL,
      ),
      'maybeRegenerateGrondslagenSummary' => 
      array (
        'name' => 'maybeRegenerateGrondslagenSummary',
        'parameters' => 
        array (
          'objectData' => 
          array (
            'name' => 'objectData',
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
            'startLine' => 209,
            'endLine' => 209,
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'oldObjectData' => 
          array (
            'name' => 'oldObjectData',
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
            'startLine' => 210,
            'endLine' => 210,
            'startColumn' => 3,
            'endColumn' => 22,
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 211,
            'endLine' => 211,
            'startColumn' => 3,
            'endColumn' => 25,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * On a dossier `checkedOn` change, fire the per-dossier grondslagen summary regen.
 *
 * Detection is payload-shape based (no schema-id lookup): an object is
 * treated as a dossier when it carries a `checkedOn` field at the top
 * level alongside `name` AND `bases` (the canonical dossier signature
 * per `add-dossier-schema`). This avoids a round-trip to OR\'s
 * SchemaMapper for every ObjectUpdatedEvent dispatched.
 *
 * The regen runs only when:
 *   - `configuration.grondslagen.autoRegenOnReview` is missing OR true.
 *   - `checkedOn` actually changed between old and new payloads
 *     (creating a checkedOn for the first time also counts).
 *
 * Regen failure is logged at warning level; the dossier update itself
 * is not affected. The dossier\'s `configuration.grondslagen.{fileId,
 * lastGeneratedAt}` is updated by the summary service after a
 * successful render.
 *
 * @param array<string, mixed> $objectData The new dossier object data.
 * @param array<string, mixed> $oldObjectData The previous dossier object data
 *                                            (empty when the event lacks a previous state).
 * @param LoggerInterface $logger Structured logger.
 *
 * @return void
 *
 * @spec openspec/specs/metadata-enrichment/spec.md
 */',
        'startLine' => 208,
        'endLine' => 241,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'aliasName' => NULL,
      ),
      'shouldRegenerateGrondslagenSummary' => 
      array (
        'name' => 'shouldRegenerateGrondslagenSummary',
        'parameters' => 
        array (
          'objectData' => 
          array (
            'name' => 'objectData',
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
            'startLine' => 256,
            'endLine' => 256,
            'startColumn' => 54,
            'endColumn' => 70,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'oldObjectData' => 
          array (
            'name' => 'oldObjectData',
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
            'startLine' => 256,
            'endLine' => 256,
            'startColumn' => 73,
            'endColumn' => 92,
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
 * Decide whether a dossier update warrants a grondslagen-summary regen.
 *
 * True only when the payload looks like a dossier, `checkedOn` actually
 * changed to a non-empty value, and auto-regen is not opted out of.
 *
 * @param array<string, mixed> $objectData The new dossier object data.
 * @param array<string, mixed> $oldObjectData The previous dossier object data.
 *
 * @return bool True when the summary should be regenerated.
 *
 * @spec openspec/specs/metadata-enrichment/spec.md
 */',
        'startLine' => 256,
        'endLine' => 268,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'aliasName' => NULL,
      ),
      'isGrondslagenAutoRegenEnabled' => 
      array (
        'name' => 'isGrondslagenAutoRegenEnabled',
        'parameters' => 
        array (
          'objectData' => 
          array (
            'name' => 'objectData',
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
            'startLine' => 279,
            'endLine' => 279,
            'startColumn' => 49,
            'endColumn' => 65,
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
 * Read `configuration.grondslagen.autoRegenOnReview`, defaulting to enabled.
 *
 * @param array<string, mixed> $objectData The dossier object data.
 *
 * @return bool True when auto-regen is enabled (the default).
 *
 * @spec openspec/specs/metadata-enrichment/spec.md
 */',
        'startLine' => 279,
        'endLine' => 287,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'aliasName' => NULL,
      ),
      'looksLikeDossier' => 
      array (
        'name' => 'looksLikeDossier',
        'parameters' => 
        array (
          'objectData' => 
          array (
            'name' => 'objectData',
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
            'startLine' => 303,
            'endLine' => 303,
            'startColumn' => 36,
            'endColumn' => 52,
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
 * Shape-detect whether an object looks like a `dossier` register record.
 *
 * Cheap heuristic: the schema-id lookup would round-trip to OR per
 * event; instead we check for the canonical dossier fields (`name` +
 * `bases` + presence of either a current `checkedOn` field on the
 * object, OR the field\'s slot existing on the object\'s `@self` /
 * configuration). This matches every real dossier and is unlikely to
 * collide with other schemas in this register.
 *
 * @param array<string, mixed> $objectData The event\'s object payload.
 *
 * @return bool
 */',
        'startLine' => 303,
        'endLine' => 309,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'aliasName' => NULL,
      ),
      'handleObjectDeleted' => 
      array (
        'name' => 'handleObjectDeleted',
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
                'name' => 'OCA\\OpenRegister\\Event\\ObjectDeletedEvent',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 321,
            'endLine' => 321,
            'startColumn' => 3,
            'endColumn' => 27,
            'parameterIndex' => 0,
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 322,
            'endLine' => 322,
            'startColumn' => 3,
            'endColumn' => 25,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'retroactive' => 
          array (
            'name' => 'retroactive',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 323,
            'endLine' => 323,
            'startColumn' => 3,
            'endColumn' => 39,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Handles object deletion events
 *
 * @param ObjectDeletedEvent $event The deletion event
 * @param LoggerInterface $logger The logger instance
 * @param PolicyRetroactiveService $retroactive Retroactive policy applicator.
 *
 * @return void
 */',
        'startLine' => 320,
        'endLine' => 347,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'aliasName' => NULL,
      ),
      'dispatchPolicyRetroactive' => 
      array (
        'name' => 'dispatchPolicyRetroactive',
        'parameters' => 
        array (
          'objectData' => 
          array (
            'name' => 'objectData',
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
            'startLine' => 375,
            'endLine' => 375,
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 0,
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 376,
            'endLine' => 376,
            'startColumn' => 3,
            'endColumn' => 25,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'reason' => 
          array (
            'name' => 'reason',
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
            'startLine' => 377,
            'endLine' => 377,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'retroactive' => 
          array (
            'name' => 'retroactive',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\PolicyRetroactiveService',
                'isIdentifier' => false,
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
            'startColumn' => 3,
            'endColumn' => 39,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Route policy-surface mutations to the retroactive layer.
 *
 * Identifies the changed object as a `publicationProhibition` or a
 * `publicationConsent` (scope=entity / scope=document) using payload-shape
 * heuristics rather than schema-ID lookups, since:
 *   - the event\'s `$object->getSchema()` returns a schema *ID* (numeric),
 *     not a slug, so we would need an extra DB round-trip per event;
 *   - the discriminating fields (`reason` + `legalAuthority` for
 *     prohibitions; `scope` for consents) are stable across versions.
 *
 * For non-policy events this is a cheap no-op and returns immediately.
 *
 * @param array<string, mixed> $objectData The changed object\'s payload.
 * @param LoggerInterface $logger Structured log sink.
 * @param string $reason \'created\' | \'updated\' | \'deleted\'.
 * @param PolicyRetroactiveService $retroactive Retroactive policy applicator
 *                                              (constructor/method-injected at
 *                                              the calling public handler).
 *
 * @return void
 *
 * @psalm-suppress UnusedParam Both $logger and $reason are used in the conditional branches —
 *                             Psalm misreads the path coverage through the try/catch.
 */',
        'startLine' => 374,
        'endLine' => 421,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'aliasName' => NULL,
      ),
      'detectPolicyShape' => 
      array (
        'name' => 'detectPolicyShape',
        'parameters' => 
        array (
          'objectData' => 
          array (
            'name' => 'objectData',
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
            'startLine' => 430,
            'endLine' => 430,
            'startColumn' => 37,
            'endColumn' => 53,
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
                  'name' => 'string',
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
 * Classify a payload as a policy record by structural signature.
 *
 * @param array<string, mixed> $objectData The changed object\'s payload.
 *
 * @return string|null \'prohibition\', \'standing_consent\', \'document_consent\', or null.
 */',
        'startLine' => 430,
        'endLine' => 440,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'aliasName' => NULL,
      ),
      'looksLikeProhibition' => 
      array (
        'name' => 'looksLikeProhibition',
        'parameters' => 
        array (
          'objectData' => 
          array (
            'name' => 'objectData',
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
            'startLine' => 453,
            'endLine' => 453,
            'startColumn' => 40,
            'endColumn' => 56,
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
 * Structural test for a `publicationProhibition` payload.
 *
 * A prohibition is identified by the combination of `matchRules` plus
 * `reason` or `legalAuthority`, and the absence of a `consentStatus` field
 * (consent records always carry it).
 *
 * @param array<string, mixed> $objectData The changed object\'s payload.
 *
 * @return bool True when the payload is a prohibition record.
 */',
        'startLine' => 453,
        'endLine' => 468,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'aliasName' => NULL,
      ),
      'detectConsentScope' => 
      array (
        'name' => 'detectConsentScope',
        'parameters' => 
        array (
          'objectData' => 
          array (
            'name' => 'objectData',
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
            'startLine' => 477,
            'endLine' => 477,
            'startColumn' => 38,
            'endColumn' => 54,
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
 * Classify a consent payload by its `scope` field.
 *
 * @param array<string, mixed> $objectData The changed object\'s payload.
 *
 * @return string \'standing_consent\' for scope=entity, otherwise \'document_consent\'.
 */',
        'startLine' => 477,
        'endLine' => 484,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'aliasName' => NULL,
      ),
      'hasContentChanged' => 
      array (
        'name' => 'hasContentChanged',
        'parameters' => 
        array (
          'objectData' => 
          array (
            'name' => 'objectData',
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
            'startLine' => 496,
            'endLine' => 496,
            'startColumn' => 37,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'oldObjectData' => 
          array (
            'name' => 'oldObjectData',
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
            'startLine' => 496,
            'endLine' => 496,
            'startColumn' => 56,
            'endColumn' => 75,
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
 * Check if content fields have changed between old and new object data
 *
 * @param array<string, mixed> $objectData The new object data
 * @param array<string, mixed> $oldObjectData The old object data
 *
 * @return bool True if content has changed
 *
 * @spec openspec/specs/metadata-enrichment/spec.md
 */',
        'startLine' => 496,
        'endLine' => 505,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\FilinqEventHandler',
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