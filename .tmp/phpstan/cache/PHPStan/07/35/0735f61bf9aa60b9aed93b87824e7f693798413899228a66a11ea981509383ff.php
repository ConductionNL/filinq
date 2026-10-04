<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Exception/ConversionFailedException.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Exception\ConversionFailedException
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-d5a146ba4805393ad25238c85707666e9e5f8de7804ff59a754a1d35b99e6967',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Exception\\ConversionFailedException',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Exception/ConversionFailedException.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Exception',
    'name' => 'OCA\\Filinq\\Exception\\ConversionFailedException',
    'shortName' => 'ConversionFailedException',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Thrown when PdfConversionService::convertToPdf cannot complete the
 * conversion via any registered backend. The aggregated `attempts`
 * array surfaces which backends were tried and why each failed —
 * consumed by the controller layer to build the 422 response.
 *
 * @category  Exception
 * @package   OCA\\Filinq\\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 43,
    'endLine' => 88,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'RuntimeException',
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
      'attempts' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Exception\\ConversionFailedException',
        'implementingClassName' => 'OCA\\Filinq\\Exception\\ConversionFailedException',
        'name' => 'attempts',
        'modifiers' => 4,
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
        'docComment' => '/**
 * Per-backend attempt records. Each entry has the shape
 * `{name: string, available: bool, supports: bool, reason: string}`
 * documented in design.md D5.
 *
 * @var array<int, array{name:string,available:bool,supports:bool,reason:string}>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 52,
        'endLine' => 52,
        'startColumn' => 2,
        'endColumn' => 25,
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
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'PDF conversion failed: no backend in the cascade could convert the source file.\'',
              'attributes' => 
              array (
                'startLine' => 68,
                'endLine' => 68,
                'startTokenPos' => 63,
                'startFilePos' => 2294,
                'endTokenPos' => 63,
                'endFilePos' => 2374,
              ),
            ),
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
            'startLine' => 68,
            'endLine' => 68,
            'startColumn' => 3,
            'endColumn' => 101,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'attempts' => 
          array (
            'name' => 'attempts',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 69,
                'endLine' => 69,
                'startTokenPos' => 72,
                'startFilePos' => 2397,
                'endTokenPos' => 73,
                'endFilePos' => 2398,
              ),
            ),
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
            'startLine' => 69,
            'endLine' => 69,
            'startColumn' => 3,
            'endColumn' => 22,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'code' => 
          array (
            'name' => 'code',
            'default' => 
            array (
              'code' => '422',
              'attributes' => 
              array (
                'startLine' => 70,
                'endLine' => 70,
                'startTokenPos' => 82,
                'startFilePos' => 2415,
                'endTokenPos' => 82,
                'endFilePos' => 2417,
              ),
            ),
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
            'startLine' => 70,
            'endLine' => 70,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'previous' => 
          array (
            'name' => 'previous',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 71,
                'endLine' => 71,
                'startTokenPos' => 92,
                'startFilePos' => 2445,
                'endTokenPos' => 92,
                'endFilePos' => 2448,
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
                      'name' => 'Throwable',
                      'isIdentifier' => false,
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
            'startLine' => 71,
            'endLine' => 71,
            'startColumn' => 3,
            'endColumn' => 29,
            'parameterIndex' => 3,
            'isOptional' => true,
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
 * All arguments are optional so callers and tests may construct the
 * exception with only the parts they care about. The default code is
 * 422 (Unprocessable Entity), matching the documented anonymise-endpoint
 * response (design D5).
 *
 * @param string $message Human-readable summary.
 * @param array<int, array{name:string,available:bool,supports:bool,reason:string}> $attempts Per-backend records.
 * @param int $code HTTP-style status code (default 422).
 * @param Throwable|null $previous Underlying cause if any.
 */',
        'startLine' => 67,
        'endLine' => 76,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Exception',
        'declaringClassName' => 'OCA\\Filinq\\Exception\\ConversionFailedException',
        'implementingClassName' => 'OCA\\Filinq\\Exception\\ConversionFailedException',
        'currentClassName' => 'OCA\\Filinq\\Exception\\ConversionFailedException',
        'aliasName' => NULL,
      ),
      'getAttempts' => 
      array (
        'name' => 'getAttempts',
        'parameters' => 
        array (
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
 * Get the per-backend attempt records. Shape per D5: each entry
 * carries the backend name, whether it was available, whether it
 * supported the input, and a short human-readable reason.
 *
 * @return array<int, array{name:string,available:bool,supports:bool,reason:string}>
 */',
        'startLine' => 85,
        'endLine' => 87,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Exception',
        'declaringClassName' => 'OCA\\Filinq\\Exception\\ConversionFailedException',
        'implementingClassName' => 'OCA\\Filinq\\Exception\\ConversionFailedException',
        'currentClassName' => 'OCA\\Filinq\\Exception\\ConversionFailedException',
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