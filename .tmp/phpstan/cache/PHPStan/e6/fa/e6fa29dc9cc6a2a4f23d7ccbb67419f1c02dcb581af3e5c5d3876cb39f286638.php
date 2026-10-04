<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DossierSummaryDataService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\DossierSummaryDataService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-8ab016f7d453ec21e151b290ee8274d6dfe8500bcd2f581ffb1f4b2f87587bb0',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DossierSummaryDataService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
    'shortName' => 'DossierSummaryDataService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Supplies every piece of dossier data the grondslagen report renders.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 48,
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
    ),
    'immediateProperties' => 
    array (
      'repository' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'name' => 'repository',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * OpenRegister object access.
 *
 * @var DossierObjectRepository
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 55,
        'endLine' => 55,
        'startColumn' => 2,
        'endColumn' => 54,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'labelResolver' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'name' => 'labelResolver',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Grondslag label resolution.
 *
 * @var BaseLabelResolver
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 2,
        'endColumn' => 51,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'ranker' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'name' => 'ranker',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Dossier placeholder ranking and sort keys.
 *
 * @var DossierPlaceholderRanker
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 69,
        'endLine' => 69,
        'startColumn' => 2,
        'endColumn' => 51,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'collector' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'name' => 'collector',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Anonymised entity collection and shaping.
 *
 * @var DossierEntityCollector
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 76,
        'endLine' => 76,
        'startColumn' => 2,
        'endColumn' => 52,
        'isPromoted' => false,
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 98,
            'endLine' => 98,
            'startColumn' => 3,
            'endColumn' => 25,
            'parameterIndex' => 0,
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 3,
            'endColumn' => 27,
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 100,
            'endLine' => 100,
            'startColumn' => 3,
            'endColumn' => 25,
            'parameterIndex' => 2,
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 101,
            'endLine' => 101,
            'startColumn' => 3,
            'endColumn' => 31,
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 102,
            'endLine' => 102,
            'startColumn' => 3,
            'endColumn' => 25,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'repository' => 
          array (
            'name' => 'repository',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 103,
                'endLine' => 103,
                'startTokenPos' => 145,
                'startFilePos' => 3253,
                'endTokenPos' => 145,
                'endFilePos' => 3256,
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
                      'name' => 'OCA\\Filinq\\Service\\DossierObjectRepository',
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
            'startLine' => 103,
            'endLine' => 103,
            'startColumn' => 3,
            'endColumn' => 45,
            'parameterIndex' => 5,
            'isOptional' => true,
          ),
          'labelResolver' => 
          array (
            'name' => 'labelResolver',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 104,
                'endLine' => 104,
                'startTokenPos' => 155,
                'startFilePos' => 3297,
                'endTokenPos' => 155,
                'endFilePos' => 3300,
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
                      'name' => 'OCA\\Filinq\\Service\\BaseLabelResolver',
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
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 6,
            'isOptional' => true,
          ),
          'ranker' => 
          array (
            'name' => 'ranker',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 105,
                'endLine' => 105,
                'startTokenPos' => 165,
                'startFilePos' => 3341,
                'endTokenPos' => 165,
                'endFilePos' => 3344,
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
                      'name' => 'OCA\\Filinq\\Service\\DossierPlaceholderRanker',
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
            'startLine' => 105,
            'endLine' => 105,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 7,
            'isOptional' => true,
          ),
          'collector' => 
          array (
            'name' => 'collector',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 106,
                'endLine' => 106,
                'startTokenPos' => 175,
                'startFilePos' => 3386,
                'endTokenPos' => 175,
                'endFilePos' => 3389,
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
                      'name' => 'OCA\\Filinq\\Service\\DossierEntityCollector',
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
            'startLine' => 106,
            'endLine' => 106,
            'startColumn' => 3,
            'endColumn' => 43,
            'parameterIndex' => 8,
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
 * The four collaborators are injected; the null defaults wire an
 * equivalent chain from the raw dependencies so Nextcloud\'s autowiring and
 * tests can both construct this service without naming the chain.
 *
 * @param IRootFolder $rootFolder Nextcloud file API entry point.
 * @param IUserSession $userSession Session-user lookup.
 * @param IAppManager $appManager App-availability check for OpenRegister.
 * @param ContainerInterface $container DI container for OpenRegister-side services.
 * @param LoggerInterface $logger Structured logger.
 * @param DossierObjectRepository|null $repository OpenRegister object access.
 * @param BaseLabelResolver|null $labelResolver Grondslag label resolution.
 * @param DossierPlaceholderRanker|null $ranker Dossier placeholder ranking.
 * @param DossierEntityCollector|null $collector Anonymised entity collection.
 *
 * @return void
 */',
        'startLine' => 97,
        'endLine' => 125,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'aliasName' => NULL,
      ),
      'loadDossier' => 
      array (
        'name' => 'loadDossier',
        'parameters' => 
        array (
          'dossierUuid' => 
          array (
            'name' => 'dossierUuid',
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
            'startLine' => 136,
            'endLine' => 136,
            'startColumn' => 30,
            'endColumn' => 48,
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
 * Load a dossier\'s context together with its resolved folder node.
 *
 * @param string $dossierUuid The OR object UUID.
 *
 * @return array{context: array<string, mixed>, folder: Folder} Dossier context and folder.
 *
 * @throws RuntimeException When the dossier or its folder cannot be resolved.
 */',
        'startLine' => 136,
        'endLine' => 144,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'aliasName' => NULL,
      ),
      'assertDossierReadable' => 
      array (
        'name' => 'assertDossierReadable',
        'parameters' => 
        array (
          'dossierId' => 
          array (
            'name' => 'dossierId',
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
            'startLine' => 180,
            'endLine' => 180,
            'startColumn' => 40,
            'endColumn' => 56,
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
 * Assert that the dossier EXISTS and is resolvable to the acting user.
 *
 * ⚠️ This is an EXISTENCE check, not an ownership check. The previous
 * docblock claimed the opposite — "OR\'s standard RBAC governs visibility:
 * a dossier the caller may not read resolves to null and we deny by
 * throwing" — and that claim is false as the app is configured.
 *
 * The call below genuinely omits `_rbac: false`, so OpenRegister\'s RBAC
 * cascade is consulted. But the cascade resolves to "configured nowhere",
 * which OpenRegister treats as OPEN: the `dossier` schema in
 * `lib/Settings/filinq_register.json` declares `"authorization": null`,
 * and no register declares the key at all. `find()` therefore returns the
 * object for any authenticated caller in the same organisation, so the
 * `null` branch below can only ever fire for a dossier that does not
 * exist — never for one the caller merely has no business reading.
 *
 * What IS still enforced: organisation scoping (multitenancy is not
 * bypassed here), and the existence check itself.
 *
 * Do not read a green result from this method as "the caller owns this
 * dossier". Closing that gap needs an agreed ownership model for dossiers
 * — a dossier is a shared work object and it is NOT obvious that only its
 * creator may regenerate its summary — so it is deliberately not invented
 * here. Tracked in ConductionNL/filinq#441.
 *
 * @param string $dossierId The OR dossier object UUID.
 *
 * @return void
 *
 * @throws RuntimeException 403 when the dossier cannot be resolved at all.
 *
 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md
 */',
        'startLine' => 180,
        'endLine' => 196,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'aliasName' => NULL,
      ),
      'placeholderRanking' => 
      array (
        'name' => 'placeholderRanking',
        'parameters' => 
        array (
          'folder' => 
          array (
            'name' => 'folder',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Files\\Folder',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 205,
            'endLine' => 205,
            'startColumn' => 37,
            'endColumn' => 50,
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
 * Recompute the dossier\'s scope-local placeholder ranking.
 *
 * @param Folder $folder The dossier folder.
 *
 * @return array{ranks: array<array-key, int>, types: array<string, string>} Ranks and entity types.
 */',
        'startLine' => 205,
        'endLine' => 207,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'aliasName' => NULL,
      ),
      'placeholderSortKey' => 
      array (
        'name' => 'placeholderSortKey',
        'parameters' => 
        array (
          'placeholder' => 
          array (
            'name' => 'placeholder',
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
            'startLine' => 216,
            'endLine' => 216,
            'startColumn' => 37,
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
 * Sort key for a `[<TYPE>: <number>]` placeholder.
 *
 * @param string $placeholder The placeholder string.
 *
 * @return array{0: string, 1: int} The [type, number] sort key.
 */',
        'startLine' => 216,
        'endLine' => 218,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'aliasName' => NULL,
      ),
      'walkDossierFiles' => 
      array (
        'name' => 'walkDossierFiles',
        'parameters' => 
        array (
          'folder' => 
          array (
            'name' => 'folder',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Files\\Folder',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 228,
            'endLine' => 228,
            'startColumn' => 35,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'placeholderMap' => 
          array (
            'name' => 'placeholderMap',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 228,
                'endLine' => 228,
                'startTokenPos' => 596,
                'startFilePos' => 7847,
                'endTokenPos' => 597,
                'endFilePos' => 7848,
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
            'startLine' => 228,
            'endLine' => 228,
            'startColumn' => 51,
            'endColumn' => 76,
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
 * Walk every file under the dossier folder and collect its anonymised entities.
 *
 * @param Folder $folder The dossier folder.
 * @param array<string, string> $placeholderMap Dossier scope-local placeholder map.
 *
 * @return array<int, array{fileId: int, filename: string, entities: array<int, array<string, mixed>>}> Per-file rows.
 */',
        'startLine' => 228,
        'endLine' => 230,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'aliasName' => NULL,
      ),
      'loadAnonymisedEntitiesForFile' => 
      array (
        'name' => 'loadAnonymisedEntitiesForFile',
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
            'startLine' => 240,
            'endLine' => 240,
            'startColumn' => 48,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'placeholderMap' => 
          array (
            'name' => 'placeholderMap',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 240,
                'endLine' => 240,
                'startTokenPos' => 648,
                'startFilePos' => 8339,
                'endTokenPos' => 649,
                'endFilePos' => 8340,
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
            'startLine' => 240,
            'endLine' => 240,
            'startColumn' => 61,
            'endColumn' => 86,
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
 * Load the shaped anonymised entity rows of one file.
 *
 * @param int $fileId The Nextcloud file ID.
 * @param array<string, string> $placeholderMap Optional scope-local placeholder map.
 *
 * @return array<int, array<string, mixed>> Shaped entity rows.
 */',
        'startLine' => 240,
        'endLine' => 242,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'aliasName' => NULL,
      ),
      'resolveBaseLabels' => 
      array (
        'name' => 'resolveBaseLabels',
        'parameters' => 
        array (
          'baseRefs' => 
          array (
            'name' => 'baseRefs',
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
            'startColumn' => 36,
            'endColumn' => 50,
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
 * Resolve a list of `base` references to human-readable labels.
 *
 * @param array<int, string> $baseRefs Slugs or UUIDs of base records.
 *
 * @return array<string, array{name: string, description: string}> The label map.
 */',
        'startLine' => 251,
        'endLine' => 253,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'aliasName' => NULL,
      ),
      'groupBasesByName' => 
      array (
        'name' => 'groupBasesByName',
        'parameters' => 
        array (
          'detail' => 
          array (
            'name' => 'detail',
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
            'startLine' => 262,
            'endLine' => 262,
            'startColumn' => 35,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Group resolved base labels by display name for the report legend.
 *
 * @param array<string, array{name: string, description: string}> $detail Resolved labels.
 *
 * @return array<int, array{name: string, description: string}> Distinct bases, sorted by name.
 */',
        'startLine' => 262,
        'endLine' => 264,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'aliasName' => NULL,
      ),
      'updateDossierConfiguration' => 
      array (
        'name' => 'updateDossierConfiguration',
        'parameters' => 
        array (
          'dossierUuid' => 
          array (
            'name' => 'dossierUuid',
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
            'startLine' => 274,
            'endLine' => 274,
            'startColumn' => 45,
            'endColumn' => 63,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'summaryFileId' => 
          array (
            'name' => 'summaryFileId',
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
            'startLine' => 274,
            'endLine' => 274,
            'startColumn' => 66,
            'endColumn' => 83,
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
 * Update the dossier object\'s `configuration.grondslagen` freshness metadata.
 *
 * @param string $dossierUuid The OR dossier object UUID.
 * @param int $summaryFileId The newly-written summary file\'s NC node id.
 *
 * @return void
 */',
        'startLine' => 274,
        'endLine' => 280,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DossierSummaryDataService',
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