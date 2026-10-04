<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Repair/ConsolidateRegisters.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Repair\ConsolidateRegisters
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-c45508549f33c2defec3cc93582dceea38cc0c62aecf667f89f0b65a254c2794',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Repair/ConsolidateRegisters.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Repair',
    'name' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
    'shortName' => 'ConsolidateRegisters',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Moves this app\'s objects out of five registers and into one.
 *
 * THREE CLASSES, AND THE SPLIT IS ALONG THE LINE THAT MATTERS FOR REVIEW.
 * ConsolidateRegistersGateway holds every statement this migration is capable
 * of issuing and nothing else, so the DELETEs can be read in one sitting;
 * ConsolidateRegistersDecisions holds the predicates that authorise them, with
 * no database in sight, so each one can be tested on its own; and this class
 * holds the sequencing that puts the two together. A single class would have
 * mixed all three, which on the riskiest step in the app is the wrong economy.
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq\'s five
 *  OpenRegister registers into one. Pointing this at an existing spec would
 *  report conformance to a requirement that says nothing about it.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 109,
    'endLine' => 650,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'OCP\\Migration\\IRepairStep',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'SOURCE_SLUGS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'name' => 'SOURCE_SLUGS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'consent\', \'signing\', \'templates\', \'document\', \'dossier\', \'docudesk\']',
          'attributes' => 
          array (
            'startLine' => 135,
            'endLine' => 142,
            'startTokenPos' => 54,
            'startFilePos' => 7329,
            'endTokenPos' => 74,
            'endFilePos' => 7414,
          ),
        ),
        'docComment' => '/**
 * Every register this app used to declare.
 *
 * ALL OF THEM, OR NONE. `templateVersion` objects sit in `templates` while
 * the `template` objects that own them sit there too, and `signerRecord`
 * rows point at `signingRequest` rows in the same register. Consolidating
 * some and not others would leave references crossing a register boundary
 * the app no longer addresses — the half-migrated state is worse than
 * either end.
 *
 * `docudesk` was added after the first five. It is the register named for
 * this app\'s OLD APP ID, and it was initially left out on the grounds that
 * it was "not one of the five" — which was true and beside the point: it
 * holds 200 objects of this app\'s own data, so leaving it standing meant
 * "five registers into one" quietly shipped an app that owned two. Measured
 * on the reference instance: consent 20, signing 12, templates 11,
 * document 162, dossier 57, docudesk 200 — 462 objects in total.
 *
 * It is listed LAST on purpose. The first five are this app\'s own declared
 * registers and move as a set; `docudesk` is the legacy one, so a partial
 * failure that stops before it leaves the coherent five already together.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 135,
        'endLine' => 142,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
      'TARGET_SLUG' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'name' => 'TARGET_SLUG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq\'',
          'attributes' => 
          array (
            'startLine' => 154,
            'endLine' => 154,
            'startTokenPos' => 87,
            'startFilePos' => 7784,
            'endTokenPos' => 87,
            'endFilePos' => 7791,
          ),
        ),
        'docComment' => '/**
 * The single register they collapse into.
 *
 * This is the slug `x-openregister.app` names in the register JSON, which is
 * what ImportHandler\'s autoCreateRegisterIfApplication() resolves a
 * `type: application` configuration against — that field IS a register slug,
 * not an attribution label.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 154,
        'endLine' => 154,
        'startColumn' => 2,
        'endColumn' => 37,
      ),
    ),
    'immediateProperties' => 
    array (
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
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
        'startLine' => 168,
        'endLine' => 168,
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
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'name' => 'gateway',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 169,
        'endLine' => 169,
        'startColumn' => 3,
        'endColumn' => 55,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'decisions' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'name' => 'decisions',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
            'isIdentifier' => false,
          ),
        ),
        'default' => 
        array (
          'code' => 'new \\OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions()',
          'attributes' => 
          array (
            'startLine' => 170,
            'endLine' => 170,
            'startTokenPos' => 127,
            'startFilePos' => 8449,
            'endTokenPos' => 131,
            'endFilePos' => 8483,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 170,
        'endLine' => 170,
        'startColumn' => 3,
        'endColumn' => 97,
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
            'startLine' => 168,
            'endLine' => 168,
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
                'name' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersGateway',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 169,
            'endLine' => 169,
            'startColumn' => 3,
            'endColumn' => 55,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'decisions' => 
          array (
            'name' => 'decisions',
            'default' => 
            array (
              'code' => 'new \\OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions()',
              'attributes' => 
              array (
                'startLine' => 170,
                'endLine' => 170,
                'startTokenPos' => 127,
                'startFilePos' => 8449,
                'endTokenPos' => 131,
                'endFilePos' => 8483,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 170,
            'endLine' => 170,
            'startColumn' => 3,
            'endColumn' => 97,
            'parameterIndex' => 2,
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
 * @param LoggerInterface $logger Logger.
 * @param ConsolidateRegistersGateway $gateway Every statement this step can issue.
 * @param ConsolidateRegistersDecisions $decisions The pure predicates.
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq\'s five
 *  OpenRegister registers into one. Pointing this at an existing spec would
 *  report conformance to a requirement that says nothing about it.
 */',
        'startLine' => 167,
        'endLine' => 172,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'aliasName' => NULL,
      ),
      'getName' => 
      array (
        'name' => 'getName',
        'parameters' => 
        array (
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
 * Step name shown by `occ maintenance:repair`.
 *
 * @return string
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq\'s five
 *  OpenRegister registers into one. Pointing this at an existing spec would
 *  report conformance to a requirement that says nothing about it.
 */',
        'startLine' => 183,
        'endLine' => 185,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'aliasName' => NULL,
      ),
      'run' => 
      array (
        'name' => 'run',
        'parameters' => 
        array (
          'output' => 
          array (
            'name' => 'output',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Migration\\IOutput',
                'isIdentifier' => false,
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
            'startColumn' => 22,
            'endColumn' => 36,
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
 * Move every movable object out of the five registers and into `filinq`.
 *
 * Reads first, decides second, writes third. The three reads that gate the
 * whole step — the register ids, the table list, the target\'s shards — each
 * return null on failure and each stop the step, because a step that cannot
 * see the install must not act on it.
 *
 * @param IOutput $output Repair output.
 *
 * @return void
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq\'s five
 *  OpenRegister registers into one. Pointing this at an existing spec would
 *  report conformance to a requirement that says nothing about it.
 */',
        'startLine' => 203,
        'endLine' => 239,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'aliasName' => NULL,
      ),
      'preflight' => 
      array (
        'name' => 'preflight',
        'parameters' => 
        array (
          'output' => 
          array (
            'name' => 'output',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Migration\\IOutput',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 253,
            'endLine' => 253,
            'startColumn' => 29,
            'endColumn' => 43,
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
 * Everything the step must know before it may write anything.
 *
 * Returns null when the step must not proceed — an unreadable register
 * table, or a target register/shard set the import has not created yet.
 * Both are reported through `$output` before the null comes back, because a
 * step that stops silently is indistinguishable from one that finished.
 *
 * @param IOutput $output Repair output, for the early-return messages.
 *
 * @return array{ids: array<string, int>, targetId: int, names: array<int, string>, targetTables: array<int, string>}|null
 */',
        'startLine' => 253,
        'endLine' => 298,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'aliasName' => NULL,
      ),
      'consolidateRegister' => 
      array (
        'name' => 'consolidateRegister',
        'parameters' => 
        array (
          'sourceId' => 
          array (
            'name' => 'sourceId',
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
            'startLine' => 312,
            'endLine' => 312,
            'startColumn' => 3,
            'endColumn' => 15,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'targetId' => 
          array (
            'name' => 'targetId',
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
            'startLine' => 313,
            'endLine' => 313,
            'startColumn' => 3,
            'endColumn' => 15,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'names' => 
          array (
            'name' => 'names',
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
            'startLine' => 314,
            'endLine' => 314,
            'startColumn' => 3,
            'endColumn' => 14,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'targetTables' => 
          array (
            'name' => 'targetTables',
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
            'startLine' => 315,
            'endLine' => 315,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'tally' => 
          array (
            'name' => 'tally',
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
            'byRef' => true,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 316,
            'endLine' => 316,
            'startColumn' => 3,
            'endColumn' => 15,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Move every (schema) pair of one source register.
 *
 * @param int $sourceId The register being emptied.
 * @param int $targetId The register being filled.
 * @param array<int, string> $names Every table name the database reported.
 * @param array<int, string> $targetTables Schema id => target table name.
 * @param array<string, int> $tally Running totals, mutated in place.
 *
 * @return void
 */',
        'startLine' => 311,
        'endLine' => 350,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'aliasName' => NULL,
      ),
      'movePair' => 
      array (
        'name' => 'movePair',
        'parameters' => 
        array (
          'sourceTable' => 
          array (
            'name' => 'sourceTable',
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
            'startLine' => 368,
            'endLine' => 368,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'targetTable' => 
          array (
            'name' => 'targetTable',
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
            'startLine' => 369,
            'endLine' => 369,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'sourceId' => 
          array (
            'name' => 'sourceId',
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
            'startLine' => 370,
            'endLine' => 370,
            'startColumn' => 3,
            'endColumn' => 15,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'targetId' => 
          array (
            'name' => 'targetId',
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
            'startLine' => 371,
            'endLine' => 371,
            'startColumn' => 3,
            'endColumn' => 15,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'schemaId' => 
          array (
            'name' => 'schemaId',
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
            'startLine' => 372,
            'endLine' => 372,
            'startColumn' => 3,
            'endColumn' => 15,
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
 * Move one (register, schema) pair, insert-verify-delete, batch by batch.
 *
 * @param string $sourceTable The table rows leave.
 * @param string $targetTable The table rows arrive in.
 * @param int $sourceId The source register id.
 * @param int $targetId The target register id, written into `_register`.
 * @param int $schemaId The schema id both tables belong to.
 *
 * @return array{moved: int, conflicts: int, refused: int}
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity) Every branch here is a
 *  distinct refusal condition; collapsing them would collapse the reasons.
 * @SuppressWarnings(PHPMD.NPathComplexity) As above.
 */',
        'startLine' => 367,
        'endLine' => 437,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'aliasName' => NULL,
      ),
      'namesAreOurs' => 
      array (
        'name' => 'namesAreOurs',
        'parameters' => 
        array (
          'sourceTable' => 
          array (
            'name' => 'sourceTable',
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
            'startLine' => 451,
            'endLine' => 451,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'targetTable' => 
          array (
            'name' => 'targetTable',
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
            'startLine' => 452,
            'endLine' => 452,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'sourceId' => 
          array (
            'name' => 'sourceId',
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
            'startLine' => 453,
            'endLine' => 453,
            'startColumn' => 3,
            'endColumn' => 15,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'targetId' => 
          array (
            'name' => 'targetId',
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
            'startLine' => 454,
            'endLine' => 454,
            'startColumn' => 3,
            'endColumn' => 15,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'schemaId' => 
          array (
            'name' => 'schemaId',
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
            'startLine' => 455,
            'endLine' => 455,
            'startColumn' => 3,
            'endColumn' => 15,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Both table names must survive the shape check against their own pair.
 *
 * @param string $sourceTable Source table name.
 * @param string $targetTable Target table name.
 * @param int $sourceId Source register id.
 * @param int $targetId Target register id.
 * @param int $schemaId Schema id.
 *
 * @return bool True when both are safe to interpolate.
 */',
        'startLine' => 450,
        'endLine' => 480,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'aliasName' => NULL,
      ),
      'agreedColumns' => 
      array (
        'name' => 'agreedColumns',
        'parameters' => 
        array (
          'sourceTable' => 
          array (
            'name' => 'sourceTable',
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
            'startLine' => 490,
            'endLine' => 490,
            'startColumn' => 33,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'targetTable' => 
          array (
            'name' => 'targetTable',
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
            'startLine' => 490,
            'endLine' => 490,
            'startColumn' => 54,
            'endColumn' => 72,
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
 * The column list both tables agree on, or null when they do not.
 *
 * @param string $sourceTable Source table name.
 * @param string $targetTable Target table name.
 *
 * @return array<int, string>|null Columns to carry, `_id` excluded.
 */',
        'startLine' => 490,
        'endLine' => 530,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'aliasName' => NULL,
      ),
      'moveBatches' => 
      array (
        'name' => 'moveBatches',
        'parameters' => 
        array (
          'sourceTable' => 
          array (
            'name' => 'sourceTable',
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
            'startLine' => 549,
            'endLine' => 549,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'targetTable' => 
          array (
            'name' => 'targetTable',
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
            'startLine' => 550,
            'endLine' => 550,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'targetId' => 
          array (
            'name' => 'targetId',
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
            'startLine' => 551,
            'endLine' => 551,
            'startColumn' => 3,
            'endColumn' => 15,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'columns' => 
          array (
            'name' => 'columns',
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
            'startLine' => 552,
            'endLine' => 552,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'uuids' => 
          array (
            'name' => 'uuids',
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
            'startLine' => 553,
            'endLine' => 553,
            'startColumn' => 3,
            'endColumn' => 14,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Insert, verify, then delete — one batch at a time.
 *
 * The ordering is the whole safety property. A batch whose insert fails, or
 * whose verification does not reconcile, stops the pair with the source
 * rows still in place: the migration is then INCOMPLETE, which is
 * recoverable, rather than PARTIAL, which is not.
 *
 * @param string $sourceTable The table rows leave.
 * @param string $targetTable The table rows arrive in.
 * @param int $targetId Written into `_register` on the way across.
 * @param array<int, string> $columns Columns to carry.
 * @param array<int, string> $uuids The movable uuids.
 *
 * @return int How many rows were moved AND confirmed.
 */',
        'startLine' => 548,
        'endLine' => 606,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'aliasName' => NULL,
      ),
      'countPresent' => 
      array (
        'name' => 'countPresent',
        'parameters' => 
        array (
          'table' => 
          array (
            'name' => 'table',
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
            'startLine' => 621,
            'endLine' => 621,
            'startColumn' => 32,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'uuids' => 
          array (
            'name' => 'uuids',
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
            'startLine' => 621,
            'endLine' => 621,
            'startColumn' => 47,
            'endColumn' => 58,
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
 * How many of these uuids the target holds RIGHT NOW.
 *
 * Queried back out of the target rather than taken from the driver\'s
 * affected-row count: a driver reporting `n` rows written is a claim, and
 * this is an observation. The delete that follows is authorised by the
 * observation alone.
 *
 * @param string $table The target table, already shape-checked.
 * @param array<int, string> $uuids The batch just inserted.
 *
 * @return int|null The count, or null when the verification itself failed.
 */',
        'startLine' => 621,
        'endLine' => 628,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'aliasName' => NULL,
      ),
      'reportAmbiguous' => 
      array (
        'name' => 'reportAmbiguous',
        'parameters' => 
        array (
          'schemaIds' => 
          array (
            'name' => 'schemaIds',
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
            'startLine' => 638,
            'endLine' => 638,
            'startColumn' => 35,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'registerId' => 
          array (
            'name' => 'registerId',
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
            'startLine' => 638,
            'endLine' => 638,
            'startColumn' => 53,
            'endColumn' => 67,
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
 * Log the (register, schema) pairs that more than one table name claimed.
 *
 * @param array<int, int> $schemaIds The ambiguous schema ids.
 * @param int $registerId The register they belong to.
 *
 * @return void
 */',
        'startLine' => 638,
        'endLine' => 649,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegisters',
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