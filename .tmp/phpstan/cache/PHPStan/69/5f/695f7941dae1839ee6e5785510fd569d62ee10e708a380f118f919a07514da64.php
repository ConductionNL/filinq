<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Conversion/ConversionBackendInterface.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Conversion\ConversionBackendInterface
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-ba5723570d03a66c073a67194fa7a0ed39725a40d92a72f82a997885671ef758',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Conversion\\ConversionBackendInterface',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Conversion/ConversionBackendInterface.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Conversion',
    'name' => 'OCA\\Filinq\\Service\\Conversion\\ConversionBackendInterface',
    'shortName' => 'ConversionBackendInterface',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Each PDF-conversion backend implements canHandle, isAvailable, convert,
 * and name. The cascade (PdfConversionService) walks an ordered list of
 * backends, calls isAvailable() first (cheap check), then canHandle()
 * for the source\'s MIME/extension, then convert() — returning on the
 * first success and aggregating failures into a ConversionFailedException
 * when nothing succeeds.
 *
 * Backends are isolated; adding a new one is "drop a new class in this
 * directory and register it in DI".
 *
 * @category  Service
 * @package   OCA\\Filinq\\Service\\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 46,
    'endLine' => 97,
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
      'name' => 
      array (
        'name' => 'name',
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
 * Short identifier used in diagnostic surfaces and the 422 body\'s
 * `conversionAttempts[].backend` field.
 *
 * Stable across versions — operators read these strings in error
 * responses.
 *
 * @return string Backend identifier (lowercase, snake_case).
 */',
        'startLine' => 56,
        'endLine' => 56,
        'startColumn' => 2,
        'endColumn' => 32,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\ConversionBackendInterface',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\ConversionBackendInterface',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\ConversionBackendInterface',
        'aliasName' => NULL,
      ),
      'isAvailable' => 
      array (
        'name' => 'isAvailable',
        'parameters' => 
        array (
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
 * Whether this backend is usable in the current install. Cheap check:
 * tenant config flag, binary on PATH, app installed and configured.
 * Called per-conversion-attempt; expected to be O(1) after any
 * one-time setup.
 *
 * @return bool True when the backend can be invoked.
 */',
        'startLine' => 66,
        'endLine' => 66,
        'startColumn' => 2,
        'endColumn' => 37,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\ConversionBackendInterface',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\ConversionBackendInterface',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\ConversionBackendInterface',
        'aliasName' => NULL,
      ),
      'canHandle' => 
      array (
        'name' => 'canHandle',
        'parameters' => 
        array (
          'mimeType' => 
          array (
            'name' => 'mimeType',
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
            'startLine' => 77,
            'endLine' => 77,
            'startColumn' => 28,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'extension' => 
          array (
            'name' => 'extension',
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
            'startLine' => 77,
            'endLine' => 77,
            'startColumn' => 46,
            'endColumn' => 62,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Whether this backend can process the given MIME type / extension.
 * Cheap predicate; no I/O.
 *
 * @param string $mimeType MIME type reported by Nextcloud (e.g. application/pdf).
 * @param string $extension Lowercased file extension WITHOUT the dot (e.g. docx).
 *
 * @return bool True when this backend claims the input format.
 */',
        'startLine' => 77,
        'endLine' => 77,
        'startColumn' => 2,
        'endColumn' => 70,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\ConversionBackendInterface',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\ConversionBackendInterface',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\ConversionBackendInterface',
        'aliasName' => NULL,
      ),
      'convert' => 
      array (
        'name' => 'convert',
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
                'name' => 'OCP\\Files\\File',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 96,
            'endLine' => 96,
            'startColumn' => 26,
            'endColumn' => 37,
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
            'name' => 'OCP\\Files\\File',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Convert the source file to a PDF (PDF/A-3b when feasible) and
 * write the result beside the source in Nextcloud Files. The
 * source file is NOT deleted by the backend — replace/delete
 * orchestration belongs to the caller.
 *
 * @param File $source Source file node.
 *
 * @return File The newly written PDF file node.
 *
 * @throws ConversionFailedException When this backend genuinely
 *                                   cannot convert this input
 *                                   (use the typed exception
 *                                   rather than a generic one so
 *                                   the cascade can aggregate
 *                                   attempts cleanly).
 */',
        'startLine' => 96,
        'endLine' => 96,
        'startColumn' => 2,
        'endColumn' => 45,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Conversion',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Conversion\\ConversionBackendInterface',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Conversion\\ConversionBackendInterface',
        'currentClassName' => 'OCA\\Filinq\\Service\\Conversion\\ConversionBackendInterface',
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