<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Charts/ThousandsFormatter.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Charts\ThousandsFormatter
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-f2ef509ea7510445e7a1b90f5bc8f5f7e0623d55f9bec934a51c840cd280f554',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Charts\\ThousandsFormatter',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Charts/ThousandsFormatter.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Charts',
    'name' => 'OCA\\Filinq\\Service\\Charts\\ThousandsFormatter',
    'shortName' => 'ThousandsFormatter',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Formats numbers with NL-style thousands separators, deterministically.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/template-charts/tasks.md#task-1.1
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 39,
    'endLine' => 94,
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
    ),
    'immediateMethods' => 
    array (
      'format' => 
      array (
        'name' => 'format',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'float',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 51,
            'endLine' => 51,
            'startColumn' => 25,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'decimals' => 
          array (
            'name' => 'decimals',
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
            'startLine' => 51,
            'endLine' => 51,
            'startColumn' => 39,
            'endColumn' => 51,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Format a number with NL-style thousands separators, independent of
 * environment locale (deterministic across containers).
 *
 * @param float $value Value to format.
 * @param int $decimals Number of decimal places.
 *
 * @return string Formatted number, e.g. \'1.234,56\'.
 *
 * @spec openspec/changes/template-charts/specs/template-charts/spec.md
 */',
        'startLine' => 51,
        'endLine' => 72,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Charts',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\ThousandsFormatter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\ThousandsFormatter',
        'currentClassName' => 'OCA\\Filinq\\Service\\Charts\\ThousandsFormatter',
        'aliasName' => NULL,
      ),
      'group' => 
      array (
        'name' => 'group',
        'parameters' => 
        array (
          'wholePart' => 
          array (
            'name' => 'wholePart',
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
            'startLine' => 81,
            'endLine' => 81,
            'startColumn' => 25,
            'endColumn' => 41,
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
 * Insert \'.\' group separators into the whole-number part of a number.
 *
 * @param string $wholePart The digits before the decimal separator.
 *
 * @return string The grouped digits.
 */',
        'startLine' => 81,
        'endLine' => 93,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Charts',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Charts\\ThousandsFormatter',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Charts\\ThousandsFormatter',
        'currentClassName' => 'OCA\\Filinq\\Service\\Charts\\ThousandsFormatter',
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