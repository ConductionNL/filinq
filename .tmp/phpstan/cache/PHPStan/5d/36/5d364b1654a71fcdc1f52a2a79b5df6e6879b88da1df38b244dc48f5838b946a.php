<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/SubjectErasure/SubjectErasureRules.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\SubjectErasure\SubjectErasureRules
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-e09ef97eacaef32e68fd52e89fe1ebbf5ac50db75394dc7f30d1756dc4b15320',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/SubjectErasure/SubjectErasureRules.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\SubjectErasure',
    'name' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
    'shortName' => 'SubjectErasureRules',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * The obligations that refuse an erasure, and the certificate it produces.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 62,
    'endLine' => 250,
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
      'RETENTION' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
        'name' => 'RETENTION',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'retention\'',
          'attributes' => 
          array (
            'startLine' => 69,
            'endLine' => 69,
            'startTokenPos' => 35,
            'startFilePos' => 2971,
            'endTokenPos' => 35,
            'endFilePos' => 2981,
          ),
        ),
        'docComment' => '/**
 * A retention obligation: the record must be kept for a term.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 69,
        'endLine' => 69,
        'startColumn' => 2,
        'endColumn' => 38,
      ),
      'PROHIBITION' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
        'name' => 'PROHIBITION',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'publication_prohibition\'',
          'attributes' => 
          array (
            'startLine' => 76,
            'endLine' => 76,
            'startTokenPos' => 48,
            'startFilePos' => 3102,
            'endTokenPos' => 48,
            'endFilePos' => 3126,
          ),
        ),
        'docComment' => '/**
 * A publication prohibition standing over this document.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 76,
        'endLine' => 76,
        'startColumn' => 2,
        'endColumn' => 54,
      ),
      'LEGAL_HOLD' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
        'name' => 'LEGAL_HOLD',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'legal_hold\'',
          'attributes' => 
          array (
            'startLine' => 83,
            'endLine' => 83,
            'startTokenPos' => 61,
            'startFilePos' => 3252,
            'endTokenPos' => 61,
            'endFilePos' => 3263,
          ),
        ),
        'docComment' => '/**
 * A legal hold: somebody may still need this exactly as it is.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 83,
        'endLine' => 83,
        'startColumn' => 2,
        'endColumn' => 40,
      ),
      'OBLIGATIONS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
        'name' => 'OBLIGATIONS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[self::LEGAL_HOLD, self::PROHIBITION, self::RETENTION]',
          'attributes' => 
          array (
            'startLine' => 90,
            'endLine' => 90,
            'startTokenPos' => 74,
            'startFilePos' => 3394,
            'endTokenPos' => 88,
            'endFilePos' => 3447,
          ),
        ),
        'docComment' => '/**
 * Every obligation that refuses, in the order they are reported.
 *
 * @var string[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 90,
        'endLine' => 90,
        'startColumn' => 2,
        'endColumn' => 83,
      ),
      'DECIDES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
        'name' => 'DECIDES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[self::LEGAL_HOLD => \'the legal department that placed the hold\', self::PROHIBITION => \'a policy administrator (docudesk-policy-admins)\', self::RETENTION => \'the archivist responsible for the selectielijst term\']',
          'attributes' => 
          array (
            'startLine' => 98,
            'endLine' => 102,
            'startTokenPos' => 101,
            'startFilePos' => 3663,
            'endTokenPos' => 130,
            'endFilePos' => 3884,
          ),
        ),
        'docComment' => '/**
 * Who decides, per obligation. A refusal nobody can escalate reads as a
 * silent failure, and the requester has a statutory clock running.
 *
 * @var array<string, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 98,
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
      'refusals' => 
      array (
        'name' => 'refusals',
        'parameters' => 
        array (
          'document' => 
          array (
            'name' => 'document',
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
            'startLine' => 117,
            'endLine' => 117,
            'startColumn' => 27,
            'endColumn' => 41,
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
 * Why this document may not be erased, if it may not.
 *
 * Returns EVERY obligation rather than the first: a requester told about a
 * legal hold, who waits for it to lift, and then meets a retention term,
 * has been answered twice and helped once.
 *
 * @param array<string, mixed> $document The document\'s obligations.
 *
 * @return array<int, array<string, mixed>> The refusals, empty when it may be erased.
 *
 * @spec openspec/changes/erase-a-person-while-the-records-stay/specs/anonymization-link/spec.md
 */',
        'startLine' => 117,
        'endLine' => 134,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\SubjectErasure',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
        'currentClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
        'aliasName' => NULL,
      ),
      'screen' => 
      array (
        'name' => 'screen',
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
            'startLine' => 151,
            'endLine' => 151,
            'startColumn' => 25,
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
 * Screen a whole request: what will be erased, and what will not and why.
 *
 * 🔴 ONE HELD DOCUMENT DOES NOT DENY THE REQUEST. The platform refuses the
 * whole act under a hold, which is proportionate for a retention sweep it
 * chose to run. A person exercising a right did not choose these documents,
 * and refusing all of them because one is held answers a request that was
 * mostly grantable with a flat no.
 *
 * @param array<int, array<string, mixed>> $documents Each with its obligations.
 *
 * @return array{erase: array<int, string>, refused: array<int, array<string, mixed>>}
 *
 * @spec openspec/changes/erase-a-person-while-the-records-stay/specs/anonymization-link/spec.md
 */',
        'startLine' => 151,
        'endLine' => 168,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\SubjectErasure',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
        'currentClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
        'aliasName' => NULL,
      ),
      'treatmentErases' => 
      array (
        'name' => 'treatmentErases',
        'parameters' => 
        array (
          'treatment' => 
          array (
            'name' => 'treatment',
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
            'startLine' => 184,
            'endLine' => 184,
            'startColumn' => 34,
            'endColumn' => 50,
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
 * Whether this treatment may be used to erase a person.
 *
 * 🔴 `pseudonym` MAY NOT. The platform\'s pseudonym is stable and joinable
 * ON PURPOSE, so an archival copy keeps its statistics. Applied to a
 * subject erasure it keeps the linkage the request exists to break: every
 * document that mentioned the person still points at the same token.
 *
 * @param string $treatment The anonymisation treatment.
 *
 * @return bool True when it genuinely removes the person.
 *
 * @spec openspec/changes/erase-a-person-while-the-records-stay/specs/anonymization-link/spec.md
 */',
        'startLine' => 184,
        'endLine' => 186,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\SubjectErasure',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
        'currentClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
        'aliasName' => NULL,
      ),
      'certificate' => 
      array (
        'name' => 'certificate',
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
            'startLine' => 205,
            'endLine' => 205,
            'startColumn' => 30,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'erased' => 
          array (
            'name' => 'erased',
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
            'startLine' => 205,
            'endLine' => 205,
            'startColumn' => 46,
            'endColumn' => 58,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'refused' => 
          array (
            'name' => 'refused',
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
            'startLine' => 205,
            'endLine' => 205,
            'startColumn' => 61,
            'endColumn' => 74,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'republish' => 
          array (
            'name' => 'republish',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 205,
                'endLine' => 205,
                'startTokenPos' => 503,
                'startFilePos' => 7655,
                'endTokenPos' => 504,
                'endFilePos' => 7656,
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
            'startLine' => 205,
            'endLine' => 205,
            'startColumn' => 77,
            'endColumn' => 97,
            'parameterIndex' => 3,
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
 * The certificate a completed request produces.
 *
 * Carries the refusals as prominently as the erasures. A certificate that
 * lists only what was erased reads as a completed request, and the
 * documents that were refused are exactly the ones the requester has to be
 * told about.
 *
 * @param array<string, mixed> $request   The request being certified.
 * @param array<int, mixed>    $erased    What was erased, with counts.
 * @param array<int, mixed>    $refused   What was refused, with obligations.
 * @param array<int, string>   $republish Anything already published that now differs.
 *
 * @return array<string, mixed> The certificate.
 *
 * @spec openspec/changes/erase-a-person-while-the-records-stay/specs/anonymization-link/spec.md
 */',
        'startLine' => 205,
        'endLine' => 221,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\SubjectErasure',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
        'currentClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
        'aliasName' => NULL,
      ),
      'reasonFor' => 
      array (
        'name' => 'reasonFor',
        'parameters' => 
        array (
          'obligation' => 
          array (
            'name' => 'obligation',
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
            'startLine' => 231,
            'endLine' => 231,
            'startColumn' => 29,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'standing' => 
          array (
            'name' => 'standing',
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
            'startLine' => 231,
            'endLine' => 231,
            'startColumn' => 49,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * One obligation as a sentence.
 *
 * @param string $obligation The obligation.
 * @param mixed  $standing   What the document records about it.
 *
 * @return string The sentence.
 */',
        'startLine' => 231,
        'endLine' => 249,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\SubjectErasure',
        'declaringClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
        'implementingClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
        'currentClassName' => 'OCA\\Filinq\\Service\\SubjectErasure\\SubjectErasureRules',
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