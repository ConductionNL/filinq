<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/TemplateLanguageService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\TemplateLanguageService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-ec0bd2b52498ff14bb94d0a33ffa49e4cf9fcbfc47c716c03bc7cbc315a11553',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\TemplateLanguageService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/TemplateLanguageService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\TemplateLanguageService',
    'shortName' => 'TemplateLanguageService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service for template language preference resolution.
 *
 * Implements the REQ-I18N-011 fallback chain and provides helpers
 * for detecting available languages in translatable template fields.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @spec openspec/changes/register-i18n/tasks.md#task-1
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 44,
    'endLine' => 202,
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
      'SUPPORTED_LANGUAGES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateLanguageService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateLanguageService',
        'name' => 'SUPPORTED_LANGUAGES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'nl\', \'en\']',
          'attributes' => 
          array (
            'startLine' => 51,
            'endLine' => 51,
            'startTokenPos' => 45,
            'startFilePos' => 1381,
            'endTokenPos' => 50,
            'endFilePos' => 1392,
          ),
        ),
        'docComment' => '/**
 * Supported languages for the templates register.
 *
 * @var string[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 51,
        'endLine' => 51,
        'startColumn' => 2,
        'endColumn' => 49,
      ),
      'DEFAULT_LANGUAGE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateLanguageService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateLanguageService',
        'name' => 'DEFAULT_LANGUAGE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'nl\'',
          'attributes' => 
          array (
            'startLine' => 58,
            'endLine' => 58,
            'startTokenPos' => 63,
            'startFilePos' => 1513,
            'endTokenPos' => 63,
            'endFilePos' => 1516,
          ),
        ),
        'docComment' => '/**
 * Default language when no preference is available.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 58,
        'endLine' => 58,
        'startColumn' => 2,
        'endColumn' => 38,
      ),
    ),
    'immediateProperties' => 
    array (
      'config' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateLanguageService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateLanguageService',
        'name' => 'config',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IConfig',
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
        'endColumn' => 34,
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
          'config' => 
          array (
            'name' => 'config',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IConfig',
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
            'endColumn' => 34,
            'parameterIndex' => 0,
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
 * @param IConfig $config System configuration for reading user language
 *
 * @spec openspec/changes/register-i18n/tasks.md#task-1
 */',
        'startLine' => 67,
        'endLine' => 70,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateLanguageService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateLanguageService',
        'currentClassName' => 'OCA\\Filinq\\Service\\TemplateLanguageService',
        'aliasName' => NULL,
      ),
      'resolveUserLanguage' => 
      array (
        'name' => 'resolveUserLanguage',
        'parameters' => 
        array (
          'user' => 
          array (
            'name' => 'user',
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
                      'name' => 'OCP\\IUser',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 86,
            'endLine' => 86,
            'startColumn' => 38,
            'endColumn' => 49,
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
 * Resolve the preferred language for a user following the fallback chain.
 *
 * Fallback order (REQ-I18N-011):
 *   1. User\'s Nextcloud language preference
 *   2. \'nl\' (Dutch — primary language per REQ-I18N-050)
 *   3. \'en\' (English — minimum secondary per REQ-I18N-051)
 *
 * @param IUser|null $user The authenticated Nextcloud user, or null for guests
 *
 * @return string BCP 47 language code (e.g. \'nl\', \'en\')
 *
 * @spec openspec/changes/register-i18n/tasks.md#task-1
 */',
        'startLine' => 86,
        'endLine' => 106,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateLanguageService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateLanguageService',
        'currentClassName' => 'OCA\\Filinq\\Service\\TemplateLanguageService',
        'aliasName' => NULL,
      ),
      'getAvailableLanguages' => 
      array (
        'name' => 'getAvailableLanguages',
        'parameters' => 
        array (
          'fieldValue' => 
          array (
            'name' => 'fieldValue',
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
            'startLine' => 121,
            'endLine' => 121,
            'startColumn' => 40,
            'endColumn' => 56,
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
 * Extract available language codes from a translatable template field.
 *
 * When OR stores a translatable value, it uses a language-keyed array:
 * {"nl": "Beschikking", "en": "Decision"}. This method detects that
 * structure and returns the available language codes.
 *
 * @param mixed $fieldValue The raw value of a translatable field
 *
 * @return string[] Array of BCP 47 language codes available in the field
 *
 * @spec openspec/changes/register-i18n/tasks.md#task-1
 */',
        'startLine' => 121,
        'endLine' => 132,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateLanguageService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateLanguageService',
        'currentClassName' => 'OCA\\Filinq\\Service\\TemplateLanguageService',
        'aliasName' => NULL,
      ),
      'resolveFieldValue' => 
      array (
        'name' => 'resolveFieldValue',
        'parameters' => 
        array (
          'fieldValue' => 
          array (
            'name' => 'fieldValue',
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
            'startLine' => 146,
            'endLine' => 146,
            'startColumn' => 36,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'preferredLanguage' => 
          array (
            'name' => 'preferredLanguage',
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
            'startLine' => 146,
            'endLine' => 146,
            'startColumn' => 55,
            'endColumn' => 79,
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
 * Resolve the best available language for a translatable field value.
 *
 * Implements the fallback chain: preferred → nl → en → first available.
 *
 * @param mixed $fieldValue The raw translatable field (string or language-keyed array)
 * @param string $preferredLanguage The caller\'s preferred BCP 47 language code
 *
 * @return array{value: string, language: string, fallback: bool} Resolved value with metadata
 *
 * @spec openspec/changes/register-i18n/tasks.md#task-1
 */',
        'startLine' => 146,
        'endLine' => 172,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateLanguageService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateLanguageService',
        'currentClassName' => 'OCA\\Filinq\\Service\\TemplateLanguageService',
        'aliasName' => NULL,
      ),
      'buildAcceptLanguageHeader' => 
      array (
        'name' => 'buildAcceptLanguageHeader',
        'parameters' => 
        array (
          'preferred' => 
          array (
            'name' => 'preferred',
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
            'startLine' => 186,
            'endLine' => 186,
            'startColumn' => 44,
            'endColumn' => 60,
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
 * Build an Accept-Language header value from a preferred language code.
 *
 * Produces "nl, en;q=0.9" when preferred is \'nl\', or "en, nl;q=0.9" when \'en\',
 * so OR\'s LanguageMiddleware negotiates correctly.
 *
 * @param string $preferred The caller\'s preferred BCP 47 language code
 *
 * @return string RFC 9110 Accept-Language header value
 *
 * @spec openspec/changes/register-i18n/tasks.md#task-1
 */',
        'startLine' => 186,
        'endLine' => 201,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\TemplateLanguageService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\TemplateLanguageService',
        'currentClassName' => 'OCA\\Filinq\\Service\\TemplateLanguageService',
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