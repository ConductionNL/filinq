<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/SubjectErasure/SubjectErasurePreview.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\SubjectErasure\SubjectErasurePreview
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-d16b82b0ef1b7f7de708f6b2925ddb8f44ac57592cb101faed7c6d5e684ab0a3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasurePreview',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/SubjectErasure/SubjectErasurePreview.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\SubjectErasure',
    'name' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasurePreview',
    'shortName' => 'SubjectErasurePreview',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Builds the preview an operator reads before an erasure runs.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 47,
    'endLine' => 166,
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
      'CAP' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasurePreview',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasurePreview',
        'name' => 'CAP',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '200',
          'attributes' => 
          array (
            'startLine' => 54,
            'endLine' => 54,
            'startTokenPos' => 35,
            'startFilePos' => 2035,
            'endTokenPos' => 35,
            'endFilePos' => 2037,
          ),
        ),
        'docComment' => '/**
 * How many documents are listed before the preview is capped.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 54,
        'endLine' => 54,
        'startColumn' => 2,
        'endColumn' => 24,
      ),
    ),
    'immediateProperties' => 
    array (
      'rules' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasurePreview',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasurePreview',
        'name' => 'rules',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
            'isIdentifier' => false,
          ),
        ),
        'default' => 
        array (
          'code' => 'new \\OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules()',
          'attributes' => 
          array (
            'startLine' => 61,
            'endLine' => 61,
            'startTokenPos' => 56,
            'startFilePos' => 2219,
            'endTokenPos' => 60,
            'endFilePos' => 2243,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 61,
        'endLine' => 61,
        'startColumn' => 30,
        'endColumn' => 100,
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
          'rules' => 
          array (
            'name' => 'rules',
            'default' => 
            array (
              'code' => 'new \\OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules()',
              'attributes' => 
              array (
                'startLine' => 61,
                'endLine' => 61,
                'startTokenPos' => 56,
                'startFilePos' => 2219,
                'endTokenPos' => 60,
                'endFilePos' => 2243,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 61,
            'endLine' => 61,
            'startColumn' => 30,
            'endColumn' => 100,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Wire the preview.
 *
 * @param SubjectErasureRules $rules The obligations that refuse.
 */',
        'startLine' => 61,
        'endLine' => 62,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\SubjectErasure',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasurePreview',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasurePreview',
        'currentClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasurePreview',
        'aliasName' => NULL,
      ),
      'build' => 
      array (
        'name' => 'build',
        'parameters' => 
        array (
          'documents' => 
          array (
            'name' => 'documents',
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
            'startLine' => 76,
            'endLine' => 76,
            'startColumn' => 24,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'cap' => 
          array (
            'name' => 'cap',
            'default' => 
            array (
              'code' => 'self::CAP',
              'attributes' => 
              array (
                'startLine' => 76,
                'endLine' => 76,
                'startTokenPos' => 87,
                'startFilePos' => 2852,
                'endTokenPos' => 89,
                'endFilePos' => 2860,
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
            'startLine' => 76,
            'endLine' => 76,
            'startColumn' => 42,
            'endColumn' => 61,
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
 * Build the preview.
 *
 * @param array<int, array<string, mixed>> $documents Candidate documents, each with
 *                                                    `id`, `occurrences`, `finalVersion`,
 *                                                    `unreadable` and any obligations.
 * @param int                              $cap       How many to list.
 *
 * @return array<string, mixed> The preview.
 *
 * @spec openspec/changes/erase-a-person-while-the-records-stay/specs/anonymization-link/spec.md
 */',
        'startLine' => 76,
        'endLine' => 134,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\SubjectErasure',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasurePreview',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasurePreview',
        'currentClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasurePreview',
        'aliasName' => NULL,
      ),
      'refuseExclusions' => 
      array (
        'name' => 'refuseExclusions',
        'parameters' => 
        array (
          'exclusions' => 
          array (
            'name' => 'exclusions',
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
            'startLine' => 149,
            'endLine' => 149,
            'startColumn' => 35,
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
 * Apply an operator\'s exclusions to a preview.
 *
 * An exclusion without a reason is refused rather than silently kept: a
 * voorkomen left standing is something the requester can ask about, and
 * "somebody unticked it" is not an answer.
 *
 * @param array<int, array<string, mixed>> $exclusions The exclusions asked for.
 *
 * @return string[] The refusals, empty when every exclusion carries a reason.
 *
 * @spec openspec/changes/erase-a-person-while-the-records-stay/specs/anonymization-link/spec.md
 */',
        'startLine' => 149,
        'endLine' => 165,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\SubjectErasure',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasurePreview',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasurePreview',
        'currentClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasurePreview',
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