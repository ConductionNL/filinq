<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/PartySuggestionService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\PartySuggestionService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-4611e62c0b8edbb51cc618f499102f6886ca729c12b8b9057ddbccb51381401f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\PartySuggestionService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/PartySuggestionService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\PartySuggestionService',
    'shortName' => 'PartySuggestionService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Offers a party read out of a document, and records what was done with it.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 55,
    'endLine' => 254,
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
      'CORRECTION_SCHEMA' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PartySuggestionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PartySuggestionService',
        'name' => 'CORRECTION_SCHEMA',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'intakePartyCorrection\'',
          'attributes' => 
          array (
            'startLine' => 62,
            'endLine' => 62,
            'startTokenPos' => 60,
            'startFilePos' => 2048,
            'endTokenPos' => 60,
            'endFilePos' => 2070,
          ),
        ),
        'docComment' => '/**
 * The schema holding the corrections corpus.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 2,
        'endColumn' => 58,
      ),
      'FIELDS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PartySuggestionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PartySuggestionService',
        'name' => 'FIELDS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'PERSON\' => \'name\', \'LOCATION\' => \'address\', \'EMAIL\' => \'email\', \'PHONE_NUMBER\' => \'telephone\']',
          'attributes' => 
          array (
            'startLine' => 69,
            'endLine' => 74,
            'startTokenPos' => 73,
            'startFilePos' => 2221,
            'endTokenPos' => 103,
            'endFilePos' => 2328,
          ),
        ),
        'docComment' => '/**
 * The entity types a party is read out of, in the order they are asked for.
 *
 * @var array<string, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 69,
        'endLine' => 74,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'entities' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PartySuggestionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PartySuggestionService',
        'name' => 'entities',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\FileEntityStatsService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 87,
        'endLine' => 87,
        'startColumn' => 3,
        'endColumn' => 51,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'objectResolver' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PartySuggestionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PartySuggestionService',
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
        'startLine' => 88,
        'endLine' => 88,
        'startColumn' => 3,
        'endColumn' => 64,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'userSession' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PartySuggestionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PartySuggestionService',
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
        'startLine' => 89,
        'endLine' => 89,
        'startColumn' => 3,
        'endColumn' => 44,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PartySuggestionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PartySuggestionService',
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
        'startLine' => 90,
        'endLine' => 90,
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
          'entities' => 
          array (
            'name' => 'entities',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\FileEntityStatsService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 87,
            'endLine' => 87,
            'startColumn' => 3,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 88,
            'endLine' => 88,
            'startColumn' => 3,
            'endColumn' => 64,
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
            'startLine' => 89,
            'endLine' => 89,
            'startColumn' => 3,
            'endColumn' => 44,
            'parameterIndex' => 2,
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
            'startLine' => 90,
            'endLine' => 90,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 3,
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
 * @param FileEntityStatsService $entities The seam onto OpenRegister\'s entity relations.
 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister\'s ObjectService.
 * @param IUserSession $userSession The current session.
 * @param LoggerInterface $logger Logger for diagnostics.
 *
 * @return void
 */',
        'startLine' => 86,
        'endLine' => 93,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PartySuggestionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PartySuggestionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PartySuggestionService',
        'aliasName' => NULL,
      ),
      'suggestFor' => 
      array (
        'name' => 'suggestFor',
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
            'startLine' => 104,
            'endLine' => 104,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Read one party out of the entities already detected on a file.
 *
 * @param int $fileId The Nextcloud file id.
 *
 * @return array<int, array<string, mixed>> Zero or one suggestion, each with its confidence and source span.
 *
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 */',
        'startLine' => 104,
        'endLine' => 152,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PartySuggestionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PartySuggestionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PartySuggestionService',
        'aliasName' => NULL,
      ),
      'fieldOf' => 
      array (
        'name' => 'fieldOf',
        'parameters' => 
        array (
          'relation' => 
          array (
            'name' => 'relation',
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
            'startLine' => 167,
            'endLine' => 167,
            'startColumn' => 27,
            'endColumn' => 41,
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
 * The party field one detected entity fills, and with what.
 *
 * A relation whose type is not one this app maps, or that carries no text,
 * fills nothing. It is skipped rather than suggested empty: a suggestion
 * with a blank name is worse than no suggestion, because a clerk accepts it.
 *
 * @param mixed $relation The entity relation, as OpenRegister returned it.
 *
 * @return array{name: string, value: string}|null The field and its value, or null.
 *
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 */',
        'startLine' => 167,
        'endLine' => 184,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PartySuggestionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PartySuggestionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PartySuggestionService',
        'aliasName' => NULL,
      ),
      'recordDecision' => 
      array (
        'name' => 'recordDecision',
        'parameters' => 
        array (
          'sender' => 
          array (
            'name' => 'sender',
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
            'startLine' => 200,
            'endLine' => 200,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'decision' => 
          array (
            'name' => 'decision',
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
            'startLine' => 201,
            'endLine' => 201,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'suggested' => 
          array (
            'name' => 'suggested',
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
            'startLine' => 202,
            'endLine' => 202,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'accepted' => 
          array (
            'name' => 'accepted',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 203,
                'endLine' => 203,
                'startTokenPos' => 703,
                'startFilePos' => 6212,
                'endTokenPos' => 704,
                'endFilePos' => 6213,
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
            'startLine' => 203,
            'endLine' => 203,
            'startColumn' => 3,
            'endColumn' => 22,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'intakeDocument' => 
          array (
            'name' => 'intakeDocument',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 204,
                'endLine' => 204,
                'startTokenPos' => 713,
                'startFilePos' => 6243,
                'endTokenPos' => 713,
                'endFilePos' => 6244,
              ),
            ),
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
            'startLine' => 204,
            'endLine' => 204,
            'startColumn' => 3,
            'endColumn' => 29,
            'parameterIndex' => 4,
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
 * Record what a clerk did with a suggestion.
 *
 * @param string $sender The sender the suggestion was made for.
 * @param string $decision One of `accepted`, `edited`, `rejected`.
 * @param array<string, mixed> $suggested What was proposed.
 * @param array<string, mixed> $accepted What was filed, empty on a rejection.
 * @param string $intakeDocument The intake document it was shown on.
 *
 * @return array<string, mixed> The stored correction.
 *
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 */',
        'startLine' => 199,
        'endLine' => 236,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PartySuggestionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PartySuggestionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PartySuggestionService',
        'aliasName' => NULL,
      ),
      'currentUserId' => 
      array (
        'name' => 'currentUserId',
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
 * The user id of the person at the keyboard.
 *
 * @return string The user id, or an empty string when there is no session.
 *
 * @spec exclude Session accessor with no behaviour of its own.
 */',
        'startLine' => 245,
        'endLine' => 253,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PartySuggestionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PartySuggestionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PartySuggestionService',
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