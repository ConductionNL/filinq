<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DomainFolderService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\DomainFolderService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-ee305c0992cacae5bdf56736496b362698befc700df5887eda9ca91d3e4c0687',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DomainFolderService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\DomainFolderService',
    'shortName' => 'DomainFolderService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Creates a domain\'s folder and reconciles who may reach it.
 *
 * 🔴 THE HARD REQUIREMENT IS NOT THE CORRECTING, IT IS THE NOT CLAIMING.
 * REQ-CDF-02\'s third scenario is a mount that REFUSES a permission change: the
 * job must report the folder, the permission and the reason, and must not claim
 * success. A reconciler that swallows a refusal and reports "reconciled" is
 * worse than none, because somebody reads that line and stops looking, while a
 * group that should have lost access still has it.
 *
 * So every outcome here is one of three, never two: corrected, already right,
 * or REFUSED WITH A REASON. There is no fourth bucket for "tried something".
 *
 * 🔑 A PIN IS A DECISION SOMEBODY RECORDED, NOT A SKIP. A folder pinned out of
 * reconciliation is reported as pinned, with the reason, rather than quietly
 * omitted: the whole point of pinning is that the next person reads why.
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 49,
    'endLine' => 295,
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
      'ROOT' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'name' => 'ROOT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'Filinq\'',
          'attributes' => 
          array (
            'startLine' => 54,
            'endLine' => 54,
            'startTokenPos' => 55,
            'startFilePos' => 1785,
            'endTokenPos' => 55,
            'endFilePos' => 1792,
          ),
        ),
        'docComment' => '/**
 * Where a domain\'s folder lives.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 54,
        'endLine' => 54,
        'startColumn' => 2,
        'endColumn' => 30,
      ),
      'PIN_KEY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'name' => 'PIN_KEY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'reconciliationPinnedReason\'',
          'attributes' => 
          array (
            'startLine' => 59,
            'endLine' => 59,
            'startTokenPos' => 68,
            'startFilePos' => 1889,
            'endTokenPos' => 68,
            'endFilePos' => 1916,
          ),
        ),
        'docComment' => '/**
 * The key a domain carries to opt out of reconciliation.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 59,
        'endLine' => 59,
        'startColumn' => 2,
        'endColumn' => 53,
      ),
      'STATE_IN_STEP' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'name' => 'STATE_IN_STEP',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'inStep\'',
          'attributes' => 
          array (
            'startLine' => 64,
            'endLine' => 64,
            'startTokenPos' => 81,
            'startFilePos' => 2009,
            'endTokenPos' => 81,
            'endFilePos' => 2016,
          ),
        ),
        'docComment' => '/**
 * The folder matched what the domain declares.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 64,
        'endLine' => 64,
        'startColumn' => 2,
        'endColumn' => 39,
      ),
      'STATE_CORRECTED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'name' => 'STATE_CORRECTED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'corrected\'',
          'attributes' => 
          array (
            'startLine' => 69,
            'endLine' => 69,
            'startTokenPos' => 94,
            'startFilePos' => 2105,
            'endTokenPos' => 94,
            'endFilePos' => 2115,
          ),
        ),
        'docComment' => '/**
 * The folder differed and was corrected.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 69,
        'endLine' => 69,
        'startColumn' => 2,
        'endColumn' => 44,
      ),
      'STATE_REFUSED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'name' => 'STATE_REFUSED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'refused\'',
          'attributes' => 
          array (
            'startLine' => 74,
            'endLine' => 74,
            'startTokenPos' => 107,
            'startFilePos' => 2211,
            'endTokenPos' => 107,
            'endFilePos' => 2219,
          ),
        ),
        'docComment' => '/**
 * The folder differed and could not be corrected.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 74,
        'endLine' => 74,
        'startColumn' => 2,
        'endColumn' => 40,
      ),
      'STATE_PINNED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'name' => 'STATE_PINNED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'pinned\'',
          'attributes' => 
          array (
            'startLine' => 79,
            'endLine' => 79,
            'startTokenPos' => 120,
            'startFilePos' => 2335,
            'endTokenPos' => 120,
            'endFilePos' => 2342,
          ),
        ),
        'docComment' => '/**
 * The folder was left alone because somebody pinned it, with a reason.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 79,
        'endLine' => 79,
        'startColumn' => 2,
        'endColumn' => 38,
      ),
    ),
    'immediateProperties' => 
    array (
      'rootFolder' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'name' => 'rootFolder',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\Files\\IRootFolder',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 89,
        'endLine' => 89,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'gateway' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'name' => 'gateway',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\DomainFolderGateway',
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
        'endColumn' => 47,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
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
        'startLine' => 91,
        'endLine' => 91,
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
          'rootFolder' => 
          array (
            'name' => 'rootFolder',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Files\\IRootFolder',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 89,
            'endLine' => 89,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'gateway' => 
          array (
            'name' => 'gateway',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\DomainFolderGateway',
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
            'endColumn' => 47,
            'parameterIndex' => 1,
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
            'startLine' => 91,
            'endLine' => 91,
            'startColumn' => 3,
            'endColumn' => 42,
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
 * Collaborators.
 *
 * @param IRootFolder         $rootFolder Where folders are made.
 * @param DomainFolderGateway $gateway    Reads and writes the folder\'s group access.
 * @param LoggerInterface     $logger     Structured logger.
 */',
        'startLine' => 88,
        'endLine' => 93,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'aliasName' => NULL,
      ),
      'pathFor' => 
      array (
        'name' => 'pathFor',
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
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 26,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The folder path a domain owns.
 *
 * @param array<string, mixed> $domain The domain.
 *
 * @return string The path.
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
        'startLine' => 104,
        'endLine' => 108,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'aliasName' => NULL,
      ),
      'ensureFolder' => 
      array (
        'name' => 'ensureFolder',
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
            'startLine' => 120,
            'endLine' => 120,
            'startColumn' => 31,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'owner' => 
          array (
            'name' => 'owner',
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
            'startLine' => 120,
            'endLine' => 120,
            'startColumn' => 46,
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
 * Create the domain\'s folder if it is not there yet.
 *
 * @param array<string, mixed> $domain The domain.
 * @param string               $owner  The user whose storage holds it.
 *
 * @return array{created: bool, path: string, error: ?string} What happened.
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
        'startLine' => 120,
        'endLine' => 146,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'aliasName' => NULL,
      ),
      'reconcile' => 
      array (
        'name' => 'reconcile',
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
            'startLine' => 168,
            'endLine' => 168,
            'startColumn' => 28,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'owner' => 
          array (
            'name' => 'owner',
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
            'startLine' => 168,
            'endLine' => 168,
            'startColumn' => 43,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Bring the folder\'s group access back to what the domain declares.
 *
 * @param array<string, mixed> $domain The domain, carrying `groups`.
 * @param string               $owner  The user whose storage holds it.
 *
 * @return array{state: string, path: string, granted: array<int, string>, revoked: array<int, string>,
 *               refused: array<int, array{group: string, action: string, reason: string}>,
 *               pinnedReason: ?string} The outcome.
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity) Each branch here is one of the
 * outcomes REQ-CDF-02 names: pinned, unreadable, granted, revoked, refused,
 * already in step. Collapsing any two of them is exactly the "partly
 * reconciled reported as reconciled" this method exists to refuse.
 *
 * @SuppressWarnings(PHPMD.NPathComplexity) Same reason: the paths are the
 * outcome matrix, not nesting that could be flattened.
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
        'startLine' => 168,
        'endLine' => 240,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'aliasName' => NULL,
      ),
      'declaredGroups' => 
      array (
        'name' => 'declaredGroups',
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
            'startLine' => 249,
            'endLine' => 249,
            'startColumn' => 34,
            'endColumn' => 46,
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
 * The groups a domain declares, as a clean list.
 *
 * @param array<string, mixed> $domain The domain.
 *
 * @return array<int, string> The group ids.
 */',
        'startLine' => 249,
        'endLine' => 264,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'aliasName' => NULL,
      ),
      'outcome' => 
      array (
        'name' => 'outcome',
        'parameters' => 
        array (
          'state' => 
          array (
            'name' => 'state',
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
            'startLine' => 279,
            'endLine' => 279,
            'startColumn' => 3,
            'endColumn' => 15,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'path' => 
          array (
            'name' => 'path',
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
            'startLine' => 280,
            'endLine' => 280,
            'startColumn' => 3,
            'endColumn' => 14,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'granted' => 
          array (
            'name' => 'granted',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 281,
                'endLine' => 281,
                'startTokenPos' => 1232,
                'startFilePos' => 9358,
                'endTokenPos' => 1233,
                'endFilePos' => 9359,
              ),
            ),
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
            'startLine' => 281,
            'endLine' => 281,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'revoked' => 
          array (
            'name' => 'revoked',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 282,
                'endLine' => 282,
                'startTokenPos' => 1242,
                'startFilePos' => 9381,
                'endTokenPos' => 1243,
                'endFilePos' => 9382,
              ),
            ),
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
            'startLine' => 282,
            'endLine' => 282,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'refused' => 
          array (
            'name' => 'refused',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 283,
                'endLine' => 283,
                'startTokenPos' => 1252,
                'startFilePos' => 9404,
                'endTokenPos' => 1253,
                'endFilePos' => 9405,
              ),
            ),
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
            'startLine' => 283,
            'endLine' => 283,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 4,
            'isOptional' => true,
          ),
          'pinnedReason' => 
          array (
            'name' => 'pinnedReason',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 284,
                'endLine' => 284,
                'startTokenPos' => 1263,
                'startFilePos' => 9434,
                'endTokenPos' => 1263,
                'endFilePos' => 9437,
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
            'startLine' => 284,
            'endLine' => 284,
            'startColumn' => 3,
            'endColumn' => 30,
            'parameterIndex' => 5,
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
 * One outcome, in the one shape every caller reads.
 *
 * @param string                                                        $state        One of the STATE_ constants.
 * @param string                                                        $path         The folder.
 * @param array<int, string>                                            $granted      Groups given access.
 * @param array<int, string>                                            $revoked      Groups whose access was removed.
 * @param array<int, array{group: string, action: string, reason: string}> $refused    What could not be done, and why.
 * @param string|null                                                   $pinnedReason Why it was pinned out.
 *
 * @return array<string, mixed> The outcome.
 */',
        'startLine' => 278,
        'endLine' => 294,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DomainFolderService',
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