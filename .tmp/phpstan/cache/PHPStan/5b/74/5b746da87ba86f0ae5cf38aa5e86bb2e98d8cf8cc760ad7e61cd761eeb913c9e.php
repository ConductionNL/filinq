<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/PlainRenditionAcceptanceGate.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\PlainRenditionAcceptanceGate
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-13ff3da31d123ce8db5d495587541c8464ea6e23c2374fe8fb78cbe07564d3e1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\PlainRenditionAcceptanceGate',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/PlainRenditionAcceptanceGate.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\PlainRenditionAcceptanceGate',
    'shortName' => 'PlainRenditionAcceptanceGate',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Holds an unaccepted machine draft inside the building.
 *
 * 🔴 THE GATE IS ONE CHOKEPOINT, ASKED THE SAME QUESTION BY EVERY PATH. REQ-DIO-05
 * says an unaccepted suggestion must not leave through generation, correspondence
 * or the portal, and a check written into one of those three is a check the other
 * two do not have. So the decision lives here and every path calls it; a path
 * that forgets is a missing call somebody can grep for, rather than a subtly
 * different rule nobody can see.
 *
 * 🔴 ACCEPTANCE IS A PERSON AND A MOMENT, BOTH OR NEITHER. "Accepted: true" can
 * be written by anything, including the same machine that drafted the text, and
 * it is exactly the flag REQ-DIO-05 exists to refuse. A name with no moment
 * cannot be placed in time when somebody asks a year later whether the draft was
 * read before or after the correction, so half an acceptance is refused as an
 * acceptance, not accepted as half.
 *
 * 🔑 A RENDITION FROM THE COUNTERPART TEMPLATE NEEDS NO ACCEPTANCE. It is the
 * organisation\'s own text, reviewed when the template was written. Demanding an
 * acceptance there would make every ordinary besluit wait for a click nobody was
 * told to make, which is how a gate takes down the feature it guards.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/letter-correspondence-generation/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 55,
    'endLine' => 138,
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
      'SOURCE_TEMPLATE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PlainRenditionAcceptanceGate',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PlainRenditionAcceptanceGate',
        'name' => 'SOURCE_TEMPLATE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'template\'',
          'attributes' => 
          array (
            'startLine' => 62,
            'endLine' => 62,
            'startTokenPos' => 40,
            'startFilePos' => 2469,
            'endTokenPos' => 40,
            'endFilePos' => 2478,
          ),
        ),
        'docComment' => '/**
 * The plain text came from the counterpart template.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 2,
        'endColumn' => 43,
      ),
      'SOURCE_MACHINE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PlainRenditionAcceptanceGate',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PlainRenditionAcceptanceGate',
        'name' => 'SOURCE_MACHINE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'machine\'',
          'attributes' => 
          array (
            'startLine' => 69,
            'endLine' => 69,
            'startTokenPos' => 53,
            'startFilePos' => 2588,
            'endTokenPos' => 53,
            'endFilePos' => 2596,
          ),
        ),
        'docComment' => '/**
 * The plain text was drafted by a machine.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 69,
        'endLine' => 69,
        'startColumn' => 2,
        'endColumn' => 41,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'needsAcceptance' => 
      array (
        'name' => 'needsAcceptance',
        'parameters' => 
        array (
          'source' => 
          array (
            'name' => 'source',
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
            'startLine' => 80,
            'endLine' => 80,
            'startColumn' => 34,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Whether a rendition of this source needs a person to accept it.
 *
 * @param string $source Where the plain text came from.
 *
 * @return bool True when an acceptance is required.
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/letter-correspondence-generation/spec.md
 */',
        'startLine' => 80,
        'endLine' => 90,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PlainRenditionAcceptanceGate',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PlainRenditionAcceptanceGate',
        'currentClassName' => 'OCA\\Filinq\\Service\\PlainRenditionAcceptanceGate',
        'aliasName' => NULL,
      ),
      'require' => 
      array (
        'name' => 'require',
        'parameters' => 
        array (
          'source' => 
          array (
            'name' => 'source',
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
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 26,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'acceptance' => 
          array (
            'name' => 'acceptance',
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
            'startColumn' => 42,
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
 * Read one acceptance, or refuse it.
 *
 * @param string               $source     Where the plain text came from.
 * @param array<string, mixed> $acceptance The acceptance as the caller offers it: `acceptedBy` and `acceptedAt`.
 *
 * @return array{acceptedBy: string, acceptedAt: string} The acceptance, empty when none is needed.
 *
 * @throws PlainRenditionRefusedException When a machine draft has no complete acceptance.
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/letter-correspondence-generation/spec.md
 */',
        'startLine' => 104,
        'endLine' => 137,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PlainRenditionAcceptanceGate',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PlainRenditionAcceptanceGate',
        'currentClassName' => 'OCA\\Filinq\\Service\\PlainRenditionAcceptanceGate',
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