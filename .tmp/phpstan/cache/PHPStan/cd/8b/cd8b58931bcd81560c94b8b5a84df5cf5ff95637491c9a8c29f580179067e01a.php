<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/ConsentRecordWriter.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\ConsentRecordWriter
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-ee093488452b5719e0f0867ef286c9d212a65763fc69e0b055c005946e71ac24',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/ConsentRecordWriter.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
    'shortName' => 'ConsentRecordWriter',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Persistence layer for publicationConsent workflow records.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/consent-management/spec.md
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-1
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 53,
    'endLine' => 547,
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
      'PRESERVED_FIELDS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'name' => 'PRESERVED_FIELDS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'notificationStatus\', \'notificationSentAt\', \'objectionDeadline\', \'objectionReceivedAt\', \'objectionReason\', \'consentStatus\', \'publicationDecision\']',
          'attributes' => 
          array (
            'startLine' => 60,
            'endLine' => 68,
            'startTokenPos' => 60,
            'startFilePos' => 2021,
            'endTokenPos' => 83,
            'endFilePos' => 2185,
          ),
        ),
        'docComment' => '/**
 * Workflow-state fields that MUST NOT be overwritten on update.
 *
 * @var string[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 60,
        'endLine' => 68,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'referentValidator' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'name' => 'referentValidator',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Service-level scope + referent validator.
 *
 * @var ConsentPolicyReferentValidator
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 75,
        'endLine' => 75,
        'startColumn' => 2,
        'endColumn' => 68,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
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
        'startLine' => 90,
        'endLine' => 90,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
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
        'startLine' => 91,
        'endLine' => 91,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
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
        'startLine' => 92,
        'endLine' => 92,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'deadlineChecker' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'name' => 'deadlineChecker',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\ObjectionDeadlineChecker',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 93,
        'endLine' => 93,
        'startColumn' => 3,
        'endColumn' => 60,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'notesHelper' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'name' => 'notesHelper',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\ConsentNotesHelper',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 94,
        'endLine' => 94,
        'startColumn' => 3,
        'endColumn' => 50,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'scopeValidator' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'name' => 'scopeValidator',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 95,
        'endLine' => 95,
        'startColumn' => 3,
        'endColumn' => 56,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'resultExtractor' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
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
            'startLine' => 97,
            'endLine' => 97,
            'startTokenPos' => 180,
            'startFilePos' => 3444,
            'endTokenPos' => 184,
            'endFilePos' => 3470,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 97,
        'endLine' => 97,
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
            'startLine' => 90,
            'endLine' => 90,
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
            'startLine' => 91,
            'endLine' => 91,
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
            'startLine' => 92,
            'endLine' => 92,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'deadlineChecker' => 
          array (
            'name' => 'deadlineChecker',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\ObjectionDeadlineChecker',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 93,
            'endLine' => 93,
            'startColumn' => 3,
            'endColumn' => 60,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'notesHelper' => 
          array (
            'name' => 'notesHelper',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\ConsentNotesHelper',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 94,
            'endLine' => 94,
            'startColumn' => 3,
            'endColumn' => 50,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'scopeValidator' => 
          array (
            'name' => 'scopeValidator',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\ConsentScopeValidator',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 95,
            'endLine' => 95,
            'startColumn' => 3,
            'endColumn' => 56,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
          'referentValidator' => 
          array (
            'name' => 'referentValidator',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 96,
                'endLine' => 96,
                'startTokenPos' => 167,
                'startFilePos' => 3378,
                'endTokenPos' => 167,
                'endFilePos' => 3381,
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
                      'name' => 'OCA\\Filinq\\Service\\ConsentPolicyReferentValidator',
                      'isIdentifier' => false,
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
            'startLine' => 96,
            'endLine' => 96,
            'startColumn' => 3,
            'endColumn' => 59,
            'parameterIndex' => 6,
            'isOptional' => true,
          ),
          'resultExtractor' => 
          array (
            'name' => 'resultExtractor',
            'default' => 
            array (
              'code' => 'new \\OCA\\Filinq\\Service\\ObjectResultExtractor()',
              'attributes' => 
              array (
                'startLine' => 97,
                'endLine' => 97,
                'startTokenPos' => 180,
                'startFilePos' => 3444,
                'endTokenPos' => 184,
                'endFilePos' => 3470,
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
            'startLine' => 97,
            'endLine' => 97,
            'startColumn' => 3,
            'endColumn' => 87,
            'parameterIndex' => 7,
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
 * @param ObjectionDeadlineChecker $deadlineChecker Objection-deadline calculator.
 * @param ConsentNotesHelper $notesHelper Sentinel-tagged notes helper.
 * @param ConsentScopeValidator $scopeValidator Consent scope validator.
 * @param ConsentPolicyReferentValidator|null $referentValidator Scope + referent validator.
 * @param ObjectResultExtractor $resultExtractor Coerces OpenRegister results to rows.
 */',
        'startLine' => 89,
        'endLine' => 105,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'aliasName' => NULL,
      ),
      'requireOpenRegister' => 
      array (
        'name' => 'requireOpenRegister',
        'parameters' => 
        array (
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
 * Assert that OpenRegister is installed and reachable.
 *
 * Callers use this to fail fast, before any other work, exactly where the
 * pre-extraction code resolved the ObjectService up front.
 *
 * @return void
 *
 * @throws RuntimeException If OpenRegister is not available.
 *
 * @spec exclude infrastructure guard — asserts OpenRegister availability, no product behaviour
 */',
        'startLine' => 119,
        'endLine' => 122,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
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
            'startLine' => 135,
            'endLine' => 135,
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
 * @throws \\InvalidArgumentException When data violates scope constraints.
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
 */',
        'startLine' => 135,
        'endLine' => 138,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'aliasName' => NULL,
      ),
      'loadConsentRecord' => 
      array (
        'name' => 'loadConsentRecord',
        'parameters' => 
        array (
          'consentId' => 
          array (
            'name' => 'consentId',
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
            'startLine' => 153,
            'endLine' => 153,
            'startColumn' => 36,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 153,
            'endLine' => 153,
            'startColumn' => 55,
            'endColumn' => 70,
            'parameterIndex' => 1,
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
            'startLine' => 153,
            'endLine' => 153,
            'startColumn' => 73,
            'endColumn' => 86,
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
 * Load a consent record\'s plain data by UUID.
 *
 * @param string $consentId The consent object UUID.
 * @param string $register The register ID.
 * @param string $schema The schema ID.
 *
 * @return array<string, mixed> The record\'s stored data.
 *
 * @throws Exception When the record does not exist.
 *
 * @spec openspec/specs/consent-management/spec.md
 */',
        'startLine' => 153,
        'endLine' => 165,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'aliasName' => NULL,
      ),
      'findExistingConsent' => 
      array (
        'name' => 'findExistingConsent',
        'parameters' => 
        array (
          'documentId' => 
          array (
            'name' => 'documentId',
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
            'startLine' => 185,
            'endLine' => 185,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'entityKey' => 
          array (
            'name' => 'entityKey',
            'default' => NULL,
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
            'startLine' => 186,
            'endLine' => 186,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'entityText' => 
          array (
            'name' => 'entityText',
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
            'startLine' => 187,
            'endLine' => 187,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
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
            'startLine' => 188,
            'endLine' => 188,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 3,
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
            'startLine' => 189,
            'endLine' => 189,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 4,
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
 * Look up an existing scope=document consent record by idempotency key.
 *
 * Primary key: (documentId, entityKey, scope=document).
 * Fallback key when entityKey is null: (documentId, entityText, scope=document).
 * scope=entity records are excluded from matching.
 *
 * @param string $documentId The document UUID.
 * @param string|null $entityKey OR entity UUID, or null.
 * @param string $entityText Detected entity text.
 * @param string $register Register ID.
 * @param string $schema Schema ID.
 *
 * @return array<string, mixed>|null Existing record data or null if not found.
 *
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-1
 */',
        'startLine' => 184,
        'endLine' => 219,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'aliasName' => NULL,
      ),
      'buildIdempotencyFilter' => 
      array (
        'name' => 'buildIdempotencyFilter',
        'parameters' => 
        array (
          'documentId' => 
          array (
            'name' => 'documentId',
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
            'startLine' => 233,
            'endLine' => 233,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'entityKey' => 
          array (
            'name' => 'entityKey',
            'default' => NULL,
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
            'startLine' => 234,
            'endLine' => 234,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'entityText' => 
          array (
            'name' => 'entityText',
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
            'startLine' => 235,
            'endLine' => 235,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
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
            'startLine' => 236,
            'endLine' => 236,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 3,
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
            'startLine' => 237,
            'endLine' => 237,
            'startColumn' => 3,
            'endColumn' => 16,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Build the idempotency-lookup filter.
 *
 * @param string $documentId The document UUID.
 * @param string|null $entityKey OR entity UUID, or null.
 * @param string $entityText Detected entity text.
 * @param string $register Register ID.
 * @param string $schema Schema ID.
 *
 * @return array<string, mixed> The searchObjects() query.
 */',
        'startLine' => 232,
        'endLine' => 255,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'aliasName' => NULL,
      ),
      'updateExistingConsent' => 
      array (
        'name' => 'updateExistingConsent',
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
            'startLine' => 273,
            'endLine' => 273,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'context' => 
          array (
            'name' => 'context',
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
            'startLine' => 274,
            'endLine' => 274,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
            'startLine' => 275,
            'endLine' => 275,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 2,
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
            'startLine' => 276,
            'endLine' => 276,
            'startColumn' => 3,
            'endColumn' => 16,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Update an existing consent record (idempotent re-submit path).
 *
 * Preserves workflow state fields; updates operator-set fields.
 * Re-evaluates policyMatch: sets it if newly applicable, never clears it.
 *
 * @param array<string, mixed> $existing Current record data.
 * @param array<string, mixed> $context The consent-request context.
 * @param string $register Register ID.
 * @param string $schema Schema ID.
 *
 * @return array<string, mixed> Updated record with `wasUpdated: true`.
 *
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-2
 */',
        'startLine' => 272,
        'endLine' => 338,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'aliasName' => NULL,
      ),
      'createNewConsent' => 
      array (
        'name' => 'createNewConsent',
        'parameters' => 
        array (
          'context' => 
          array (
            'name' => 'context',
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
            'startLine' => 367,
            'endLine' => 367,
            'startColumn' => 35,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 367,
            'endLine' => 367,
            'startColumn' => 51,
            'endColumn' => 66,
            'parameterIndex' => 1,
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
            'startLine' => 367,
            'endLine' => 367,
            'startColumn' => 69,
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
 * Create a brand-new consent record.
 *
 * On the WOO fall-through path (no policy match) the record is created with
 * notificationStatus=pending and a computed objectionDeadline. No email or
 * postal notification is dispatched (CONS-049).
 *
 * On a standing-consent match the record is instead pre-empted — see
 * {@see buildNewConsentPayload()} — and carries no objection deadline.
 *
 * The `$context` array carries the caller\'s inputs plus the resolved
 * policy discriminators:
 *   - `documentId`, `entityType`, `entityText` (string)
 *   - `entityKey`, `contactEmail`, `contactAddress` (string|null)
 *   - `publicationBases` (string[]): [0] → legalBasis, [1..] → notes sentinel
 *   - `policyMatchUuid`, `policyMatchKind` (string|null): any policy match
 *   - `standingConsentUuid` (string|null): set only when the match was a
 *     standing consent, i.e. the only kind persisted on a new record
 *
 * @param array<string, mixed> $context The consent-request context.
 * @param string $register Register ID.
 * @param string $schema Schema ID.
 *
 * @return array<string, mixed> Created record with `wasUpdated: false`.
 *
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-5
 */',
        'startLine' => 367,
        'endLine' => 400,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'aliasName' => NULL,
      ),
      'buildNewConsentPayload' => 
      array (
        'name' => 'buildNewConsentPayload',
        'parameters' => 
        array (
          'context' => 
          array (
            'name' => 'context',
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
            'startLine' => 433,
            'endLine' => 433,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Assemble the payload for a brand-new consent record.
 *
 * Two mutually exclusive outcomes, both mandated by the canonical specs:
 *
 * - **No policy match** — the WOO objection workflow runs:
 *   `consentStatus/publicationDecision/notificationStatus: "pending"` plus a
 *   computed `objectionDeadline` (`entity-publication-policies`, scenario
 *   "No policy match falls through to WOO workflow").
 * - **Standing-consent match** — the record is policy-pre-empted:
 *   `consentStatus: "consent_given"`, `publicationDecision:
 *   "publish_with_consent"`, `notificationStatus: "skipped"` and NO
 *   objection deadline (`consent-management`, scenario "Standing-consent
 *   match resolves to existing \'consent_given\' status"; and
 *   `entity-publication-policies`, scenario "Standing consent match
 *   short-circuits when no prohibition match").
 *
 * The pre-empted branch was previously unimplemented: every new record got
 * the `pending` triple, so a matched standing consent silently started the
 * WOO objection clock it is supposed to short-circuit. A prohibition match
 * never reaches here — it aborts in `ConsentService` with a
 * `PolicyRejectedException` — so `standingConsentUuid` is the only match
 * kind this method has to resolve.
 *
 * @param array<string, mixed> $context The consent-request context.
 *
 * @return array<string, mixed> The payload to persist.
 *
 * @spec openspec/specs/consent-management/spec.md
 * @spec openspec/specs/entity-publication-policies/spec.md
 */',
        'startLine' => 433,
        'endLine' => 488,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'aliasName' => NULL,
      ),
      'withStandingConsentPreEmption' => 
      array (
        'name' => 'withStandingConsentPreEmption',
        'parameters' => 
        array (
          'consentData' => 
          array (
            'name' => 'consentData',
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
            'startLine' => 511,
            'endLine' => 511,
            'startColumn' => 49,
            'endColumn' => 66,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'context' => 
          array (
            'name' => 'context',
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
            'startLine' => 511,
            'endLine' => 511,
            'startColumn' => 69,
            'endColumn' => 82,
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
 * Apply standing-consent pre-emption to a new-record payload.
 *
 * A standing consent short-circuits the WOO objection workflow: the record
 * is born in its terminal state and carries no objection deadline. Both
 * canonical specs state the same triple —
 * `consentStatus: "consent_given"`, `publicationDecision:
 * "publish_with_consent"`, `notificationStatus: "skipped"`, with
 * `objectionDeadline: null` and `policyMatch` referencing the rule.
 *
 * No-op when the entity matched no standing consent, in which case the
 * caller\'s WOO defaults (`pending` + computed deadline) stand.
 *
 * @param array<string, mixed> $consentData The payload assembled so far.
 * @param array<string, mixed> $context The consent-request context.
 *
 * @return array<string, mixed> The payload, pre-empted where applicable.
 *
 * @spec openspec/specs/consent-management/spec.md
 * @spec openspec/specs/entity-publication-policies/spec.md
 */',
        'startLine' => 511,
        'endLine' => 531,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
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
        'startLine' => 540,
        'endLine' => 546,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConsentRecordWriter',
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