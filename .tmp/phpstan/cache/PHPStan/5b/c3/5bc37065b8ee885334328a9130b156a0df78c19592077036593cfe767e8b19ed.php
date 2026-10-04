<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/SigningMandateService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\SigningMandateService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-4185cfe5eeecf9ef4e52820a781f28e4a73ed195bf59753caab58d5104ddba04',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/SigningMandateService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\SigningMandateService',
    'shortName' => 'SigningMandateService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Stores and applies the mandate a consuming app declares per record type.
 *
 * ADR-023: whether this person may sign this type of document is an action
 * check. The consuming app declares the rule against the type reference it
 * already sends on the signing request (`sourceApp` + `subjectSchema`), and
 * filinq applies it. Filinq invents no rule of its own: where a type carries
 * no declaration, every pending signer record stands, which is what the
 * folder showed before any of this existed.
 *
 * The declarations live in app config rather than in a register. They are
 * instance configuration written by an administrator, not records, and the
 * signingRequest schema is deprecated in favour of OR task sequences, so a
 * new schema here would be a write path nobody wants to migrate twice.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 55,
    'endLine' => 300,
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
      'CONFIG_KEY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'name' => 'CONFIG_KEY',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'signingMandates\'',
          'attributes' => 
          array (
            'startLine' => 62,
            'endLine' => 62,
            'startTokenPos' => 60,
            'startFilePos' => 2151,
            'endTokenPos' => 60,
            'endFilePos' => 2167,
          ),
        ),
        'docComment' => '/**
 * App config key holding the declarations, as a JSON object keyed by type reference.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 2,
        'endColumn' => 46,
      ),
    ),
    'immediateProperties' => 
    array (
      'config' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
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
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 3,
        'endColumn' => 37,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'groupManager' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
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
        'startLine' => 74,
        'endLine' => 74,
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
            'startLine' => 73,
            'endLine' => 73,
            'startColumn' => 3,
            'endColumn' => 37,
            'parameterIndex' => 0,
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
            'startLine' => 74,
            'endLine' => 74,
            'startColumn' => 3,
            'endColumn' => 46,
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
 * @param IAppConfig $config App config, holding the declarations.
 * @param IGroupManager $groupManager Resolves the groups a mandate names.
 *
 * @return void
 */',
        'startLine' => 72,
        'endLine' => 77,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'aliasName' => NULL,
      ),
      'typeReference' => 
      array (
        'name' => 'typeReference',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
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
            'startLine' => 92,
            'endLine' => 92,
            'startColumn' => 32,
            'endColumn' => 45,
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
 * The type reference a signing request carries, or \'\' when it carries none.
 *
 * The reference is the consuming app plus the schema of the record the
 * document belongs to. It is a reference to that app\'s type, never a copy
 * of the record.
 *
 * @param array<string, mixed> $request The signing request.
 *
 * @return string `<app>/<schema>`, or \'\' when either half is missing.
 *
 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
 */',
        'startLine' => 92,
        'endLine' => 102,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'aliasName' => NULL,
      ),
      'declarations' => 
      array (
        'name' => 'declarations',
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
 * Every declaration on this instance, keyed by type reference.
 *
 * @return array<string, array{groups: list<string>, rule: string}>
 *
 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
 */',
        'startLine' => 111,
        'endLine' => 131,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'aliasName' => NULL,
      ),
      'declarationFor' => 
      array (
        'name' => 'declarationFor',
        'parameters' => 
        array (
          'typeReference' => 
          array (
            'name' => 'typeReference',
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
            'startLine' => 142,
            'endLine' => 142,
            'startColumn' => 33,
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
 * The declaration for one type reference, or null when the type carries none.
 *
 * @param string $typeReference The `<app>/<schema>` reference.
 *
 * @return array{groups: list<string>, rule: string}|null
 *
 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
 */',
        'startLine' => 142,
        'endLine' => 151,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'aliasName' => NULL,
      ),
      'declareMandate' => 
      array (
        'name' => 'declareMandate',
        'parameters' => 
        array (
          'typeReference' => 
          array (
            'name' => 'typeReference',
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
            'startLine' => 166,
            'endLine' => 166,
            'startColumn' => 33,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'groups' => 
          array (
            'name' => 'groups',
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
            'startLine' => 166,
            'endLine' => 166,
            'startColumn' => 56,
            'endColumn' => 68,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'rule' => 
          array (
            'name' => 'rule',
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
            'startLine' => 166,
            'endLine' => 166,
            'startColumn' => 71,
            'endColumn' => 82,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Record a consuming app\'s mandate for one of its record types.
 *
 * @param string $typeReference The `<app>/<schema>` reference the rule binds to.
 * @param array<int, string> $groups The groups whose members hold the mandate.
 * @param string $rule The rule in the consuming app\'s own words, quoted back on a refusal.
 *
 * @return array{groups: list<string>, rule: string} The stored declaration.
 *
 * @throws InvalidArgumentException When the reference, the groups or the rule is empty.
 *
 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
 */',
        'startLine' => 166,
        'endLine' => 200,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'aliasName' => NULL,
      ),
      'withdrawMandate' => 
      array (
        'name' => 'withdrawMandate',
        'parameters' => 
        array (
          'typeReference' => 
          array (
            'name' => 'typeReference',
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
            'startLine' => 211,
            'endLine' => 211,
            'startColumn' => 34,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Withdraw the declaration for one type reference.
 *
 * @param string $typeReference The `<app>/<schema>` reference.
 *
 * @return bool True when a declaration was withdrawn, false when there was none.
 *
 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
 */',
        'startLine' => 211,
        'endLine' => 222,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'aliasName' => NULL,
      ),
      'maySign' => 
      array (
        'name' => 'maySign',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
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
            'startLine' => 234,
            'endLine' => 234,
            'startColumn' => 26,
            'endColumn' => 39,
            'parameterIndex' => 0,
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
            'startLine' => 234,
            'endLine' => 234,
            'startColumn' => 42,
            'endColumn' => 55,
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
 * May this user sign this request\'s document.
 *
 * @param array<string, mixed> $request The signing request.
 * @param string $userId The Nextcloud user asking.
 *
 * @return bool True when no declaration restricts the type, or the user holds the mandate.
 *
 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
 */',
        'startLine' => 234,
        'endLine' => 254,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'aliasName' => NULL,
      ),
      'assertMaySign' => 
      array (
        'name' => 'assertMaySign',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
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
            'startLine' => 268,
            'endLine' => 268,
            'startColumn' => 32,
            'endColumn' => 45,
            'parameterIndex' => 0,
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
            'startLine' => 268,
            'endLine' => 268,
            'startColumn' => 48,
            'endColumn' => 61,
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
 * Refuse a signature outside the mandate, naming the rule that refused it.
 *
 * @param array<string, mixed> $request The signing request.
 * @param string $userId The Nextcloud user asking.
 *
 * @return void
 *
 * @throws RuntimeException When the user is outside the declared mandate.
 *
 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
 */',
        'startLine' => 268,
        'endLine' => 285,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'aliasName' => NULL,
      ),
      'store' => 
      array (
        'name' => 'store',
        'parameters' => 
        array (
          'declarations' => 
          array (
            'name' => 'declarations',
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
            'startLine' => 296,
            'endLine' => 296,
            'startColumn' => 25,
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
 * Write the declarations back.
 *
 * @param array<string, mixed> $declarations The full declaration set.
 *
 * @return void
 *
 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
 */',
        'startLine' => 296,
        'endLine' => 299,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningMandateService',
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