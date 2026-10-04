<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/FinalDocumentUnfreezeService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\FinalDocumentUnfreezeService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-d41969e274b962351864b3acbede37213af0bab28d905948cdc9efecb82cf7f5',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/FinalDocumentUnfreezeService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
    'shortName' => 'FinalDocumentUnfreezeService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Unfreezes a final document version, and records that it happened.
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
    'startLine' => 47,
    'endLine' => 262,
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
      'UNFREEZE_GROUP' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
        'name' => 'UNFREEZE_GROUP',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'docudesk-final-document-admins\'',
          'attributes' => 
          array (
            'startLine' => 60,
            'endLine' => 60,
            'startTokenPos' => 60,
            'startFilePos' => 1927,
            'endTokenPos' => 60,
            'endFilePos' => 1958,
          ),
        ),
        'docComment' => '/**
 * The named right that unfreezing needs.
 *
 * The `docudesk-` prefix is the family every group in this app already
 * belongs to (`docudesk-policy-admins`, `docudesk-signing-admins`). Group
 * names are configuration an administrator has already typed on live
 * instances, so the family stays on the old prefix rather than splitting in
 * two when the app id moves.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 60,
        'endLine' => 60,
        'startColumn' => 2,
        'endColumn' => 64,
      ),
    ),
    'immediateProperties' => 
    array (
      'repository' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
        'name' => 'repository',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
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
        'endColumn' => 54,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'finalDocuments' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
        'name' => 'finalDocuments',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\FinalDocumentService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 75,
        'endLine' => 75,
        'startColumn' => 3,
        'endColumn' => 55,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'userSession' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
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
        'startLine' => 76,
        'endLine' => 76,
        'startColumn' => 3,
        'endColumn' => 44,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'groupManager' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
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
        'startLine' => 77,
        'endLine' => 77,
        'startColumn' => 3,
        'endColumn' => 46,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
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
        'startLine' => 78,
        'endLine' => 78,
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
          'repository' => 
          array (
            'name' => 'repository',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\FinalDocumentRepository',
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
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'finalDocuments' => 
          array (
            'name' => 'finalDocuments',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\FinalDocumentService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 75,
            'endLine' => 75,
            'startColumn' => 3,
            'endColumn' => 55,
            'parameterIndex' => 1,
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
            'startLine' => 76,
            'endLine' => 76,
            'startColumn' => 3,
            'endColumn' => 44,
            'parameterIndex' => 2,
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
            'startLine' => 77,
            'endLine' => 77,
            'startColumn' => 3,
            'endColumn' => 46,
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
            'startLine' => 78,
            'endLine' => 78,
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
 * @param FinalDocumentRepository $repository Store of the finalisation records.
 * @param FinalDocumentService $finalDocuments The finalisation service.
 * @param IUserSession $userSession The current user session.
 * @param IGroupManager $groupManager Group membership, for the named right.
 * @param LoggerInterface $logger Logger for diagnostics.
 *
 * @return void
 */',
        'startLine' => 73,
        'endLine' => 81,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
        'aliasName' => NULL,
      ),
      'unfreeze' => 
      array (
        'name' => 'unfreeze',
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
            'startLine' => 95,
            'endLine' => 95,
            'startColumn' => 27,
            'endColumn' => 37,
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
            'startLine' => 95,
            'endLine' => 95,
            'startColumn' => 40,
            'endColumn' => 53,
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
 * Unfreeze a final version, with a reason, leaving a permanent mark.
 *
 * @param int $fileId The Nextcloud file id.
 * @param string $reason Why the version is being unfrozen.
 *
 * @return array<string, mixed> The unfrozen record.
 *
 * @throws RuntimeException When the caller lacks the right, gives no reason, or the version is not final.
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */',
        'startLine' => 95,
        'endLine' => 135,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
        'aliasName' => NULL,
      ),
      'refuseClearingTheMark' => 
      array (
        'name' => 'refuseClearingTheMark',
        'parameters' => 
        array (
          'incoming' => 
          array (
            'name' => 'incoming',
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
            'startLine' => 153,
            'endLine' => 153,
            'startColumn' => 40,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'stored' => 
          array (
            'name' => 'stored',
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
            'startLine' => 153,
            'endLine' => 153,
            'startColumn' => 57,
            'endColumn' => 69,
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
 * Refuse an attempt to clear the mark.
 *
 * The mark is what makes the exception honest. A product whose scar can be
 * rubbed out has the silent exception this one was written to avoid, so
 * clearing it is refused rather than quietly ignored.
 *
 * @param array<string, mixed> $incoming The record a caller wants to write.
 * @param array<string, mixed> $stored The record as it stands.
 *
 * @return void
 *
 * @throws RuntimeException When the incoming record would clear the mark.
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */',
        'startLine' => 153,
        'endLine' => 168,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
        'aliasName' => NULL,
      ),
      'mayUnfreeze' => 
      array (
        'name' => 'mayUnfreeze',
        'parameters' => 
        array (
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
 * Whether the acting user holds the named right.
 *
 * @return bool True when the acting user may unfreeze.
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */',
        'startLine' => 177,
        'endLine' => 189,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
        'aliasName' => NULL,
      ),
      'requireRight' => 
      array (
        'name' => 'requireRight',
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
 * Require the named right, naming it in the refusal.
 *
 * @return array{id: string, name: string} The acting administrator.
 *
 * @throws RuntimeException When the caller does not hold the right.
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */',
        'startLine' => 200,
        'endLine' => 220,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
        'aliasName' => NULL,
      ),
      'writeAudit' => 
      array (
        'name' => 'writeAudit',
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
            'startLine' => 247,
            'endLine' => 247,
            'startColumn' => 30,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actor' => 
          array (
            'name' => 'actor',
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
            'startLine' => 247,
            'endLine' => 247,
            'startColumn' => 45,
            'endColumn' => 56,
            'parameterIndex' => 1,
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
            'startLine' => 247,
            'endLine' => 247,
            'startColumn' => 59,
            'endColumn' => 72,
            'parameterIndex' => 2,
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
            'startLine' => 247,
            'endLine' => 247,
            'startColumn' => 75,
            'endColumn' => 88,
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
 * Record the unfreeze where an archivist will find it.
 *
 * The audit entry itself is OpenRegister\'s, written by the save above:
 * `documentVersion` is an OpenRegister object, and the immutable audit trail
 * records every change to one with the acting user and the moment, while
 * `unfrozenBy`, `unfrozenAt` and `unfrozenReason` carry the three facts on
 * the record itself. Filinq does not keep a second audit trail beside it
 * (ADR-022), and the write is fail-closed because `save()` throws: an
 * unfreeze that could not be recorded did not happen.
 *
 * What is added here is the operational signal. An unfreeze is rare and
 * consequential, so it belongs in the server log at warning level too,
 * where an administrator watching the instance sees it without querying the
 * register.
 *
 * @param array<string, mixed> $record The unfrozen record, as stored.
 * @param array{id: string, name: string} $actor The acting administrator.
 * @param string $reason Why the version was unfrozen.
 * @param string $moment When it happened (ISO 8601).
 *
 * @return void
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */',
        'startLine' => 247,
        'endLine' => 261,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
        'currentClassName' => 'OCA\\Filinq\\Service\\FinalDocumentUnfreezeService',
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