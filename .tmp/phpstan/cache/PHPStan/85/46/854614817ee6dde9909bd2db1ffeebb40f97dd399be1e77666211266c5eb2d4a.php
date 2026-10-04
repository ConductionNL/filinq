<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Event/GlAccountSuggestedEvent.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Event\GlAccountSuggestedEvent
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-b33a3d0734a7fbb3c7e7de6869ebc5613c54f38d6dcc0cb900c6c81d44276465',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Event/GlAccountSuggestedEvent.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Event',
    'name' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
    'shortName' => 'GlAccountSuggestedEvent',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Cross-app completion event: Filinq reports a computed GL-account
 * suggestion. Fully immutable — Filinq constructs it from the computed
 * suggestion result; consumers only read.
 *
 * @category Event
 * @package  OCA\\Filinq\\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 48,
    'endLine' => 176,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'OCP\\EventDispatcher\\Event',
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
      'extractionId' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'name' => 'extractionId',
        'modifiers' => 132,
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
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 67,
        'endLine' => 67,
        'startColumn' => 3,
        'endColumn' => 39,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'supplierIdentity' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'name' => 'supplierIdentity',
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
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 68,
        'endLine' => 68,
        'startColumn' => 3,
        'endColumn' => 44,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'identityType' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'name' => 'identityType',
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
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 69,
        'endLine' => 69,
        'startColumn' => 3,
        'endColumn' => 40,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'suggestedAccounts' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'name' => 'suggestedAccounts',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 70,
        'endLine' => 70,
        'startColumn' => 3,
        'endColumn' => 43,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'source' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'name' => 'source',
        'modifiers' => 132,
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
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 71,
        'endLine' => 71,
        'startColumn' => 3,
        'endColumn' => 33,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'sourceApp' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'name' => 'sourceApp',
        'modifiers' => 132,
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
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 72,
        'endLine' => 72,
        'startColumn' => 3,
        'endColumn' => 36,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'requestedBy' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'name' => 'requestedBy',
        'modifiers' => 132,
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
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 73,
        'endLine' => 73,
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
          'extractionId' => 
          array (
            'name' => 'extractionId',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 3,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'supplierIdentity' => 
          array (
            'name' => 'supplierIdentity',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 68,
            'endLine' => 68,
            'startColumn' => 3,
            'endColumn' => 44,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'identityType' => 
          array (
            'name' => 'identityType',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 69,
            'endLine' => 69,
            'startColumn' => 3,
            'endColumn' => 40,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'suggestedAccounts' => 
          array (
            'name' => 'suggestedAccounts',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 70,
            'endLine' => 70,
            'startColumn' => 3,
            'endColumn' => 43,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'source' => 
          array (
            'name' => 'source',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 71,
            'endLine' => 71,
            'startColumn' => 3,
            'endColumn' => 33,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'sourceApp' => 
          array (
            'name' => 'sourceApp',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 72,
            'endLine' => 72,
            'startColumn' => 3,
            'endColumn' => 36,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
          'requestedBy' => 
          array (
            'name' => 'requestedBy',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 73,
            'endLine' => 73,
            'startColumn' => 3,
            'endColumn' => 38,
            'parameterIndex' => 6,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Construct the suggestion event.
 *
 * @param string $extractionId The `financialExtraction` object id.
 * @param string|null $supplierIdentity The resolved supplier identity, or null
 *                                      when unresolvable.
 * @param string|null $identityType `kvk`|`iban`|`name`, or null when
 *                                  unresolvable.
 * @param array<int, array<string, mixed>> $suggestedAccounts Ranked candidate accounts (each `{code,
 *                                                            label, confidence, rationale}`).
 * @param string $source `history`|`keyword-rule`|`none`.
 * @param string $sourceApp App id of the requester.
 * @param string $requestedBy Nextcloud user id that requested the
 *                            suggestion.
 *
 * @return void
 */',
        'startLine' => 66,
        'endLine' => 77,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'aliasName' => NULL,
      ),
      'getExtractionId' => 
      array (
        'name' => 'getExtractionId',
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
 * Get the `financialExtraction` object id.
 *
 * @return string The extraction id.
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 */',
        'startLine' => 86,
        'endLine' => 88,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'aliasName' => NULL,
      ),
      'getSupplierIdentity' => 
      array (
        'name' => 'getSupplierIdentity',
        'parameters' => 
        array (
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
 * Get the resolved supplier identity.
 *
 * @return string|null The identity, or null when unresolvable.
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 */',
        'startLine' => 97,
        'endLine' => 99,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'aliasName' => NULL,
      ),
      'getIdentityType' => 
      array (
        'name' => 'getIdentityType',
        'parameters' => 
        array (
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
 * Get the resolved identity type.
 *
 * @return string|null `kvk`|`iban`|`name`, or null when unresolvable.
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 */',
        'startLine' => 108,
        'endLine' => 110,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'aliasName' => NULL,
      ),
      'getSuggestedAccounts' => 
      array (
        'name' => 'getSuggestedAccounts',
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
 * Get the ranked candidate accounts.
 *
 * @return array<int, array<string, mixed>> The ranked candidates (each `{code, label, confidence,
 *                                          rationale}`).
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 */',
        'startLine' => 120,
        'endLine' => 122,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'aliasName' => NULL,
      ),
      'getSource' => 
      array (
        'name' => 'getSource',
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
 * Get the suggestion source.
 *
 * @return string `history`|`keyword-rule`|`none`.
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 */',
        'startLine' => 131,
        'endLine' => 133,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'aliasName' => NULL,
      ),
      'getSourceApp' => 
      array (
        'name' => 'getSourceApp',
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
 * Get the requesting app id.
 *
 * @return string The source app id.
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 */',
        'startLine' => 142,
        'endLine' => 144,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'aliasName' => NULL,
      ),
      'getRequestedBy' => 
      array (
        'name' => 'getRequestedBy',
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
 * Get the requesting Nextcloud user id.
 *
 * @return string The requesting user id.
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 */',
        'startLine' => 153,
        'endLine' => 155,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'aliasName' => NULL,
      ),
      'toPayload' => 
      array (
        'name' => 'toPayload',
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
 * Build the wire payload for `nl.conduction.filinq.gl-account.suggested`.
 *
 * @return array<string, mixed> The payload shape.
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 */',
        'startLine' => 164,
        'endLine' => 175,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Event',
        'declaringClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'implementingClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
        'currentClassName' => 'OCA\\Filinq\\Event\\GlAccountSuggestedEvent',
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