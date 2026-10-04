<?php declare(strict_types = 1);

// osfsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Signing/GuardianAgePolicy.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Signing\GuardianAgePolicy
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-172bd7413893e0ce9da21db3e69626036e50dbe7a674603b0f295523df1c0065-8.3-6.70.0.6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Signing/GuardianAgePolicy.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Signing',
    'name' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
    'shortName' => 'GuardianAgePolicy',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * The age of consent that applies, and whether a signer was under it.
 *
 * The default is 16, the age of consent in Dutch law (UAVG article 5 on AVG
 * article 8). An administrator can set another age; a request can raise it
 * for its own signers (18 on a praktijkovereenkomst, where civil-law minority
 * runs to 18) and never lower it.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 46,
    'endLine' => 190,
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
      'DEFAULT_AGE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
        'name' => 'DEFAULT_AGE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '16',
          'attributes' => 
          array (
            'startLine' => 53,
            'endLine' => 53,
            'startTokenPos' => 50,
            'startFilePos' => 1565,
            'endTokenPos' => 50,
            'endFilePos' => 1566,
          ),
        ),
        'docComment' => '/**
 * The age of consent when nobody configured one.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 53,
        'endLine' => 53,
        'startColumn' => 2,
        'endColumn' => 31,
      ),
      'SETTING' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
        'name' => 'SETTING',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'signing_guardian_consent_age\'',
          'attributes' => 
          array (
            'startLine' => 60,
            'endLine' => 60,
            'startTokenPos' => 63,
            'startFilePos' => 1679,
            'endTokenPos' => 63,
            'endFilePos' => 1708,
          ),
        ),
        'docComment' => '/**
 * The feature-toggle key holding the configured age.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 60,
        'endLine' => 60,
        'startColumn' => 2,
        'endColumn' => 55,
      ),
    ),
    'immediateProperties' => 
    array (
      'settingsService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
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
        'startLine' => 70,
        'endLine' => 70,
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
            'startLine' => 70,
            'endLine' => 70,
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
 * Constructor.
 *
 * @param SettingsService $settingsService Reads the configured age.
 *
 * @return void
 */',
        'startLine' => 69,
        'endLine' => 73,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
        'aliasName' => NULL,
      ),
      'appliedAge' => 
      array (
        'name' => 'appliedAge',
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
            'startLine' => 84,
            'endLine' => 84,
            'startColumn' => 29,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The age of consent that applies to a request.
 *
 * @param array<string, mixed> $data The request (or its creation payload).
 *
 * @return int The configured age, raised to the request\'s own age when that is higher.
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */',
        'startLine' => 84,
        'endLine' => 89,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
        'aliasName' => NULL,
      ),
      'isUnderAge' => 
      array (
        'name' => 'isUnderAge',
        'parameters' => 
        array (
          'birthDate' => 
          array (
            'name' => 'birthDate',
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
            'startLine' => 105,
            'endLine' => 105,
            'startColumn' => 29,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'age' => 
          array (
            'name' => 'age',
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
            'startLine' => 105,
            'endLine' => 105,
            'startColumn' => 48,
            'endColumn' => 55,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'moment' => 
          array (
            'name' => 'moment',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'DateTimeImmutable',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 105,
            'endLine' => 105,
            'startColumn' => 58,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Was a person born on this date under the age at this moment.
 *
 * A birth date nobody can read, or one in the future, counts as under the
 * age: the rule fails towards asking for a guardian, never away from it.
 *
 * @param string $birthDate The birth date (YYYY-MM-DD).
 * @param int $age The age of consent.
 * @param DateTimeImmutable $moment The moment of the act.
 *
 * @return bool True when under the age.
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */',
        'startLine' => 105,
        'endLine' => 113,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
        'aliasName' => NULL,
      ),
      'parseBirthDate' => 
      array (
        'name' => 'parseBirthDate',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
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
            'startLine' => 124,
            'endLine' => 124,
            'startColumn' => 33,
            'endColumn' => 45,
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
                  'name' => 'DateTimeImmutable',
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Parse a birth date, strictly.
 *
 * @param string $value The candidate date.
 *
 * @return DateTimeImmutable|null Midnight UTC on that date, or null when it is not a real YYYY-MM-DD date.
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */',
        'startLine' => 124,
        'endLine' => 135,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
        'aliasName' => NULL,
      ),
      'moment' => 
      array (
        'name' => 'moment',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
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
            'startLine' => 146,
            'endLine' => 146,
            'startColumn' => 25,
            'endColumn' => 37,
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
            'name' => 'DateTimeImmutable',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The moment a recorded act took place.
 *
 * @param string $value The recorded ISO 8601 moment, or \'\'.
 *
 * @return DateTimeImmutable That moment, or now when none was recorded or it cannot be read.
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */',
        'startLine' => 146,
        'endLine' => 157,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
        'aliasName' => NULL,
      ),
      'configuredAge' => 
      array (
        'name' => 'configuredAge',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The age an administrator configured, or the default.
 *
 * @return int The configured age, or 16 when unset, non-numeric or not positive.
 */',
        'startLine' => 164,
        'endLine' => 169,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
        'aliasName' => NULL,
      ),
      'positiveInt' => 
      array (
        'name' => 'positiveInt',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
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
            'startLine' => 178,
            'endLine' => 178,
            'startColumn' => 31,
            'endColumn' => 42,
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
                  'name' => 'int',
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
 * Read a positive whole number from an integer or a digit string.
 *
 * @param mixed $value The candidate.
 *
 * @return int|null The number, or null when it is not a positive whole number.
 */',
        'startLine' => 178,
        'endLine' => 189,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
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