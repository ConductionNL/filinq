<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/EventListener/EnrichmentRunner.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\EventListener\EnrichmentRunner
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-bfce0acc48c3bbb231707a3bebad5d8a30e94f930fb3b0ecf8918d10b98e387b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\EventListener\\EnrichmentRunner',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/EventListener/EnrichmentRunner.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\EventListener',
    'name' => 'OCA\\Filinq\\EventListener\\EnrichmentRunner',
    'shortName' => 'EnrichmentRunner',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Runs metadata enrichment for Filinq objects
 *
 * @category EventListener
 * @package  OCA\\Filinq\\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/metadata-enrichment/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 44,
    'endLine' => 140,
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
    ),
    'immediateMethods' => 
    array (
      'isEnrichmentEnabled' => 
      array (
        'name' => 'isEnrichmentEnabled',
        'parameters' => 
        array (
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
            'startLine' => 61,
            'endLine' => 61,
            'startColumn' => 38,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Check if enrichment is enabled based on settings
 *
 * Reads the three toggles ONLY. It must not call `getAllSettings()`: that
 * assembles the admin-settings payload, including a walk of every register
 * and every schema on the instance. This runs inside an unrelated app\'s
 * object save, and that walk issued 1,471 `SchemaMapper::find()` calls per
 * create — 96% of all schema reads during an OpenRegister create, measured
 * 2026-07-29, on a code path whose entire job is to answer a boolean.
 *
 * @param SettingsService $settingsService The settings service
 *
 * @return bool True if at least one enrichment feature is enabled
 *
 * @spec openspec/specs/metadata-enrichment/spec.md
 */',
        'startLine' => 61,
        'endLine' => 68,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\EnrichmentRunner',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\EnrichmentRunner',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\EnrichmentRunner',
        'aliasName' => NULL,
      ),
      'enrichObject' => 
      array (
        'name' => 'enrichObject',
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
            'startLine' => 84,
            'endLine' => 84,
            'startColumn' => 3,
            'endColumn' => 15,
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
            'startLine' => 85,
            'endLine' => 85,
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
            'startLine' => 86,
            'endLine' => 86,
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
            'startLine' => 87,
            'endLine' => 87,
            'startColumn' => 3,
            'endColumn' => 25,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'logContext' => 
          array (
            'name' => 'logContext',
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
            'startLine' => 88,
            'endLine' => 88,
            'startColumn' => 3,
            'endColumn' => 20,
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
 * Enrich an object with metadata if enrichment is enabled
 *
 * @param mixed $object The OpenRegister object
 * @param MetadataService $metadataService The metadata service
 * @param SettingsService $settingsService The settings service
 * @param LoggerInterface $logger The logger instance
 * @param string $logContext Context string for logging
 *
 * @return void
 *
 * @spec openspec/specs/metadata-enrichment/spec.md
 */',
        'startLine' => 83,
        'endLine' => 139,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\EnrichmentRunner',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\EnrichmentRunner',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\EnrichmentRunner',
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