<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Redaction/DownloadAgreementRepository.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Redaction\DownloadAgreementRepository
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-69b9a55dfa96e608ef14373ad9460284db6e9a94befc2518f2d06812258604b3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementRepository',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Redaction/DownloadAgreementRepository.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Redaction',
    'name' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementRepository',
    'shortName' => 'DownloadAgreementRepository',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Reads and writes `downloadAgreement` rows in the `filinq` register.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 34,
    'endLine' => 281,
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
      'SCHEMA' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementRepository',
        'name' => 'SCHEMA',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'downloadAgreement\'',
          'attributes' => 
          array (
            'startLine' => 41,
            'endLine' => 41,
            'startTokenPos' => 70,
            'startFilePos' => 1110,
            'endTokenPos' => 70,
            'endFilePos' => 1128,
          ),
        ),
        'docComment' => '/**
 * The schema these rows live in.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 41,
        'endLine' => 41,
        'startColumn' => 2,
        'endColumn' => 43,
      ),
    ),
    'immediateProperties' => 
    array (
      'objectResolver' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementRepository',
        'name' => 'objectResolver',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\DocumentObjectServiceResolver',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 52,
        'endLine' => 52,
        'startColumn' => 3,
        'endColumn' => 64,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementRepository',
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
        'startLine' => 53,
        'endLine' => 53,
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
          'objectResolver' => 
          array (
            'name' => 'objectResolver',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\DocumentObjectServiceResolver',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 52,
            'endLine' => 52,
            'startColumn' => 3,
            'endColumn' => 64,
            'parameterIndex' => 0,
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
            'startLine' => 53,
            'endLine' => 53,
            'startColumn' => 3,
            'endColumn' => 42,
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
 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister\'s ObjectService.
 * @param LoggerInterface               $logger         Logger for diagnostics.
 *
 * @return void
 */',
        'startLine' => 51,
        'endLine' => 56,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementRepository',
        'aliasName' => NULL,
      ),
      'forDocument' => 
      array (
        'name' => 'forDocument',
        'parameters' => 
        array (
          'document' => 
          array (
            'name' => 'document',
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
            'startLine' => 69,
            'endLine' => 69,
            'startColumn' => 30,
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
 * The terms in force for one document, or null when it is not gated.
 *
 * @param string $document The document.
 *
 * @return array<string, mixed>|null The agreement.
 *
 * @throws AgreementStoreUnreadableException When the store could not be read.
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */',
        'startLine' => 69,
        'endLine' => 95,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementRepository',
        'aliasName' => NULL,
      ),
      'acceptanceOf' => 
      array (
        'name' => 'acceptanceOf',
        'parameters' => 
        array (
          'document' => 
          array (
            'name' => 'document',
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
            'startLine' => 109,
            'endLine' => 109,
            'startColumn' => 31,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'person' => 
          array (
            'name' => 'person',
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
            'startLine' => 109,
            'endLine' => 109,
            'startColumn' => 49,
            'endColumn' => 62,
            'parameterIndex' => 1,
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
 * What this person has accepted for this document, if anything.
 *
 * @param string $document The document.
 * @param string $person   Who is asking.
 *
 * @return array<string, mixed>|null The acceptance.
 *
 * @throws AgreementStoreUnreadableException When the store could not be read.
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */',
        'startLine' => 109,
        'endLine' => 130,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementRepository',
        'aliasName' => NULL,
      ),
      'recordAcceptance' => 
      array (
        'name' => 'recordAcceptance',
        'parameters' => 
        array (
          'document' => 
          array (
            'name' => 'document',
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
            'startColumn' => 35,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'person' => 
          array (
            'name' => 'person',
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
            'startColumn' => 53,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'version' => 
          array (
            'name' => 'version',
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
            'startColumn' => 69,
            'endColumn' => 83,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'text' => 
          array (
            'name' => 'text',
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
            'startColumn' => 86,
            'endColumn' => 97,
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
 * Write one acceptance.
 *
 * @param string $document The document.
 * @param string $person   Who accepted.
 * @param string $version  Which version they accepted.
 * @param string $text     The text they were shown.
 *
 * @return array<string, mixed> The stored acceptance.
 *
 * @throws RuntimeException When the write fails, which stops the download.
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */',
        'startLine' => 146,
        'endLine' => 170,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementRepository',
        'aliasName' => NULL,
      ),
      'rowsFor' => 
      array (
        'name' => 'rowsFor',
        'parameters' => 
        array (
          'document' => 
          array (
            'name' => 'document',
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
            'startLine' => 183,
            'endLine' => 183,
            'startColumn' => 27,
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
 * Every row about one document.
 *
 * @param string $document The document.
 *
 * @return array<int, array<string, mixed>> The rows.
 *
 * @throws AgreementStoreUnreadableException When the read failed, as opposed to finding nothing.
 *
 * @spec exclude Read helper behind the public methods.
 */',
        'startLine' => 183,
        'endLine' => 253,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementRepository',
        'aliasName' => NULL,
      ),
      'normalise' => 
      array (
        'name' => 'normalise',
        'parameters' => 
        array (
          'row' => 
          array (
            'name' => 'row',
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
            'startLine' => 264,
            'endLine' => 264,
            'startColumn' => 29,
            'endColumn' => 38,
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
 * Read one OpenRegister row into the flat shape this app uses.
 *
 * @param mixed $row The row.
 *
 * @return array<string, mixed> The row.
 *
 * @spec exclude Shape adapter over an OpenRegister response.
 */',
        'startLine' => 264,
        'endLine' => 280,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementRepository',
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