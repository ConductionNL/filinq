<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/EditSessionService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Editing\EditSessionService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-78e27b934040bfceea241dd1234de19ceb6633cd5e3778edf9e0c94b71fe6a01',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/EditSessionService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Editing',
    'name' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
    'shortName' => 'EditSessionService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Runs an agent\'s document edit under a lock and a version precondition.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-an-in-place-write-is-guarded-by-the-lock-and-a-version-precondition
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 72,
    'endLine' => 779,
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
      'MODE_IN_PLACE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'name' => 'MODE_IN_PLACE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'inPlace\'',
          'attributes' => 
          array (
            'startLine' => 79,
            'endLine' => 79,
            'startTokenPos' => 65,
            'startFilePos' => 2979,
            'endTokenPos' => 65,
            'endFilePos' => 2987,
          ),
        ),
        'docComment' => '/**
 * Write the change back into the source file, producing a Nextcloud version.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 79,
        'endLine' => 79,
        'startColumn' => 2,
        'endColumn' => 40,
      ),
      'MODE_SIBLING' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'name' => 'MODE_SIBLING',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'sibling\'',
          'attributes' => 
          array (
            'startLine' => 86,
            'endLine' => 86,
            'startTokenPos' => 78,
            'startFilePos' => 3134,
            'endTokenPos' => 78,
            'endFilePos' => 3142,
          ),
        ),
        'docComment' => '/**
 * Write the change to a new file beside the source, leaving the source untouched.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 86,
        'endLine' => 86,
        'startColumn' => 2,
        'endColumn' => 39,
      ),
      'CONFIG_OUTPUT_MODE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'name' => 'CONFIG_OUTPUT_MODE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'agent_edit_output_mode\'',
          'attributes' => 
          array (
            'startLine' => 97,
            'endLine' => 97,
            'startTokenPos' => 91,
            'startFilePos' => 3430,
            'endTokenPos' => 91,
            'endFilePos' => 3453,
          ),
        ),
        'docComment' => '/**
 * App-config key setting the CEILING on output mode.
 *
 * A tool argument may narrow this to `sibling`; it may never widen it to
 * `inPlace`. An agent that can widen its own blast radius has no blast
 * radius.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 97,
        'endLine' => 97,
        'startColumn' => 2,
        'endColumn' => 60,
      ),
      'MAX_BYTES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'name' => 'MAX_BYTES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '26214400',
          'attributes' => 
          array (
            'startLine' => 104,
            'endLine' => 104,
            'startTokenPos' => 104,
            'startFilePos' => 3567,
            'endTokenPos' => 104,
            'endFilePos' => 3574,
          ),
        ),
        'docComment' => '/**
 * Largest package this service will load into memory.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 104,
        'endLine' => 104,
        'startColumn' => 2,
        'endColumn' => 36,
      ),
      'MAX_BLOCKS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'name' => 'MAX_BLOCKS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '400',
          'attributes' => 
          array (
            'startLine' => 111,
            'endLine' => 111,
            'startTokenPos' => 117,
            'startFilePos' => 3696,
            'endTokenPos' => 117,
            'endFilePos' => 3698,
          ),
        ),
        'docComment' => '/**
 * Largest number of blocks returned to a caller in one read.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 111,
        'endLine' => 111,
        'startColumn' => 2,
        'endColumn' => 32,
      ),
    ),
    'immediateProperties' => 
    array (
      'rootFolder' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'name' => 'rootFolder',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\Files\\IRootFolder',
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
      'codecs' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'name' => 'codecs',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Editing\\DocumentCodecs',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 128,
        'endLine' => 128,
        'startColumn' => 3,
        'endColumn' => 41,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'writer' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'name' => 'writer',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Editing\\GuardedWriter',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 129,
        'endLine' => 129,
        'startColumn' => 3,
        'endColumn' => 40,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'guard' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'name' => 'guard',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 130,
        'endLine' => 130,
        'startColumn' => 3,
        'endColumn' => 39,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'appConfig' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
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
        'startLine' => 131,
        'endLine' => 131,
        'startColumn' => 3,
        'endColumn' => 40,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'metadata' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'name' => 'metadata',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
            'isIdentifier' => false,
          ),
        ),
        'default' => 
        array (
          'code' => 'new \\OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec()',
          'attributes' => 
          array (
            'startLine' => 132,
            'endLine' => 132,
            'startTokenPos' => 184,
            'startFilePos' => 4473,
            'endTokenPos' => 188,
            'endFilePos' => 4498,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 132,
        'endLine' => 132,
        'startColumn' => 3,
        'endColumn' => 78,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'charts' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'name' => 'charts',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
            'isIdentifier' => false,
          ),
        ),
        'default' => 
        array (
          'code' => 'new \\OCA\\Filinq\\Service\\Editing\\ChartCodec()',
          'attributes' => 
          array (
            'startLine' => 133,
            'endLine' => 133,
            'startTokenPos' => 201,
            'startFilePos' => 4541,
            'endTokenPos' => 205,
            'endFilePos' => 4556,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 133,
        'endLine' => 133,
        'startColumn' => 3,
        'endColumn' => 56,
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
          'rootFolder' => 
          array (
            'name' => 'rootFolder',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Files\\IRootFolder',
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
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'codecs' => 
          array (
            'name' => 'codecs',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Editing\\DocumentCodecs',
                'isIdentifier' => false,
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
            'endColumn' => 41,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'writer' => 
          array (
            'name' => 'writer',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Editing\\GuardedWriter',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 129,
            'endLine' => 129,
            'startColumn' => 3,
            'endColumn' => 40,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'guard' => 
          array (
            'name' => 'guard',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Editing\\DocumentGuard',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 130,
            'endLine' => 130,
            'startColumn' => 3,
            'endColumn' => 39,
            'parameterIndex' => 3,
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
            'startLine' => 131,
            'endLine' => 131,
            'startColumn' => 3,
            'endColumn' => 40,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'metadata' => 
          array (
            'name' => 'metadata',
            'default' => 
            array (
              'code' => 'new \\OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec()',
              'attributes' => 
              array (
                'startLine' => 132,
                'endLine' => 132,
                'startTokenPos' => 184,
                'startFilePos' => 4473,
                'endTokenPos' => 188,
                'endFilePos' => 4498,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Editing\\PackageMetadataCodec',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 132,
            'endLine' => 132,
            'startColumn' => 3,
            'endColumn' => 78,
            'parameterIndex' => 5,
            'isOptional' => true,
          ),
          'charts' => 
          array (
            'name' => 'charts',
            'default' => 
            array (
              'code' => 'new \\OCA\\Filinq\\Service\\Editing\\ChartCodec()',
              'attributes' => 
              array (
                'startLine' => 133,
                'endLine' => 133,
                'startTokenPos' => 201,
                'startFilePos' => 4541,
                'endTokenPos' => 205,
                'endFilePos' => 4556,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Editing\\ChartCodec',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 133,
            'endLine' => 133,
            'startColumn' => 3,
            'endColumn' => 56,
            'parameterIndex' => 6,
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
 * @param IRootFolder $rootFolder The Nextcloud root folder.
 * @param DocumentCodecs $codecs The per-kind document codecs.
 * @param GuardedWriter $writer The guarded read-modify-write.
 * @param DocumentGuard $guard The standing refusals.
 * @param IAppConfig $appConfig App configuration.
 * @param PackageMetadataCodec $metadata The document metadata codec.
 * @param ChartCodec $charts The chart embedding codec.
 *
 * @return void
 */',
        'startLine' => 126,
        'endLine' => 136,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'aliasName' => NULL,
      ),
      'openForAgent' => 
      array (
        'name' => 'openForAgent',
        'parameters' => 
        array (
          'uid' => 
          array (
            'name' => 'uid',
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
            'startLine' => 155,
            'endLine' => 155,
            'startColumn' => 31,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 155,
            'endLine' => 155,
            'startColumn' => 44,
            'endColumn' => 54,
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
 * Read a document\'s anchored blocks.
 *
 * The returned `version` is the token the caller must hand back to
 * {@see self::editForAgent()}. Without it an edit would be applied to
 * whatever the file happens to be at write time, which is how one writer
 * silently overwrites another.
 *
 * @param string $uid The acting user id.
 * @param int $fileId The Nextcloud file id.
 *
 * @return array<string, mixed> The document outline.
 *
 * @throws RuntimeException When the file cannot be read or is not an editable package.
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-edits-address-stable-anchors-never-positional-indexes
 */',
        'startLine' => 155,
        'endLine' => 172,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'aliasName' => NULL,
      ),
      'openEnvelope' => 
      array (
        'name' => 'openEnvelope',
        'parameters' => 
        array (
          'uid' => 
          array (
            'name' => 'uid',
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
            'startLine' => 192,
            'endLine' => 192,
            'startColumn' => 3,
            'endColumn' => 13,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 193,
            'endLine' => 193,
            'startColumn' => 3,
            'endColumn' => 12,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'items' => 
          array (
            'name' => 'items',
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
            'startLine' => 194,
            'endLine' => 194,
            'startColumn' => 3,
            'endColumn' => 14,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'countKey' => 
          array (
            'name' => 'countKey',
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
            'startLine' => 195,
            'endLine' => 195,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'itemsKey' => 
          array (
            'name' => 'itemsKey',
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
            'startLine' => 196,
            'endLine' => 196,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'packageBytes' => 
          array (
            'name' => 'packageBytes',
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
            'startLine' => 197,
            'endLine' => 197,
            'startColumn' => 3,
            'endColumn' => 22,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
          'extra' => 
          array (
            'name' => 'extra',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 198,
                'endLine' => 198,
                'startTokenPos' => 416,
                'startFilePos' => 6675,
                'endTokenPos' => 417,
                'endFilePos' => 6676,
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
            'startLine' => 198,
            'endLine' => 198,
            'startColumn' => 3,
            'endColumn' => 19,
            'parameterIndex' => 6,
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
 * The common shape of every "open for an agent" read.
 *
 * All three reads do the same four things — resolve, read, bound, describe
 * — and differ only in what they call the items. Writing that out three
 * times is how the truncation rule ends up applied in two of them.
 *
 * @param string $uid The acting user id.
 * @param File $file The resolved file.
 * @param array<int, mixed> $items Everything the codec read.
 * @param string $countKey The envelope key for the total count.
 * @param string $itemsKey The envelope key for the items.
 * @param string $packageBytes The bytes the items were read from.
 * @param array<string, mixed> $extra Format-specific additions.
 *
 * @return array<string, mixed> The envelope.
 */',
        'startLine' => 191,
        'endLine' => 220,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'aliasName' => NULL,
      ),
      'editForAgent' => 
      array (
        'name' => 'editForAgent',
        'parameters' => 
        array (
          'uid' => 
          array (
            'name' => 'uid',
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
            'startLine' => 240,
            'endLine' => 240,
            'startColumn' => 3,
            'endColumn' => 13,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 241,
            'endLine' => 241,
            'startColumn' => 3,
            'endColumn' => 13,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'edits' => 
          array (
            'name' => 'edits',
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
            'startLine' => 242,
            'endLine' => 242,
            'startColumn' => 3,
            'endColumn' => 14,
            'parameterIndex' => 2,
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
            'startLine' => 243,
            'endLine' => 243,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'requestedMode' => 
          array (
            'name' => 'requestedMode',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 244,
                'endLine' => 244,
                'startTokenPos' => 642,
                'startFilePos' => 8548,
                'endTokenPos' => 642,
                'endFilePos' => 8551,
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
            'startLine' => 244,
            'endLine' => 244,
            'startColumn' => 3,
            'endColumn' => 31,
            'parameterIndex' => 4,
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
 * Apply anchored edits to a document.
 *
 * @param string $uid The acting user id.
 * @param int $fileId The Nextcloud file id.
 * @param array<int, array{anchor: string, action?: string, text?: string}> $edits The edits.
 * @param string $version The `version` returned by the read that produced these anchors.
 * @param string|null $requestedMode An output mode narrowing request, or null for the configured mode.
 *
 * @return array<string, mixed> The outcome, including the produced file id.
 *
 * @throws RuntimeException On any refusal or failure. Nothing is written on a throw.
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-an-in-place-write-is-guarded-by-the-lock-and-a-version-precondition
 * @spec openspec/specs/document-editing/spec.md#requirement-output-mode-may-be-narrowed-by-a-caller-but-never-widened
 * @spec openspec/specs/document-editing/spec.md#requirement-lock-contention-is-refused-not-waited-out
 */',
        'startLine' => 239,
        'endLine' => 265,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'aliasName' => NULL,
      ),
      'openSpreadsheetForAgent' => 
      array (
        'name' => 'openSpreadsheetForAgent',
        'parameters' => 
        array (
          'uid' => 
          array (
            'name' => 'uid',
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
            'startLine' => 279,
            'endLine' => 279,
            'startColumn' => 42,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 279,
            'endLine' => 279,
            'startColumn' => 55,
            'endColumn' => 65,
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
 * Read a spreadsheet\'s cells for an agent.
 *
 * @param string $uid The acting user id.
 * @param int $fileId The Nextcloud file id.
 *
 * @return array<string, mixed> The sheet\'s cells and the version an edit requires.
 *
 * @throws RuntimeException When the file cannot be read or is not a spreadsheet.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
 */',
        'startLine' => 279,
        'endLine' => 300,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'aliasName' => NULL,
      ),
      'editSpreadsheetForAgent' => 
      array (
        'name' => 'editSpreadsheetForAgent',
        'parameters' => 
        array (
          'uid' => 
          array (
            'name' => 'uid',
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
            'startLine' => 320,
            'endLine' => 320,
            'startColumn' => 42,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 320,
            'endLine' => 320,
            'startColumn' => 55,
            'endColumn' => 65,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'edits' => 
          array (
            'name' => 'edits',
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
            'startLine' => 320,
            'endLine' => 320,
            'startColumn' => 68,
            'endColumn' => 79,
            'parameterIndex' => 2,
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
            'startLine' => 320,
            'endLine' => 320,
            'startColumn' => 82,
            'endColumn' => 96,
            'parameterIndex' => 3,
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
 * Write literal values into a spreadsheet\'s cells.
 *
 * Reuses the SAME lock discipline, version precondition and agent-authored
 * marking as text editing. None of that is type-specific, and a per-type
 * write path would eventually re-implement one of them wrongly.
 *
 * @param string $uid The acting user id.
 * @param int $fileId The Nextcloud file id.
 * @param array $edits Each `{cell, value, replaceFormula?}`.
 * @param string $version The `version` from the read that produced these addresses.
 *
 * @return array<string, mixed> The outcome, including cells whose cached values went stale.
 *
 * @throws RuntimeException On any refusal. Nothing is written on a throw.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
 */',
        'startLine' => 320,
        'endLine' => 356,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'aliasName' => NULL,
      ),
      'openPresentationForAgent' => 
      array (
        'name' => 'openPresentationForAgent',
        'parameters' => 
        array (
          'uid' => 
          array (
            'name' => 'uid',
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
            'startLine' => 370,
            'endLine' => 370,
            'startColumn' => 43,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 370,
            'endLine' => 370,
            'startColumn' => 56,
            'endColumn' => 66,
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
 * Read a presentation\'s shapes for an agent.
 *
 * @param string $uid The acting user id.
 * @param int $fileId The Nextcloud file id.
 *
 * @return array<string, mixed> The deck\'s shapes and the version an edit requires.
 *
 * @throws RuntimeException When the file cannot be read or is not a presentation.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
 */',
        'startLine' => 370,
        'endLine' => 391,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'aliasName' => NULL,
      ),
      'editPresentationForAgent' => 
      array (
        'name' => 'editPresentationForAgent',
        'parameters' => 
        array (
          'uid' => 
          array (
            'name' => 'uid',
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
            'startLine' => 407,
            'endLine' => 407,
            'startColumn' => 43,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 407,
            'endLine' => 407,
            'startColumn' => 56,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'edits' => 
          array (
            'name' => 'edits',
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
            'startLine' => 407,
            'endLine' => 407,
            'startColumn' => 69,
            'endColumn' => 80,
            'parameterIndex' => 2,
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
            'startLine' => 407,
            'endLine' => 407,
            'startColumn' => 83,
            'endColumn' => 97,
            'parameterIndex' => 3,
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
 * Replace the text of addressed presentation shapes.
 *
 * @param string $uid The acting user id.
 * @param int $fileId The Nextcloud file id.
 * @param array $edits Each `{slide, shape, text, region?}`.
 * @param string $version The `version` from the read that produced these ids.
 *
 * @return array<string, mixed> The outcome.
 *
 * @throws RuntimeException On any refusal. Nothing is written on a throw.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
 */',
        'startLine' => 407,
        'endLine' => 428,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'aliasName' => NULL,
      ),
      'readMetadataForAgent' => 
      array (
        'name' => 'readMetadataForAgent',
        'parameters' => 
        array (
          'uid' => 
          array (
            'name' => 'uid',
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
            'startLine' => 442,
            'endLine' => 442,
            'startColumn' => 39,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 442,
            'endLine' => 442,
            'startColumn' => 52,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Read a document\'s metadata.
 *
 * @param string $uid The acting user id.
 * @param int $fileId The Nextcloud file id.
 *
 * @return array<string, mixed> The document\'s name, format, version and metadata.
 *
 * @throws RuntimeException When the file cannot be read or the format is unsupported.
 *
 * @spec openspec/specs/document-rich-editing/spec.md
 */',
        'startLine' => 442,
        'endLine' => 458,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'aliasName' => NULL,
      ),
      'setMetadataForAgent' => 
      array (
        'name' => 'setMetadataForAgent',
        'parameters' => 
        array (
          'uid' => 
          array (
            'name' => 'uid',
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
            'startLine' => 481,
            'endLine' => 481,
            'startColumn' => 3,
            'endColumn' => 13,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 482,
            'endLine' => 482,
            'startColumn' => 3,
            'endColumn' => 13,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'values' => 
          array (
            'name' => 'values',
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
            'startLine' => 483,
            'endLine' => 483,
            'startColumn' => 3,
            'endColumn' => 15,
            'parameterIndex' => 2,
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
            'startLine' => 484,
            'endLine' => 484,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'requestedMode' => 
          array (
            'name' => 'requestedMode',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 485,
                'endLine' => 485,
                'startTokenPos' => 1838,
                'startFilePos' => 16258,
                'endTokenPos' => 1838,
                'endFilePos' => 16261,
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
            'startLine' => 485,
            'endLine' => 485,
            'startColumn' => 3,
            'endColumn' => 31,
            'parameterIndex' => 4,
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
 * Write a document\'s metadata.
 *
 * Runs through the same session as a body edit -- same lock, same version
 * recheck immediately before the write, same agent-authored tag applied before
 * the bytes become visible. Metadata is a smaller change than a paragraph
 * rewrite, not a less accountable one.
 *
 * @param string $uid The acting user id.
 * @param int $fileId The Nextcloud file id.
 * @param array<string, string> $values Field name => new value.
 * @param string $version The version returned by the preceding read.
 * @param string|null $requestedMode The requested output mode, or null for the configured default.
 *
 * @return array<string, mixed> The write result.
 *
 * @throws RuntimeException On any refusal or failure.
 *
 * @spec openspec/specs/document-rich-editing/spec.md
 */',
        'startLine' => 480,
        'endLine' => 506,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'aliasName' => NULL,
      ),
      'embedChartForAgent' => 
      array (
        'name' => 'embedChartForAgent',
        'parameters' => 
        array (
          'uid' => 
          array (
            'name' => 'uid',
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
            'startLine' => 530,
            'endLine' => 530,
            'startColumn' => 3,
            'endColumn' => 13,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 531,
            'endLine' => 531,
            'startColumn' => 3,
            'endColumn' => 13,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'chart' => 
          array (
            'name' => 'chart',
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
            'startLine' => 532,
            'endLine' => 532,
            'startColumn' => 3,
            'endColumn' => 14,
            'parameterIndex' => 2,
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
            'startLine' => 533,
            'endLine' => 533,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'afterAnchor' => 
          array (
            'name' => 'afterAnchor',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 534,
                'endLine' => 534,
                'startTokenPos' => 2010,
                'startFilePos' => 17809,
                'endTokenPos' => 2010,
                'endFilePos' => 17812,
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
            'startLine' => 534,
            'endLine' => 534,
            'startColumn' => 3,
            'endColumn' => 29,
            'parameterIndex' => 4,
            'isOptional' => true,
          ),
          'requestedMode' => 
          array (
            'name' => 'requestedMode',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 535,
                'endLine' => 535,
                'startTokenPos' => 2020,
                'startFilePos' => 17842,
                'endTokenPos' => 2020,
                'endFilePos' => 17845,
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
            'startLine' => 535,
            'endLine' => 535,
            'startColumn' => 3,
            'endColumn' => 31,
            'parameterIndex' => 5,
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
 * Embed a chart into a document.
 *
 * Runs through the same session as a text edit -- same lock, same version
 * recheck, same agent-authored tag. A chart adds package PARTS rather than
 * rewriting one, which makes the version precondition more important rather
 * than less: a half-applied multi-part write is a document no suite will open.
 *
 * @param string $uid The acting user id.
 * @param int $fileId The Nextcloud file id.
 * @param array<string, mixed> $chart The chart definition.
 * @param string $version The version returned by the preceding read.
 * @param string|null $afterAnchor The anchor to place the chart after, or null for the end.
 * @param string|null $requestedMode The requested output mode, or null for the configured default.
 *
 * @return array<string, mixed> The write result.
 *
 * @throws RuntimeException On any refusal or failure.
 *
 * @spec openspec/specs/document-chart-embedding/spec.md
 */',
        'startLine' => 529,
        'endLine' => 557,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'aliasName' => NULL,
      ),
      'prepareWrite' => 
      array (
        'name' => 'prepareWrite',
        'parameters' => 
        array (
          'uid' => 
          array (
            'name' => 'uid',
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
            'startLine' => 585,
            'endLine' => 585,
            'startColumn' => 3,
            'endColumn' => 13,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 586,
            'endLine' => 586,
            'startColumn' => 3,
            'endColumn' => 13,
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
            'startLine' => 587,
            'endLine' => 587,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'requestedMode' => 
          array (
            'name' => 'requestedMode',
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
            'startLine' => 588,
            'endLine' => 588,
            'startColumn' => 3,
            'endColumn' => 24,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'supports' => 
          array (
            'name' => 'supports',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 589,
                'endLine' => 589,
                'startTokenPos' => 2199,
                'startFilePos' => 19849,
                'endTokenPos' => 2199,
                'endFilePos' => 19852,
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
                      'name' => 'callable',
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
            'startLine' => 589,
            'endLine' => 589,
            'startColumn' => 3,
            'endColumn' => 28,
            'parameterIndex' => 4,
            'isOptional' => true,
          ),
          'formats' => 
          array (
            'name' => 'formats',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 590,
                'endLine' => 590,
                'startTokenPos' => 2208,
                'startFilePos' => 19875,
                'endTokenPos' => 2208,
                'endFilePos' => 19876,
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
            'startLine' => 590,
            'endLine' => 590,
            'startColumn' => 3,
            'endColumn' => 22,
            'parameterIndex' => 5,
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
 * The checks every agent write shares, run in the order they must run in.
 *
 * Extracted because three entry points -- text edits, metadata and charts --
 * repeated it verbatim. Triplicated preconditions drift: the third copy is
 * where someone eventually omits `refuseIfGuarded()` and a document under a
 * signing request becomes editable through the newest tool only.
 *
 * @param string $uid The acting user id.
 * @param int $fileId The Nextcloud file id.
 * @param string $version The version the caller is writing against.
 * @param string|null $requestedMode The requested output mode, or null.
 * @param callable|null $supports Predicate deciding whether this file\'s
 *                                extension is editable by the calling codec.
 *                                Null means the text codec\'s own support set.
 * @param string $formats The human-readable format list named in the refusal
 *                        when `$supports` says no. Empty means the text
 *                        codec\'s supported extensions.
 *
 * @return array{0: File, 1: string} The resolved file and output mode.
 *
 * @throws RuntimeException When the version is absent, the file is guarded, or it is not writable.
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-an-in-place-write-is-guarded-by-the-lock-and-a-version-precondition
 */',
        'startLine' => 584,
        'endLine' => 608,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'aliasName' => NULL,
      ),
      'resolveMode' => 
      array (
        'name' => 'resolveMode',
        'parameters' => 
        array (
          'requested' => 
          array (
            'name' => 'requested',
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
            'startLine' => 619,
            'endLine' => 619,
            'startColumn' => 31,
            'endColumn' => 48,
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
 * Resolve the output mode: configuration sets the ceiling, the argument may only narrow it.
 *
 * @param string|null $requested The requested mode, or null.
 *
 * @return string The resolved mode.
 *
 * @throws RuntimeException When the requested mode is not a mode.
 */',
        'startLine' => 619,
        'endLine' => 647,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'aliasName' => NULL,
      ),
      'refuseIfGuarded' => 
      array (
        'name' => 'refuseIfGuarded',
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
            'startLine' => 662,
            'endLine' => 662,
            'startColumn' => 35,
            'endColumn' => 44,
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
 * Apply the standing refusals.
 *
 * Both apply to sibling output too: a redacted document re-edited into a
 * near-identical neighbour is the same re-identification risk, and a copy of
 * a document under signature invites signing the wrong artefact.
 *
 * @param File $file The file to check.
 *
 * @return void
 *
 * @throws RuntimeException When a refusal applies.
 */',
        'startLine' => 662,
        'endLine' => 679,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'aliasName' => NULL,
      ),
      'resolveFile' => 
      array (
        'name' => 'resolveFile',
        'parameters' => 
        array (
          'uid' => 
          array (
            'name' => 'uid',
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
            'startLine' => 699,
            'endLine' => 699,
            'startColumn' => 31,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 699,
            'endLine' => 699,
            'startColumn' => 44,
            'endColumn' => 54,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'supports' => 
          array (
            'name' => 'supports',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 699,
                'endLine' => 699,
                'startTokenPos' => 2698,
                'startFilePos' => 23777,
                'endTokenPos' => 2698,
                'endFilePos' => 23780,
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
                      'name' => 'callable',
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
            'startLine' => 699,
            'endLine' => 699,
            'startColumn' => 57,
            'endColumn' => 82,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'formats' => 
          array (
            'name' => 'formats',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 699,
                'endLine' => 699,
                'startTokenPos' => 2707,
                'startFilePos' => 23801,
                'endTokenPos' => 2707,
                'endFilePos' => 23802,
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
            'startLine' => 699,
            'endLine' => 699,
            'startColumn' => 85,
            'endColumn' => 104,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\Files\\File',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolve a file id in the acting user\'s own folder.
 *
 * Resolving through the user folder rather than the root is the IDOR
 * boundary: a file id the user cannot reach does not resolve at all.
 *
 * @param string $uid The acting user id.
 * @param int $fileId The Nextcloud file id.
 * @param callable|null $supports Predicate deciding whether this file\'s
 *                                extension is editable by the calling codec.
 *                                Null means the text codec\'s own support set.
 * @param string $formats The human-readable format list named in the refusal
 *                        when `$supports` says no.
 *
 * @return File The file.
 *
 * @throws RuntimeException When the id names nothing the user can reach, or an uneditable format.
 */',
        'startLine' => 699,
        'endLine' => 737,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'aliasName' => NULL,
      ),
      'readBytes' => 
      array (
        'name' => 'readBytes',
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
            'startLine' => 748,
            'endLine' => 748,
            'startColumn' => 29,
            'endColumn' => 38,
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
 * Read a file\'s bytes, refusing anything too large to hold in memory.
 *
 * @param File $file The file.
 *
 * @return string The bytes.
 *
 * @throws RuntimeException When the file is too large or unreadable.
 */',
        'startLine' => 748,
        'endLine' => 761,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'aliasName' => NULL,
      ),
      'userPath' => 
      array (
        'name' => 'userPath',
        'parameters' => 
        array (
          'uid' => 
          array (
            'name' => 'uid',
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
            'startLine' => 771,
            'endLine' => 771,
            'startColumn' => 28,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 771,
            'endLine' => 771,
            'startColumn' => 41,
            'endColumn' => 50,
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
 * Express a file\'s path relative to the acting user\'s root, for a human-readable answer.
 *
 * @param string $uid The acting user id.
 * @param File $file The file.
 *
 * @return string The relative path, or the file name when it cannot be derived.
 */',
        'startLine' => 771,
        'endLine' => 778,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
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