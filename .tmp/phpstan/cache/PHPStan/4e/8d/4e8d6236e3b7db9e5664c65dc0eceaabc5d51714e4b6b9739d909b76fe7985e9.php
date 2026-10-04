<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Redaction/PublicationListComposer.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Redaction\PublicationListComposer
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-207d6e76970a69272a4c98388105f3b0c374a6b4a006727a0172a1f3fd315b47',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Redaction\\PublicationListComposer',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Redaction/PublicationListComposer.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Redaction',
    'name' => 'OCA\\Filinq\\Service\\Redaction\\PublicationListComposer',
    'shortName' => 'PublicationListComposer',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Composes one publishable document over the records a saved view returns.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 43,
    'endLine' => 203,
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
      'NO_SUCH_VIEW' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\PublicationListComposer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\PublicationListComposer',
        'name' => 'NO_SUCH_VIEW',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'no_such_view\'',
          'attributes' => 
          array (
            'startLine' => 50,
            'endLine' => 50,
            'startTokenPos' => 40,
            'startFilePos' => 1841,
            'endTokenPos' => 40,
            'endFilePos' => 1854,
          ),
        ),
        'docComment' => '/**
 * The view does not resolve at all.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 50,
        'endLine' => 50,
        'startColumn' => 2,
        'endColumn' => 44,
      ),
      'NOT_READY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\PublicationListComposer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\PublicationListComposer',
        'name' => 'NOT_READY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'not_ready\'',
          'attributes' => 
          array (
            'startLine' => 57,
            'endLine' => 57,
            'startTokenPos' => 53,
            'startFilePos' => 1965,
            'endTokenPos' => 53,
            'endFilePos' => 1975,
          ),
        ),
        'docComment' => '/**
 * One or more records have no redacted copy yet.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 2,
        'endColumn' => 38,
      ),
    ),
    'immediateProperties' => 
    array (
      'views' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\PublicationListComposer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\PublicationListComposer',
        'name' => 'views',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\SavedViewReader',
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
        'endColumn' => 41,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'links' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\PublicationListComposer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\PublicationListComposer',
        'name' => 'links',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Redaction\\AnonymizationLinkReader',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 69,
        'endLine' => 69,
        'startColumn' => 3,
        'endColumn' => 49,
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
          'views' => 
          array (
            'name' => 'views',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\SavedViewReader',
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
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'links' => 
          array (
            'name' => 'links',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Redaction\\AnonymizationLinkReader',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 69,
            'endLine' => 69,
            'startColumn' => 3,
            'endColumn' => 49,
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
 * @param SavedViewReader          $views The saved view and its records.
 * @param AnonymizationLinkReader  $links The source-to-copy pairing.
 *
 * @return void
 */',
        'startLine' => 67,
        'endLine' => 72,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\PublicationListComposer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\PublicationListComposer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\PublicationListComposer',
        'aliasName' => NULL,
      ),
      'compose' => 
      array (
        'name' => 'compose',
        'parameters' => 
        array (
          'view' => 
          array (
            'name' => 'view',
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
            'startLine' => 84,
            'endLine' => 84,
            'startColumn' => 26,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'title' => 
          array (
            'name' => 'title',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 84,
                'endLine' => 84,
                'startTokenPos' => 109,
                'startFilePos' => 2737,
                'endTokenPos' => 109,
                'endFilePos' => 2738,
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
            'startLine' => 84,
            'endLine' => 84,
            'startColumn' => 40,
            'endColumn' => 57,
            'parameterIndex' => 1,
            'isOptional' => true,
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
 * Compose the list over one saved view.
 *
 * @param string $view  The view id or slug.
 * @param string $title What the list is called.
 *
 * @return array<string, mixed> The composed list, or a refusal carrying `refused`.
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */',
        'startLine' => 84,
        'endLine' => 151,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\PublicationListComposer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\PublicationListComposer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\PublicationListComposer',
        'aliasName' => NULL,
      ),
      'fieldsOf' => 
      array (
        'name' => 'fieldsOf',
        'parameters' => 
        array (
          'record' => 
          array (
            'name' => 'record',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 162,
            'endLine' => 162,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * One record\'s fields, whatever shape OpenRegister returned it in.
 *
 * @param mixed $record The record.
 *
 * @return array<string, mixed> The fields.
 *
 * @spec exclude Shape adapter over an OpenRegister response.
 */',
        'startLine' => 162,
        'endLine' => 178,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\PublicationListComposer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\PublicationListComposer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\PublicationListComposer',
        'aliasName' => NULL,
      ),
      'labelOf' => 
      array (
        'name' => 'labelOf',
        'parameters' => 
        array (
          'fields' => 
          array (
            'name' => 'fields',
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
            'startLine' => 190,
            'endLine' => 190,
            'startColumn' => 27,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'sourceFileId' => 
          array (
            'name' => 'sourceFileId',
            'default' => NULL,
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
            'startLine' => 190,
            'endLine' => 190,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * What to call one record in the list.
 *
 * @param array<string, mixed> $fields       The record\'s fields.
 * @param int                  $sourceFileId Its source file id.
 *
 * @return string The label.
 *
 * @spec exclude Naming helper behind compose().
 */',
        'startLine' => 190,
        'endLine' => 202,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\PublicationListComposer',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\PublicationListComposer',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\PublicationListComposer',
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