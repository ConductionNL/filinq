<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/AnonymizeRequestValidator.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\AnonymizeRequestValidator
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-09bba098b8557ca091a053cc28d02234898094750990a7c2c44905abbd141434',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\AnonymizeRequestValidator',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/AnonymizeRequestValidator.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\AnonymizeRequestValidator',
    'shortName' => 'AnonymizeRequestValidator',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Validates and normalises the anonymize endpoint\'s request body.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 43,
    'endLine' => 256,
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
      'unredactedValidator' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizeRequestValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizeRequestValidator',
        'name' => 'unredactedValidator',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\UnredactedEntitiesValidator',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Validator for the shared `unredactedEntities[]` payload shape.
 *
 * @var UnredactedEntitiesValidator
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 50,
        'endLine' => 50,
        'startColumn' => 2,
        'endColumn' => 67,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'l10n' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizeRequestValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizeRequestValidator',
        'name' => 'l10n',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IL10N',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 60,
        'endLine' => 60,
        'startColumn' => 3,
        'endColumn' => 30,
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
          'l10n' => 
          array (
            'name' => 'l10n',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IL10N',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 60,
            'endLine' => 60,
            'startColumn' => 3,
            'endColumn' => 30,
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
 * Constructor for AnonymizeRequestValidator
 *
 * @param IL10N $l10n Translator for the user-facing validation messages.
 *
 * @return void
 */',
        'startLine' => 59,
        'endLine' => 64,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizeRequestValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizeRequestValidator',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymizeRequestValidator',
        'aliasName' => NULL,
      ),
      'validateBody' => 
      array (
        'name' => 'validateBody',
        'parameters' => 
        array (
          'params' => 
          array (
            'name' => 'params',
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
            'startLine' => 82,
            'endLine' => 82,
            'startColumn' => 31,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Validate the anonymize request body and normalise it for the executor.
 *
 * The checks run in the same order the controller used to run them inline,
 * so a malformed body still yields the same first-failing HTTP 400.
 *
 * @param array<string, mixed> $params Request parameters.
 *
 * @return array{error: array{status: int, body: array<string, mixed>}|null,
 *               request: array<string, mixed>|null} The first validation failure, or the
 *                                                   normalised request under `request`.
 *
 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-1
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
 */',
        'startLine' => 82,
        'endLine' => 130,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizeRequestValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizeRequestValidator',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymizeRequestValidator',
        'aliasName' => NULL,
      ),
      'extractAppendBasisSummary' => 
      array (
        'name' => 'extractAppendBasisSummary',
        'parameters' => 
        array (
          'params' => 
          array (
            'name' => 'params',
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
            'startLine' => 145,
            'endLine' => 145,
            'startColumn' => 45,
            'endColumn' => 57,
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
                  'name' => 'bool',
                  'isIdentifier' => true,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'array',
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
 * Extract and validate the appendBasisSummary flag from request params.
 *
 * Returns the HTTP 400 status/body pair when the field is present but not boolean.
 *
 * @param array<string, mixed> $params Request parameters.
 *
 * @return bool|array{status: int, body: array<string, mixed>} False when omitted, the supplied
 *                                                             boolean when set, or the 400
 *                                                             payload on a type error.
 *
 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-1
 */',
        'startLine' => 145,
        'endLine' => 156,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizeRequestValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizeRequestValidator',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymizeRequestValidator',
        'aliasName' => NULL,
      ),
      'validateOverrides' => 
      array (
        'name' => 'validateOverrides',
        'parameters' => 
        array (
          'overrides' => 
          array (
            'name' => 'overrides',
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
            'startLine' => 174,
            'endLine' => 174,
            'startColumn' => 37,
            'endColumn' => 52,
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
 * Validate the acknowledgedOverrides[] payload entries.
 *
 * Each entry must have:
 *   - ruleId   (string, required)
 *   - entityId (int, required)
 * Optional:
 *   - reason (string)
 *
 * @param array<int, mixed> $overrides The acknowledgedOverrides array from the request.
 *
 * @return array{status: int, body: array<string, mixed>}|null HTTP 400 payload for the first
 *                                                             invalid entry, null when all valid.
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
 */',
        'startLine' => 174,
        'endLine' => 202,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizeRequestValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizeRequestValidator',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymizeRequestValidator',
        'aliasName' => NULL,
      ),
      'hasStrayBases' => 
      array (
        'name' => 'hasStrayBases',
        'parameters' => 
        array (
          'entities' => 
          array (
            'name' => 'entities',
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
            'startLine' => 217,
            'endLine' => 217,
            'startColumn' => 33,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Detect stray `bases[]` fields on entity entries.
 *
 * Bases are set through OpenRegister\'s PATCH /api/entity-relations/{id};
 * a stray field on the anonymize payload is ignored but reported back as
 * `ignoredFields` for GDPR accountability.
 *
 * @param array<int, mixed> $entities The submitted entity entries.
 *
 * @return bool True when at least one entry carries a `bases` key.
 *
 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-1
 */',
        'startLine' => 217,
        'endLine' => 225,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizeRequestValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizeRequestValidator',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymizeRequestValidator',
        'aliasName' => NULL,
      ),
      'rejected' => 
      array (
        'name' => 'rejected',
        'parameters' => 
        array (
          'error' => 
          array (
            'name' => 'error',
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
            'startLine' => 236,
            'endLine' => 236,
            'startColumn' => 28,
            'endColumn' => 39,
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
 * Wrap a validation failure in the validateBody() return shape.
 *
 * @param array{status: int, body: array<string, mixed>} $error The failing status/body pair.
 *
 * @return array{error: array{status: int, body: array<string, mixed>}, request: null} The rejection.
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
 */',
        'startLine' => 236,
        'endLine' => 238,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizeRequestValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizeRequestValidator',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymizeRequestValidator',
        'aliasName' => NULL,
      ),
      'badRequest' => 
      array (
        'name' => 'badRequest',
        'parameters' => 
        array (
          'message' => 
          array (
            'name' => 'message',
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
            'startLine' => 249,
            'endLine' => 249,
            'startColumn' => 30,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Build the HTTP 400 status/body pair for a validation failure.
 *
 * @param string $message The already-translated error message.
 *
 * @return array{status: int, body: array<string, mixed>} The 400 payload.
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
 */',
        'startLine' => 249,
        'endLine' => 255,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\AnonymizeRequestValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\AnonymizeRequestValidator',
        'currentClassName' => 'OCA\\Filinq\\Service\\AnonymizeRequestValidator',
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