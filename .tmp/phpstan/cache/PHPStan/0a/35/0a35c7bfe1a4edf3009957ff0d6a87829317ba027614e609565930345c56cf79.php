<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Suggestion/SupplierIdentityResolver.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Suggestion\SupplierIdentityResolver
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-5f90d1cca0e3a2d9586b572607b3f37e518b349d967b4a0ff8798397bcda5a8f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Suggestion\\SupplierIdentityResolver',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Suggestion/SupplierIdentityResolver.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Suggestion',
    'name' => 'OCA\\Filinq\\Service\\Suggestion\\SupplierIdentityResolver',
    'shortName' => 'SupplierIdentityResolver',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Resolves a supplier identity (KvK > IBAN > normalised name) from
 * extraction fields.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Suggestion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 42,
    'endLine' => 95,
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
      'resolve' => 
      array (
        'name' => 'resolve',
        'parameters' => 
        array (
          'fields' => 
          array (
            'name' => 'fields',
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
            'startLine' => 55,
            'endLine' => 55,
            'startColumn' => 26,
            'endColumn' => 38,
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
                  'name' => 'array',
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
 * Resolve the supplier identity and its type from extraction fields.
 *
 * @param array<string, mixed> $fields The `financialExtraction` `fields` map
 *                                     (`supplierKvk`, `supplierIban`, `supplierName`).
 *
 * @return array{identity: string, identityType: string}|null The resolved
 *                                                            identity, or null when none of the three source fields yield
 *                                                            a usable value.
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 */',
        'startLine' => 55,
        'endLine' => 72,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Suggestion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Suggestion\\SupplierIdentityResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Suggestion\\SupplierIdentityResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\Suggestion\\SupplierIdentityResolver',
        'aliasName' => NULL,
      ),
      'normaliseName' => 
      array (
        'name' => 'normaliseName',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
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
            'startLine' => 82,
            'endLine' => 82,
            'startColumn' => 33,
            'endColumn' => 44,
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
 * Normalise a supplier name for use as a stable grouping key: trimmed,
 * whitespace-collapsed, lower-cased.
 *
 * @param string $name The raw supplier name.
 *
 * @return string The normalised name, or \'\' when the input is blank.
 */',
        'startLine' => 82,
        'endLine' => 94,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Suggestion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Suggestion\\SupplierIdentityResolver',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Suggestion\\SupplierIdentityResolver',
        'currentClassName' => 'OCA\\Filinq\\Service\\Suggestion\\SupplierIdentityResolver',
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