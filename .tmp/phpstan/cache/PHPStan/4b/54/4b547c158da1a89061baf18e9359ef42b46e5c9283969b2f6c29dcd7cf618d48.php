<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Signing/AssertionCanonicalizer.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Signing\AssertionCanonicalizer
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-e97beb9172cb7fa0a3b166babd0dec61c14164f66ffd1e2248e39927bb7709a2',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Signing\\AssertionCanonicalizer',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Signing/AssertionCanonicalizer.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Signing',
    'name' => 'OCA\\Filinq\\Service\\Signing\\AssertionCanonicalizer',
    'shortName' => 'AssertionCanonicalizer',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Deterministic (sorted-key) JSON encoding of an assertion payload.
 *
 * The v2 MAC formula is `HMAC-SHA256(secret, sha256(canonical-document) . "\\n"
 * . canonical-JSON(assertion-minus-mac))`. Both the writer and the verifier
 * must independently produce byte-identical JSON for the same logical data —
 * this class is the single source of truth for that encoding so the two
 * sides can never drift.
 *
 * Stateless but deliberately NOT static: the writer
 * ({@see \\OCA\\Filinq\\Service\\Signing\\NativeSigningProvider}) and the verifier
 * ({@see \\OCA\\Filinq\\Service\\SigningVerificationService}) take it as an
 * injected collaborator, so the encoding is a substitutable dependency rather
 * than a hard-wired static call.
 *
 * @spec openspec/specs/document-signing/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 44,
    'endLine' => 78,
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
      'canonicalJson' => 
      array (
        'name' => 'canonicalJson',
        'parameters' => 
        array (
          'data' => 
          array (
            'name' => 'data',
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
            'startLine' => 54,
            'endLine' => 54,
            'startColumn' => 32,
            'endColumn' => 42,
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
 * Canonical-JSON encode an assertion payload with recursively sorted keys.
 *
 * @param array<string, mixed> $data The assertion data (already excluding `mac`).
 *
 * @return string The canonical JSON string.
 *
 * @spec openspec/specs/document-signing/spec.md
 */',
        'startLine' => 54,
        'endLine' => 58,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\AssertionCanonicalizer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\AssertionCanonicalizer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\AssertionCanonicalizer',
        'aliasName' => NULL,
      ),
      'sortRecursive' => 
      array (
        'name' => 'sortRecursive',
        'parameters' => 
        array (
          'data' => 
          array (
            'name' => 'data',
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
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 33,
            'endColumn' => 43,
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
 * Recursively sort array keys so nested maps encode deterministically.
 *
 * @param array<mixed, mixed> $data The data to sort.
 *
 * @return array<mixed, mixed> The recursively key-sorted data.
 */',
        'startLine' => 67,
        'endLine' => 77,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Signing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Signing\\AssertionCanonicalizer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Signing\\AssertionCanonicalizer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Signing\\AssertionCanonicalizer',
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