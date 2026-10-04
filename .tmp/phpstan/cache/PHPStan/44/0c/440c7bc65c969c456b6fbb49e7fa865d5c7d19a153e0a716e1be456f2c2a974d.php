<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Signing/GuardianEntryPreparer.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Signing\GuardianEntryPreparer
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-ee01be116dbf7bd3966ca196562b0e77ea6cd6a71d875b2cecd2f5223140421d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Signing/GuardianEntryPreparer.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Signing',
    'name' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
    'shortName' => 'GuardianEntryPreparer',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Turns signer entries into the guardian fields their records carry.
 *
 * A consumer names a guardian with `role: guardian` and `guardianFor`, the
 * `userId` or email of another entry in the same list. Every refusal here is
 * an InvalidArgumentException with code 400, thrown before the request or any
 * signer record is written.
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
    'startLine' => 45,
    'endLine' => 298,
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
      'ACTS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'name' => 'ACTS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'co-sign\', \'consent\']',
          'attributes' => 
          array (
            'startLine' => 52,
            'endLine' => 52,
            'startTokenPos' => 45,
            'startFilePos' => 1538,
            'endTokenPos' => 50,
            'endFilePos' => 1559,
          ),
        ),
        'docComment' => '/**
 * The acts a guardian can perform.
 *
 * @var list<string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 52,
        'endLine' => 52,
        'startColumn' => 2,
        'endColumn' => 44,
      ),
    ),
    'immediateProperties' => 
    array (
      'agePolicy' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'name' => 'agePolicy',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
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
        'endColumn' => 47,
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
          'agePolicy' => 
          array (
            'name' => 'agePolicy',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Signing\\GuardianAgePolicy',
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
            'endColumn' => 47,
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
 * @param GuardianAgePolicy $agePolicy Parses birth dates and evaluates the age.
 *
 * @return void
 */',
        'startLine' => 61,
        'endLine' => 65,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'aliasName' => NULL,
      ),
      'prepare' => 
      array (
        'name' => 'prepare',
        'parameters' => 
        array (
          'signers' => 
          array (
            'name' => 'signers',
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
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 26,
            'endColumn' => 39,
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
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 42,
            'endColumn' => 49,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'now' => 
          array (
            'name' => 'now',
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
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 52,
            'endColumn' => 73,
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
 * Validate the entries and link each guardian to their signer.
 *
 * @param array<int|string, mixed> $signers The signer entries as the consumer sent them.
 * @param int $age The age of consent that applies to the request.
 * @param DateTimeImmutable $now The moment of creation.
 *
 * @return array{fields: array<int|string, array<string, mixed>>, links: array<int|string, int|string>}
 *     `fields` holds, per entry, the guardian fields to store on its signer
 *     record ([] for an entry outside the rule); `links` maps each guardian
 *     entry to the entry it stands beside.
 *
 * @throws InvalidArgumentException With code 400 when an entry is invalid.
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */',
        'startLine' => 83,
        'endLine' => 94,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'aliasName' => NULL,
      ),
      'entryFields' => 
      array (
        'name' => 'entryFields',
        'parameters' => 
        array (
          'entry' => 
          array (
            'name' => 'entry',
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
            'startLine' => 105,
            'endLine' => 105,
            'startColumn' => 31,
            'endColumn' => 42,
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
 * The guardian fields one entry carries.
 *
 * @param array<string, mixed> $entry The signer entry.
 *
 * @return array<string, mixed> The fields to store, or [] for an entry outside the rule.
 *
 * @throws InvalidArgumentException With code 400 when the entry is invalid.
 */',
        'startLine' => 105,
        'endLine' => 127,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'aliasName' => NULL,
      ),
      'roleOf' => 
      array (
        'name' => 'roleOf',
        'parameters' => 
        array (
          'entry' => 
          array (
            'name' => 'entry',
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
            'startColumn' => 26,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The role an entry declares, or \'\' when it takes no part in the rule.
 *
 * @param array<string, mixed> $entry The signer entry.
 *
 * @return string `signer`, `guardian`, or \'\'.
 *
 * @throws InvalidArgumentException With code 400 for an unknown role.
 */',
        'startLine' => 138,
        'endLine' => 157,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'aliasName' => NULL,
      ),
      'guardianFields' => 
      array (
        'name' => 'guardianFields',
        'parameters' => 
        array (
          'entry' => 
          array (
            'name' => 'entry',
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
            'startLine' => 168,
            'endLine' => 168,
            'startColumn' => 34,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The fields only a guardian entry carries.
 *
 * @param array<string, mixed> $entry The guardian entry.
 *
 * @return array<string, mixed> The act, and the statement and reference when present.
 *
 * @throws InvalidArgumentException With code 400 for an unknown act or a consent without a statement.
 */',
        'startLine' => 168,
        'endLine' => 191,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'aliasName' => NULL,
      ),
      'resolveLinks' => 
      array (
        'name' => 'resolveLinks',
        'parameters' => 
        array (
          'signers' => 
          array (
            'name' => 'signers',
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
            'startLine' => 203,
            'endLine' => 203,
            'startColumn' => 32,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 203,
            'endLine' => 203,
            'startColumn' => 48,
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
 * Link each guardian entry to the entry it names in `guardianFor`.
 *
 * @param array<int|string, mixed> $signers The signer entries.
 * @param array<int|string, array<string, mixed>> $fields The prepared fields per entry.
 *
 * @return array<int|string, int|string> Guardian entry index to signer entry index.
 *
 * @throws InvalidArgumentException With code 400 when a guardian names nobody, themselves or another guardian.
 */',
        'startLine' => 203,
        'endLine' => 224,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'aliasName' => NULL,
      ),
      'findEntry' => 
      array (
        'name' => 'findEntry',
        'parameters' => 
        array (
          'signers' => 
          array (
            'name' => 'signers',
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
            'startLine' => 235,
            'endLine' => 235,
            'startColumn' => 29,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'key' => 
          array (
            'name' => 'key',
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
            'startColumn' => 45,
            'endColumn' => 55,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'except' => 
          array (
            'name' => 'except',
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
                      'name' => 'int',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'string',
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
            'startLine' => 235,
            'endLine' => 235,
            'startColumn' => 58,
            'endColumn' => 75,
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
                  'name' => 'int',
                  'isIdentifier' => true,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'string',
                  'isIdentifier' => true,
                ),
              ),
              2 => 
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
 * Find the entry a guardian names, by user id or by email.
 *
 * @param array<int|string, mixed> $signers The signer entries.
 * @param string $key The `userId` or email the guardian named.
 * @param int|string $except The guardian\'s own index, never a match.
 *
 * @return int|string|null The matching entry\'s index, or null.
 */',
        'startLine' => 235,
        'endLine' => 248,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'aliasName' => NULL,
      ),
      'entryMatches' => 
      array (
        'name' => 'entryMatches',
        'parameters' => 
        array (
          'entry' => 
          array (
            'name' => 'entry',
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
            'startLine' => 258,
            'endLine' => 258,
            'startColumn' => 32,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'key' => 
          array (
            'name' => 'key',
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
            'startLine' => 258,
            'endLine' => 258,
            'startColumn' => 46,
            'endColumn' => 56,
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
 * Does this entry answer to the key a guardian named.
 *
 * @param array<string, mixed> $entry The candidate entry.
 * @param string $key The `userId` or email named.
 *
 * @return bool True on a user id match, or a case-insensitive email match.
 */',
        'startLine' => 258,
        'endLine' => 264,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'aliasName' => NULL,
      ),
      'assertMinorsGuarded' => 
      array (
        'name' => 'assertMinorsGuarded',
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
            'startLine' => 278,
            'endLine' => 278,
            'startColumn' => 39,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'links' => 
          array (
            'name' => 'links',
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
            'startLine' => 278,
            'endLine' => 278,
            'startColumn' => 54,
            'endColumn' => 65,
            'parameterIndex' => 1,
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
            'startLine' => 278,
            'endLine' => 278,
            'startColumn' => 68,
            'endColumn' => 75,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'now' => 
          array (
            'name' => 'now',
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
            'startLine' => 278,
            'endLine' => 278,
            'startColumn' => 78,
            'endColumn' => 99,
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
 * Refuse a request that names a minor without a guardian, or a minor as guardian.
 *
 * @param array<int|string, array<string, mixed>> $fields The prepared fields per entry.
 * @param array<int|string, int|string> $links Guardian entry index to signer entry index.
 * @param int $age The age of consent.
 * @param DateTimeImmutable $now The moment of creation.
 *
 * @return void
 *
 * @throws InvalidArgumentException With code 400.
 */',
        'startLine' => 278,
        'endLine' => 297,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\GuardianEntryPreparer',
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