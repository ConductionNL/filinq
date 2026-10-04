<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/SigningFolderService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\SigningFolderService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-04f1d9f3f012639910fe454b16a6ffae74bcddcc3030e754535625d82f4f821e',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/SigningFolderService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\SigningFolderService',
    'shortName' => 'SigningFolderService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * The signing folder: a query, not a list somebody keeps.
 *
 * A wethouder signing forty decisions on a Friday afternoon opens one folder,
 * not forty cases. The folder is read at the moment it is asked for and is
 * never stored, so a request cancelled elsewhere is gone the next time it is
 * opened without anything having to rebuild anything.
 *
 * What a signature means and what it proves is answered in one place already,
 * by SigningVerificationService (filinq#1121). The folder shows the documents
 * and reuses the signing path; it says nothing of its own about what the
 * resulting signature is worth.
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
    'startLine' => 50,
    'endLine' => 389,
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
      'OPEN_STATUSES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'name' => 'OPEN_STATUSES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'PENDING\', \'IN_PROGRESS\']',
          'attributes' => 
          array (
            'startLine' => 57,
            'endLine' => 57,
            'startTokenPos' => 45,
            'startFilePos' => 1866,
            'endTokenPos' => 50,
            'endFilePos' => 1891,
          ),
        ),
        'docComment' => '/**
 * The statuses a request must be in to hold a signature still to be given.
 *
 * @var list<string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 2,
        'endColumn' => 58,
      ),
    ),
    'immediateProperties' => 
    array (
      'signingService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'name' => 'signingService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\SigningService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 69,
        'endLine' => 69,
        'startColumn' => 3,
        'endColumn' => 49,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'actorResolver' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'name' => 'actorResolver',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\SigningActorResolver',
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
        'endColumn' => 54,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'mandateService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'name' => 'mandateService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\SigningMandateService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 71,
        'endLine' => 71,
        'startColumn' => 3,
        'endColumn' => 56,
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
          'signingService' => 
          array (
            'name' => 'signingService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\SigningService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 69,
            'endLine' => 69,
            'startColumn' => 3,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actorResolver' => 
          array (
            'name' => 'actorResolver',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\SigningActorResolver',
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
            'endColumn' => 54,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'mandateService' => 
          array (
            'name' => 'mandateService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\SigningMandateService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 71,
            'endLine' => 71,
            'startColumn' => 3,
            'endColumn' => 56,
            'parameterIndex' => 2,
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
 * @param SigningService $signingService The per-request signing path, reused unchanged.
 * @param SigningActorResolver $actorResolver Finds the caller\'s pending signer record.
 * @param SigningMandateService $mandateService Applies the consuming app\'s mandate.
 *
 * @return void
 */',
        'startLine' => 68,
        'endLine' => 74,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'aliasName' => NULL,
      ),
      'folder' => 
      array (
        'name' => 'folder',
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
            'startLine' => 93,
            'endLine' => 93,
            'startColumn' => 25,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'limit' => 
          array (
            'name' => 'limit',
            'default' => 
            array (
              'code' => '50',
              'attributes' => 
              array (
                'startLine' => 93,
                'endLine' => 93,
                'startTokenPos' => 115,
                'startFilePos' => 3160,
                'endTokenPos' => 115,
                'endFilePos' => 3161,
              ),
            ),
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
            'startLine' => 93,
            'endLine' => 93,
            'startColumn' => 41,
            'endColumn' => 55,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'offset' => 
          array (
            'name' => 'offset',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 93,
                'endLine' => 93,
                'startTokenPos' => 124,
                'startFilePos' => 3178,
                'endTokenPos' => 124,
                'endFilePos' => 3178,
              ),
            ),
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
            'startLine' => 93,
            'endLine' => 93,
            'startColumn' => 58,
            'endColumn' => 72,
            'parameterIndex' => 2,
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
 * Everything pending for one signer, in one folder.
 *
 * Ordered by deadline first and age second. A document with no deadline
 * sorts after every document that has one: an undated request is not
 * urgent, and putting it first would push the dated ones off the screen.
 *
 * @param string $userId The signer asking.
 * @param int $limit Page size (1..200).
 * @param int $offset Page offset.
 *
 * @return array{total: int, limit: int, offset: int, entries: list<array<string, mixed>>}
 *
 * @throws RuntimeException When there is no authenticated user.
 *
 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
 */',
        'startLine' => 93,
        'endLine' => 118,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'aliasName' => NULL,
      ),
      'signSelection' => 
      array (
        'name' => 'signSelection',
        'parameters' => 
        array (
          'requestIds' => 
          array (
            'name' => 'requestIds',
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
            'startLine' => 143,
            'endLine' => 143,
            'startColumn' => 32,
            'endColumn' => 48,
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
            'startLine' => 143,
            'endLine' => 143,
            'startColumn' => 51,
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
 * Sign a selection from the folder, one pass, one record per document.
 *
 * Every document goes through SigningService::sign(), so each gets its own
 * signature, artifact and audit entry, and meets the same level floors and
 * honest-completion gates it would meet as a single request. A refusal is
 * reported against its own document and the pass carries on.
 *
 * The pass is resumable because it holds no state of its own: a document
 * already signed no longer has a pending signer record, so a second pass
 * over the same selection reports it as settled and signs the rest. No
 * document is ever left half signed, because a document is one sign()
 * call and that call either completes or throws.
 *
 * @param array<int, string> $requestIds The selected signing requests.
 * @param string $userId The signer asking.
 *
 * @return array{signed: int, refused: int, results: list<array<string, mixed>>}
 *
 * @throws RuntimeException When there is no authenticated user.
 *
 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
 */',
        'startLine' => 143,
        'endLine' => 166,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'aliasName' => NULL,
      ),
      'signOne' => 
      array (
        'name' => 'signOne',
        'parameters' => 
        array (
          'requestId' => 
          array (
            'name' => 'requestId',
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
            'startLine' => 178,
            'endLine' => 178,
            'startColumn' => 27,
            'endColumn' => 43,
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
            'startLine' => 178,
            'endLine' => 178,
            'startColumn' => 46,
            'endColumn' => 59,
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
 * Sign one document of the selection, reporting its own refusal.
 *
 * @param string $requestId The signing request.
 * @param string $userId The signer asking.
 *
 * @return array<string, mixed> The per-document result.
 *
 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
 */',
        'startLine' => 178,
        'endLine' => 215,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'aliasName' => NULL,
      ),
      'refusal' => 
      array (
        'name' => 'refusal',
        'parameters' => 
        array (
          'requestId' => 
          array (
            'name' => 'requestId',
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
            'startLine' => 227,
            'endLine' => 227,
            'startColumn' => 27,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'reason' => 
          array (
            'name' => 'reason',
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
            'startLine' => 227,
            'endLine' => 227,
            'startColumn' => 46,
            'endColumn' => 59,
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
 * A refusal against one document.
 *
 * @param string $requestId The signing request.
 * @param string $reason Why this document was not signed.
 *
 * @return array<string, mixed>
 *
 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
 */',
        'startLine' => 227,
        'endLine' => 234,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'aliasName' => NULL,
      ),
      'entryFor' => 
      array (
        'name' => 'entryFor',
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
            'startLine' => 246,
            'endLine' => 246,
            'startColumn' => 28,
            'endColumn' => 41,
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
            'startLine' => 246,
            'endLine' => 246,
            'startColumn' => 44,
            'endColumn' => 57,
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
 * The folder entry for one request, or null when it does not belong in the folder.
 *
 * @param array<string, mixed> $request The signing request.
 * @param string $userId The signer asking.
 *
 * @return array<string, mixed>|null
 *
 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
 */',
        'startLine' => 246,
        'endLine' => 268,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'aliasName' => NULL,
      ),
      'project' => 
      array (
        'name' => 'project',
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
            'startLine' => 285,
            'endLine' => 285,
            'startColumn' => 27,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'signerId' => 
          array (
            'name' => 'signerId',
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
            'startLine' => 285,
            'endLine' => 285,
            'startColumn' => 43,
            'endColumn' => 58,
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
 * Project one request onto a folder entry.
 *
 * The projection is an allow-list, deliberately. A consuming app that
 * sends more than filinq asked for must not have its case fields copied
 * into a filinq surface by accident: the entry holds a reference to the
 * record and nothing out of it.
 *
 * @param array<string, mixed> $request The signing request.
 * @param string $signerId The caller\'s pending signer record.
 *
 * @return array<string, mixed>
 *
 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
 */',
        'startLine' => 285,
        'endLine' => 310,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'aliasName' => NULL,
      ),
      'requestedAt' => 
      array (
        'name' => 'requestedAt',
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
            'startLine' => 324,
            'endLine' => 324,
            'startColumn' => 31,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * When the signature was asked for.
 *
 * OpenRegister carries its own timestamps under `@self`; older rows read
 * back with a flat `created`. Both are read, neither is required.
 *
 * @param array<string, mixed> $request The signing request.
 *
 * @return string An ISO 8601 timestamp, or \'\'.
 *
 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
 */',
        'startLine' => 324,
        'endLine' => 332,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'aliasName' => NULL,
      ),
      'compareEntries' => 
      array (
        'name' => 'compareEntries',
        'parameters' => 
        array (
          'left' => 
          array (
            'name' => 'left',
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
            'startLine' => 352,
            'endLine' => 352,
            'startColumn' => 34,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'right' => 
          array (
            'name' => 'right',
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
            'startLine' => 352,
            'endLine' => 352,
            'startColumn' => 47,
            'endColumn' => 58,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Deadline first, age second.
 *
 * @param array<string, mixed> $left The first entry.
 * @param array<string, mixed> $right The second entry.
 *
 * @return int
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateMethod) Called as the callable array
 * `[$this, \'compareEntries\']` from usort() at line 109. PHPMD resolves only
 * direct `$this->method()` calls, so a callable-array reference reads to it
 * as no caller at all — a false positive, verified by grep.
 *
 * @psalm-suppress UnusedReturnValue usort() consumes the comparison; psalm
 * reads the callable array no better than PHPMD does.
 *
 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
 */',
        'startLine' => 352,
        'endLine' => 388,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
        'currentClassName' => 'OCA\\Filinq\\Service\\SigningFolderService',
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