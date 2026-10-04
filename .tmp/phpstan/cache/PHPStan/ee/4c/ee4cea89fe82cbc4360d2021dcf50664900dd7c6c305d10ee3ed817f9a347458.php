<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Validation/DocumentFileInspector.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Validation\DocumentFileInspector
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-41aa76c0e011f668583c148acc3f0a9eeef058275111d0c81554337e7e35b099',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Validation/DocumentFileInspector.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Validation',
    'name' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
    'shortName' => 'DocumentFileInspector',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Runs the file-level probes used by the validation checks.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Validation
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-validation-checks/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 43,
    'endLine' => 220,
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
      'CONFIG_TEXT_LAYER_MIN' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'name' => 'CONFIG_TEXT_LAYER_MIN',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'validation.text_layer_min_chars_per_page\'',
          'attributes' => 
          array (
            'startLine' => 50,
            'endLine' => 50,
            'startTokenPos' => 50,
            'startFilePos' => 1392,
            'endTokenPos' => 50,
            'endFilePos' => 1433,
          ),
        ),
        'docComment' => '/**
 * App config key for the minimum chars-per-page threshold.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 50,
        'endLine' => 50,
        'startColumn' => 2,
        'endColumn' => 82,
      ),
      'DEFAULT_TEXT_LAYER_MIN' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'name' => 'DEFAULT_TEXT_LAYER_MIN',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '32',
          'attributes' => 
          array (
            'startLine' => 57,
            'endLine' => 57,
            'startTokenPos' => 63,
            'startFilePos' => 1559,
            'endTokenPos' => 63,
            'endFilePos' => 1560,
          ),
        ),
        'docComment' => '/**
 * Default minimum extracted characters per page.
 *
 * @var integer
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 2,
        'endColumn' => 43,
      ),
      'EXTENSION_MIME_MAP' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'name' => 'EXTENSION_MIME_MAP',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'pdf\' => [\'application/pdf\'], \'docx\' => [\'application/vnd.openxmlformats-officedocument.wordprocessingml.document\'], \'doc\' => [\'application/msword\'], \'odt\' => [\'application/vnd.oasis.opendocument.text\'], \'txt\' => [\'text/plain\'], \'md\' => [\'text/markdown\', \'text/plain\'], \'html\' => [\'text/html\'], \'htm\' => [\'text/html\']]',
          'attributes' => 
          array (
            'startLine' => 64,
            'endLine' => 73,
            'startTokenPos' => 76,
            'startFilePos' => 1726,
            'endTokenPos' => 153,
            'endFilePos' => 2064,
          ),
        ),
        'docComment' => '/**
 * Extension → expected mime prefixes used by the mismatch check.
 *
 * @var array<string, array<int, string>>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 64,
        'endLine' => 73,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'appConfig' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'name' => 'appConfig',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IAppConfig',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 83,
        'endLine' => 83,
        'startColumn' => 3,
        'endColumn' => 40,
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
          'appConfig' => 
          array (
            'name' => 'appConfig',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IAppConfig',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 3,
            'endColumn' => 40,
            'parameterIndex' => 0,
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
 * @param IAppConfig $appConfig App configuration.
 *
 * @return void
 */',
        'startLine' => 82,
        'endLine' => 86,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Validation',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'currentClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'aliasName' => NULL,
      ),
      'safeMimeType' => 
      array (
        'name' => 'safeMimeType',
        'parameters' => 
        array (
          'file' => 
          array (
            'name' => 'file',
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
            'startLine' => 97,
            'endLine' => 97,
            'startColumn' => 31,
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
 * Read a file\'s mime type defensively.
 *
 * @param File $file The file.
 *
 * @return string The mime type or \'\'.
 *
 * @spec openspec/specs/document-validation-checks/spec.md
 */',
        'startLine' => 97,
        'endLine' => 104,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Validation',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'currentClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'aliasName' => NULL,
      ),
      'safeName' => 
      array (
        'name' => 'safeName',
        'parameters' => 
        array (
          'file' => 
          array (
            'name' => 'file',
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
            'startLine' => 115,
            'endLine' => 115,
            'startColumn' => 27,
            'endColumn' => 36,
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
 * Read a file\'s name defensively.
 *
 * @param File $file The file.
 *
 * @return string The name or \'\'.
 *
 * @spec openspec/specs/document-validation-checks/spec.md
 */',
        'startLine' => 115,
        'endLine' => 122,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Validation',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'currentClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'aliasName' => NULL,
      ),
      'extensionMismatches' => 
      array (
        'name' => 'extensionMismatches',
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
            'startLine' => 134,
            'endLine' => 134,
            'startColumn' => 38,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'mime' => 
          array (
            'name' => 'mime',
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
            'startLine' => 134,
            'endLine' => 134,
            'startColumn' => 52,
            'endColumn' => 63,
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
 * Whether the file extension contradicts the detected mime type.
 *
 * @param string $name The file name.
 * @param string $mime The detected mime type.
 *
 * @return bool True when a known extension maps to a different mime.
 *
 * @spec openspec/specs/document-validation-checks/spec.md
 */',
        'startLine' => 134,
        'endLine' => 150,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Validation',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'currentClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'aliasName' => NULL,
      ),
      'isPdfEncrypted' => 
      array (
        'name' => 'isPdfEncrypted',
        'parameters' => 
        array (
          'content' => 
          array (
            'name' => 'content',
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
            'startLine' => 164,
            'endLine' => 164,
            'startColumn' => 33,
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
 * Heuristic: whether a PDF byte stream is encrypted.
 *
 * Looks for an `/Encrypt` entry in the trailer, which a non-encrypted PDF
 * does not carry. Cheap and parser-free.
 *
 * @param string $content The PDF bytes.
 *
 * @return bool True when encrypted.
 *
 * @spec openspec/specs/document-validation-checks/spec.md
 */',
        'startLine' => 164,
        'endLine' => 170,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Validation',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'currentClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'aliasName' => NULL,
      ),
      'textLayerMissing' => 
      array (
        'name' => 'textLayerMissing',
        'parameters' => 
        array (
          'content' => 
          array (
            'name' => 'content',
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
            'startLine' => 185,
            'endLine' => 185,
            'startColumn' => 35,
            'endColumn' => 49,
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
 * Heuristic: whether a PDF lacks a usable text layer.
 *
 * Counts PDF page objects (`/Type /Page`) and the text-show operators
 * (`Tj`/`TJ`); when the average extractable signal per page falls below the
 * configured threshold, the text layer is considered missing (scan-only).
 *
 * @param string $content The PDF bytes.
 *
 * @return bool True when the text layer is missing.
 *
 * @spec openspec/specs/document-validation-checks/spec.md
 */',
        'startLine' => 185,
        'endLine' => 205,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Validation',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'currentClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'aliasName' => NULL,
      ),
      'getTextLayerMin' => 
      array (
        'name' => 'getTextLayerMin',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Read the configured minimum chars-per-page threshold.
 *
 * @return int The threshold.
 */',
        'startLine' => 212,
        'endLine' => 219,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Validation',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
        'currentClassName' => 'OCA\\Filinq\\Service\\Validation\\DocumentFileInspector',
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