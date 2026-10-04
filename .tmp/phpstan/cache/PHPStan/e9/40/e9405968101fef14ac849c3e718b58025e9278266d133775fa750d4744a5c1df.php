<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/ConsentPolicyReferentValidator.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\ConsentPolicyReferentValidator
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-90e17137cb7d3f9c8b6e73ccbefb576d9f0d0d7aed3464a36184a71e15ea8181',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/ConsentPolicyReferentValidator.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
    'shortName' => 'ConsentPolicyReferentValidator',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Validates publicationConsent scope rules and policyMatch referents.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 52,
    'endLine' => 246,
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
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
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
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'container' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
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
        'startLine' => 63,
        'endLine' => 63,
        'startColumn' => 3,
        'endColumn' => 48,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'appManager' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
        'name' => 'appManager',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\App\\IAppManager',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 64,
        'endLine' => 64,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'resultExtractor' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
        'name' => 'resultExtractor',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\ObjectResultExtractor',
            'isIdentifier' => false,
          ),
        ),
        'default' => 
        array (
          'code' => 'new \\OCA\\Filinq\\Service\\ObjectResultExtractor()',
          'attributes' => 
          array (
            'startLine' => 65,
            'endLine' => 65,
            'startTokenPos' => 101,
            'startFilePos' => 2353,
            'endTokenPos' => 105,
            'endFilePos' => 2379,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 65,
        'endLine' => 65,
        'startColumn' => 3,
        'endColumn' => 87,
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
            'startLine' => 62,
            'endLine' => 62,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 63,
            'endLine' => 63,
            'startColumn' => 3,
            'endColumn' => 48,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'appManager' => 
          array (
            'name' => 'appManager',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\App\\IAppManager',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 64,
            'endLine' => 64,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'resultExtractor' => 
          array (
            'name' => 'resultExtractor',
            'default' => 
            array (
              'code' => 'new \\OCA\\Filinq\\Service\\ObjectResultExtractor()',
              'attributes' => 
              array (
                'startLine' => 65,
                'endLine' => 65,
                'startTokenPos' => 101,
                'startFilePos' => 2353,
                'endTokenPos' => 105,
                'endFilePos' => 2379,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\ObjectResultExtractor',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 65,
            'endLine' => 65,
            'startColumn' => 3,
            'endColumn' => 87,
            'parameterIndex' => 3,
            'isOptional' => true,
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
 * @param LoggerInterface $logger Logger for error reporting.
 * @param ContainerInterface $container Container for DI.
 * @param IAppManager $appManager App manager interface.
 * @param ObjectResultExtractor $resultExtractor Coerces OpenRegister results to plain rows.
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
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
        'aliasName' => NULL,
      ),
      'validatePublicationConsentData' => 
      array (
        'name' => 'validatePublicationConsentData',
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
            'startLine' => 81,
            'endLine' => 81,
            'startColumn' => 49,
            'endColumn' => 59,
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
 * Validate publication consent data against the scope rules.
 *
 * @param array<string, mixed> $data Consent data to validate.
 *
 * @return void
 *
 * @throws InvalidArgumentException When data violates scope constraints.
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
 */',
        'startLine' => 81,
        'endLine' => 93,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
        'aliasName' => NULL,
      ),
      'assertDocumentScopeData' => 
      array (
        'name' => 'assertDocumentScopeData',
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
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 43,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Enforce the scope=document field-set contract.
 *
 * @param array<string, mixed> $data Consent data to validate.
 *
 * @return void
 *
 * @throws InvalidArgumentException When the contract is violated.
 */',
        'startLine' => 104,
        'endLine' => 116,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
        'aliasName' => NULL,
      ),
      'assertEntityScopeData' => 
      array (
        'name' => 'assertEntityScopeData',
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
            'startLine' => 127,
            'endLine' => 127,
            'startColumn' => 41,
            'endColumn' => 51,
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
 * Enforce the scope=entity field-set contract.
 *
 * @param array<string, mixed> $data Consent data to validate.
 *
 * @return void
 *
 * @throws InvalidArgumentException When the contract is violated.
 */',
        'startLine' => 127,
        'endLine' => 144,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
        'aliasName' => NULL,
      ),
      'assertPolicyMatchReferentValid' => 
      array (
        'name' => 'assertPolicyMatchReferentValid',
        'parameters' => 
        array (
          'uuid' => 
          array (
            'name' => 'uuid',
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
            'startLine' => 161,
            'endLine' => 161,
            'startColumn' => 50,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Verify a policyMatch UUID points at a permitted referent.
 *
 * Permitted: a `publicationProhibition` record, or a `publicationConsent`
 * record with `scope: "entity"`. Rejects: a `publicationConsent` with
 * `scope: "document"` (or missing scope). Dangling UUIDs are not blocked
 * here — the spec leaves that to OpenRegister\'s referential-integrity
 * surface — but they are logged.
 *
 * @param string $uuid The candidate UUID.
 *
 * @return void
 *
 * @throws InvalidArgumentException If the referent\'s scope is not entity.
 */',
        'startLine' => 161,
        'endLine' => 230,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
        'aliasName' => NULL,
      ),
      'getObjectService' => 
      array (
        'name' => 'getObjectService',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\OpenRegister\\Service\\ObjectService',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the ObjectService from OpenRegister.
 *
 * @return \\OCA\\OpenRegister\\Service\\ObjectService The ObjectService instance.
 *
 * @throws RuntimeException If OpenRegister is not available.
 */',
        'startLine' => 239,
        'endLine' => 245,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
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