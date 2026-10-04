<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Event/SigningConcludedEventFactory.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Event\SigningConcludedEventFactory
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-4067585e99b3ba4258d7911974c8805839f68e746f4ff61f77cb614c592d768a',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Event\\SigningConcludedEventFactory',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Event/SigningConcludedEventFactory.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Event',
    'name' => 'OCA\\Filinq\\Event\\SigningConcludedEventFactory',
    'shortName' => 'SigningConcludedEventFactory',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Builds a SigningConcludedEvent from a persisted signing-request array.
 *
 * @category Event
 * @package  OCA\\Filinq\\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 41,
    'endLine' => 116,
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
      'create' => 
      array (
        'name' => 'create',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
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
            'startLine' => 59,
            'endLine' => 59,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'status' => 
          array (
            'name' => 'status',
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
            'startLine' => 60,
            'endLine' => 60,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'signedDocumentRef' => 
          array (
            'name' => 'signedDocumentRef',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 61,
                'endLine' => 61,
                'startTokenPos' => 51,
                'startFilePos' => 2204,
                'endTokenPos' => 51,
                'endFilePos' => 2207,
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 61,
            'endLine' => 61,
            'startColumn' => 3,
            'endColumn' => 35,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Event\\SigningConcludedEvent',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Build a conclusion event from a persisted signing-request array.
 *
 * The request array already carries the provenance fields (persisted by
 * SigningService::createRequest from the request event) and the signers;
 * the status is the normalised terminal vocabulary value supplied by the
 * caller.
 *
 * @param array<string, mixed> $request Persisted signing-request object array
 * @param string $status Normalised status (signed|declined|expired|cancelled)
 * @param string|null $signedDocumentRef Reference to the signed document, when signed
 *
 * @return SigningConcludedEvent The mapped conclusion event.
 *
 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
 */',
        'startLine' => 58,
        'endLine' => 80,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEventFactory',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEventFactory',
        'currentClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEventFactory',
        'aliasName' => NULL,
      ),
      'resolveAssuranceLevel' => 
      array (
        'name' => 'resolveAssuranceLevel',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
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
            'startLine' => 100,
            'endLine' => 100,
            'startColumn' => 41,
            'endColumn' => 54,
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
 * Resolve the eIDAS assurance level for the completion payload
 * (signing-trust-rebuild REQ-DDSTR-010).
 *
 * Conservative-by-construction: the native provider only ever produces SES
 * artifacts (`NativeSigningProvider::supportsLevel()`), so a native/SES
 * request always resolves `low`. Any level this map does not explicitly
 * recognise falls back to `low` rather than over-claiming an assurance the
 * request cannot actually evidence. `signer-identity-rails` is expected to
 * populate a broker-resolved, higher assurance into this SAME field for a
 * request whose provider actually supports it — this mapping never needs
 * to change for that, only the request\'s own `provider`/`signatureLevel`
 * do.
 *
 * @param array<string, mixed> $request The persisted signing-request array.
 *
 * @return string One of low|substantial|high.
 */',
        'startLine' => 100,
        'endLine' => 115,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEventFactory',
        'implementingClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEventFactory',
        'currentClassName' => 'OCA\\Filinq\\Event\\SigningConcludedEventFactory',
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