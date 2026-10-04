<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/DocumentGuard.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Editing\DocumentGuard
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-b695983eee8b577c6611ae8715cde7f13b4f10549c9547248bbbe86011599dc0',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/DocumentGuard.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Editing',
    'name' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
    'shortName' => 'DocumentGuard',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Enforces the refusals that bound what an agent may do to a document.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-documents-under-signature-or-produced-by-anonymisation-are-not-editable
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 47,
    'endLine' => 372,
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
      'CANCELLED' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'name' => 'CANCELLED',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'cancelled\'',
          'attributes' => 
          array (
            'startLine' => 55,
            'endLine' => 55,
            'startTokenPos' => 70,
            'startFilePos' => 1711,
            'endTokenPos' => 70,
            'endFilePos' => 1721,
          ),
        ),
        'docComment' => '/**
 * The one signing-request status under which the underlying document is
 * still editable, because the process it belonged to was abandoned.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 55,
        'endLine' => 55,
        'startColumn' => 2,
        'endColumn' => 39,
      ),
      'REGISTER' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'name' => 'REGISTER',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq\'',
          'attributes' => 
          array (
            'startLine' => 65,
            'endLine' => 65,
            'startTokenPos' => 83,
            'startFilePos' => 1980,
            'endTokenPos' => 83,
            'endFilePos' => 1987,
          ),
        ),
        'docComment' => '/**
 * The OpenRegister register holding Filinq\'s document schemas.
 *
 * `filinq`, not `document`: this app declares ONE register holding all 23
 * schemas. The five it used to declare are retired.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 65,
        'endLine' => 65,
        'startColumn' => 2,
        'endColumn' => 35,
      ),
    ),
    'immediateProperties' => 
    array (
      'objectResolver' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'name' => 'objectResolver',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\DocumentObjectServiceResolver',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 77,
        'endLine' => 77,
        'startColumn' => 3,
        'endColumn' => 64,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
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
        'startLine' => 78,
        'endLine' => 78,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'finalDocuments' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'name' => 'finalDocuments',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\FinalDocumentService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 79,
        'endLine' => 79,
        'startColumn' => 3,
        'endColumn' => 55,
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
          'objectResolver' => 
          array (
            'name' => 'objectResolver',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\DocumentObjectServiceResolver',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 77,
            'endLine' => 77,
            'startColumn' => 3,
            'endColumn' => 64,
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
            'startLine' => 78,
            'endLine' => 78,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'finalDocuments' => 
          array (
            'name' => 'finalDocuments',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\FinalDocumentService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 79,
            'endLine' => 79,
            'startColumn' => 3,
            'endColumn' => 55,
            'parameterIndex' => 2,
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
 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister\'s ObjectService.
 * @param LoggerInterface $logger Logger for diagnostics.
 * @param FinalDocumentService $finalDocuments The final-document guard.
 *
 * @return void
 */',
        'startLine' => 76,
        'endLine' => 82,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'aliasName' => NULL,
      ),
      'finalRefusal' => 
      array (
        'name' => 'finalRefusal',
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
            'startLine' => 101,
            'endLine' => 101,
            'startColumn' => 31,
            'endColumn' => 40,
            'parameterIndex' => 0,
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
                  'name' => 'string',
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
 * Refuse to edit a version somebody has made final.
 *
 * A final version is the record of what was published. Editing it does not
 * correct the document, it destroys the only proof of what the document
 * said. The correction is a new version, and the refusal says so.
 *
 * FAILS CLOSED, like the signature check and for the same reason: an
 * unreachable register is exactly when an unnoticed edit to a besluit is
 * most likely.
 *
 * @param File $file The file to check.
 *
 * @return string|null A refusal message, or null when the version is not final.
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */',
        'startLine' => 101,
        'endLine' => 107,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'aliasName' => NULL,
      ),
      'signatureRefusal' => 
      array (
        'name' => 'signatureRefusal',
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
            'startLine' => 126,
            'endLine' => 126,
            'startColumn' => 35,
            'endColumn' => 44,
            'parameterIndex' => 0,
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
                  'name' => 'string',
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
 * Refuse to edit a file that is under a live signature process.
 *
 * Editing the artefact behind a signature invalidates what the signature
 * attests to. This is checked before the lock is taken, so a refusal never
 * disturbs a file it is about to decline to touch.
 *
 * FAILS CLOSED: if OpenRegister cannot be reached, the answer is "refuse",
 * not "allow". An unreachable register is exactly when an unnoticed edit to
 * a document under signature is most likely.
 *
 * @param File $file The file to check.
 *
 * @return string|null A refusal message, or null when the file is free to edit.
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-documents-under-signature-or-produced-by-anonymisation-are-not-editable
 */',
        'startLine' => 126,
        'endLine' => 159,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'aliasName' => NULL,
      ),
      'anonymisationRefusal' => 
      array (
        'name' => 'anonymisationRefusal',
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
            'startLine' => 178,
            'endLine' => 178,
            'startColumn' => 39,
            'endColumn' => 48,
            'parameterIndex' => 0,
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
                  'name' => 'string',
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
 * Refuse to edit anonymisation output.
 *
 * A redacted document is a deliberate artefact produced under a legal basis.
 * Re-editing it risks re-identification, and the edit would not be visible
 * to whoever relies on the redaction.
 *
 * Unlike the signature check this one fails OPEN: an unreachable register
 * here would block every ordinary edit on every instance that has never run
 * an anonymisation, and the harm it guards against is narrower.
 *
 * @param File $file The file to check.
 *
 * @return string|null A refusal message, or null when the file is not redaction output.
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-documents-under-signature-or-produced-by-anonymisation-are-not-editable
 */',
        'startLine' => 178,
        'endLine' => 191,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'aliasName' => NULL,
      ),
      'query' => 
      array (
        'name' => 'query',
        'parameters' => 
        array (
          'schema' => 
          array (
            'name' => 'schema',
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
            'startColumn' => 25,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'filter' => 
          array (
            'name' => 'filter',
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
            'startLine' => 201,
            'endLine' => 201,
            'startColumn' => 41,
            'endColumn' => 53,
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
 * Run a filtered OpenRegister query, distinguishing "no rows" from "no answer".
 *
 * @param string $schema The schema slug.
 * @param array<string, mixed> $filter The property filter.
 *
 * @return array<int, mixed>|null The matching rows, or null when the register could not be read.
 */',
        'startLine' => 201,
        'endLine' => 244,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'aliasName' => NULL,
      ),
      'field' => 
      array (
        'name' => 'field',
        'parameters' => 
        array (
          'row' => 
          array (
            'name' => 'row',
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
            'startLine' => 254,
            'endLine' => 254,
            'startColumn' => 25,
            'endColumn' => 34,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'key' => 
          array (
            'name' => 'key',
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
            'startLine' => 254,
            'endLine' => 254,
            'startColumn' => 37,
            'endColumn' => 47,
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
            'name' => 'mixed',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Read one property off an OpenRegister row, whatever shape it came back in.
 *
 * @param mixed $row The row.
 * @param string $key The property name.
 *
 * @return mixed The value, or null.
 */',
        'startLine' => 254,
        'endLine' => 267,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'aliasName' => NULL,
      ),
      'formatRefusal' => 
      array (
        'name' => 'formatRefusal',
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
            'startLine' => 292,
            'endLine' => 292,
            'startColumn' => 32,
            'endColumn' => 41,
            'parameterIndex' => 0,
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
                  'name' => 'string',
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
 * Refuse a file whose FORMAT must never be content-edited.
 *
 * 🔴 Determined from CONTENT, not from the filename. An extension is a
 * caller\'s claim about a file; the bytes are the file. A `.docx` that
 * actually carries a VBA project is exactly the input a name-based check
 * waves through, and writing into a macro-bearing package is a
 * code-execution vector in document clothing.
 *
 * Three standing refusals:
 * - macro-bearing OOXML (`.docm`/`.xlsm`/`.pptm`, or ANY package carrying
 *   `vbaProject.bin`)
 * - `.odb`, which is a database rather than a document and has no
 *   meaningful "edit a block" semantics
 * - PDF content rewriting; a PDF is a final-form artefact and silently
 *   rewriting its text produces something forgery-shaped
 *
 * @param File $file The file to check.
 *
 * @return string|null A refusal message, or null when the format is editable.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#3-refusals
 */',
        'startLine' => 292,
        'endLine' => 312,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'aliasName' => NULL,
      ),
      'carriesMacros' => 
      array (
        'name' => 'carriesMacros',
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
            'startLine' => 326,
            'endLine' => 326,
            'startColumn' => 33,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Whether a package carries a VBA macro project.
 *
 * ⚠️ Reads the package rather than trusting `.docm`/`.xlsm`/`.pptm`. Both
 * halves matter: a renamed `.docx` is caught, and an unreadable package is
 * treated as macro-bearing — "I could not tell" must not resolve to "safe"
 * for a code-execution check.
 *
 * @param File $file The file to inspect.
 *
 * @return bool True when macros are present or cannot be ruled out.
 */',
        'startLine' => 326,
        'endLine' => 371,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
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