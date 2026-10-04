<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Extraction/TotalsReconciler.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Extraction\TotalsReconciler
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-1b63769757208798d3dcb9e9521be4fc3aadfebb832ddfac0475183485b1f420',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Extraction/TotalsReconciler.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Extraction',
    'name' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
    'shortName' => 'TotalsReconciler',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Reconciles totalExcl + totalVat against totalIncl within a tolerance.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Extraction
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/financial-document-field-extraction/tasks.md#2-4
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 40,
    'endLine' => 77,
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
      'DEFAULT_TOLERANCE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
        'name' => 'DEFAULT_TOLERANCE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0.01',
          'attributes' => 
          array (
            'startLine' => 47,
            'endLine' => 47,
            'startTokenPos' => 35,
            'startFilePos' => 1378,
            'endTokenPos' => 35,
            'endFilePos' => 1381,
          ),
        ),
        'docComment' => '/**
 * Default rounding tolerance (in currency units) for reconciliation.
 *
 * @var float
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 47,
        'endLine' => 47,
        'startColumn' => 2,
        'endColumn' => 40,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'reconciles' => 
      array (
        'name' => 'reconciles',
        'parameters' => 
        array (
          'totalExcl' => 
          array (
            'name' => 'totalExcl',
            'default' => NULL,
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
                      'name' => 'float',
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
            'startLine' => 66,
            'endLine' => 66,
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'totalVat' => 
          array (
            'name' => 'totalVat',
            'default' => NULL,
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
                      'name' => 'float',
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
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'totalIncl' => 
          array (
            'name' => 'totalIncl',
            'default' => NULL,
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
                      'name' => 'float',
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
            'startLine' => 68,
            'endLine' => 68,
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'tolerance' => 
          array (
            'name' => 'tolerance',
            'default' => 
            array (
              'code' => 'self::DEFAULT_TOLERANCE',
              'attributes' => 
              array (
                'startLine' => 69,
                'endLine' => 69,
                'startTokenPos' => 71,
                'startFilePos' => 2140,
                'endTokenPos' => 73,
                'endFilePos' => 2162,
              ),
            ),
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
            'startLine' => 69,
            'endLine' => 69,
            'startColumn' => 3,
            'endColumn' => 44,
            'parameterIndex' => 3,
            'isOptional' => true,
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
 * Check whether totalExcl + totalVat reconciles with totalIncl.
 *
 * All three values must be present (non-null) for reconciliation to be
 * possible; a missing value always yields false.
 *
 * @param float|null $totalExcl The excl.-VAT total, or null.
 * @param float|null $totalVat The VAT amount, or null.
 * @param float|null $totalIncl The incl.-VAT total, or null.
 * @param float $tolerance Rounding tolerance in currency units.
 *
 * @return bool True when all three values are present and reconcile
 *              within the tolerance.
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 */',
        'startLine' => 65,
        'endLine' => 76,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Extraction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
        'currentClassName' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
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