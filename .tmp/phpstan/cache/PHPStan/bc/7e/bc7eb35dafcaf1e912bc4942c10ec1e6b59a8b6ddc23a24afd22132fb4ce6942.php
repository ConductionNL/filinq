<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/ProhibitionOverrideCommitter.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\ProhibitionOverrideCommitter
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-bd9cb52d37a58d659b29dafb6e524df5090a903dfab39f152227aae972a98050',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\ProhibitionOverrideCommitter',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/ProhibitionOverrideCommitter.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\ProhibitionOverrideCommitter',
    'shortName' => 'ProhibitionOverrideCommitter',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Writes the audit entry and the OpenRegister skip flag for released overrides.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 49,
    'endLine' => 231,
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
      'OVERRIDE_AUDIT_REGISTER' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionOverrideCommitter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionOverrideCommitter',
        'name' => 'OVERRIDE_AUDIT_REGISTER',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq\'',
          'attributes' => 
          array (
            'startLine' => 59,
            'endLine' => 59,
            'startTokenPos' => 60,
            'startFilePos' => 1886,
            'endTokenPos' => 60,
            'endFilePos' => 1893,
          ),
        ),
        'docComment' => '/**
 * Register slug for the prohibition override audit schema.
 *
 * `filinq`, not `consent`: this app declares ONE register holding all 23
 * schemas. The five it used to declare are retired, and the objects were
 * moved across by OCA\\Filinq\\Repair\\ConsolidateRegisters.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 59,
        'endLine' => 59,
        'startColumn' => 2,
        'endColumn' => 50,
      ),
      'OVERRIDE_AUDIT_SCHEMA' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionOverrideCommitter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionOverrideCommitter',
        'name' => 'OVERRIDE_AUDIT_SCHEMA',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'prohibitionOverrideAudit\'',
          'attributes' => 
          array (
            'startLine' => 66,
            'endLine' => 66,
            'startTokenPos' => 73,
            'startFilePos' => 2026,
            'endTokenPos' => 73,
            'endFilePos' => 2051,
          ),
        ),
        'docComment' => '/**
 * Schema slug for the prohibition override audit entries.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 66,
        'endLine' => 66,
        'startColumn' => 2,
        'endColumn' => 66,
      ),
    ),
    'immediateProperties' => 
    array (
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionOverrideCommitter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionOverrideCommitter',
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
        'startLine' => 77,
        'endLine' => 77,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'locator' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionOverrideCommitter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionOverrideCommitter',
        'name' => 'locator',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\OpenRegisterServiceLocator',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 78,
        'endLine' => 78,
        'startColumn' => 3,
        'endColumn' => 54,
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
            'startLine' => 77,
            'endLine' => 77,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'locator' => 
          array (
            'name' => 'locator',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\OpenRegisterServiceLocator',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 78,
            'endLine' => 78,
            'startColumn' => 3,
            'endColumn' => 54,
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
 * Constructor for ProhibitionOverrideCommitter
 *
 * @param LoggerInterface $logger Logger for commit failures.
 * @param OpenRegisterServiceLocator $locator Resolver for OpenRegister services and mappers.
 *
 * @return void
 */',
        'startLine' => 76,
        'endLine' => 81,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionOverrideCommitter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionOverrideCommitter',
        'currentClassName' => 'OCA\\Filinq\\Service\\ProhibitionOverrideCommitter',
        'aliasName' => NULL,
      ),
      'commit' => 
      array (
        'name' => 'commit',
        'parameters' => 
        array (
          'released' => 
          array (
            'name' => 'released',
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
            'startLine' => 100,
            'endLine' => 100,
            'startColumn' => 25,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'fileId' => 
          array (
            'name' => 'fileId',
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
            'startLine' => 100,
            'endLine' => 100,
            'startColumn' => 42,
            'endColumn' => 52,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'userId' => 
          array (
            'name' => 'userId',
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
            'startLine' => 100,
            'endLine' => 100,
            'startColumn' => 55,
            'endColumn' => 68,
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
 * Commit validated override entries: write audit + PATCH OR skip flag.
 *
 * Processes overrides sequentially. Writes the Filinq audit entry BEFORE
 * the OR PATCH for each override. On OR PATCH failure, stops processing
 * further overrides and throws RuntimeException (HTTP 500).
 *
 * @param array<string, array<string, mixed>> $released Validated overrides keyed by \'ruleId|entityId\'.
 * @param int $fileId Nextcloud file ID (for audit entry).
 * @param string $userId UID of the acting user.
 *
 * @return void
 *
 * @throws RuntimeException When the audit write or the OR PATCH fails.
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
 */',
        'startLine' => 100,
        'endLine' => 142,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionOverrideCommitter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionOverrideCommitter',
        'currentClassName' => 'OCA\\Filinq\\Service\\ProhibitionOverrideCommitter',
        'aliasName' => NULL,
      ),
      'writeAudit' => 
      array (
        'name' => 'writeAudit',
        'parameters' => 
        array (
          'objectService' => 
          array (
            'name' => 'objectService',
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
            'startLine' => 158,
            'endLine' => 158,
            'startColumn' => 30,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'auditEntry' => 
          array (
            'name' => 'auditEntry',
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
            'startLine' => 158,
            'endLine' => 158,
            'startColumn' => 52,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Write the Filinq audit entry for one released override.
 *
 * If the audit write fails we MUST NOT proceed to the OR PATCH — fail-closed.
 *
 * @param mixed $objectService OpenRegister ObjectService.
 * @param array<string, mixed> $auditEntry The audit record to persist.
 *
 * @return void
 *
 * @throws RuntimeException When the audit write fails.
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
 */',
        'startLine' => 158,
        'endLine' => 182,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionOverrideCommitter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionOverrideCommitter',
        'currentClassName' => 'OCA\\Filinq\\Service\\ProhibitionOverrideCommitter',
        'aliasName' => NULL,
      ),
      'patchSkipFlag' => 
      array (
        'name' => 'patchSkipFlag',
        'parameters' => 
        array (
          'entityRelationMapper' => 
          array (
            'name' => 'entityRelationMapper',
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
            'startLine' => 199,
            'endLine' => 199,
            'startColumn' => 3,
            'endColumn' => 29,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'relationId' => 
          array (
            'name' => 'relationId',
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
            'startLine' => 200,
            'endLine' => 200,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'ruleId' => 
          array (
            'name' => 'ruleId',
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
            'startLine' => 201,
            'endLine' => 201,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'fileId' => 
          array (
            'name' => 'fileId',
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
            'startLine' => 202,
            'endLine' => 202,
            'startColumn' => 3,
            'endColumn' => 13,
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
 * PATCH the OpenRegister EntityRelation with skipAnonymization=true.
 *
 * @param mixed $entityRelationMapper OpenRegister EntityRelationMapper.
 * @param int $relationId The relation to patch; ignored when not positive.
 * @param string $ruleId The rule id (for the log context).
 * @param int $fileId The file id (for the log context).
 *
 * @return void
 *
 * @throws RuntimeException When the OR PATCH fails.
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
 */',
        'startLine' => 198,
        'endLine' => 230,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ProhibitionOverrideCommitter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ProhibitionOverrideCommitter',
        'currentClassName' => 'OCA\\Filinq\\Service\\ProhibitionOverrideCommitter',
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