<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DocumentService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\DocumentService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-db84028112092539392e586742c2e849ca445a3193531f8c0a712e200deaa312',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\DocumentService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DocumentService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\DocumentService',
    'shortName' => 'DocumentService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service for generating formal documents from templates and OpenRegister data
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 50,
    'endLine' => 976,
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
      'SYNC_BATCH_LIMIT' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'name' => 'SYNC_BATCH_LIMIT',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '10',
          'attributes' => 
          array (
            'startLine' => 57,
            'endLine' => 57,
            'startTokenPos' => 60,
            'startFilePos' => 1695,
            'endTokenPos' => 60,
            'endFilePos' => 1696,
          ),
        ),
        'docComment' => '/**
 * Maximum number of objects for synchronous bulk processing.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 2,
        'endColumn' => 37,
      ),
      'DEFAULT_FORMAT' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'name' => 'DEFAULT_FORMAT',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'pdf\'',
          'attributes' => 
          array (
            'startLine' => 64,
            'endLine' => 64,
            'startTokenPos' => 73,
            'startFilePos' => 1789,
            'endTokenPos' => 73,
            'endFilePos' => 1793,
          ),
        ),
        'docComment' => '/**
 * Default output format.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 64,
        'endLine' => 64,
        'startColumn' => 2,
        'endColumn' => 38,
      ),
      'VALID_FORMATS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'name' => 'VALID_FORMATS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'pdf\', \'odf\', \'html\']',
          'attributes' => 
          array (
            'startLine' => 71,
            'endLine' => 71,
            'startTokenPos' => 86,
            'startFilePos' => 1886,
            'endTokenPos' => 94,
            'endFilePos' => 1907,
          ),
        ),
        'docComment' => '/**
 * Valid output formats.
 *
 * @var string[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 71,
        'endLine' => 71,
        'startColumn' => 2,
        'endColumn' => 54,
      ),
      'DEFAULT_OUTPUT_MODE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'name' => 'DEFAULT_OUTPUT_MODE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'return\'',
          'attributes' => 
          array (
            'startLine' => 78,
            'endLine' => 78,
            'startTokenPos' => 107,
            'startFilePos' => 2015,
            'endTokenPos' => 107,
            'endFilePos' => 2022,
          ),
        ),
        'docComment' => '/**
 * Default output destination mode.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 78,
        'endLine' => 78,
        'startColumn' => 2,
        'endColumn' => 46,
      ),
      'VALID_OUTPUT_MODES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'name' => 'VALID_OUTPUT_MODES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'return\', \'files\', \'both\']',
          'attributes' => 
          array (
            'startLine' => 85,
            'endLine' => 85,
            'startTokenPos' => 120,
            'startFilePos' => 2130,
            'endTokenPos' => 128,
            'endFilePos' => 2156,
          ),
        ),
        'docComment' => '/**
 * Valid output destination modes.
 *
 * @var string[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 85,
        'endLine' => 85,
        'startColumn' => 2,
        'endColumn' => 64,
      ),
      'DEFAULT_OUTPUT_FOLDER_PREFIX' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'name' => 'DEFAULT_OUTPUT_FOLDER_PREFIX',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'DocuDesk\'',
          'attributes' => 
          array (
            'startLine' => 102,
            'endLine' => 102,
            'startTokenPos' => 141,
            'startFilePos' => 2980,
            'endTokenPos' => 141,
            'endFilePos' => 2989,
          ),
        ),
        'docComment' => '/**
 * Default storage folder prefix (a template\'s namespace is appended).
 *
 * @var string
 *
 * ⚠️ STILL `DocuDesk`, DELIBERATELY, ACROSS THE FILINQ RENAME. This is a
 * real FOLDER NAME in the user\'s Nextcloud Files tree — every document this
 * app has ever generated or stored lives under `<user>/files/DocuDesk/…`.
 * Renaming the constant does not move a single file: the app simply starts
 * writing into a new, empty `Filinq/` folder while every existing document
 * is orphaned where nothing looks for it any more. Same class of failure as
 * renaming an OpenRegister register slug, and equally invisible — there is
 * no error, the folder is just empty. Moving the folder needs its own
 * repair step that renames the node per user.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 102,
        'endLine' => 102,
        'startColumn' => 2,
        'endColumn' => 57,
      ),
    ),
    'immediateProperties' => 
    array (
      'templateService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'name' => 'templateService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\TemplateService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 120,
        'endLine' => 120,
        'startColumn' => 3,
        'endColumn' => 51,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'dataResolver' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'name' => 'dataResolver',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\DataResolverService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 121,
        'endLine' => 121,
        'startColumn' => 3,
        'endColumn' => 52,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'renderPipeline' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'name' => 'renderPipeline',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\DocumentRenderPipeline',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 122,
        'endLine' => 122,
        'startColumn' => 3,
        'endColumn' => 57,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'storageService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'name' => 'storageService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\DocumentStorageService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 123,
        'endLine' => 123,
        'startColumn' => 3,
        'endColumn' => 57,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'documentLogger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'name' => 'documentLogger',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\GeneratedDocumentLogger',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 124,
        'endLine' => 124,
        'startColumn' => 3,
        'endColumn' => 58,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'container' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'name' => 'container',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Psr\\Container\\ContainerInterface',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 125,
        'endLine' => 125,
        'startColumn' => 3,
        'endColumn' => 48,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'jobList' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'name' => 'jobList',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\BackgroundJob\\IJobList',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 126,
        'endLine' => 126,
        'startColumn' => 3,
        'endColumn' => 36,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
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
        'startLine' => 127,
        'endLine' => 127,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'plainRendition' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'name' => 'plainRendition',
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
                  'name' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
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
            'startLine' => 128,
            'endLine' => 128,
            'startTokenPos' => 236,
            'startFilePos' => 4345,
            'endTokenPos' => 236,
            'endFilePos' => 4348,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 128,
        'endLine' => 128,
        'startColumn' => 3,
        'endColumn' => 72,
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
          'templateService' => 
          array (
            'name' => 'templateService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\TemplateService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 120,
            'endLine' => 120,
            'startColumn' => 3,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'dataResolver' => 
          array (
            'name' => 'dataResolver',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\DataResolverService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 121,
            'endLine' => 121,
            'startColumn' => 3,
            'endColumn' => 52,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'renderPipeline' => 
          array (
            'name' => 'renderPipeline',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\DocumentRenderPipeline',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 122,
            'endLine' => 122,
            'startColumn' => 3,
            'endColumn' => 57,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'storageService' => 
          array (
            'name' => 'storageService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\DocumentStorageService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 123,
            'endLine' => 123,
            'startColumn' => 3,
            'endColumn' => 57,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'documentLogger' => 
          array (
            'name' => 'documentLogger',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\GeneratedDocumentLogger',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 124,
            'endLine' => 124,
            'startColumn' => 3,
            'endColumn' => 58,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'container' => 
          array (
            'name' => 'container',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Psr\\Container\\ContainerInterface',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 125,
            'endLine' => 125,
            'startColumn' => 3,
            'endColumn' => 48,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
          'jobList' => 
          array (
            'name' => 'jobList',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\BackgroundJob\\IJobList',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 126,
            'endLine' => 126,
            'startColumn' => 3,
            'endColumn' => 36,
            'parameterIndex' => 6,
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
            'startLine' => 127,
            'endLine' => 127,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 7,
            'isOptional' => false,
          ),
          'plainRendition' => 
          array (
            'name' => 'plainRendition',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 128,
                'endLine' => 128,
                'startTokenPos' => 236,
                'startFilePos' => 4345,
                'endTokenPos' => 236,
                'endFilePos' => 4348,
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
                      'name' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
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
            'startLine' => 128,
            'endLine' => 128,
            'startColumn' => 3,
            'endColumn' => 72,
            'parameterIndex' => 8,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor for DocumentService.
 *
 * @param TemplateService $templateService Service for template CRUD
 * @param DataResolverService $dataResolver Service for OpenRegister data resolution
 * @param DocumentRenderPipeline $renderPipeline Huisstijl + Twig rendering and output production
 * @param DocumentStorageService $storageService Service for storing output in Files
 * @param GeneratedDocumentLogger $documentLogger Audit-trail writer for generated documents
 * @param ContainerInterface $container Container for dependency injection
 * @param IJobList $jobList Nextcloud job list for async processing
 * @param LoggerInterface $logger Logger for error reporting
 * @param PlainLanguageRenditionService|null $plainRendition The plain-language counterpart, when a template declares one
 *
 * @return void
 */',
        'startLine' => 119,
        'endLine' => 131,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'aliasName' => NULL,
      ),
      'generateDocument' => 
      array (
        'name' => 'generateDocument',
        'parameters' => 
        array (
          'templateId' => 
          array (
            'name' => 'templateId',
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
            'startLine' => 169,
            'endLine' => 169,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'dataRefs' => 
          array (
            'name' => 'dataRefs',
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
            'startLine' => 170,
            'endLine' => 170,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 171,
                'endLine' => 171,
                'startTokenPos' => 271,
                'startFilePos' => 6407,
                'endTokenPos' => 272,
                'endFilePos' => 6408,
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
            'startLine' => 171,
            'endLine' => 171,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 2,
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
 * Generate a single document from a template and data references.
 *
 * Resolves data from OpenRegister objects, applies huisstijl, renders
 * the Twig template, and produces output in the requested format.
 * Generated document metadata is stored in the document register for
 * audit trail (DCS-072).
 *
 * @param string $templateId The UUID of the template to use
 * @param array $dataRefs Data references: [{register, schema, id}, ...]
 * @param array $options Options: format (pdf|odf|html), huisstijlId,
 *                       zaakId, adHocData, listRefs, pdfOptions, userId,
 *                       filename, output.
 *                       listRefs: [{register, schema, filter?, limit?,
 *                       order?, as?}, ...] — each resolves to an array
 *                       of objects under the Twig context key \'as\'
 *                       (default: schema + \'_list\')
 *                       output: {mode?: return|files|both, targetPath?}
 *                       — defaults to mode \'return\' (byte-identical to
 *                       this method\'s behaviour before output support
 *                       existed)
 *
 * @return array{content: string, format: string, metadata: array, warnings: string[], output: array}
 *
 * @throws Exception If generation fails
 *
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
 * @spec openspec/changes/document-generation-list-refs/specs/document-creatie-sjablonen/spec.md
 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md
 *
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength) The plain-language rendition
 * step pushed this past the threshold. It belongs in the same method because
 * the formal document and its plain counterpart are filed together or not at
 * all; splitting the step out would make a half-filed pair reachable.
 */',
        'startLine' => 168,
        'endLine' => 282,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'aliasName' => NULL,
      ),
      'generatePreview' => 
      array (
        'name' => 'generatePreview',
        'parameters' => 
        array (
          'templateId' => 
          array (
            'name' => 'templateId',
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
            'startLine' => 306,
            'endLine' => 306,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'dataRefs' => 
          array (
            'name' => 'dataRefs',
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
            'startLine' => 307,
            'endLine' => 307,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 308,
                'endLine' => 308,
                'startTokenPos' => 1114,
                'startFilePos' => 10825,
                'endTokenPos' => 1115,
                'endFilePos' => 10826,
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
            'startLine' => 308,
            'endLine' => 308,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 2,
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
 * Generate an HTML preview of a template without producing final output.
 *
 * Resolves data and renders the Twig template but returns plain HTML
 * without PDF/ODF conversion. No audit log entry is created for previews.
 *
 * @param string $templateId The UUID of the template to preview
 * @param array $dataRefs Data references: [{register, schema, id}, ...]
 * @param array $options Options: huisstijlId, adHocData, listRefs.
 *                       listRefs: [{register, schema, filter?, limit?,
 *                       order?, as?}, ...] — each resolves to an array
 *                       of objects under the Twig context key \'as\'
 *                       (default: schema + \'_list\')
 *
 * @return array{html: string, warnings: string[]}
 *
 * @throws Exception If rendering fails
 *
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
 * @spec openspec/changes/document-generation-list-refs/specs/document-creatie-sjablonen/spec.md
 */',
        'startLine' => 305,
        'endLine' => 338,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'aliasName' => NULL,
      ),
      'generateBulk' => 
      array (
        'name' => 'generateBulk',
        'parameters' => 
        array (
          'templateId' => 
          array (
            'name' => 'templateId',
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
            'startLine' => 379,
            'endLine' => 379,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'objectIds' => 
          array (
            'name' => 'objectIds',
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
            'startLine' => 380,
            'endLine' => 380,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 381,
                'endLine' => 381,
                'startTokenPos' => 1411,
                'startFilePos' => 13914,
                'endTokenPos' => 1412,
                'endFilePos' => 13915,
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
            'startLine' => 381,
            'endLine' => 381,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 2,
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
 * Generate documents for multiple objects in a single request.
 *
 * For batches <= SYNC_BATCH_LIMIT objects processing is synchronous.
 * For larger batches a queued background job is dispatched and a jobId
 * is returned so the caller can poll GET /api/documents/jobs/{jobId}.
 *
 * Note: `options.listRefs` is still NOT supported on this path. As of
 * document-output-destinations-and-bulk-retention the async job no
 * longer discards its per-object output (see REQ-DDOB-006) — the
 * original justification for excluding listRefs (nowhere for a
 * per-object-resolved collection to end up) no longer holds — but
 * wiring listRefs through bulk was intentionally left out of this
 * change\'s scope; it remains unimplemented pending a real use case.
 * See openspec/changes/document-generation-list-refs/proposal.md and
 * openspec/changes/document-output-destinations-and-bulk-retention/proposal.md.
 *
 * For batches larger than SYNC_BATCH_LIMIT (async), `options.output.mode`
 * MUST be exactly \'files\' — the generated bytes have nowhere to go once
 * the job is queued and today\'s synchronous HTTP response has already
 * returned, so \'return\' (default) and \'both\' are both rejected with
 * HTTP 400 (REQ-DDOB-005).
 *
 * @param string $templateId The UUID of the template
 * @param array $objectIds Array of object UUIDs to generate for
 * @param array $options Options: register, schema, format, huisstijlId,
 *                       userId, output. For batches >SYNC_BATCH_LIMIT,
 *                       options.output.mode must be \'files\'.
 *
 * @return array Synchronous: {results, total, completed, errors}
 *               Async: {jobId, status, total}
 *
 * @throws Exception If dispatch fails, or if an async batch does not
 *                   request options.output.mode \'files\'
 *
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md#req-ddob-005
 */',
        'startLine' => 378,
        'endLine' => 401,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'aliasName' => NULL,
      ),
      'validateAsyncOutputMode' => 
      array (
        'name' => 'validateAsyncOutputMode',
        'parameters' => 
        array (
          'options' => 
          array (
            'name' => 'options',
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
            'startLine' => 416,
            'endLine' => 416,
            'startColumn' => 43,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Validate that an async (>SYNC_BATCH_LIMIT) bulk request requests
 * options.output.mode \'files\' — the only mode that makes sense once
 * the job is queued and the HTTP response has already returned.
 *
 * @param array $options The request options
 *
 * @return void
 *
 * @throws Exception Code 400 if options.output.mode is not exactly \'files\'
 *
 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md#req-ddob-005
 */',
        'startLine' => 416,
        'endLine' => 428,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'aliasName' => NULL,
      ),
      'getJobStatus' => 
      array (
        'name' => 'getJobStatus',
        'parameters' => 
        array (
          'jobId' => 
          array (
            'name' => 'jobId',
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
            'startLine' => 439,
            'endLine' => 439,
            'startColumn' => 31,
            'endColumn' => 43,
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
 * Get the status of an async bulk document generation job.
 *
 * @param string $jobId The job UUID
 *
 * @return array|null The job status or null if not found
 *
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
 */',
        'startLine' => 439,
        'endLine' => 461,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'aliasName' => NULL,
      ),
      'updateJobStatus' => 
      array (
        'name' => 'updateJobStatus',
        'parameters' => 
        array (
          'jobId' => 
          array (
            'name' => 'jobId',
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
            'startLine' => 473,
            'endLine' => 473,
            'startColumn' => 34,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'status' => 
          array (
            'name' => 'status',
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
            'startLine' => 473,
            'endLine' => 473,
            'startColumn' => 49,
            'endColumn' => 61,
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
 * Update an async job status in the app config.
 *
 * @param string $jobId The job UUID
 * @param array $status The status data to store
 *
 * @return void
 *
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
 */',
        'startLine' => 473,
        'endLine' => 488,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'aliasName' => NULL,
      ),
      'validateFormat' => 
      array (
        'name' => 'validateFormat',
        'parameters' => 
        array (
          'format' => 
          array (
            'name' => 'format',
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
            'startLine' => 499,
            'endLine' => 499,
            'startColumn' => 34,
            'endColumn' => 47,
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
 * Validate that the requested format is supported.
 *
 * @param string $format The output format
 *
 * @return void
 *
 * @throws Exception If the format is not supported
 */',
        'startLine' => 499,
        'endLine' => 508,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'aliasName' => NULL,
      ),
      'resolveOutputMode' => 
      array (
        'name' => 'resolveOutputMode',
        'parameters' => 
        array (
          'options' => 
          array (
            'name' => 'options',
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
            'startLine' => 521,
            'endLine' => 521,
            'startColumn' => 37,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolve and validate options.output.mode.
 *
 * @param array $options The request options
 *
 * @return string One of \'return\', \'files\', \'both\'
 *
 * @throws Exception Code 400 if an unsupported mode is requested
 *
 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md#req-ddob-001
 */',
        'startLine' => 521,
        'endLine' => 533,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'aliasName' => NULL,
      ),
      'buildOutputTargetPath' => 
      array (
        'name' => 'buildOutputTargetPath',
        'parameters' => 
        array (
          'templateId' => 
          array (
            'name' => 'templateId',
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
            'startLine' => 553,
            'endLine' => 553,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'explicitTargetPath' => 
          array (
            'name' => 'explicitTargetPath',
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
            'startLine' => 554,
            'endLine' => 554,
            'startColumn' => 3,
            'endColumn' => 29,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'template' => 
          array (
            'name' => 'template',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 555,
                'endLine' => 555,
                'startTokenPos' => 2129,
                'startFilePos' => 19012,
                'endTokenPos' => 2129,
                'endFilePos' => 19015,
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
            'startLine' => 555,
            'endLine' => 555,
            'startColumn' => 3,
            'endColumn' => 25,
            'parameterIndex' => 2,
            'isOptional' => true,
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
 * Build the effective storage target path for a generation.
 *
 * An explicit `options.output.targetPath` always wins. Otherwise the
 * default is `DocuDesk/<template namespace>/`. When a template array is
 * not supplied (the async bulk job calls this once, before its
 * per-object loop, with no template already loaded) the template is
 * looked up via TemplateService.
 *
 * @param string $templateId The template UUID
 * @param string|null $explicitTargetPath An explicit targetPath, if provided
 * @param array|null $template A pre-fetched template array, if available
 *
 * @return string The effective target path (no trailing slash)
 *
 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md#req-ddob-002
 */',
        'startLine' => 552,
        'endLine' => 572,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'aliasName' => NULL,
      ),
      'buildOutputFilename' => 
      array (
        'name' => 'buildOutputFilename',
        'parameters' => 
        array (
          'filename' => 
          array (
            'name' => 'filename',
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
            'startLine' => 584,
            'endLine' => 584,
            'startColumn' => 39,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'format' => 
          array (
            'name' => 'format',
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
            'startLine' => 584,
            'endLine' => 584,
            'startColumn' => 57,
            'endColumn' => 70,
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
 * Build the filename a stored (or downloaded) document should use,
 * mirroring the extension DocumentController\'s download response uses
 * per format.
 *
 * @param string $filename The requested filename (without extension)
 * @param string $format The output format (pdf, odf, html)
 *
 * @return string The filename including extension
 */',
        'startLine' => 584,
        'endLine' => 598,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'aliasName' => NULL,
      ),
      'producePlainRendition' => 
      array (
        'name' => 'producePlainRendition',
        'parameters' => 
        array (
          'plan' => 
          array (
            'name' => 'plan',
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
            'startLine' => 656,
            'endLine' => 656,
            'startColumn' => 3,
            'endColumn' => 14,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'data' => 
          array (
            'name' => 'data',
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
            'startLine' => 657,
            'endLine' => 657,
            'startColumn' => 3,
            'endColumn' => 13,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'format' => 
          array (
            'name' => 'format',
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
            'startLine' => 658,
            'endLine' => 658,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'huisstijl' => 
          array (
            'name' => 'huisstijl',
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
            'startLine' => 659,
            'endLine' => 659,
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'pdfOptions' => 
          array (
            'name' => 'pdfOptions',
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
            'startLine' => 660,
            'endLine' => 660,
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
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
            'startLine' => 661,
            'endLine' => 661,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
          'outputMode' => 
          array (
            'name' => 'outputMode',
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
            'startLine' => 662,
            'endLine' => 662,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 6,
            'isOptional' => false,
          ),
          'formal' => 
          array (
            'name' => 'formal',
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
            'startLine' => 663,
            'endLine' => 663,
            'startColumn' => 3,
            'endColumn' => 15,
            'parameterIndex' => 7,
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
 * Produce the plain-language rendition, when the template declared one.
 *
 * 🔴 BOTH RENDITIONS COME OUT OF ONE GENERATION, which is what makes
 * REQ-DIO-04\'s fifth scenario true by construction: regenerating the formal
 * letter after a correction regenerates the plain one in the same act,
 * because there is no path that produces one without the other. A separate
 * "regenerate the plain version" call would be a path somebody can forget,
 * and a stale plain letter beside a corrected formal one is the failure the
 * scenario names.
 *
 * 🔑 IT RENDERS FROM THE SAME DATA AND THE SAME HUISSTIJL. Rendering the
 * plain counterpart from anything else would let the two letters disagree
 * about a date while both claim to describe one decision.
 *
 * 🔑 A PLAIN RENDITION THAT COULD NOT BE STORED IS A WARNING, NOT A THROW,
 * and only once the formal document is already filed. Everything that can
 * refuse the pair has refused before this point; failing here would mean
 * losing a formal letter that is already on disk over its companion.
 *
 * @param array<string, mixed>|null $plan       The plan, or null when no counterpart is declared.
 * @param array<string, mixed>      $data       The resolved generation data.
 * @param string                    $format     The output format.
 * @param array<string, mixed>|null $huisstijl  The huisstijl the formal letter used.
 * @param array<string, mixed>      $pdfOptions The PDF options the formal letter used.
 * @param array<string, mixed>      $options    The generation options.
 * @param string                    $outputMode Where the output goes.
 * @param array<string, mixed>      $formal     The formal document as it was filed.
 *
 * @return array{rendition: array<string, mixed>|null, record: array<string, mixed>,
 *               warnings: array<int, string>} The rendition, its record fields and any warnings.
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/letter-correspondence-generation/spec.md
 */',
        'startLine' => 655,
        'endLine' => 716,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'aliasName' => NULL,
      ),
      'storeOutputIfRequested' => 
      array (
        'name' => 'storeOutputIfRequested',
        'parameters' => 
        array (
          'mode' => 
          array (
            'name' => 'mode',
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
            'startLine' => 735,
            'endLine' => 735,
            'startColumn' => 3,
            'endColumn' => 14,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'templateId' => 
          array (
            'name' => 'templateId',
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
            'startLine' => 736,
            'endLine' => 736,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'template' => 
          array (
            'name' => 'template',
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
            'startLine' => 737,
            'endLine' => 737,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'format' => 
          array (
            'name' => 'format',
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
            'startLine' => 738,
            'endLine' => 738,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'content' => 
          array (
            'name' => 'content',
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
            'startLine' => 739,
            'endLine' => 739,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
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
            'startLine' => 740,
            'endLine' => 740,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
          'warnings' => 
          array (
            'name' => 'warnings',
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
            'startLine' => 741,
            'endLine' => 741,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 6,
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
 * File the rendered bytes when the caller asked for them to be stored.
 *
 * @param string               $mode       `return` to hand the bytes back unfiled, anything else to file them.
 * @param string               $templateId The template\'s identifier, for the record.
 * @param array<string, mixed> $template   The template the bytes came from.
 * @param string               $format     The output format the bytes are in.
 * @param string               $content    The rendered bytes.
 * @param array<string, mixed> $options    The generation options, carrying `userId` and the target.
 * @param array<int, string>   $warnings   The warnings collected so far, carried through.
 *
 * @return array{fileId: ?int, path: ?string, name: ?string, size: ?int, warnings: array<int, string>}
 *         What was filed, or nulls when the mode was `return`.
 *
 * @spec exclude Private helper of generateDocument(); the storing rule is specified there.
 */',
        'startLine' => 734,
        'endLine' => 795,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'aliasName' => NULL,
      ),
      'generateBulkSync' => 
      array (
        'name' => 'generateBulkSync',
        'parameters' => 
        array (
          'templateId' => 
          array (
            'name' => 'templateId',
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
            'startLine' => 815,
            'endLine' => 815,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'objectIds' => 
          array (
            'name' => 'objectIds',
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
            'startLine' => 816,
            'endLine' => 816,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
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
            'startLine' => 817,
            'endLine' => 817,
            'startColumn' => 3,
            'endColumn' => 16,
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
 * Process a bulk generation request synchronously.
 *
 * Honours `options.output.mode` per object exactly as single-generate
 * does (REQ-DDOB-007): \'return\' (default) keeps today\'s inline
 * `content`; \'files\' stores each object\'s output and returns
 * `{fileId, path, name, size}` inline instead of `content`; \'both\'
 * returns both.
 *
 * @param string $templateId The template UUID
 * @param array $objectIds Array of object UUIDs
 * @param array $options Generation options (register, schema, format, output, ...)
 *
 * @return array{results: array, total: int, completed: int, errors: int}
 *
 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md#req-ddob-007
 */',
        'startLine' => 814,
        'endLine' => 901,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'aliasName' => NULL,
      ),
      'dispatchBulkJob' => 
      array (
        'name' => 'dispatchBulkJob',
        'parameters' => 
        array (
          'templateId' => 
          array (
            'name' => 'templateId',
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
            'startLine' => 924,
            'endLine' => 924,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'objectIds' => 
          array (
            'name' => 'objectIds',
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
            'startLine' => 925,
            'endLine' => 925,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
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
            'startLine' => 926,
            'endLine' => 926,
            'startColumn' => 3,
            'endColumn' => 16,
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
 * Dispatch an async bulk generation background job.
 *
 * Computes the per-job storage folder once (REQ-DDOB-006):
 * `<targetPath>/<jobId>/`, where `<targetPath>` is the request\'s
 * `options.output.targetPath` if provided, else the same
 * `DocuDesk/<template namespace>/` default single-generate uses. Every
 * per-object `generateDocument()` call the job makes uses this same
 * fixed path, so a large batch\'s outputs land in one folder instead of
 * spraying across the destination.
 *
 * @param string $templateId The template UUID
 * @param array $objectIds Array of object UUIDs
 * @param array $options Generation options (options.output.mode is
 *                       already validated to be \'files\' by the caller)
 *
 * @return array{jobId: string, status: string, total: int}
 *
 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md#req-ddob-006
 */',
        'startLine' => 923,
        'endLine' => 963,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'aliasName' => NULL,
      ),
      'generateJobId' => 
      array (
        'name' => 'generateJobId',
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
 * Generate a cryptographically secure job UUID.
 *
 * @return string A RFC-4122 v4 UUID job identifier
 */',
        'startLine' => 970,
        'endLine' => 975,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentService',
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