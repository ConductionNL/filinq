<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/AppInfo/IntegrationLeafRegistrar.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\AppInfo\IntegrationLeafRegistrar
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-d5d99e4a93b40869f2b749c8ffa2e8a17236096be431a110d65f63dd8cdd3b5d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\AppInfo\\IntegrationLeafRegistrar',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/AppInfo/IntegrationLeafRegistrar.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\AppInfo',
    'name' => 'OCA\\Filinq\\AppInfo\\IntegrationLeafRegistrar',
    'shortName' => 'IntegrationLeafRegistrar',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Registers Filinq\'s leaf descriptors with OpenRegister\'s collect-event.
 *
 * @category AppInfo
 * @package  OCA\\Filinq\\AppInfo
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 50,
    'endLine' => 87,
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
      'LEAF_EVENT' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\AppInfo\\IntegrationLeafRegistrar',
        'implementingClassName' => 'OCA\\Filinq\\AppInfo\\IntegrationLeafRegistrar',
        'name' => 'LEAF_EVENT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'OCA\\OpenRegister\\Event\\RegisterLeafProvidersEvent\'',
          'attributes' => 
          array (
            'startLine' => 61,
            'endLine' => 61,
            'startTokenPos' => 45,
            'startFilePos' => 2282,
            'endTokenPos' => 45,
            'endFilePos' => 2335,
          ),
        ),
        'docComment' => '/**
 * OpenRegister\'s leaf collect-event, as an FQN string literal.
 *
 * A literal rather than `::class` on an import: this name is read while the
 * OpenRegister prefix may still be unresolvable, and a literal keeps that
 * true even if someone later adds the `use`.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 61,
        'endLine' => 61,
        'startColumn' => 2,
        'endColumn' => 82,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'register' => 
      array (
        'name' => 'register',
        'parameters' => 
        array (
          'context' => 
          array (
            'name' => 'context',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\AppFramework\\Bootstrap\\IRegistrationContext',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 74,
            'endLine' => 74,
            'startColumn' => 27,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Register the server-side half of every Filinq integration leaf.
 *
 * @param IRegistrationContext $context The registration context.
 *
 * @return void
 *
 * @SuppressWarnings(PHPMD.StaticAccess) OpenRegisterAutoloader::register() is
 * the app\'s own static prelude; there is no container at the composition root
 * to resolve an adapter from.
 */',
        'startLine' => 74,
        'endLine' => 86,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\AppInfo',
        'declaringClassName' => 'OCA\\Filinq\\AppInfo\\IntegrationLeafRegistrar',
        'implementingClassName' => 'OCA\\Filinq\\AppInfo\\IntegrationLeafRegistrar',
        'currentClassName' => 'OCA\\Filinq\\AppInfo\\IntegrationLeafRegistrar',
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