<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/PdfConversionService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\PdfConversionService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-2f3abe87872c3a8f3d9c0f3962e1a044b1d6dc752c2785246ae2318cac6539a1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\PdfConversionService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/PdfConversionService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\PdfConversionService',
    'shortName' => 'PdfConversionService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Walks a cascade of conversion backends, returning the first success
 * and aggregating failures.
 *
 * The backend list is injected (ordered); DI registers concrete
 * backends in design D2 priority order. The walk is:
 *
 *   for each backend in order:
 *     if not isAvailable() → record attempt {available: false} and continue
 *     if not canHandle()   → record attempt {supports: false} and continue
 *     try convert()
 *       success → return file
 *       failure → record attempt {available: true, supports: true, reason: <exception message>}
 *   if no success → throw ConversionFailedException(attempts)
 *
 * @category  Service
 * @package   OCA\\Filinq\\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-conversion-routes-through-the-nextcloud-conversion-broker
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 59,
    'endLine' => 209,
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
      'backends' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PdfConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PdfConversionService',
        'name' => 'backends',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 67,
        'endLine' => 67,
        'startColumn' => 3,
        'endColumn' => 34,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PdfConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PdfConversionService',
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
        'startLine' => 68,
        'endLine' => 68,
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
          'backends' => 
          array (
            'name' => 'backends',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 3,
            'endColumn' => 34,
            'parameterIndex' => 0,
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
            'startLine' => 68,
            'endLine' => 68,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor.
 *
 * @param array<int, ConversionBackendInterface> $backends Ordered list of backends; first success wins.
 * @param LoggerInterface $logger Logger for per-attempt diagnostics.
 */',
        'startLine' => 66,
        'endLine' => 71,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PdfConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PdfConversionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PdfConversionService',
        'aliasName' => NULL,
      ),
      'convertToPdf' => 
      array (
        'name' => 'convertToPdf',
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
            'startLine' => 84,
            'endLine' => 84,
            'startColumn' => 31,
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
            'name' => 'OCP\\Files\\File',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Convert the source file to PDF via the backend cascade.
 *
 * @param File $source Source file (any supported input format).
 *
 * @return File The newly written PDF file node.
 *
 * @throws ConversionFailedException When no backend in the cascade succeeded.
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-conversion-routes-through-the-nextcloud-conversion-broker
 */',
        'startLine' => 84,
        'endLine' => 86,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PdfConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PdfConversionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PdfConversionService',
        'aliasName' => NULL,
      ),
      'convertToPdfReporting' => 
      array (
        'name' => 'convertToPdfReporting',
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
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 40,
            'endColumn' => 51,
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
 * Convert to PDF and report WHICH backend claimed the conversion.
 *
 * Same cascade, same failure. The difference is that the caller learns
 * whether an office app produced the PDF or the mPDF fallback did -- two
 * results with visibly different fidelity that are otherwise
 * indistinguishable to anyone reading a log after the fact.
 *
 * @param File $source Source file (any supported input format).
 *
 * @return array{file: File, backend: string} The PDF and the backend that produced it.
 *
 * @throws ConversionFailedException When no backend in the cascade succeeded.
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-conversion-routes-through-the-nextcloud-conversion-broker
 */',
        'startLine' => 104,
        'endLine' => 191,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PdfConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PdfConversionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PdfConversionService',
        'aliasName' => NULL,
      ),
      'extractExtension' => 
      array (
        'name' => 'extractExtension',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
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
            'startLine' => 201,
            'endLine' => 201,
            'startColumn' => 36,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Return the lowercased extension of $name without the leading dot.
 *
 * @param string $name File name, with or without an extension.
 *
 * @return string Lowercased extension, or an empty string when the name
 *                carries no dot.
 */',
        'startLine' => 201,
        'endLine' => 208,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PdfConversionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PdfConversionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PdfConversionService',
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