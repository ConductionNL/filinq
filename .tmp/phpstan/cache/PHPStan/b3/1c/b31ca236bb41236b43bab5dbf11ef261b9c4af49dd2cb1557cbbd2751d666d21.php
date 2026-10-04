<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Redaction/DownloadAgreementGate.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Redaction\DownloadAgreementGate
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-80071716dc598ebf25ad7db7ee4d4010e4a22e99b372f62f1208fbbb7c0a8403',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Redaction/DownloadAgreementGate.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Redaction',
    'name' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
    'shortName' => 'DownloadAgreementGate',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Decides whether a gated file may be served, and records the acceptance.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 53,
    'endLine' => 298,
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
      'NOT_ACCEPTED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
        'name' => 'NOT_ACCEPTED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'not_accepted\'',
          'attributes' => 
          array (
            'startLine' => 60,
            'endLine' => 60,
            'startTokenPos' => 50,
            'startFilePos' => 2502,
            'endTokenPos' => 50,
            'endFilePos' => 2515,
          ),
        ),
        'docComment' => '/**
 * Nobody has accepted anything for this file.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 60,
        'endLine' => 60,
        'startColumn' => 2,
        'endColumn' => 44,
      ),
      'VERSION_MOVED_ON' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
        'name' => 'VERSION_MOVED_ON',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'version_moved_on\'',
          'attributes' => 
          array (
            'startLine' => 67,
            'endLine' => 67,
            'startTokenPos' => 63,
            'startFilePos' => 2651,
            'endTokenPos' => 63,
            'endFilePos' => 2668,
          ),
        ),
        'docComment' => '/**
 * An older version was accepted, and the terms have changed since.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 67,
        'endLine' => 67,
        'startColumn' => 2,
        'endColumn' => 52,
      ),
      'NOT_RECORDED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
        'name' => 'NOT_RECORDED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'not_recorded\'',
          'attributes' => 
          array (
            'startLine' => 74,
            'endLine' => 74,
            'startTokenPos' => 76,
            'startFilePos' => 2794,
            'endTokenPos' => 76,
            'endFilePos' => 2807,
          ),
        ),
        'docComment' => '/**
 * The acceptance could not be written, so nothing is served.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 74,
        'endLine' => 74,
        'startColumn' => 2,
        'endColumn' => 44,
      ),
      'NOT_KNOWN' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
        'name' => 'NOT_KNOWN',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'not_known\'',
          'attributes' => 
          array (
            'startLine' => 81,
            'endLine' => 81,
            'startTokenPos' => 89,
            'startFilePos' => 2943,
            'endTokenPos' => 89,
            'endFilePos' => 2953,
          ),
        ),
        'docComment' => '/**
 * The conditions could not be read, so it is not known whether any apply.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 81,
        'endLine' => 81,
        'startColumn' => 2,
        'endColumn' => 38,
      ),
    ),
    'immediateProperties' => 
    array (
      'agreements' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
        'name' => 'agreements',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementRepository',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 96,
        'endLine' => 96,
        'startColumn' => 3,
        'endColumn' => 58,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'l10n' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
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
            'startLine' => 97,
            'endLine' => 97,
            'startTokenPos' => 121,
            'startFilePos' => 3671,
            'endTokenPos' => 121,
            'endFilePos' => 3674,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 97,
        'endLine' => 97,
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
          'agreements' => 
          array (
            'name' => 'agreements',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementRepository',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 96,
            'endLine' => 96,
            'startColumn' => 3,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'l10n' => 
          array (
            'name' => 'l10n',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 97,
                'endLine' => 97,
                'startTokenPos' => 121,
                'startFilePos' => 3671,
                'endTokenPos' => 121,
                'endFilePos' => 3674,
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
            'startLine' => 97,
            'endLine' => 97,
            'startColumn' => 3,
            'endColumn' => 38,
            'parameterIndex' => 1,
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
 * @param DownloadAgreementRepository $agreements Where the terms and the acceptances live.
 * @param IL10N|null                  $l10n       The reader\'s language, when there is one. The
 *                                                reader of a gated download is often a member
 *                                                of the public rather than an operator, so
 *                                                these strings are the ones that most need a
 *                                                translation.
 *
 * @return void
 */',
        'startLine' => 95,
        'endLine' => 100,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
        'aliasName' => NULL,
      ),
      'check' => 
      array (
        'name' => 'check',
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
            'startLine' => 112,
            'endLine' => 112,
            'startColumn' => 24,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'person' => 
          array (
            'name' => 'person',
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
            'startLine' => 112,
            'endLine' => 112,
            'startColumn' => 42,
            'endColumn' => 55,
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
 * Whether this person may have this file yet.
 *
 * @param string $document The document being downloaded.
 * @param string $person   Who is asking.
 *
 * @return array<string, mixed> `mayDownload`, and the agreement to show when not.
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */',
        'startLine' => 112,
        'endLine' => 172,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
        'aliasName' => NULL,
      ),
      'accept' => 
      array (
        'name' => 'accept',
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
            'startColumn' => 25,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'person' => 
          array (
            'name' => 'person',
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
            'startColumn' => 43,
            'endColumn' => 56,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'version' => 
          array (
            'name' => 'version',
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
            'startColumn' => 59,
            'endColumn' => 73,
            'parameterIndex' => 2,
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
 * Record an acceptance, and say whether the file may now be served.
 *
 * @param string $document The document.
 * @param string $person   Who accepted.
 * @param string $version  Which version they were shown.
 *
 * @return array<string, mixed> `mayDownload`, and why not when it is false.
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */',
        'startLine' => 185,
        'endLine' => 233,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
        'aliasName' => NULL,
      ),
      'cannotTell' => 
      array (
        'name' => 'cannotTell',
        'parameters' => 
        array (
          'error' => 
          array (
            'name' => 'error',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Exception\\AgreementStoreUnreadableException',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 249,
            'endLine' => 249,
            'startColumn' => 30,
            'endColumn' => 69,
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
 * The answer when the store could not be read at all.
 *
 * 🔴 NOT KNOWING IS NOT PERMISSION. The store used to answer a failed read
 * with an empty result, which reads as "no terms were declared", which
 * serves the file. The reader is told the conditions could not be read
 * rather than being handed something nobody agreed to.
 *
 * @param AgreementStoreUnreadableException $error What went wrong.
 *
 * @return array<string, mixed> The refusal.
 *
 * @spec exclude Refusal helper behind check() and accept().
 */',
        'startLine' => 249,
        'endLine' => 261,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
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
            'startLine' => 272,
            'endLine' => 272,
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
 *
 * @spec exclude Localisation helper behind check() and accept().
 */',
        'startLine' => 272,
        'endLine' => 279,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
        'aliasName' => NULL,
      ),
      'shownTerms' => 
      array (
        'name' => 'shownTerms',
        'parameters' => 
        array (
          'agreement' => 
          array (
            'name' => 'agreement',
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
            'startLine' => 290,
            'endLine' => 290,
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
 * The agreement as a reader sees it.
 *
 * @param array<string, mixed> $agreement The stored agreement.
 *
 * @return array<string, mixed> The text, its version and its language.
 *
 * @spec exclude Presentation helper behind check() and accept().
 */',
        'startLine' => 290,
        'endLine' => 297,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Redaction',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
        'currentClassName' => 'OCA\\Filinq\\Service\\Redaction\\DownloadAgreementGate',
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