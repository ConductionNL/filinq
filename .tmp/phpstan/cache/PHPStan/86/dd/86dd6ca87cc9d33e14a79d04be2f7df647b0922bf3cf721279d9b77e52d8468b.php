<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/FinalDocumentRepository.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\FinalDocumentRepository
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-88665a4b06dfdb0562ba290cd854648111d918715363c8b42fb92882c563681b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/FinalDocumentRepository.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
    'shortName' => 'FinalDocumentRepository',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Stores and finds the finalisation record of a document version.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 49,
    'endLine' => 480,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'name' => 'REGISTER',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq\'',
          'attributes' => 
          array (
            'startLine' => 59,
            'endLine' => 59,
            'startTokenPos' => 55,
            'startFilePos' => 1884,
            'endTokenPos' => 55,
            'endFilePos' => 1891,
          ),
        ),
        'docComment' => '/**
 * The OpenRegister register holding Filinq\'s schemas.
 *
 * `filinq`, not `document`: this app declares one register since the
 * consolidation in descriptor v8.0.0.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 59,
        'endLine' => 59,
        'startColumn' => 2,
        'endColumn' => 34,
      ),
      'SCHEMA' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'name' => 'SCHEMA',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'documentVersion\'',
          'attributes' => 
          array (
            'startLine' => 66,
            'endLine' => 66,
            'startTokenPos' => 68,
            'startFilePos' => 1997,
            'endTokenPos' => 68,
            'endFilePos' => 2013,
          ),
        ),
        'docComment' => '/**
 * The schema holding the finalisation records.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 66,
        'endLine' => 66,
        'startColumn' => 2,
        'endColumn' => 41,
      ),
      'STATUS_FINAL' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'name' => 'STATUS_FINAL',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'final\'',
          'attributes' => 
          array (
            'startLine' => 73,
            'endLine' => 73,
            'startTokenPos' => 81,
            'startFilePos' => 2141,
            'endTokenPos' => 81,
            'endFilePos' => 2147,
          ),
        ),
        'docComment' => '/**
 * The final state. Terminal: there is no transition out of it.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 2,
        'endColumn' => 37,
      ),
      'STATUS_DRAFT' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'name' => 'STATUS_DRAFT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'draft\'',
          'attributes' => 
          array (
            'startLine' => 80,
            'endLine' => 80,
            'startTokenPos' => 94,
            'startFilePos' => 2282,
            'endTokenPos' => 94,
            'endFilePos' => 2288,
          ),
        ),
        'docComment' => '/**
 * The draft state. Everything is draft until somebody makes it final.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 80,
        'endLine' => 80,
        'startColumn' => 2,
        'endColumn' => 37,
      ),
    ),
    'immediateProperties' => 
    array (
      'objectResolver' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
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
        'startLine' => 91,
        'endLine' => 91,
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
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
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
            'startLine' => 91,
            'endLine' => 91,
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
            'startLine' => 92,
            'endLine' => 92,
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
 * @param LoggerInterface $logger Logger for diagnostics.
 *
 * @return void
 */',
        'startLine' => 90,
        'endLine' => 95,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'aliasName' => NULL,
      ),
      'findForFile' => 
      array (
        'name' => 'findForFile',
        'parameters' => 
        array (
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
            'startLine' => 112,
            'endLine' => 112,
            'startColumn' => 30,
            'endColumn' => 40,
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
 * Find every finalisation record for one document, newest last.
 *
 * 🔴 Returns null, not an empty array, when the register could not be read.
 * The guard above this fails closed on null, and it can only do that if
 * "no rows" and "no answer" arrive as different values. An unreachable
 * register is exactly when an unnoticed write to a final document is most
 * likely.
 *
 * @param int $fileId The Nextcloud file id.
 *
 * @return array<int, array<string, mixed>>|null The records, or null when the register could not be read.
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */',
        'startLine' => 112,
        'endLine' => 158,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'aliasName' => NULL,
      ),
      'findCurrent' => 
      array (
        'name' => 'findCurrent',
        'parameters' => 
        array (
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
            'startLine' => 175,
            'endLine' => 175,
            'startColumn' => 30,
            'endColumn' => 40,
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
 * Find the record describing a document\'s current content.
 *
 * The current content is the record with an empty `versionLabel`. A record
 * that names a `files_versions` label describes an older set of bytes, and
 * those are frozen by the file store rather than by this guard.
 *
 * @param int $fileId The Nextcloud file id.
 *
 * @return array<string, mixed>|null The record, or null when there is none.
 *
 * @throws RuntimeException When the register could not be read.
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */',
        'startLine' => 175,
        'endLine' => 191,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'aliasName' => NULL,
      ),
      'findByCreator' => 
      array (
        'name' => 'findByCreator',
        'parameters' => 
        array (
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
            'startLine' => 202,
            'endLine' => 202,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Every document record one person created.
 *
 * @param string $userId The user id.
 *
 * @return array<int, array<string, mixed>> Their records.
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
        'startLine' => 202,
        'endLine' => 234,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'aliasName' => NULL,
      ),
      'findByDomain' => 
      array (
        'name' => 'findByDomain',
        'parameters' => 
        array (
          'domain' => 
          array (
            'name' => 'domain',
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
            'startLine' => 251,
            'endLine' => 251,
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
 * Every document record linked to one domain.
 *
 * 🔴 The domain filter is applied in the READING, not in the query.
 * OpenRegister\'s object filters are scalar equality: a filter on `domains`
 * would be compared against an ARRAY of references and would answer the
 * empty set with a confident zero rather than an error. Reading the rows
 * and matching here is slower and correct.
 *
 * @param array<string, mixed> $domain The domain, as register, schema and id.
 *
 * @return array<int, array<string, mixed>> The records on that domain.
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
        'startLine' => 251,
        'endLine' => 293,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'aliasName' => NULL,
      ),
      'sitsIn' => 
      array (
        'name' => 'sitsIn',
        'parameters' => 
        array (
          'record' => 
          array (
            'name' => 'record',
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
            'startLine' => 309,
            'endLine' => 309,
            'startColumn' => 26,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'reference' => 
          array (
            'name' => 'reference',
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
            'startLine' => 309,
            'endLine' => 309,
            'startColumn' => 41,
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
 * Whether one record names the given domain among its own.
 *
 * A domain is matched on all three of register, schema and id. Matching on
 * fewer would put a record in a domain it only half belongs to, and the
 * domains are what decides who may read it.
 *
 * @param array<string, mixed> $record The record.
 * @param array<string, string> $reference The domain, as register, schema and id.
 *
 * @return bool True when the record names it.
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
        'startLine' => 309,
        'endLine' => 327,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'aliasName' => NULL,
      ),
      'sameDomain' => 
      array (
        'name' => 'sameDomain',
        'parameters' => 
        array (
          'candidate' => 
          array (
            'name' => 'candidate',
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
            'startLine' => 339,
            'endLine' => 339,
            'startColumn' => 30,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'reference' => 
          array (
            'name' => 'reference',
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
            'startLine' => 339,
            'endLine' => 339,
            'startColumn' => 48,
            'endColumn' => 63,
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
 * Whether one domain entry is the domain asked for.
 *
 * @param array<string, mixed> $candidate The entry on the record.
 * @param array<string, string> $reference The domain asked for.
 *
 * @return bool True when all three fields match.
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
        'startLine' => 339,
        'endLine' => 348,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'aliasName' => NULL,
      ),
      'findByUuid' => 
      array (
        'name' => 'findByUuid',
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
            'startLine' => 359,
            'endLine' => 359,
            'startColumn' => 29,
            'endColumn' => 40,
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
 * Find one record by its uuid.
 *
 * @param string $uuid The record uuid.
 *
 * @return array<string, mixed>|null The record, or null when there is none.
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */',
        'startLine' => 359,
        'endLine' => 389,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'aliasName' => NULL,
      ),
      'save' => 
      array (
        'name' => 'save',
        'parameters' => 
        array (
          'record' => 
          array (
            'name' => 'record',
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
            'startLine' => 403,
            'endLine' => 403,
            'startColumn' => 23,
            'endColumn' => 35,
            'parameterIndex' => 0,
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
                'startLine' => 403,
                'endLine' => 403,
                'startTokenPos' => 1550,
                'startFilePos' => 12201,
                'endTokenPos' => 1550,
                'endFilePos' => 12204,
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
            'startLine' => 403,
            'endLine' => 403,
            'startColumn' => 38,
            'endColumn' => 57,
            'parameterIndex' => 1,
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
 * Write a finalisation record, creating it when its uuid is not known yet.
 *
 * @param array<string, mixed> $record The record to store.
 * @param string|null $uuid The uuid to write under, or null to create one.
 *
 * @return array<string, mixed> The stored record.
 *
 * @throws RuntimeException When the write fails.
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */',
        'startLine' => 403,
        'endLine' => 438,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
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
            'startLine' => 453,
            'endLine' => 453,
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
 * Read one OpenRegister row into the flat record shape this app uses.
 *
 * OpenRegister answers with an entity, an array, or an array wrapping the
 * data under `object`, depending on the caller and the version. Reading all
 * three here keeps every consumer from re-discovering that.
 *
 * @param mixed $row The row as OpenRegister returned it.
 *
 * @return array<string, mixed> The flat record, including its `uuid`.
 *
 * @spec exclude Shape adapter over an OpenRegister response; no behaviour of its own.
 */',
        'startLine' => 453,
        'endLine' => 479,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
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