<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Conversion/OutputLayoutResolver.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Conversion\OutputLayoutResolver
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-4d1c785d5f298e1e7cbe9c0658e271ea2cad6a8531985408bfba9b5fec88ea1a',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Conversion/OutputLayoutResolver.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Conversion',
    'name' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
    'shortName' => 'OutputLayoutResolver',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Computes batch-output destinations for the anonymisation pipeline.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 57,
    'endLine' => 216,
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
      'SUBFOLDER_CONFIG_KEY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'name' => 'SUBFOLDER_CONFIG_KEY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'anonymisation.output_subfolder_name\'',
          'attributes' => 
          array (
            'startLine' => 62,
            'endLine' => 62,
            'startTokenPos' => 45,
            'startFilePos' => 2341,
            'endTokenPos' => 45,
            'endFilePos' => 2377,
          ),
        ),
        'docComment' => '/**
 * App-config key for the configurable subfolder name.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 2,
        'endColumn' => 75,
      ),
      'DEFAULT_SUBFOLDER_NAME' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'name' => 'DEFAULT_SUBFOLDER_NAME',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'anonymised\'',
          'attributes' => 
          array (
            'startLine' => 67,
            'endLine' => 67,
            'startTokenPos' => 58,
            'startFilePos' => 2498,
            'endTokenPos' => 58,
            'endFilePos' => 2509,
          ),
        ),
        'docComment' => '/**
 * Default subfolder name when the config key is unset or invalid.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 67,
        'endLine' => 67,
        'startColumn' => 2,
        'endColumn' => 52,
      ),
      'SUBFOLDER_NAME_REGEX' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'name' => 'SUBFOLDER_NAME_REGEX',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'/^[a-z0-9_-]+$/\'',
          'attributes' => 
          array (
            'startLine' => 72,
            'endLine' => 72,
            'startTokenPos' => 71,
            'startFilePos' => 2610,
            'endTokenPos' => 71,
            'endFilePos' => 2626,
          ),
        ),
        'docComment' => '/**
 * Regex that valid subfolder names must match.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 72,
        'endLine' => 72,
        'startColumn' => 2,
        'endColumn' => 56,
      ),
      'LEGACY_SUFFIX_REGEX' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'name' => 'LEGACY_SUFFIX_REGEX',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'/_anonymized$/\'',
          'attributes' => 
          array (
            'startLine' => 79,
            'endLine' => 79,
            'startTokenPos' => 84,
            'startFilePos' => 2883,
            'endTokenPos' => 84,
            'endFilePos' => 2898,
          ),
        ),
        'docComment' => '/**
 * Trailing-`_anonymized` strip pattern; matches the literal suffix on
 * the base name (post-strip of the extension) so `Report_anonymized`
 * becomes `Report` while `_anonymized_summary` is untouched.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 79,
        'endLine' => 79,
        'startColumn' => 2,
        'endColumn' => 54,
      ),
    ),
    'immediateProperties' => 
    array (
      'config' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
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
        'startLine' => 88,
        'endLine' => 88,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
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
        'startLine' => 89,
        'endLine' => 89,
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
            'startLine' => 88,
            'endLine' => 88,
            'startColumn' => 3,
            'endColumn' => 37,
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 89,
            'endLine' => 89,
            'startColumn' => 3,
            'endColumn' => 42,
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
 * Constructor.
 *
 * @param IAppConfig $config App configuration provider.
 * @param LoggerInterface $logger Logger for the invalid-config warning.
 */',
        'startLine' => 87,
        'endLine' => 92,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'aliasName' => NULL,
      ),
      'resolveBatchDestination' => 
      array (
        'name' => 'resolveBatchDestination',
        'parameters' => 
        array (
          'sourceFolderPath' => 
          array (
            'name' => 'sourceFolderPath',
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
            'startLine' => 107,
            'endLine' => 107,
            'startColumn' => 3,
            'endColumn' => 26,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'sourceBaseName' => 
          array (
            'name' => 'sourceBaseName',
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
            'startLine' => 108,
            'endLine' => 108,
            'startColumn' => 3,
            'endColumn' => 24,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'extension' => 
          array (
            'name' => 'extension',
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
            'startLine' => 109,
            'endLine' => 109,
            'startColumn' => 3,
            'endColumn' => 19,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolve the canonical batch-output relative path for a given source.
 *
 * Output shape: `<sourceFolderPath>/<subfolder>/<cleanBaseName>.<extension>`.
 *
 * @param string $sourceFolderPath Absolute Nextcloud path of the source
 *                                 folder (must start with `/`).
 * @param string $sourceBaseName Base name of the source file (no extension).
 * @param string $extension File extension (without leading dot).
 *
 * @return string Canonical destination path.
 */',
        'startLine' => 106,
        'endLine' => 122,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'aliasName' => NULL,
      ),
      'stripLegacyAnonymizedSuffix' => 
      array (
        'name' => 'stripLegacyAnonymizedSuffix',
        'parameters' => 
        array (
          'baseName' => 
          array (
            'name' => 'baseName',
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
            'startLine' => 135,
            'endLine' => 135,
            'startColumn' => 46,
            'endColumn' => 61,
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
 * Strip a trailing `_anonymized` suffix from the supplied base name.
 *
 * Public so source-discovery filters (`BatchExtractionService`,
 * `FolderBatchService`, `FolderExtractionJob`) can quickly test whether
 * a candidate file is itself a prior anonymisation output.
 *
 * @param string $baseName Source base name (without extension).
 *
 * @return string Base name with one trailing `_anonymized` stripped, if present.
 */',
        'startLine' => 135,
        'endLine' => 142,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'aliasName' => NULL,
      ),
      'isLegacyAnonymizedOutput' => 
      array (
        'name' => 'isLegacyAnonymizedOutput',
        'parameters' => 
        array (
          'baseName' => 
          array (
            'name' => 'baseName',
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
            'startLine' => 152,
            'endLine' => 152,
            'startColumn' => 43,
            'endColumn' => 58,
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
 * Indicate whether the given base name is itself a prior anonymisation
 * output (used by the source-discovery filter).
 *
 * @param string $baseName Source base name (without extension).
 *
 * @return bool True iff the base name ends with the legacy `_anonymized` suffix.
 */',
        'startLine' => 152,
        'endLine' => 154,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'aliasName' => NULL,
      ),
      'hasAnonymizedSuffix' => 
      array (
        'name' => 'hasAnonymizedSuffix',
        'parameters' => 
        array (
          'fileName' => 
          array (
            'name' => 'fileName',
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
            'startLine' => 169,
            'endLine' => 169,
            'startColumn' => 38,
            'endColumn' => 53,
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
 * Indicate whether a full file name (with extension) is itself a prior
 * anonymisation output, i.e. its base name ends with `_anonymized`.
 *
 * This is the file-name-oriented discriminator used by the folder-flow
 * source filter (`FolderExtractionJob`) — it strips the extension first
 * and then defers to {@see isLegacyAnonymizedOutput()} so there is a
 * single source of truth for the suffix semantics.
 *
 * @param string $fileName Full file name including extension.
 *
 * @return bool True iff the base name ends with the legacy `_anonymized` suffix.
 */',
        'startLine' => 169,
        'endLine' => 172,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'aliasName' => NULL,
      ),
      'readSubfolderName' => 
      array (
        'name' => 'readSubfolderName',
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
 * Alias for {@see getSubfolderName()} expressing the "read from config"
 * intent at the folder-flow call-site (`FolderExtractionJob`).
 *
 * @return string A safe, validated subfolder name.
 */',
        'startLine' => 180,
        'endLine' => 182,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'aliasName' => NULL,
      ),
      'getSubfolderName' => 
      array (
        'name' => 'getSubfolderName',
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
 * Resolve the configured subfolder name with validation and fallback.
 *
 * Reads `filinq.anonymisation.output_subfolder_name`; falls back to
 * `anonymised` and logs a warning when the value does not match the
 * `/^[a-z0-9_-]+$/` validation regex.
 *
 * @return string A safe, validated subfolder name.
 *
 * @spec openspec/specs/folder-analysis-anonymization/spec.md#requirement-folder-driven-anonymisation-must-write-outputs-to-the-configured-subfolder
 */',
        'startLine' => 195,
        'endLine' => 215,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\OutputLayoutResolver',
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