<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/PolicyCrudService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\PolicyCrudService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-5510dfcb1531bff5505f3b71df966ef51cff784dadb2f166727eacda5a711337',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/PolicyCrudService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\PolicyCrudService',
    'shortName' => 'PolicyCrudService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * CRUD wrapper around the two policy surfaces.
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
    'startLine' => 50,
    'endLine' => 647,
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
      'REGISTER' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'name' => 'REGISTER',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq\'',
          'attributes' => 
          array (
            'startLine' => 58,
            'endLine' => 58,
            'startTokenPos' => 65,
            'startFilePos' => 1804,
            'endTokenPos' => 65,
            'endFilePos' => 1811,
          ),
        ),
        'docComment' => '/**
 * Register slug shared by both policy surfaces.
 *
 * `filinq`, not `consent`: this app declares ONE register holding all 23
 * schemas. The five it used to declare are retired.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 58,
        'endLine' => 58,
        'startColumn' => 2,
        'endColumn' => 34,
      ),
      'SCHEMA_PROHIBITION' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'name' => 'SCHEMA_PROHIBITION',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'publicationProhibition\'',
          'attributes' => 
          array (
            'startLine' => 63,
            'endLine' => 63,
            'startTokenPos' => 78,
            'startFilePos' => 1903,
            'endTokenPos' => 78,
            'endFilePos' => 1926,
          ),
        ),
        'docComment' => '/**
 * Schema slug for the deny-list surface.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 63,
        'endLine' => 63,
        'startColumn' => 2,
        'endColumn' => 60,
      ),
      'SCHEMA_CONSENT' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'name' => 'SCHEMA_CONSENT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'publicationConsent\'',
          'attributes' => 
          array (
            'startLine' => 68,
            'endLine' => 68,
            'startTokenPos' => 91,
            'startFilePos' => 2046,
            'endTokenPos' => 91,
            'endFilePos' => 2065,
          ),
        ),
        'docComment' => '/**
 * Schema slug shared between standing consents and per-document records.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 68,
        'endLine' => 68,
        'startColumn' => 2,
        'endColumn' => 52,
      ),
      'SURFACE_PROHIBITION' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'name' => 'SURFACE_PROHIBITION',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'prohibition\'',
          'attributes' => 
          array (
            'startLine' => 75,
            'endLine' => 75,
            'startTokenPos' => 104,
            'startFilePos' => 2258,
            'endTokenPos' => 104,
            'endFilePos' => 2270,
          ),
        ),
        'docComment' => '/**
 * Policy surface selector for the entity-level deny-list.
 *
 * Used as the `$surface` argument to {@see self::requirePolicyPermission()}.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 75,
        'endLine' => 75,
        'startColumn' => 2,
        'endColumn' => 50,
      ),
      'SURFACE_STANDING_CONSENT' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'name' => 'SURFACE_STANDING_CONSENT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'standingConsent\'',
          'attributes' => 
          array (
            'startLine' => 82,
            'endLine' => 82,
            'startTokenPos' => 117,
            'startFilePos' => 2482,
            'endTokenPos' => 117,
            'endFilePos' => 2498,
          ),
        ),
        'docComment' => '/**
 * Policy surface selector for standing consents (scope=entity records).
 *
 * Used as the `$surface` argument to {@see self::requirePolicyPermission()}.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 82,
        'endLine' => 82,
        'startColumn' => 2,
        'endColumn' => 59,
      ),
      'STANDING_CONSENT_GROUP' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'name' => 'STANDING_CONSENT_GROUP',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'docudesk-standing-consent-admins\'',
          'attributes' => 
          array (
            'startLine' => 96,
            'endLine' => 96,
            'startTokenPos' => 130,
            'startFilePos' => 3077,
            'endTokenPos' => 130,
            'endFilePos' => 3110,
          ),
        ),
        'docComment' => '/**
 * Group whose members may create/update/delete standing-consent records.
 *
 * Enforced at service level per spec §RBAC scenario "Standing-consent
 * write requires standing-consent permission" — the underlying
 * publicationConsent schema\'s RBAC cannot discriminate by `scope`, so the
 * scope-aware gate lives here.
 *
 * ⚠️ STILL `docudesk-`, DELIBERATELY, ACROSS THE FILINQ RENAME — see
 * PROHIBITION_GROUP below for the full reasoning; it applies to every
 * `docudesk-*` group id this app declares.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 96,
        'endLine' => 96,
        'startColumn' => 2,
        'endColumn' => 74,
      ),
      'PROHIBITION_GROUP' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'name' => 'PROHIBITION_GROUP',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'docudesk-prohibition-admins\'',
          'attributes' => 
          array (
            'startLine' => 115,
            'endLine' => 115,
            'startTokenPos' => 143,
            'startFilePos' => 4205,
            'endTokenPos' => 143,
            'endFilePos' => 4233,
          ),
        ),
        'docComment' => '/**
 * Group whose members may create/update/delete publication-prohibition
 * records. Prohibitions are operator-level blocking rules that override
 * the standard anonymise flow, so write authorisation requires either
 * admin role or membership in this group — never any authenticated user.
 *
 * ⚠️ STILL `docudesk-`, DELIBERATELY, ACROSS THE FILINQ RENAME. This is a
 * Nextcloud GROUP ID, declared in `lib/Settings/filinq_register.json` and
 * provisioned by OpenRegister CREATE-ONLY, then populated by admins.
 * Renaming the id therefore creates a NEW, EMPTY group while the existing
 * one keeps every member and nothing reads it any more. An empty group
 * denies everyone except admins and object owners, so the symptom is
 * prohibition writes starting to 403 for exactly the operators who were
 * granted them — no error at provisioning time, nothing in the log. Same
 * failure shape as renaming an OpenRegister register slug. Renaming these
 * groups needs its own membership migration, not a token substitution.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 115,
        'endLine' => 115,
        'startColumn' => 2,
        'endColumn' => 64,
      ),
    ),
    'immediateProperties' => 
    array (
      'settingsService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
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
        'startLine' => 129,
        'endLine' => 129,
        'startColumn' => 3,
        'endColumn' => 51,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'consentService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'name' => 'consentService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\ConsentService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 130,
        'endLine' => 130,
        'startColumn' => 3,
        'endColumn' => 49,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'groupManager' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
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
        'startLine' => 131,
        'endLine' => 131,
        'startColumn' => 3,
        'endColumn' => 46,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'userSession' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'name' => 'userSession',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IUserSession',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 132,
        'endLine' => 132,
        'startColumn' => 3,
        'endColumn' => 44,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
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
        'startLine' => 133,
        'endLine' => 133,
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
            'startLine' => 129,
            'endLine' => 129,
            'startColumn' => 3,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'consentService' => 
          array (
            'name' => 'consentService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\ConsentService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 130,
            'endLine' => 130,
            'startColumn' => 3,
            'endColumn' => 49,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
            'startLine' => 131,
            'endLine' => 131,
            'startColumn' => 3,
            'endColumn' => 46,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'userSession' => 
          array (
            'name' => 'userSession',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IUserSession',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 132,
            'endLine' => 132,
            'startColumn' => 3,
            'endColumn' => 44,
            'parameterIndex' => 3,
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
            'startLine' => 133,
            'endLine' => 133,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 4,
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
 * @param SettingsService $settingsService Settings service providing ObjectService.
 * @param ConsentService $consentService Consent service for scope-validation.
 * @param IGroupManager $groupManager Group membership check.
 * @param IUserSession $userSession Current-user lookup.
 * @param LoggerInterface $logger Structured logger.
 *
 * @return void
 */',
        'startLine' => 128,
        'endLine' => 136,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'aliasName' => NULL,
      ),
      'listProhibitions' => 
      array (
        'name' => 'listProhibitions',
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
 * List all publicationProhibition records.
 *
 * @return array<int, array<string, mixed>>
 *
 * @throws Exception On query failure.
 */',
        'startLine' => 145,
        'endLine' => 151,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'aliasName' => NULL,
      ),
      'listStandingConsents' => 
      array (
        'name' => 'listStandingConsents',
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
 * List publicationConsent records with `scope: "entity"` (standing consents).
 *
 * @return array<int, array<string, mixed>>
 *
 * @throws Exception On query failure.
 */',
        'startLine' => 160,
        'endLine' => 173,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'aliasName' => NULL,
      ),
      'getProhibition' => 
      array (
        'name' => 'getProhibition',
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
            'startLine' => 184,
            'endLine' => 184,
            'startColumn' => 33,
            'endColumn' => 44,
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
 * Get a single prohibition by UUID.
 *
 * @param string $uuid The record UUID.
 *
 * @return array<string, mixed>|null
 *
 * @throws Exception On lookup failure.
 */',
        'startLine' => 184,
        'endLine' => 191,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'aliasName' => NULL,
      ),
      'getStandingConsent' => 
      array (
        'name' => 'getStandingConsent',
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
            'startLine' => 202,
            'endLine' => 202,
            'startColumn' => 37,
            'endColumn' => 48,
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
 * Get a single standing consent by UUID, asserting its scope.
 *
 * @param string $uuid The record UUID.
 *
 * @return array<string, mixed>|null
 *
 * @throws Exception On lookup failure.
 */',
        'startLine' => 202,
        'endLine' => 218,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'aliasName' => NULL,
      ),
      'createProhibition' => 
      array (
        'name' => 'createProhibition',
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
            'startLine' => 229,
            'endLine' => 229,
            'startColumn' => 36,
            'endColumn' => 46,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a publicationProhibition record.
 *
 * @param array<string, mixed> $data Caller-supplied prohibition data.
 *
 * @return array<string, mixed> The created record.
 *
 * @throws Exception On write failure.
 */',
        'startLine' => 229,
        'endLine' => 243,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'aliasName' => NULL,
      ),
      'updateProhibition' => 
      array (
        'name' => 'updateProhibition',
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
            'startLine' => 255,
            'endLine' => 255,
            'startColumn' => 36,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startColumn' => 50,
            'endColumn' => 60,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Update an existing prohibition record.
 *
 * @param string $uuid The record UUID.
 * @param array<string, mixed> $data The updated data.
 *
 * @return array<string, mixed> The updated record.
 *
 * @throws Exception On write failure.
 */',
        'startLine' => 255,
        'endLine' => 266,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'aliasName' => NULL,
      ),
      'deleteProhibition' => 
      array (
        'name' => 'deleteProhibition',
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
            'startLine' => 296,
            'endLine' => 296,
            'startColumn' => 36,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Delete a prohibition record.
 *
 * NOTE: this can never succeed while `publicationProhibition` declares
 * `x-openregister-archival` (see `lib/Settings/filinq_register.json`).
 * OpenRegister\'s `ObjectService::deleteObject()` refuses every user-driven
 * delete on an archival schema and throws
 * `OCA\\OpenRegister\\Exception\\ArchivalImmutableException`; rows are removed
 * only by `OCA\\OpenRegister\\BackgroundJob\\ArchivalRetentionTask` once their
 * retention lapses. `PolicyController::deleteProhibition()` translates that
 * refusal into HTTP 409 Conflict instead of letting it surface as a 500.
 *
 * In practice this method therefore always throws: an
 * `ArchivalImmutableException` while the annotation is present, or another
 * `Exception` on an unrelated failure. It is kept (rather than removed)
 * because the annotation is a policy declaration that may be lifted, and
 * because the endpoint must keep answering a specific, documented status.
 *
 * @param string $uuid The record UUID.
 *
 * @return void
 *
 * @throws Exception On deletion failure, including OpenRegister\'s
 *                   ArchivalImmutableException (not type-hinted here: the class is
 *                   absent during static analysis, OpenRegister is a runtime sibling).
 *
 * @spec openspec/specs/entity-publication-policies/spec.md
 */',
        'startLine' => 296,
        'endLine' => 308,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'aliasName' => NULL,
      ),
      'createStandingConsent' => 
      array (
        'name' => 'createStandingConsent',
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
            'startLine' => 322,
            'endLine' => 322,
            'startColumn' => 40,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a standing consent (scope=entity publicationConsent) record.
 *
 * Service-level scope validation rejects records without `matchRules` or
 * `consentMethod`, or that include a `documentId`.
 *
 * @param array<string, mixed> $data Caller-supplied data.
 *
 * @return array<string, mixed> The created record.
 *
 * @throws Exception On write failure.
 */',
        'startLine' => 322,
        'endLine' => 337,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'aliasName' => NULL,
      ),
      'updateStandingConsent' => 
      array (
        'name' => 'updateStandingConsent',
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
            'startLine' => 349,
            'endLine' => 349,
            'startColumn' => 40,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 349,
            'endLine' => 349,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Update an existing standing consent record.
 *
 * @param string $uuid The record UUID.
 * @param array<string, mixed> $data The updated data.
 *
 * @return array<string, mixed> The updated record.
 *
 * @throws Exception On write failure.
 */',
        'startLine' => 349,
        'endLine' => 369,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'aliasName' => NULL,
      ),
      'deleteStandingConsent' => 
      array (
        'name' => 'deleteStandingConsent',
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
            'startLine' => 387,
            'endLine' => 387,
            'startColumn' => 40,
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
 * Delete a standing consent.
 *
 * Unlike {@see deleteProhibition()} this really can succeed: the
 * `publicationConsent` schema declares NO `x-openregister-archival` in
 * `lib/Settings/filinq_register.json`, so OpenRegister\'s archival gate
 * does not apply to it.
 *
 * @param string $uuid The record UUID.
 *
 * @return void
 *
 * @throws Exception On deletion failure.
 *
 * @spec openspec/specs/entity-publication-policies/spec.md
 */',
        'startLine' => 387,
        'endLine' => 404,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'aliasName' => NULL,
      ),
      'requirePolicyPermission' => 
      array (
        'name' => 'requirePolicyPermission',
        'parameters' => 
        array (
          'surface' => 
          array (
            'name' => 'surface',
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
            'startLine' => 426,
            'endLine' => 426,
            'startColumn' => 42,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'action' => 
          array (
            'name' => 'action',
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
            'startLine' => 426,
            'endLine' => 426,
            'startColumn' => 59,
            'endColumn' => 72,
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
 * Public entry point to the per-surface permission gates — called by the
 * PolicyController and the StandingConsentController.
 *
 * Delegates to the private gate for the requested surface so the controllers
 * do not need to duplicate the permission logic. Throws RuntimeException
 * (mapped to 403) when the current user is not authorised, and
 * InvalidArgumentException — never a silent pass — for an unknown surface.
 *
 * @param string $surface The policy surface: self::SURFACE_PROHIBITION or self::SURFACE_STANDING_CONSENT.
 * @param string $action The operation being authorised (\'read\', \'create\', \'update\', \'delete\').
 *
 * @return void
 *
 * @throws RuntimeException When the current user is not authorised.
 * @throws InvalidArgumentException When $surface names no known policy surface.
 *
 * @spec openspec/changes/archive/2026-06-14-publication-prohibition-schema/tasks.md
 * @spec openspec/changes/archive/2026-06-14-publication-consent-policy-fields/tasks.md
 */',
        'startLine' => 426,
        'endLine' => 441,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'aliasName' => NULL,
      ),
      'assertStandingConsentPermission' => 
      array (
        'name' => 'assertStandingConsentPermission',
        'parameters' => 
        array (
          'action' => 
          array (
            'name' => 'action',
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
            'startLine' => 461,
            'endLine' => 461,
            'startColumn' => 51,
            'endColumn' => 64,
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
 * Enforce service-level standing-consent group membership.
 *
 * Spec §RBAC, scenario "Standing-consent write requires standing-consent
 * permission". A consent-officer with normal `publicationConsent` write
 * permission may write `scope: "document"` records, but writes to
 * `scope: "entity"` require explicit membership in
 * `docudesk-standing-consent-admins`.
 *
 * Admin users bypass this gate (NC convention — they implicitly belong to
 * every privileged group).
 *
 * @param string $action \'create\', \'update\', or \'delete\' (used in error msg only).
 *
 * @return void
 *
 * @throws RuntimeException When the current user is not authorised. Mapped to 403 by the controller.
 */',
        'startLine' => 461,
        'endLine' => 485,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'aliasName' => NULL,
      ),
      'assertProhibitionPermission' => 
      array (
        'name' => 'assertProhibitionPermission',
        'parameters' => 
        array (
          'action' => 
          array (
            'name' => 'action',
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
            'startLine' => 499,
            'endLine' => 499,
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
 * Assert the current user can create/update/delete a prohibition record.
 *
 * Prohibitions are tenant-wide blocking rules; write authorisation requires
 * admin role or membership in `PROHIBITION_GROUP`. Throws otherwise.
 *
 * @param string $action The operator action being authorised (`create`, `update`, `delete`).
 *
 * @return void
 *
 * @throws RuntimeException When the current user is not authorised. Mapped to 403 by the controller.
 */',
        'startLine' => 499,
        'endLine' => 523,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'aliasName' => NULL,
      ),
      'stripFrameworkParams' => 
      array (
        'name' => 'stripFrameworkParams',
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
            'startLine' => 532,
            'endLine' => 532,
            'startColumn' => 40,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Strip framework-injected request params before persistence.
 *
 * @param array<string, mixed> $data Raw incoming data.
 *
 * @return array<string, mixed>
 */',
        'startLine' => 532,
        'endLine' => 535,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'aliasName' => NULL,
      ),
      'listByRegisterSchema' => 
      array (
        'name' => 'listByRegisterSchema',
        'parameters' => 
        array (
          'register' => 
          array (
            'name' => 'register',
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
            'startLine' => 547,
            'endLine' => 547,
            'startColumn' => 40,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'schema' => 
          array (
            'name' => 'schema',
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
            'startLine' => 547,
            'endLine' => 547,
            'startColumn' => 58,
            'endColumn' => 71,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * List records by register+schema slugs and serialise them to plain arrays.
 *
 * @param string $register Register slug.
 * @param string $schema Schema slug.
 *
 * @return array<int, array<string, mixed>>
 *
 * @throws Exception On query failure.
 */',
        'startLine' => 547,
        'endLine' => 575,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'aliasName' => NULL,
      ),
      'findOne' => 
      array (
        'name' => 'findOne',
        'parameters' => 
        array (
          'register' => 
          array (
            'name' => 'register',
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
            'startLine' => 588,
            'endLine' => 588,
            'startColumn' => 27,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'schema' => 
          array (
            'name' => 'schema',
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
            'startLine' => 588,
            'endLine' => 588,
            'startColumn' => 45,
            'endColumn' => 58,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
            'startLine' => 588,
            'endLine' => 588,
            'startColumn' => 61,
            'endColumn' => 72,
            'parameterIndex' => 2,
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
 * Look up one record by UUID.
 *
 * @param string $register Register slug.
 * @param string $schema Schema slug.
 * @param string $uuid Record UUID.
 *
 * @return array<string, mixed>|null
 *
 * @throws Exception On lookup failure.
 */',
        'startLine' => 588,
        'endLine' => 607,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'aliasName' => NULL,
      ),
      'saveObject' => 
      array (
        'name' => 'saveObject',
        'parameters' => 
        array (
          'register' => 
          array (
            'name' => 'register',
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
            'startLine' => 622,
            'endLine' => 622,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'schema' => 
          array (
            'name' => 'schema',
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
            'startLine' => 623,
            'endLine' => 623,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
            'startLine' => 624,
            'endLine' => 624,
            'startColumn' => 3,
            'endColumn' => 13,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'uuid' => 
          array (
            'name' => 'uuid',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 625,
                'endLine' => 625,
                'startTokenPos' => 2040,
                'startFilePos' => 18999,
                'endTokenPos' => 2040,
                'endFilePos' => 19002,
              ),
            ),
            'type' => 
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 625,
            'endLine' => 625,
            'startColumn' => 3,
            'endColumn' => 22,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
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
 * Persist a record via ObjectService::saveObject.
 *
 * @param string $register Register slug.
 * @param string $schema Schema slug.
 * @param array<string, mixed> $data The record payload.
 * @param string|null $uuid Optional UUID for updates.
 *
 * @return array<string, mixed>
 *
 * @throws Exception On write failure.
 */',
        'startLine' => 621,
        'endLine' => 646,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PolicyCrudService',
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