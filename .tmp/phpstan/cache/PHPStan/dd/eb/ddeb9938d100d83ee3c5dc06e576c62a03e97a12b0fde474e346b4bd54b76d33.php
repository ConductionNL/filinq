<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/OpenRegisterResolver.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\OpenRegisterResolver
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-51aa22956e7c90068ca4a55009bc3879562d231dddee925586b5f4aa5891a586',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/OpenRegisterResolver.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
    'shortName' => 'OpenRegisterResolver',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service for resolving OpenRegister configuration and namespace validation
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 38,
    'endLine' => 205,
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
      'settingsService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
        'name' => 'settingsService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\SettingsService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 47,
        'endLine' => 47,
        'startColumn' => 3,
        'endColumn' => 51,
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 47,
            'endLine' => 47,
            'startColumn' => 3,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor for OpenRegisterResolver
 *
 * @param SettingsService $settingsService Settings service for register/schema IDs
 *
 * @return void
 */',
        'startLine' => 46,
        'endLine' => 50,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
        'aliasName' => NULL,
      ),
      'getRegisterAndSchema' => 
      array (
        'name' => 'getRegisterAndSchema',
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
 * Get the template register and schema IDs from settings
 *
 * @return array{register: string, schema: string} Register and schema IDs
 *
 * @throws RegisterNotConfiguredException If template register/schema is not configured
 *
 * @spec openspec/specs/openregister-bridge/spec.md
 */',
        'startLine' => 61,
        'endLine' => 71,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
        'aliasName' => NULL,
      ),
      'getVersionRegisterAndSchema' => 
      array (
        'name' => 'getVersionRegisterAndSchema',
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
 * Get the template version register and schema IDs from settings
 *
 * @return array{register: string, schema: string} Register and schema IDs
 *
 * @throws RegisterNotConfiguredException If template version register/schema is not configured
 *
 * @spec openspec/specs/openregister-bridge/spec.md
 */',
        'startLine' => 82,
        'endLine' => 94,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
        'aliasName' => NULL,
      ),
      'validateNamespace' => 
      array (
        'name' => 'validateNamespace',
        'parameters' => 
        array (
          'namespace' => 
          array (
            'name' => 'namespace',
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
 * Validate that a namespace string is a valid Nextcloud app ID
 *
 * @param string $namespace The namespace to validate
 *
 * @return bool True if valid
 *
 * @throws Exception If the namespace is invalid
 *
 * @spec openspec/specs/openregister-bridge/spec.md
 */',
        'startLine' => 107,
        'endLine' => 116,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
        'aliasName' => NULL,
      ),
      'getFinancialExtractionRegisterAndSchema' => 
      array (
        'name' => 'getFinancialExtractionRegisterAndSchema',
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
 * Resolve the financialExtraction register/schema binding, failing closed.
 *
 * SettingsService owns the READ and returns null when either half is unset;
 * this turns that null into the same RegisterNotConfiguredException the
 * template accessors above already raise. The split exists so the two
 * concerns sit where they belong — the settings surface reads config, this
 * resolver decides that an unset binding is an error — and it keeps the
 * exception type out of SettingsService, whose object coupling is at its
 * PHPMD ceiling.
 *
 * @return array{register: string, schema: string} The resolved binding.
 *
 * @throws RegisterNotConfiguredException If the binding is not configured.
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 */',
        'startLine' => 135,
        'endLine' => 144,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
        'aliasName' => NULL,
      ),
      'getGlAccountBookingRegisterAndSchema' => 
      array (
        'name' => 'getGlAccountBookingRegisterAndSchema',
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
 * Resolve the glAccountBooking register/schema binding, failing closed.
 *
 * @return array{register: string, schema: string} The resolved binding.
 *
 * @throws RegisterNotConfiguredException If the binding is not configured.
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 */',
        'startLine' => 155,
        'endLine' => 164,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
        'aliasName' => NULL,
      ),
      'getGlAccountMappingRuleRegisterAndSchema' => 
      array (
        'name' => 'getGlAccountMappingRuleRegisterAndSchema',
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
 * Resolve the glAccountMappingRule register/schema binding, failing closed.
 *
 * @return array{register: string, schema: string} The resolved binding.
 *
 * @throws RegisterNotConfiguredException If the binding is not configured.
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 */',
        'startLine' => 175,
        'endLine' => 184,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
        'aliasName' => NULL,
      ),
      'getSignerRecordRegisterAndSchema' => 
      array (
        'name' => 'getSignerRecordRegisterAndSchema',
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
 * Resolve the signerRecord register/schema binding, failing closed.
 *
 * @return array{register: string, schema: string} The resolved binding.
 *
 * @throws RegisterNotConfiguredException If the binding is not configured.
 *
 * @spec openspec/specs/document-signing/spec.md
 */',
        'startLine' => 195,
        'endLine' => 204,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\OpenRegisterResolver',
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