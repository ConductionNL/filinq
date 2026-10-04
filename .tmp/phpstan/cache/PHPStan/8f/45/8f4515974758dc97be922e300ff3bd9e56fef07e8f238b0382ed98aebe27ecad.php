<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/EmlPreviewService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\EmlPreviewService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-59f64d48dfdc5cb0b1bced3eb0b1aa3a0e69b5b3b9b882bab639843da17d44eb',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\EmlPreviewService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/EmlPreviewService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\EmlPreviewService',
    'shortName' => 'EmlPreviewService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Produces a PDF/A-3b preview of an original EML message.
 *
 * @category  Service
 * @package   OCA\\Filinq\\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 48,
    'endLine' => 136,
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
      'OR_FILE_SERVICE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\EmlPreviewService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EmlPreviewService',
        'name' => 'OR_FILE_SERVICE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'OCA\\OpenRegister\\Service\\FileService\'',
          'attributes' => 
          array (
            'startLine' => 53,
            'endLine' => 53,
            'startTokenPos' => 65,
            'startFilePos' => 1774,
            'endTokenPos' => 65,
            'endFilePos' => 1814,
          ),
        ),
        'docComment' => '/**
 * OpenRegister FileService FQCN — exposes anonymizeEmlStructured().
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 53,
        'endLine' => 53,
        'startColumn' => 2,
        'endColumn' => 75,
      ),
    ),
    'immediateProperties' => 
    array (
      'appManager' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\EmlPreviewService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EmlPreviewService',
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
        'startLine' => 64,
        'endLine' => 64,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'container' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\EmlPreviewService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EmlPreviewService',
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
        'startLine' => 65,
        'endLine' => 65,
        'startColumn' => 3,
        'endColumn' => 48,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'emlAssembly' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\EmlPreviewService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EmlPreviewService',
        'name' => 'emlAssembly',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 66,
        'endLine' => 66,
        'startColumn' => 3,
        'endColumn' => 53,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\EmlPreviewService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EmlPreviewService',
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
        'startLine' => 67,
        'endLine' => 67,
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
            'startLine' => 64,
            'endLine' => 64,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 0,
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
            'startLine' => 65,
            'endLine' => 65,
            'startColumn' => 3,
            'endColumn' => 48,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'emlAssembly' => 
          array (
            'name' => 'emlAssembly',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\EmlPdfAssemblyService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 66,
            'endLine' => 66,
            'startColumn' => 3,
            'endColumn' => 53,
            'parameterIndex' => 2,
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
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 3,
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
 * @param IAppManager $appManager App manager (OpenRegister installed check).
 * @param ContainerInterface $container DI container for OR service resolution.
 * @param EmlPdfAssemblyService $emlAssembly Assembles the parsed structure into a PDF.
 * @param LoggerInterface $logger Logger for diagnostics.
 */',
        'startLine' => 63,
        'endLine' => 70,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\EmlPreviewService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EmlPreviewService',
        'currentClassName' => 'OCA\\Filinq\\Service\\EmlPreviewService',
        'aliasName' => NULL,
      ),
      'renderOriginalPreview' => 
      array (
        'name' => 'renderOriginalPreview',
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
            'startLine' => 90,
            'endLine' => 90,
            'startColumn' => 40,
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
 * Render the original EML identified by $fileId to a preview PDF/A-3b.
 *
 * An EMPTY entity set is passed so nothing is redacted: the assembled PDF
 * is a faithful preview of the source message (original headers, body and
 * renderable attachments). No file is written — the bytes are streamed by
 * the caller. `EmlPdfAssemblyService::assemble()` throws
 * `ConversionFailedException` on an unrecoverable render failure; that is
 * left to propagate so the controller can surface a 422.
 *
 * @param int $fileId Nextcloud file id of the source .eml.
 *
 * @return string PDF/A-3b bytes of the original-message preview.
 *
 * @throws RuntimeException When OpenRegister is unavailable, the file
 *                          cannot be resolved, or the anonymise-EML API
 *                          is absent.
 */',
        'startLine' => 90,
        'endLine' => 110,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\EmlPreviewService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EmlPreviewService',
        'currentClassName' => 'OCA\\Filinq\\Service\\EmlPreviewService',
        'aliasName' => NULL,
      ),
      'resolveFileService' => 
      array (
        'name' => 'resolveFileService',
        'parameters' => 
        array (
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
 * Resolve OpenRegister\'s FileService or fail with a clear message.
 *
 * @return mixed The OpenRegister FileService.
 *
 * @throws RuntimeException When OpenRegister is not installed or the
 *                          service cannot be resolved.
 */',
        'startLine' => 120,
        'endLine' => 135,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\EmlPreviewService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\EmlPreviewService',
        'currentClassName' => 'OCA\\Filinq\\Service\\EmlPreviewService',
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