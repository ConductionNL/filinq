<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Redaction/RedactionOutputModes.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Redaction\RedactionOutputModes
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-1b583e9cf202de1e4d760c72c0b0fe97b3c364ae2c2449ec5b41f5a04017d53c',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Redaction\\RedactionOutputModes',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Redaction/RedactionOutputModes.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Redaction',
    'name' => 'OCA\\Filinq\\Service\\Redaction\\RedactionOutputModes',
    'shortName' => 'RedactionOutputModes',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * The output modes, and which of them are verified.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 36,
    'endLine' => 129,
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
      'NATIVE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionOutputModes',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionOutputModes',
        'name' => 'NATIVE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'native\'',
          'attributes' => 
          array (
            'startLine' => 43,
            'endLine' => 43,
            'startTokenPos' => 37,
            'startFilePos' => 1465,
            'endTokenPos' => 37,
            'endFilePos' => 1472,
          ),
        ),
        'docComment' => '/**
 * The mode written in the document\'s own format.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 43,
        'endLine' => 43,
        'startColumn' => 2,
        'endColumn' => 32,
      ),
      'PDF' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionOutputModes',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionOutputModes',
        'name' => 'PDF',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'pdf\'',
          'attributes' => 
          array (
            'startLine' => 50,
            'endLine' => 50,
            'startTokenPos' => 50,
            'startFilePos' => 1579,
            'endTokenPos' => 50,
            'endFilePos' => 1583,
          ),
        ),
        'docComment' => '/**
 * Converted to PDF beside the native intermediate.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 50,
        'endLine' => 50,
        'startColumn' => 2,
        'endColumn' => 26,
      ),
      'PDF_ONLY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionOutputModes',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionOutputModes',
        'name' => 'PDF_ONLY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'pdf-only\'',
          'attributes' => 
          array (
            'startLine' => 57,
            'endLine' => 57,
            'startTokenPos' => 63,
            'startFilePos' => 1702,
            'endTokenPos' => 63,
            'endFilePos' => 1711,
          ),
        ),
        'docComment' => '/**
 * Converted to PDF, with the native intermediate deleted.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 2,
        'endColumn' => 36,
      ),
      'PDF_A' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionOutputModes',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionOutputModes',
        'name' => 'PDF_A',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'pdf/a\'',
          'attributes' => 
          array (
            'startLine' => 64,
            'endLine' => 64,
            'startTokenPos' => 76,
            'startFilePos' => 1793,
            'endTokenPos' => 76,
            'endFilePos' => 1799,
          ),
        ),
        'docComment' => '/**
 * The archival profile.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 64,
        'endLine' => 64,
        'startColumn' => 2,
        'endColumn' => 30,
      ),
      'PDF_UA' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionOutputModes',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionOutputModes',
        'name' => 'PDF_UA',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'pdf/ua\'',
          'attributes' => 
          array (
            'startLine' => 71,
            'endLine' => 71,
            'startTokenPos' => 89,
            'startFilePos' => 1887,
            'endTokenPos' => 89,
            'endFilePos' => 1894,
          ),
        ),
        'docComment' => '/**
 * The accessibility profile.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 71,
        'endLine' => 71,
        'startColumn' => 2,
        'endColumn' => 32,
      ),
      'ALL' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionOutputModes',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionOutputModes',
        'name' => 'ALL',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[self::NATIVE, self::PDF, self::PDF_ONLY, self::PDF_A, self::PDF_UA]',
          'attributes' => 
          array (
            'startLine' => 78,
            'endLine' => 84,
            'startTokenPos' => 102,
            'startFilePos' => 2008,
            'endTokenPos' => 129,
            'endFilePos' => 2089,
          ),
        ),
        'docComment' => '/**
 * Every mode a redacted copy can leave the building in.
 *
 * @var string[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 78,
        'endLine' => 84,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
      'VERIFIED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionOutputModes',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionOutputModes',
        'name' => 'VERIFIED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[self::NATIVE, self::PDF, self::PDF_ONLY, self::PDF_A, self::PDF_UA]',
          'attributes' => 
          array (
            'startLine' => 96,
            'endLine' => 102,
            'startTokenPos' => 142,
            'startFilePos' => 2483,
            'endTokenPos' => 169,
            'endFilePos' => 2564,
          ),
        ),
        'docComment' => '/**
 * The modes the verifier is wired into.
 *
 * 🔴 THIS IS NOT A COPY OF ALL, AND IT MUST NOT BECOME ONE BY HABIT. It is
 * a separate statement so that adding a mode to `ALL` and forgetting this
 * list is a test failure rather than a silent gap. Somebody adding a mode
 * has to make a deliberate claim that it is verified.
 *
 * @var string[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 96,
        'endLine' => 102,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'unverified' => 
      array (
        'name' => 'unverified',
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
 * Modes that write a copy and are not verified.
 *
 * @return string[] The gaps, empty when every mode is covered.
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */',
        'startLine' => 111,
        'endLine' => 113,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionOutputModes',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionOutputModes',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionOutputModes',
        'aliasName' => NULL,
      ),
      'isVerified' => 
      array (
        'name' => 'isVerified',
        'parameters' => 
        array (
          'mode' => 
          array (
            'name' => 'mode',
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
            'startLine' => 126,
            'endLine' => 126,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Whether this mode is verified before its output may be published.
 *
 * An unknown mode answers false rather than true. A mode nobody declared is
 * a mode nobody verified, and the safe reading of "I have not heard of
 * this" is not "it is fine".
 *
 * @param string $mode The output mode.
 *
 * @return bool True when the verifier covers it.
 */',
        'startLine' => 126,
        'endLine' => 128,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionOutputModes',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionOutputModes',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionOutputModes',
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