<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/TextNormaliser.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\TextNormaliser
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-baf5e3d837765b58e79c75f0e40c8b1030c29ab2a11557d1359a1a750fefd925',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\TextNormaliser',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/TextNormaliser.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\TextNormaliser',
    'shortName' => 'TextNormaliser',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Accent-stripping, lower-casing text normaliser with an intl-free fallback.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/entity-publication-policies/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 41,
    'endLine' => 105,
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
      'RULESET' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\TextNormaliser',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TextNormaliser',
        'name' => 'RULESET',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'Any-Latin; Latin-ASCII; Lower\'',
          'attributes' => 
          array (
            'startLine' => 46,
            'endLine' => 46,
            'startTokenPos' => 35,
            'startFilePos' => 1462,
            'endTokenPos' => 35,
            'endFilePos' => 1492,
          ),
        ),
        'docComment' => '/**
 * The transliteration ruleset applied to every value.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 46,
        'endLine' => 46,
        'startColumn' => 2,
        'endColumn' => 57,
      ),
    ),
    'immediateProperties' => 
    array (
      'transliterator' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\TextNormaliser',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TextNormaliser',
        'name' => 'transliterator',
        'modifiers' => 4,
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
                  'name' => 'object',
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
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 53,
            'endLine' => 53,
            'startTokenPos' => 49,
            'startFilePos' => 1637,
            'endTokenPos' => 49,
            'endFilePos' => 1640,
          ),
        ),
        'docComment' => '/**
 * Lazily-built transliterator, or null when ext-intl is unavailable.
 *
 * @var object|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 53,
        'endLine' => 53,
        'startColumn' => 2,
        'endColumn' => 40,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'lookupAttempted' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\TextNormaliser',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TextNormaliser',
        'name' => 'lookupAttempted',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 60,
            'endLine' => 60,
            'startTokenPos' => 62,
            'startFilePos' => 1774,
            'endTokenPos' => 62,
            'endFilePos' => 1778,
          ),
        ),
        'docComment' => '/**
 * Whether the transliterator lookup has already been attempted.
 *
 * @var boolean
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 60,
        'endLine' => 60,
        'startColumn' => 2,
        'endColumn' => 39,
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
      'normalise' => 
      array (
        'name' => 'normalise',
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
            'startLine' => 74,
            'endLine' => 74,
            'startColumn' => 28,
            'endColumn' => 40,
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
 * Lower-case and accent-strip a string.
 *
 * Falls back to `mb_strtolower()` when the PHP intl extension is not
 * available (e.g. bare-CLI CI environments without ext-intl).
 *
 * @param string $value Source string.
 *
 * @return string Normalised string.
 *
 * @spec openspec/specs/entity-publication-policies/spec.md
 */',
        'startLine' => 74,
        'endLine' => 84,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\TextNormaliser',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TextNormaliser',
        'currentClassName' => 'OCA\\Filinq\\Service\\TextNormaliser',
        'aliasName' => NULL,
      ),
      'transliterator' => 
      array (
        'name' => 'transliterator',
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
                  'name' => 'object',
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
 * Resolve the transliterator once per instance.
 *
 * @return object|null The transliterator, or null when ext-intl is absent
 *                     or the ruleset could not be compiled.
 */',
        'startLine' => 92,
        'endLine' => 104,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\TextNormaliser',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TextNormaliser',
        'currentClassName' => 'OCA\\Filinq\\Service\\TextNormaliser',
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