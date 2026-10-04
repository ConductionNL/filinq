<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Exception/SigningCancellationNotSupportedException.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Exception\SigningCancellationNotSupportedException
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-f34d669ae13368d45330fe140ba5e5cad7ede9a69cb6280a52a9318eae38420e',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Exception\\SigningCancellationNotSupportedException',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Exception/SigningCancellationNotSupportedException.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Exception',
    'name' => 'OCA\\Filinq\\Exception\\SigningCancellationNotSupportedException',
    'shortName' => 'SigningCancellationNotSupportedException',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * A provider that cannot cancel says so, rather than returning success.
 *
 * This exception exists because of a measured defect. `ValidSignProvider::
 * cancelSigning()` was, in full:
 *
 *     public function cancelSigning(string $externalId): bool {
 *         return true;
 *     }
 *
 * No call to ValidSign. Connected to a UI, a user would cancel a request, be told
 * it succeeded, and the request would stay live at the provider — signatories could
 * still open and sign a document the user believed withdrawn, producing a legally
 * valid signature nobody expected.
 *
 * A `bool` return is what made that look like an implementation. Void-or-throw
 * removes the option: a provider either completes, or raises something the caller
 * must handle. "I cannot do this" is information a user can act on; `false` is
 * indistinguishable from a transient failure.
 *
 * @category Exception
 * @package  OCA\\Filinq\\Exception
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://filinq.app
 *
 * @spec openspec/changes/signing-cancellation/specs/signing-cancellation/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 62,
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
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'provider' => 
          array (
            'name' => 'provider',
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
            'startLine' => 74,
            'endLine' => 74,
            'startColumn' => 30,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'reason' => 
          array (
            'name' => 'reason',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 74,
                'endLine' => 74,
                'startTokenPos' => 53,
                'startFilePos' => 2307,
                'endTokenPos' => 53,
                'endFilePos' => 2308,
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
            'startLine' => 74,
            'endLine' => 74,
            'startColumn' => 48,
            'endColumn' => 66,
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
 * @param string $provider The provider that cannot cancel.
 * @param string $reason Why, in terms a user can act on.
 *
 * @return void
 *
 * @spec openspec/changes/signing-cancellation/specs/signing-cancellation/spec.md
 */',
        'startLine' => 74,
        'endLine' => 87,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Exception',
        'declaringClassName' => 'OCA\\Filinq\\Exception\\SigningCancellationNotSupportedException',
        'implementingClassName' => 'OCA\\Filinq\\Exception\\SigningCancellationNotSupportedException',
        'currentClassName' => 'OCA\\Filinq\\Exception\\SigningCancellationNotSupportedException',
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