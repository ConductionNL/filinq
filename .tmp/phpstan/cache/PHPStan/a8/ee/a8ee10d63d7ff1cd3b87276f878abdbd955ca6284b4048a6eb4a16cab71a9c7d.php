<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DomainDirectory.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\DomainDirectory
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-6bcb4d8a88d618aac6f19ebb2b3026d3a3c4b8c715f5afda5b27dd7590552226',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\DomainDirectory',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DomainDirectory.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\DomainDirectory',
    'shortName' => 'DomainDirectory',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Reads the domains whose folders filinq keeps in step.
 *
 * 🔴 A DOMAIN IS NOT FILINQ\'S RECORD. A unit or case domain belongs to the app
 * that owns cases; filinq owns the documents and the folder underneath them. So
 * the directory does not define a domain schema here, it reads whatever register
 * and schema an administrator points it at, and it says so when nobody has
 * pointed it anywhere.
 *
 * 🔑 AN UNCONFIGURED DIRECTORY IS REPORTED, NEVER READ AS "NO DOMAINS". Those
 * two look identical to a caller that only counts rows, and the difference is
 * the whole question: no domains means there is nothing to reconcile, while
 * unconfigured means every domain there is went unreconciled and nobody was
 * told. The nightly job reports the second as a skip with its reason.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction Development Team <dev@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 52,
    'endLine' => 201,
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
      'REGISTER_KEY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainDirectory',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainDirectory',
        'name' => 'REGISTER_KEY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'domainFolder_register\'',
          'attributes' => 
          array (
            'startLine' => 59,
            'endLine' => 59,
            'startTokenPos' => 50,
            'startFilePos' => 1913,
            'endTokenPos' => 50,
            'endFilePos' => 1935,
          ),
        ),
        'docComment' => '/**
 * The app config key naming the register domains live in.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 59,
        'endLine' => 59,
        'startColumn' => 2,
        'endColumn' => 53,
      ),
      'SCHEMA_KEY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainDirectory',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainDirectory',
        'name' => 'SCHEMA_KEY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'domainFolder_schema\'',
          'attributes' => 
          array (
            'startLine' => 66,
            'endLine' => 66,
            'startTokenPos' => 63,
            'startFilePos' => 2054,
            'endTokenPos' => 63,
            'endFilePos' => 2074,
          ),
        ),
        'docComment' => '/**
 * The app config key naming the schema domains live in.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 66,
        'endLine' => 66,
        'startColumn' => 2,
        'endColumn' => 49,
      ),
      'OWNER_KEY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainDirectory',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainDirectory',
        'name' => 'OWNER_KEY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'domainFolder_owner\'',
          'attributes' => 
          array (
            'startLine' => 73,
            'endLine' => 73,
            'startTokenPos' => 76,
            'startFilePos' => 2206,
            'endTokenPos' => 76,
            'endFilePos' => 2225,
          ),
        ),
        'docComment' => '/**
 * The app config key naming the user whose storage holds the folders.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 2,
        'endColumn' => 47,
      ),
    ),
    'immediateProperties' => 
    array (
      'objectResolver' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainDirectory',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainDirectory',
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
        'startLine' => 85,
        'endLine' => 85,
        'startColumn' => 3,
        'endColumn' => 64,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'config' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainDirectory',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainDirectory',
        'name' => 'config',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IAppConfig',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 86,
        'endLine' => 86,
        'startColumn' => 3,
        'endColumn' => 37,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainDirectory',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainDirectory',
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
        'startLine' => 87,
        'endLine' => 87,
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
            'startLine' => 85,
            'endLine' => 85,
            'startColumn' => 3,
            'endColumn' => 64,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'config' => 
          array (
            'name' => 'config',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IAppConfig',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 86,
            'endLine' => 86,
            'startColumn' => 3,
            'endColumn' => 37,
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
            'startLine' => 87,
            'endLine' => 87,
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
 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister\'s ObjectService.
 * @param IAppConfig                    $config         Where the register, the schema and the owner are named.
 * @param LoggerInterface               $logger         Structured logger.
 *
 * @return void
 */',
        'startLine' => 84,
        'endLine' => 90,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainDirectory',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainDirectory',
        'currentClassName' => 'OCA\\Filinq\\Service\\DomainDirectory',
        'aliasName' => NULL,
      ),
      'owner' => 
      array (
        'name' => 'owner',
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
 * The user whose storage holds the domain folders.
 *
 * @return string The owner, or an empty string when none is named.
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
        'startLine' => 99,
        'endLine' => 102,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainDirectory',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainDirectory',
        'currentClassName' => 'OCA\\Filinq\\Service\\DomainDirectory',
        'aliasName' => NULL,
      ),
      'all' => 
      array (
        'name' => 'all',
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
 * Every domain the directory is pointed at.
 *
 * @return array{configured: bool, reason: string, domains: array<int, array<string, mixed>>} The domains, or why there are none.
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
        'startLine' => 111,
        'endLine' => 161,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainDirectory',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainDirectory',
        'currentClassName' => 'OCA\\Filinq\\Service\\DomainDirectory',
        'aliasName' => NULL,
      ),
      'plain' => 
      array (
        'name' => 'plain',
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
            'startLine' => 172,
            'endLine' => 172,
            'startColumn' => 25,
            'endColumn' => 34,
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
 * One search result as a plain domain array.
 *
 * @param mixed $row The row as the object service returned it.
 *
 * @return array<string, mixed>|null The domain, or null when the row carries no id.
 *
 * @spec exclude Shape adapter over a search result; no behaviour of its own.
 */',
        'startLine' => 172,
        'endLine' => 200,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DomainDirectory',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DomainDirectory',
        'currentClassName' => 'OCA\\Filinq\\Service\\DomainDirectory',
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