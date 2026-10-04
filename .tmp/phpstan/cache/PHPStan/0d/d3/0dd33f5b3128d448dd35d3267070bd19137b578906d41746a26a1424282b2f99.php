<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Middleware/LanguageNegotiationMiddleware.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Middleware\LanguageNegotiationMiddleware
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-59b31b55a928a0e50c6306cfb712a7abdd838a72a3c7af7e4e3d66f1253dafab',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Middleware\\LanguageNegotiationMiddleware',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Middleware/LanguageNegotiationMiddleware.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Middleware',
    'name' => 'OCA\\Filinq\\Middleware\\LanguageNegotiationMiddleware',
    'shortName' => 'LanguageNegotiationMiddleware',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Bridges OpenRegister\'s LanguageService into Filinq\'s controller
 * request lifecycle.
 *
 * @package OCA\\Filinq\\Middleware
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 64,
    'endLine' => 262,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'OCP\\AppFramework\\Middleware',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'BCP47_PATTERN' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Middleware\\LanguageNegotiationMiddleware',
        'implementingClassName' => 'OCA\\Filinq\\Middleware\\LanguageNegotiationMiddleware',
        'name' => 'BCP47_PATTERN',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'/^[a-z]{2,3}(-[a-zA-Z0-9]{2,8})*$/\'',
          'attributes' => 
          array (
            'startLine' => 73,
            'endLine' => 73,
            'startTokenPos' => 64,
            'startFilePos' => 2500,
            'endTokenPos' => 64,
            'endFilePos' => 2535,
          ),
        ),
        'docComment' => '/**
 * Basic BCP-47 syntax check used to discard malformed overrides.
 *
 * Lax by design — never 400 on a malformed tag; fall through.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 2,
        'endColumn' => 68,
      ),
    ),
    'immediateProperties' => 
    array (
      'request' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Middleware\\LanguageNegotiationMiddleware',
        'implementingClassName' => 'OCA\\Filinq\\Middleware\\LanguageNegotiationMiddleware',
        'name' => 'request',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IRequest',
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
        'endColumn' => 36,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'languageService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Middleware\\LanguageNegotiationMiddleware',
        'implementingClassName' => 'OCA\\Filinq\\Middleware\\LanguageNegotiationMiddleware',
        'name' => 'languageService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\OpenRegister\\Service\\LanguageService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 84,
        'endLine' => 84,
        'startColumn' => 3,
        'endColumn' => 51,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Middleware\\LanguageNegotiationMiddleware',
        'implementingClassName' => 'OCA\\Filinq\\Middleware\\LanguageNegotiationMiddleware',
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
        'startLine' => 85,
        'endLine' => 85,
        'startColumn' => 3,
        'endColumn' => 42,
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
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IRequest',
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
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'languageService' => 
          array (
            'name' => 'languageService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\OpenRegister\\Service\\LanguageService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 84,
            'endLine' => 84,
            'startColumn' => 3,
            'endColumn' => 51,
            'parameterIndex' => 1,
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
            'startLine' => 85,
            'endLine' => 85,
            'startColumn' => 3,
            'endColumn' => 42,
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
 * @param IRequest $request The incoming request.
 * @param LanguageService $languageService Request-scoped OR language service.
 * @param LoggerInterface $logger Logger for invalid-tag warnings.
 */',
        'startLine' => 82,
        'endLine' => 87,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Middleware',
        'declaringClassName' => 'OCA\\Filinq\\Middleware\\LanguageNegotiationMiddleware',
        'implementingClassName' => 'OCA\\Filinq\\Middleware\\LanguageNegotiationMiddleware',
        'currentClassName' => 'OCA\\Filinq\\Middleware\\LanguageNegotiationMiddleware',
        'aliasName' => NULL,
      ),
      'beforeController' => 
      array (
        'name' => 'beforeController',
        'parameters' => 
        array (
          'controller' => 
          array (
            'name' => 'controller',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 106,
            'endLine' => 106,
            'startColumn' => 35,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'methodName' => 
          array (
            'name' => 'methodName',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 106,
            'endLine' => 106,
            'startColumn' => 48,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * {@inheritDoc}
 *
 * Resolve the preferred language and write-side target language
 * from the incoming request and stash them on OR\'s LanguageService.
 *
 * `$controller` and `$methodName` are pinned by the inherited
 * `OCP\\AppFramework\\Middleware::beforeController()` signature; negotiation
 * is route-agnostic so neither is consulted.
 *
 * @param mixed $controller The controller instance.
 * @param string $methodName The method name being called.
 *
 * @return void
 *
 * @spec openspec/changes/register-i18n/tasks.md#task-3-2
 */',
        'startLine' => 106,
        'endLine' => 125,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Middleware',
        'declaringClassName' => 'OCA\\Filinq\\Middleware\\LanguageNegotiationMiddleware',
        'implementingClassName' => 'OCA\\Filinq\\Middleware\\LanguageNegotiationMiddleware',
        'currentClassName' => 'OCA\\Filinq\\Middleware\\LanguageNegotiationMiddleware',
        'aliasName' => NULL,
      ),
      'applyAcceptLanguageHeader' => 
      array (
        'name' => 'applyAcceptLanguageHeader',
        'parameters' => 
        array (
          'queryOverride' => 
          array (
            'name' => 'queryOverride',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 139,
            'endLine' => 139,
            'startColumn' => 45,
            'endColumn' => 66,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Apply the `Accept-Language` header to OR\'s LanguageService.
 *
 * Always records the parsed priority list. Only promotes the top entry to
 * the preferred language when no higher-priority query override was found.
 *
 * @param string|null $queryOverride The language already resolved from query params, or null.
 *
 * @return void
 *
 * @spec openspec/changes/register-i18n/tasks.md#task-3-2
 */',
        'startLine' => 139,
        'endLine' => 159,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Middleware',
        'declaringClassName' => 'OCA\\Filinq\\Middleware\\LanguageNegotiationMiddleware',
        'implementingClassName' => 'OCA\\Filinq\\Middleware\\LanguageNegotiationMiddleware',
        'currentClassName' => 'OCA\\Filinq\\Middleware\\LanguageNegotiationMiddleware',
        'aliasName' => NULL,
      ),
      'applyTargetLanguageHeader' => 
      array (
        'name' => 'applyTargetLanguageHeader',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Apply the write-side `X-Translation-Target-Language` header on mutating verbs.
 *
 * Only POST/PUT/PATCH carry a write-side target. A malformed tag is logged
 * and ignored — we never 400 on a malformed language tag.
 *
 * @return void
 *
 * @spec openspec/changes/register-i18n/tasks.md#task-3-2
 */',
        'startLine' => 171,
        'endLine' => 195,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Middleware',
        'declaringClassName' => 'OCA\\Filinq\\Middleware\\LanguageNegotiationMiddleware',
        'implementingClassName' => 'OCA\\Filinq\\Middleware\\LanguageNegotiationMiddleware',
        'currentClassName' => 'OCA\\Filinq\\Middleware\\LanguageNegotiationMiddleware',
        'aliasName' => NULL,
      ),
      'afterController' => 
      array (
        'name' => 'afterController',
        'parameters' => 
        array (
          'controller' => 
          array (
            'name' => 'controller',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 213,
            'endLine' => 213,
            'startColumn' => 34,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'methodName' => 
          array (
            'name' => 'methodName',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 213,
            'endLine' => 213,
            'startColumn' => 47,
            'endColumn' => 57,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'response' => 
          array (
            'name' => 'response',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\AppFramework\\Http\\Response',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 213,
            'endLine' => 213,
            'startColumn' => 60,
            'endColumn' => 77,
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
            'name' => 'OCP\\AppFramework\\Http\\Response',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * {@inheritDoc}
 *
 * Emit language response headers so filinq responses surface the
 * resolved language the same way OR responses do.
 *
 * `$controller` and `$methodName` are pinned by the inherited
 * `OCP\\AppFramework\\Middleware::afterController()` signature; the emitted
 * headers are route-agnostic so neither is consulted.
 *
 * @param mixed $controller The controller instance.
 * @param string $methodName The method name that was called.
 * @param Response $response The response object.
 *
 * @return Response The modified response with language headers.
 */',
        'startLine' => 213,
        'endLine' => 222,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Middleware',
        'declaringClassName' => 'OCA\\Filinq\\Middleware\\LanguageNegotiationMiddleware',
        'implementingClassName' => 'OCA\\Filinq\\Middleware\\LanguageNegotiationMiddleware',
        'currentClassName' => 'OCA\\Filinq\\Middleware\\LanguageNegotiationMiddleware',
        'aliasName' => NULL,
      ),
      'resolveFromQueryParams' => 
      array (
        'name' => 'resolveFromQueryParams',
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
 * Resolve a language from `?_lang=` or `?language=`, in that order.
 *
 * Returns null when neither is set, or when neither value passes
 * basic BCP-47 syntax validation. Invalid tags log a warning and
 * cause the lookup to fall through to the next priority level —
 * we never 400 on a malformed language tag.
 *
 * @return string|null The resolved tag, or null when no valid query override is present.
 */',
        'startLine' => 234,
        'endLine' => 261,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Middleware',
        'declaringClassName' => 'OCA\\Filinq\\Middleware\\LanguageNegotiationMiddleware',
        'implementingClassName' => 'OCA\\Filinq\\Middleware\\LanguageNegotiationMiddleware',
        'currentClassName' => 'OCA\\Filinq\\Middleware\\LanguageNegotiationMiddleware',
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