<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/ConsentScopeValidator.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\ConsentScopeValidator
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-f96d022e8ddf9dfafdf02f82ed5f5fbed7bf9a4ace91b3eb2daf0e892138df04',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/ConsentScopeValidator.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
    'shortName' => 'ConsentScopeValidator',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Stateless validator for publicationConsent writes — service-layer
 * complement to the schema (the schema cannot express
 * "matchRules is required when scope=entity but forbidden when
 * scope=document").
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @spec openspec/changes/revive-dead-capabilities/tasks.md#task-2
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 57,
    'endLine' => 343,
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
      'STANDING_CONSENT_GROUP' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'name' => 'STANDING_CONSENT_GROUP',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'docudesk-standing-consent-admins\'',
          'attributes' => 
          array (
            'startLine' => 75,
            'endLine' => 75,
            'startTokenPos' => 55,
            'startFilePos' => 2906,
            'endTokenPos' => 55,
            'endFilePos' => 2939,
          ),
        ),
        'docComment' => '/**
 * Group whose members may write scope=entity (standing-consent) records.
 *
 * Mirrored from PolicyCrudService to keep the validator self-contained.
 *
 * ⚠️ STILL `docudesk-`, DELIBERATELY, ACROSS THE FILINQ RENAME. This is a
 * Nextcloud GROUP ID, provisioned by OpenRegister from the declaration in
 * `lib/Settings/filinq_register.json` and populated by admins afterwards.
 * OpenRegister provisions a declared group CREATE-ONLY, so renaming the id
 * makes it create a NEW, EMPTY group; the existing one keeps every member
 * and nothing reads it any more. An empty group denies everyone except
 * admins and object owners, so the visible symptom is standing-consent
 * writes starting to 403 for exactly the people who were granted them —
 * with no error at provisioning time and nothing in the log. Same failure
 * shape as renaming an OpenRegister register slug. Renaming the group needs
 * its own membership migration, not a token substitution.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 75,
        'endLine' => 75,
        'startColumn' => 2,
        'endColumn' => 75,
      ),
    ),
    'immediateProperties' => 
    array (
      'groupManager' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'name' => 'groupManager',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IGroupManager',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 83,
        'endLine' => 83,
        'startColumn' => 3,
        'endColumn' => 46,
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
          'groupManager' => 
          array (
            'name' => 'groupManager',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IGroupManager',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 3,
            'endColumn' => 46,
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
 * Constructor.
 *
 * @param IGroupManager $groupManager Group manager for RBAC checks.
 */',
        'startLine' => 82,
        'endLine' => 86,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'aliasName' => NULL,
      ),
      'requireStandingConsentAdminGroup' => 
      array (
        'name' => 'requireStandingConsentAdminGroup',
        'parameters' => 
        array (
          'user' => 
          array (
            'name' => 'user',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IUser',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 102,
            'endLine' => 102,
            'startColumn' => 51,
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
 * Assert that the given user is a member of the standing-consent admin group
 * (or is a Nextcloud admin, who implicitly bypasses all group gates).
 *
 * Called by ConsentService before any scope=entity write operation.
 *
 * @param IUser $user The acting user.
 *
 * @return void
 *
 * @throws RuntimeException When the user lacks the required group membership.
 *
 * @spec openspec/changes/archive/2026-06-14-publication-consent-policy-fields/tasks.md
 */',
        'startLine' => 102,
        'endLine' => 118,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'aliasName' => NULL,
      ),
      'validateTransition' => 
      array (
        'name' => 'validateTransition',
        'parameters' => 
        array (
          'existing' => 
          array (
            'name' => 'existing',
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
            'startLine' => 138,
            'endLine' => 138,
            'startColumn' => 37,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'update' => 
          array (
            'name' => 'update',
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
            'startLine' => 138,
            'endLine' => 138,
            'startColumn' => 54,
            'endColumn' => 66,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Validate a state transition on an existing publicationConsent record.
 *
 * Ensures that the update payload does not violate any transition rules
 * relative to the persisted record. Currently enforces:
 *   - The `scope` field MUST NOT change once set.
 *   - The updated record (merged) must satisfy the same scope contract as
 *     assertValid().
 *
 * @param array<string, mixed> $existing The persisted record.
 * @param array<string, mixed> $update The incoming update payload.
 *
 * @return void
 *
 * @throws InvalidArgumentException When the transition is not allowed.
 *
 * @spec openspec/changes/archive/2026-06-14-publication-consent-policy-fields/tasks.md
 */',
        'startLine' => 138,
        'endLine' => 157,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'aliasName' => NULL,
      ),
      'assertValid' => 
      array (
        'name' => 'assertValid',
        'parameters' => 
        array (
          'consent' => 
          array (
            'name' => 'consent',
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
            'startLine' => 170,
            'endLine' => 170,
            'startColumn' => 30,
            'endColumn' => 43,
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
 * Validate a candidate publicationConsent write.
 *
 * @param array<string, mixed> $consent Candidate publicationConsent record.
 *
 * @throws InvalidArgumentException When the scope contract is violated.
 *
 * @return void
 *
 * @spec openspec/changes/publication-consent-policy-fields/tasks.md
 */',
        'startLine' => 170,
        'endLine' => 186,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'aliasName' => NULL,
      ),
      'assertDocumentScope' => 
      array (
        'name' => 'assertDocumentScope',
        'parameters' => 
        array (
          'consent' => 
          array (
            'name' => 'consent',
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
            'startLine' => 197,
            'endLine' => 197,
            'startColumn' => 39,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Enforce the document-scope contract.
 *
 * @param array<string, mixed> $consent Candidate publicationConsent record.
 *
 * @throws InvalidArgumentException When the document-scope contract is violated.
 *
 * @return void
 */',
        'startLine' => 197,
        'endLine' => 205,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'aliasName' => NULL,
      ),
      'assertEntityScope' => 
      array (
        'name' => 'assertEntityScope',
        'parameters' => 
        array (
          'consent' => 
          array (
            'name' => 'consent',
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
            'startLine' => 216,
            'endLine' => 216,
            'startColumn' => 37,
            'endColumn' => 50,
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
 * Enforce the entity-scope contract.
 *
 * @param array<string, mixed> $consent Candidate publicationConsent record.
 *
 * @throws InvalidArgumentException When the entity-scope contract is violated.
 *
 * @return void
 */',
        'startLine' => 216,
        'endLine' => 222,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'aliasName' => NULL,
      ),
      'assertEntityHasNoDocumentId' => 
      array (
        'name' => 'assertEntityHasNoDocumentId',
        'parameters' => 
        array (
          'consent' => 
          array (
            'name' => 'consent',
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
            'startLine' => 233,
            'endLine' => 233,
            'startColumn' => 47,
            'endColumn' => 60,
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
 * Enforce that an entity-scope record carries no documentId.
 *
 * @param array<string, mixed> $consent Candidate publicationConsent record.
 *
 * @throws InvalidArgumentException When a documentId is present.
 *
 * @return void
 */',
        'startLine' => 233,
        'endLine' => 241,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'aliasName' => NULL,
      ),
      'assertEntityMatchRules' => 
      array (
        'name' => 'assertEntityMatchRules',
        'parameters' => 
        array (
          'consent' => 
          array (
            'name' => 'consent',
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
            'startLine' => 252,
            'endLine' => 252,
            'startColumn' => 42,
            'endColumn' => 55,
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
 * Enforce that an entity-scope record carries at least one well-formed matchRule.
 *
 * @param array<string, mixed> $consent Candidate publicationConsent record.
 *
 * @throws InvalidArgumentException When matchRules are missing or malformed.
 *
 * @return void
 */',
        'startLine' => 252,
        'endLine' => 264,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'aliasName' => NULL,
      ),
      'assertEntityMatchRule' => 
      array (
        'name' => 'assertEntityMatchRule',
        'parameters' => 
        array (
          'rule' => 
          array (
            'name' => 'rule',
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
            'startLine' => 276,
            'endLine' => 276,
            'startColumn' => 41,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'idx' => 
          array (
            'name' => 'idx',
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
            'startLine' => 276,
            'endLine' => 276,
            'startColumn' => 54,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Enforce the shape and type vocabulary of a single matchRule.
 *
 * @param mixed $rule The candidate matchRule entry.
 * @param string $idx The rule\'s index, used in the error message.
 *
 * @throws InvalidArgumentException When the rule is malformed or the type is unknown.
 *
 * @return void
 */',
        'startLine' => 276,
        'endLine' => 292,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'aliasName' => NULL,
      ),
      'assertEntityConsentMethod' => 
      array (
        'name' => 'assertEntityConsentMethod',
        'parameters' => 
        array (
          'consent' => 
          array (
            'name' => 'consent',
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
            'startColumn' => 45,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Enforce that an entity-scope record declares a recognised consentMethod.
 *
 * @param array<string, mixed> $consent Candidate publicationConsent record.
 *
 * @throws InvalidArgumentException When the consentMethod is missing or unknown.
 *
 * @return void
 */',
        'startLine' => 303,
        'endLine' => 316,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'aliasName' => NULL,
      ),
      'assertEntityHasNoPolicyMatch' => 
      array (
        'name' => 'assertEntityHasNoPolicyMatch',
        'parameters' => 
        array (
          'consent' => 
          array (
            'name' => 'consent',
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
            'startLine' => 330,
            'endLine' => 330,
            'startColumn' => 48,
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
 * Enforce that an entity-scope record carries no policyMatch.
 *
 * Standing-consent rows are never themselves matched — they are
 * referenced from scope=document rows via policyMatch.
 *
 * @param array<string, mixed> $consent Candidate publicationConsent record.
 *
 * @throws InvalidArgumentException When a policyMatch is present.
 *
 * @return void
 */',
        'startLine' => 330,
        'endLine' => 342,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
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