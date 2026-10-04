<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/ConfidentialityLabelService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\ConfidentialityLabelService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-ef2948de1074c36815af24bba8768d5a6f2c5595863197c165ed9a2b6e2e97f3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/ConfidentialityLabelService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
    'shortName' => 'ConfidentialityLabelService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service for reading a file\'s confidentiality label as a read-only signal.
 *
 * Read-only, no policy/enforcement of its own — see design.md (Non-Goals).
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 55,
    'endLine' => 198,
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
      'FILES_CONFIDENTIAL_APP_ID' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
        'name' => 'FILES_CONFIDENTIAL_APP_ID',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'files_confidential\'',
          'attributes' => 
          array (
            'startLine' => 62,
            'endLine' => 62,
            'startTokenPos' => 65,
            'startFilePos' => 2141,
            'endTokenPos' => 65,
            'endFilePos' => 2160,
          ),
        ),
        'docComment' => '/**
 * App id of the optional files_confidential app this service integrates with.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 2,
        'endColumn' => 64,
      ),
      'TAG_OBJECT_TYPE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
        'name' => 'TAG_OBJECT_TYPE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'files\'',
          'attributes' => 
          array (
            'startLine' => 69,
            'endLine' => 69,
            'startTokenPos' => 78,
            'startFilePos' => 2300,
            'endTokenPos' => 78,
            'endFilePos' => 2306,
          ),
        ),
        'docComment' => '/**
 * Nextcloud object type used by ISystemTagObjectMapper for file nodes.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 69,
        'endLine' => 69,
        'startColumn' => 2,
        'endColumn' => 41,
      ),
      'VOCABULARY_KEY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
        'name' => 'VOCABULARY_KEY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq.confidentiality.label_vocabulary\'',
          'attributes' => 
          array (
            'startLine' => 77,
            'endLine' => 77,
            'startTokenPos' => 91,
            'startFilePos' => 2490,
            'endTokenPos' => 91,
            'endFilePos' => 2530,
          ),
        ),
        'docComment' => '/**
 * App config key for the admin-configurable label vocabulary
 * (tag/label name => normalised level, JSON-encoded).
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 77,
        'endLine' => 77,
        'startColumn' => 2,
        'endColumn' => 73,
      ),
      'DEFAULT_VOCABULARY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
        'name' => 'DEFAULT_VOCABULARY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'Public\' => 0, \'Internal\' => 1, \'Confidential\' => 2, \'Secret\' => 3]',
          'attributes' => 
          array (
            'startLine' => 86,
            'endLine' => 91,
            'startTokenPos' => 104,
            'startFilePos' => 2822,
            'endTokenPos' => 134,
            'endFilePos' => 2901,
          ),
        ),
        'docComment' => '/**
 * Default TSCP/BAILS-style confidentiality vocabulary, seeded when the
 * admin has not configured `filinq.confidentiality.label_vocabulary`
 * (design.md Open Questions — adjustable without code change).
 *
 * @var array<string, int>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 86,
        'endLine' => 91,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
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
        'startLine' => 105,
        'endLine' => 105,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'appManager' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
        'name' => 'appManager',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\App\\IAppManager',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 106,
        'endLine' => 106,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'appConfig' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
        'name' => 'appConfig',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IAppConfig',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 107,
        'endLine' => 107,
        'startColumn' => 3,
        'endColumn' => 40,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'tagManager' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
        'name' => 'tagManager',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\SystemTag\\ISystemTagManager',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 108,
        'endLine' => 108,
        'startColumn' => 3,
        'endColumn' => 48,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'tagObjectMapper' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
        'name' => 'tagObjectMapper',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\SystemTag\\ISystemTagObjectMapper',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 109,
        'endLine' => 109,
        'startColumn' => 3,
        'endColumn' => 58,
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
            'startLine' => 105,
            'endLine' => 105,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'appManager' => 
          array (
            'name' => 'appManager',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\App\\IAppManager',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 106,
            'endLine' => 106,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'appConfig' => 
          array (
            'name' => 'appConfig',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IAppConfig',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 107,
            'endLine' => 107,
            'startColumn' => 3,
            'endColumn' => 40,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'tagManager' => 
          array (
            'name' => 'tagManager',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\SystemTag\\ISystemTagManager',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 108,
            'endLine' => 108,
            'startColumn' => 3,
            'endColumn' => 48,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'tagObjectMapper' => 
          array (
            'name' => 'tagObjectMapper',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\SystemTag\\ISystemTagObjectMapper',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 109,
            'endLine' => 109,
            'startColumn' => 3,
            'endColumn' => 58,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor for ConfidentialityLabelService
 *
 * @param LoggerInterface $logger Logger for diagnostic (non-fatal) reporting
 * @param IAppManager $appManager App manager, used to guard on files_confidential presence
 * @param IAppConfig $appConfig App configuration for the label vocabulary
 * @param ISystemTagManager $tagManager Nextcloud\'s public system-tag manager
 * @param ISystemTagObjectMapper $tagObjectMapper Nextcloud\'s public system-tag/object mapper
 *
 * @return void
 */',
        'startLine' => 104,
        'endLine' => 112,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
        'aliasName' => NULL,
      ),
      'getLabelForFile' => 
      array (
        'name' => 'getLabelForFile',
        'parameters' => 
        array (
          'fileId' => 
          array (
            'name' => 'fileId',
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
            'startLine' => 129,
            'endLine' => 129,
            'startColumn' => 34,
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
                  'name' => 'OCA\\Filinq\\Service\\ConfidentialityLabel',
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolve a file\'s confidentiality label, if any.
 *
 * Returns null (never throws) when `files_confidential` is not
 * installed, the file carries no system tag that matches the
 * configured vocabulary, or the system-tag API fails for any reason.
 * When several assigned tags match the vocabulary, the highest-level
 * match wins.
 *
 * @param int $fileId Nextcloud file id
 *
 * @return ConfidentialityLabel|null The resolved label, or null when no signal applies
 *
 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md#requirement-read-a-files-confidentiality-label-availability-guarded-req-ddfcl-001
 */',
        'startLine' => 129,
        'endLine' => 177,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
        'aliasName' => NULL,
      ),
      'getVocabulary' => 
      array (
        'name' => 'getVocabulary',
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
 * Read the admin-configured label vocabulary, falling back to the
 * default TSCP/BAILS names when unset or unreadable.
 *
 * @return array<string, int> Map of label/tag name to normalised level
 */',
        'startLine' => 185,
        'endLine' => 197,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
        'currentClassName' => 'OCA\\Filinq\\Service\\ConfidentialityLabelService',
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