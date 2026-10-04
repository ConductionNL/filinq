<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Redaction/RedactionReviewGate.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Redaction\RedactionReviewGate
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-4eadab3ff7126e8cb94486825abb399be8e794146d33f872562ac8fc4101465b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Redaction/RedactionReviewGate.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Redaction',
    'name' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
    'shortName' => 'RedactionReviewGate',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Decides whether a redacted copy may be written yet.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 47,
    'endLine' => 253,
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
      'NEVER_CHECKED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'name' => 'NEVER_CHECKED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'never_checked\'',
          'attributes' => 
          array (
            'startLine' => 73,
            'endLine' => 73,
            'startTokenPos' => 70,
            'startFilePos' => 2637,
            'endTokenPos' => 70,
            'endFilePos' => 2651,
          ),
        ),
        'docComment' => '/**
 * Nobody has checked this document at all.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 2,
        'endColumn' => 46,
      ),
      'CHECKED_AN_OLDER_RUN' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'name' => 'CHECKED_AN_OLDER_RUN',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'checked_an_older_run\'',
          'attributes' => 
          array (
            'startLine' => 80,
            'endLine' => 80,
            'startTokenPos' => 83,
            'startFilePos' => 2773,
            'endTokenPos' => 83,
            'endFilePos' => 2794,
          ),
        ),
        'docComment' => '/**
 * It was checked, and then detection was re-run.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 80,
        'endLine' => 80,
        'startColumn' => 2,
        'endColumn' => 60,
      ),
      'UNATTRIBUTED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'name' => 'UNATTRIBUTED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'unattributed\'',
          'attributes' => 
          array (
            'startLine' => 87,
            'endLine' => 87,
            'startTokenPos' => 96,
            'startFilePos' => 2934,
            'endTokenPos' => 96,
            'endFilePos' => 2947,
          ),
        ),
        'docComment' => '/**
 * A mark exists but does not say who checked it, so nobody is accountable.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 87,
        'endLine' => 87,
        'startColumn' => 2,
        'endColumn' => 44,
      ),
    ),
    'immediateProperties' => 
    array (
      'l10n' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'name' => 'l10n',
        'modifiers' => 132,
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
                  'name' => 'OCP\\IL10N',
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
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 63,
            'endLine' => 63,
            'startTokenPos' => 50,
            'startFilePos' => 2497,
            'endTokenPos' => 50,
            'endFilePos' => 2500,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 63,
        'endLine' => 63,
        'startColumn' => 3,
        'endColumn' => 38,
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
          'l10n' => 
          array (
            'name' => 'l10n',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 63,
                'endLine' => 63,
                'startTokenPos' => 50,
                'startFilePos' => 2497,
                'endTokenPos' => 50,
                'endFilePos' => 2500,
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
                      'name' => 'OCP\\IL10N',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 63,
            'endLine' => 63,
            'startColumn' => 3,
            'endColumn' => 38,
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
 * Constructor.
 *
 * 🔴 THE REFUSAL IS THE ONLY THING A USER EVER SEES OF THIS CLASS, so it
 * is translated. `IL10N` is nullable and defaults to null, the pattern
 * `LegalBasesSummaryService` already uses here: dependency injection always
 * supplies it, and where it is absent the English source string is
 * returned, which is a correct message rather than a placeholder.
 *
 * @param IL10N|null $l10n The acting user\'s language, when there is one.
 *
 * @return void
 */',
        'startLine' => 62,
        'endLine' => 66,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'aliasName' => NULL,
      ),
      'refuse' => 
      array (
        'name' => 'refuse',
        'parameters' => 
        array (
          'mark' => 
          array (
            'name' => 'mark',
            'default' => NULL,
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
                      'name' => 'array',
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
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 25,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'detectionRunId' => 
          array (
            'name' => 'detectionRunId',
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
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 39,
            'endColumn' => 60,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
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
                  'name' => 'array',
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Why this document may not be written yet, if it may not.
 *
 * @param array<string, mixed>|null $mark            The review mark, or null.
 * @param string                    $detectionRunId  The detection run being published.
 *
 * @return array<string, mixed>|null The refusal, or null when output may proceed.
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */',
        'startLine' => 99,
        'endLine' => 158,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'aliasName' => NULL,
      ),
      'mayWrite' => 
      array (
        'name' => 'mayWrite',
        'parameters' => 
        array (
          'mark' => 
          array (
            'name' => 'mark',
            'default' => NULL,
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
                      'name' => 'array',
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
            'startLine' => 170,
            'endLine' => 170,
            'startColumn' => 27,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'detectionRunId' => 
          array (
            'name' => 'detectionRunId',
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
            'startLine' => 170,
            'endLine' => 170,
            'startColumn' => 41,
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
 * Whether a redacted copy may be written for this document.
 *
 * @param array<string, mixed>|null $mark           The review mark, or null.
 * @param string                    $detectionRunId The detection run.
 *
 * @return bool True only when a person has checked this run.
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */',
        'startLine' => 170,
        'endLine' => 172,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'aliasName' => NULL,
      ),
      'screenBatch' => 
      array (
        'name' => 'screenBatch',
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
            'startLine' => 188,
            'endLine' => 188,
            'startColumn' => 30,
            'endColumn' => 45,
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
 * Which documents in a batch are refused, and why each one.
 *
 * 🔴 THE BATCH REPORTS ITS REFUSALS RATHER THAN DROPPING THEM. A run over
 * fifty-five thousand documents that quietly writes the reviewed ones and
 * says nothing about the rest reads as a complete run, and the gap is
 * invisible precisely because it is large.
 *
 * @param array<int, array<string, mixed>> $documents Each with `id`, `mark` and `detectionRun`.
 *
 * @return array{write: array<int, string>, refused: array<int, array<string, mixed>>}
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */',
        'startLine' => 188,
        'endLine' => 208,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'aliasName' => NULL,
      ),
      'checkedByClause' => 
      array (
        'name' => 'checkedByClause',
        'parameters' => 
        array (
          'mark' => 
          array (
            'name' => 'mark',
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
            'startLine' => 217,
            'endLine' => 217,
            'startColumn' => 35,
            'endColumn' => 45,
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
 * Who checked it and when, appended to a refusal that needs it.
 *
 * @param array<string, mixed> $mark The mark.
 *
 * @return string The clause, or an empty string.
 */',
        'startLine' => 217,
        'endLine' => 225,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'aliasName' => NULL,
      ),
      'refusal' => 
      array (
        'name' => 'refusal',
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
            'startLine' => 235,
            'endLine' => 235,
            'startColumn' => 27,
            'endColumn' => 40,
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
            'startLine' => 235,
            'endLine' => 235,
            'startColumn' => 43,
            'endColumn' => 57,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * One refusal.
 *
 * @param string $reason  The machine-readable reason.
 * @param string $message What the operator reads.
 *
 * @return array<string, mixed> The refusal.
 */',
        'startLine' => 235,
        'endLine' => 237,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'aliasName' => NULL,
      ),
      'say' => 
      array (
        'name' => 'say',
        'parameters' => 
        array (
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
            'startLine' => 246,
            'endLine' => 246,
            'startColumn' => 23,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * One message in the reader\'s language, or in English when there is none.
 *
 * @param string $message The English source string.
 *
 * @return string The message.
 */',
        'startLine' => 246,
        'endLine' => 252,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\RedactionReviewGate',
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