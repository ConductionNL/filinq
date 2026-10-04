<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Exception/Pdfa3ConversionException.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Exception\Pdfa3ConversionException
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-abea3fbed158a91e837764b320fcca47f67cb5740504aef494a08ea00e1aa140',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Exception/Pdfa3ConversionException.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Exception',
    'name' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
    'shortName' => 'Pdfa3ConversionException',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Thrown by Pdfa3ConversionService. Carries a stable machine-readable
 * `reason` code (used by the controller to pick an HTTP status) and an
 * `adminHint` string suitable for direct display to an administrator —
 * mirroring the guarded-command-runner pattern used elsewhere in the
 * fleet (e.g. LibreSign/soffice availability checks) so an operator
 * always gets an actionable message instead of a stack trace.
 *
 * @category  Exception
 * @package   OCA\\Filinq\\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 51,
    'endLine' => 153,
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
      'REASON_SOURCE_TOO_LARGE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
        'implementingClassName' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
        'name' => 'REASON_SOURCE_TOO_LARGE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'source_too_large\'',
          'attributes' => 
          array (
            'startLine' => 57,
            'endLine' => 57,
            'startTokenPos' => 49,
            'startFilePos' => 1920,
            'endTokenPos' => 49,
            'endFilePos' => 1937,
          ),
        ),
        'docComment' => '/**
 * Source file (or one of its attachments) exceeds the configured
 * byte cap. HTTP 413.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 2,
        'endColumn' => 59,
      ),
      'REASON_ATTACHMENT_TOO_LARGE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
        'implementingClassName' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
        'name' => 'REASON_ATTACHMENT_TOO_LARGE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'attachment_too_large\'',
          'attributes' => 
          array (
            'startLine' => 63,
            'endLine' => 63,
            'startTokenPos' => 62,
            'startFilePos' => 2075,
            'endTokenPos' => 62,
            'endFilePos' => 2096,
          ),
        ),
        'docComment' => '/**
 * An attachment to be embedded exceeds the configured byte cap.
 * HTTP 413.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 63,
        'endLine' => 63,
        'startColumn' => 2,
        'endColumn' => 67,
      ),
      'REASON_TIME_LIMIT_EXCEEDED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
        'implementingClassName' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
        'name' => 'REASON_TIME_LIMIT_EXCEEDED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'time_limit_exceeded\'',
          'attributes' => 
          array (
            'startLine' => 68,
            'endLine' => 68,
            'startTokenPos' => 75,
            'startFilePos' => 2215,
            'endTokenPos' => 75,
            'endFilePos' => 2235,
          ),
        ),
        'docComment' => '/**
 * Conversion exceeded the configured time budget. HTTP 504.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 68,
        'endLine' => 68,
        'startColumn' => 2,
        'endColumn' => 65,
      ),
      'REASON_CONVERTER_UNAVAILABLE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
        'implementingClassName' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
        'name' => 'REASON_CONVERTER_UNAVAILABLE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'converter_unavailable\'',
          'attributes' => 
          array (
            'startLine' => 74,
            'endLine' => 74,
            'startTokenPos' => 88,
            'startFilePos' => 2412,
            'endTokenPos' => 88,
            'endFilePos' => 2434,
          ),
        ),
        'docComment' => '/**
 * The PDF/A-3 converter (mPDF/FPDI) is disabled in tenant config or
 * its classes are not autoloadable. HTTP 503.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 74,
        'endLine' => 74,
        'startColumn' => 2,
        'endColumn' => 69,
      ),
      'REASON_SOURCE_UNREADABLE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
        'implementingClassName' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
        'name' => 'REASON_SOURCE_UNREADABLE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'source_unreadable\'',
          'attributes' => 
          array (
            'startLine' => 80,
            'endLine' => 80,
            'startTokenPos' => 101,
            'startFilePos' => 2623,
            'endTokenPos' => 101,
            'endFilePos' => 2641,
          ),
        ),
        'docComment' => '/**
 * The source could not be parsed as a PDF (corrupt, encrypted, or
 * not actually a PDF despite its declared MIME type). HTTP 422.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 80,
        'endLine' => 80,
        'startColumn' => 2,
        'endColumn' => 61,
      ),
      'REASON_RENDER_FAILED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
        'implementingClassName' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
        'name' => 'REASON_RENDER_FAILED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'render_failed\'',
          'attributes' => 
          array (
            'startLine' => 85,
            'endLine' => 85,
            'startTokenPos' => 114,
            'startFilePos' => 2765,
            'endTokenPos' => 114,
            'endFilePos' => 2779,
          ),
        ),
        'docComment' => '/**
 * MPDF raised during rendering/attachment/metadata assembly. HTTP 500.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 85,
        'endLine' => 85,
        'startColumn' => 2,
        'endColumn' => 53,
      ),
      'REASON_OUTPUT_VALIDATION_FAILED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
        'implementingClassName' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
        'name' => 'REASON_OUTPUT_VALIDATION_FAILED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'output_validation_failed\'',
          'attributes' => 
          array (
            'startLine' => 93,
            'endLine' => 93,
            'startTokenPos' => 127,
            'startFilePos' => 3073,
            'endTokenPos' => 127,
            'endFilePos' => 3098,
          ),
        ),
        'docComment' => '/**
 * The assembled output failed this service\'s own post-conversion
 * PDF/A-3 marker check (missing `%PDF` header or XMP
 * `pdfaid:part`/`pdfaid:conformance` identification) — the
 * no-silent-passthrough guardrail. HTTP 500.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 93,
        'endLine' => 93,
        'startColumn' => 2,
        'endColumn' => 75,
      ),
    ),
    'immediateProperties' => 
    array (
      'reason' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
        'implementingClassName' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
        'name' => 'reason',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Machine-readable reason code — one of the REASON_* constants.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 100,
        'endLine' => 100,
        'startColumn' => 2,
        'endColumn' => 24,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'adminHint' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
        'implementingClassName' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
        'name' => 'adminHint',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Human-readable, safe-to-display admin hint describing what an
 * operator should check or configure.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 108,
        'endLine' => 108,
        'startColumn' => 2,
        'endColumn' => 27,
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
          'reason' => 
          array (
            'name' => 'reason',
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
            'startLine' => 120,
            'endLine' => 120,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
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
            'startLine' => 121,
            'endLine' => 121,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'adminHint' => 
          array (
            'name' => 'adminHint',
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
            'startLine' => 122,
            'endLine' => 122,
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'code' => 
          array (
            'name' => 'code',
            'default' => 
            array (
              'code' => '500',
              'attributes' => 
              array (
                'startLine' => 123,
                'endLine' => 123,
                'startTokenPos' => 178,
                'startFilePos' => 3830,
                'endTokenPos' => 178,
                'endFilePos' => 3832,
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
            'startLine' => 123,
            'endLine' => 123,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 3,
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
                'startLine' => 124,
                'endLine' => 124,
                'startTokenPos' => 188,
                'startFilePos' => 3860,
                'endTokenPos' => 188,
                'endFilePos' => 3863,
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
            'startLine' => 124,
            'endLine' => 124,
            'startColumn' => 3,
            'endColumn' => 29,
            'parameterIndex' => 4,
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
 * @param string $reason One of the REASON_* constants.
 * @param string $message Human-readable summary (safe for API responses).
 * @param string $adminHint Actionable hint for an administrator.
 * @param int $code HTTP-style status code.
 * @param Throwable|null $previous Underlying cause if any.
 */',
        'startLine' => 119,
        'endLine' => 130,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Exception',
        'declaringClassName' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
        'implementingClassName' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
        'currentClassName' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
        'aliasName' => NULL,
      ),
      'getReason' => 
      array (
        'name' => 'getReason',
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
 * Get the machine-readable reason code.
 *
 * @return string One of the REASON_* constants.
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 */',
        'startLine' => 139,
        'endLine' => 141,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Exception',
        'declaringClassName' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
        'implementingClassName' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
        'currentClassName' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
        'aliasName' => NULL,
      ),
      'getAdminHint' => 
      array (
        'name' => 'getAdminHint',
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
 * Get the admin-facing hint.
 *
 * @return string
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 */',
        'startLine' => 150,
        'endLine' => 152,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Exception',
        'declaringClassName' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
        'implementingClassName' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
        'currentClassName' => 'OCA\\Filinq\\Exception\\Pdfa3ConversionException',
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