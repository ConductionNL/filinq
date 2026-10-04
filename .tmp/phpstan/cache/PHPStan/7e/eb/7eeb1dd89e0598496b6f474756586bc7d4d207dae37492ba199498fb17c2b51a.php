<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Dashboard/AnonymizationWidget.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Dashboard\AnonymizationWidget
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-4fd35ebff4e62b42506bb2aedf8999245595cbcae07b49b49f19e9fa082ace2d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Dashboard\\AnonymizationWidget',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Dashboard/AnonymizationWidget.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Dashboard',
    'name' => 'OCA\\Filinq\\Dashboard\\AnonymizationWidget',
    'shortName' => 'AnonymizationWidget',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Dashboard widget for document anonymization
 *
 * @category Dashboard
 * @package  OCA\\Filinq\\Dashboard
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 41,
    'endLine' => 142,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'OCP\\Dashboard\\IWidget',
      1 => 'OCP\\Dashboard\\IIconWidget',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'urlGenerator' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Dashboard\\AnonymizationWidget',
        'implementingClassName' => 'OCA\\Filinq\\Dashboard\\AnonymizationWidget',
        'name' => 'urlGenerator',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IURLGenerator',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 48,
        'endLine' => 48,
        'startColumn' => 3,
        'endColumn' => 46,
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
          'urlGenerator' => 
          array (
            'name' => 'urlGenerator',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IURLGenerator',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 48,
            'endLine' => 48,
            'startColumn' => 3,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor for AnonymizationWidget
 *
 * @param IURLGenerator $urlGenerator The URL generator service
 */',
        'startLine' => 47,
        'endLine' => 51,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Dashboard',
        'declaringClassName' => 'OCA\\Filinq\\Dashboard\\AnonymizationWidget',
        'implementingClassName' => 'OCA\\Filinq\\Dashboard\\AnonymizationWidget',
        'currentClassName' => 'OCA\\Filinq\\Dashboard\\AnonymizationWidget',
        'aliasName' => NULL,
      ),
      'getId' => 
      array (
        'name' => 'getId',
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
 * Returns the unique widget identifier
 *
 * @return string
 *
 * @spec openspec/specs/dashboard/spec.md
 */',
        'startLine' => 60,
        'endLine' => 66,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Dashboard',
        'declaringClassName' => 'OCA\\Filinq\\Dashboard\\AnonymizationWidget',
        'implementingClassName' => 'OCA\\Filinq\\Dashboard\\AnonymizationWidget',
        'currentClassName' => 'OCA\\Filinq\\Dashboard\\AnonymizationWidget',
        'aliasName' => NULL,
      ),
      'getTitle' => 
      array (
        'name' => 'getTitle',
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
 * Returns the widget display title
 *
 * @return string
 *
 * @spec openspec/specs/dashboard/spec.md
 */',
        'startLine' => 75,
        'endLine' => 77,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Dashboard',
        'declaringClassName' => 'OCA\\Filinq\\Dashboard\\AnonymizationWidget',
        'implementingClassName' => 'OCA\\Filinq\\Dashboard\\AnonymizationWidget',
        'currentClassName' => 'OCA\\Filinq\\Dashboard\\AnonymizationWidget',
        'aliasName' => NULL,
      ),
      'getOrder' => 
      array (
        'name' => 'getOrder',
        'parameters' => 
        array (
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
 * Returns the widget display order
 *
 * @return int
 *
 * @spec openspec/specs/dashboard/spec.md
 */',
        'startLine' => 86,
        'endLine' => 88,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Dashboard',
        'declaringClassName' => 'OCA\\Filinq\\Dashboard\\AnonymizationWidget',
        'implementingClassName' => 'OCA\\Filinq\\Dashboard\\AnonymizationWidget',
        'currentClassName' => 'OCA\\Filinq\\Dashboard\\AnonymizationWidget',
        'aliasName' => NULL,
      ),
      'getIconClass' => 
      array (
        'name' => 'getIconClass',
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
 * Returns the CSS icon class for the widget
 *
 * @return string
 *
 * @spec openspec/specs/dashboard/spec.md
 */',
        'startLine' => 97,
        'endLine' => 99,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Dashboard',
        'declaringClassName' => 'OCA\\Filinq\\Dashboard\\AnonymizationWidget',
        'implementingClassName' => 'OCA\\Filinq\\Dashboard\\AnonymizationWidget',
        'currentClassName' => 'OCA\\Filinq\\Dashboard\\AnonymizationWidget',
        'aliasName' => NULL,
      ),
      'getIconUrl' => 
      array (
        'name' => 'getIconUrl',
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
 * Returns the URL to the widget icon
 *
 * @return string
 *
 * @spec openspec/specs/dashboard/spec.md
 */',
        'startLine' => 108,
        'endLine' => 113,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Dashboard',
        'declaringClassName' => 'OCA\\Filinq\\Dashboard\\AnonymizationWidget',
        'implementingClassName' => 'OCA\\Filinq\\Dashboard\\AnonymizationWidget',
        'currentClassName' => 'OCA\\Filinq\\Dashboard\\AnonymizationWidget',
        'aliasName' => NULL,
      ),
      'getUrl' => 
      array (
        'name' => 'getUrl',
        'parameters' => 
        array (
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns the URL the widget links to
 *
 * @return string|null
 *
 * @spec openspec/specs/dashboard/spec.md
 */',
        'startLine' => 122,
        'endLine' => 124,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Dashboard',
        'declaringClassName' => 'OCA\\Filinq\\Dashboard\\AnonymizationWidget',
        'implementingClassName' => 'OCA\\Filinq\\Dashboard\\AnonymizationWidget',
        'currentClassName' => 'OCA\\Filinq\\Dashboard\\AnonymizationWidget',
        'aliasName' => NULL,
      ),
      'load' => 
      array (
        'name' => 'load',
        'parameters' => 
        array (
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
 * Loads the widget scripts and styles
 *
 * @return void
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 *
 * @spec openspec/specs/dashboard/spec.md
 */',
        'startLine' => 135,
        'endLine' => 141,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Dashboard',
        'declaringClassName' => 'OCA\\Filinq\\Dashboard\\AnonymizationWidget',
        'implementingClassName' => 'OCA\\Filinq\\Dashboard\\AnonymizationWidget',
        'currentClassName' => 'OCA\\Filinq\\Dashboard\\AnonymizationWidget',
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