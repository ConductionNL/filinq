<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/UploadFragmentReaper.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\UploadFragmentReaper
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-a07043d336b31f99f531b200cd860cb67414e554f57d2649984861293ba2a0bc',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\UploadFragmentReaper',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/UploadFragmentReaper.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\UploadFragmentReaper',
    'shortName' => 'UploadFragmentReaper',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Reaps upload fragments older than a declared age, and says what it removed.
 *
 * 🔴 IT SWEEPS FILINQ\'S OWN TREE AND NOTHING ELSE. Nextcloud already reaps its
 * DAV chunk directories under `<user>/uploads` with `OCA\\DAV\\BackgroundJob\\
 * UploadCleanup`, and a second reaper over the same directory would be a second
 * answer to one question, with two different declared ages disagreeing about
 * which one won. What nothing reaps is the half-written file an interrupted
 * upload leaves INSIDE the documents folder, which is filinq\'s own tree, so
 * that is the only place this looks.
 *
 * 🔴 A FRAGMENT IS RECOGNISED BY ITS SUFFIX, NEVER BY BEING EMPTY OR SMALL.
 * "Zero bytes" would reap a legitimately empty document somebody created on
 * purpose, and "small" would reap a one-line note. The sync client and the web
 * uploader both write a recognisable suffix while a transfer is in flight; a
 * finished upload no longer carries it. Anything without one of these suffixes
 * is somebody\'s file and is not this job\'s business.
 *
 * 🔑 THE COUNT AND THE BYTES ARE THE DELIVERABLE. REQ-CDF-05 asks for both,
 * because a count alone cannot answer "did the reaper reclaim anything", and
 * bytes alone cannot answer "is something writing fragments in a loop".
 *
 * 🔑 A FRAGMENT THAT COULD NOT BE DELETED IS REPORTED, NOT COUNTED AS REMOVED.
 * Counting it would make the reclaimed bytes a number nobody can check against
 * the disk, and a read-only mount would report a clean sweep every night for
 * ever.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction Development Team <dev@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 66,
    'endLine' => 238,
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
      'FRAGMENT_SUFFIXES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\UploadFragmentReaper',
        'implementingClassName' => 'OCA\\Filinq\\Service\\UploadFragmentReaper',
        'name' => 'FRAGMENT_SUFFIXES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'.part\', \'.filepart\', \'.ocTransferId\']',
          'attributes' => 
          array (
            'startLine' => 73,
            'endLine' => 73,
            'startTokenPos' => 60,
            'startFilePos' => 2709,
            'endTokenPos' => 68,
            'endFilePos' => 2747,
          ),
        ),
        'docComment' => '/**
 * The suffixes an in-flight upload carries.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 2,
        'endColumn' => 74,
      ),
    ),
    'immediateProperties' => 
    array (
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\UploadFragmentReaper',
        'implementingClassName' => 'OCA\\Filinq\\Service\\UploadFragmentReaper',
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
        'startLine' => 83,
        'endLine' => 83,
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
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 3,
            'endColumn' => 42,
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
 * Collaborators.
 *
 * @param LoggerInterface $logger Structured logger.
 *
 * @return void
 */',
        'startLine' => 82,
        'endLine' => 86,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\UploadFragmentReaper',
        'implementingClassName' => 'OCA\\Filinq\\Service\\UploadFragmentReaper',
        'currentClassName' => 'OCA\\Filinq\\Service\\UploadFragmentReaper',
        'aliasName' => NULL,
      ),
      'isFragmentName' => 
      array (
        'name' => 'isFragmentName',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
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
            'startLine' => 97,
            'endLine' => 97,
            'startColumn' => 33,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Whether a name is an upload fragment\'s name.
 *
 * @param string $name The file name.
 *
 * @return bool True when the name carries a fragment suffix.
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
        'startLine' => 97,
        'endLine' => 114,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\UploadFragmentReaper',
        'implementingClassName' => 'OCA\\Filinq\\Service\\UploadFragmentReaper',
        'currentClassName' => 'OCA\\Filinq\\Service\\UploadFragmentReaper',
        'aliasName' => NULL,
      ),
      'reap' => 
      array (
        'name' => 'reap',
        'parameters' => 
        array (
          'folder' => 
          array (
            'name' => 'folder',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Files\\Folder',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 127,
            'endLine' => 127,
            'startColumn' => 23,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'maxAgeSeconds' => 
          array (
            'name' => 'maxAgeSeconds',
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
            'startLine' => 127,
            'endLine' => 127,
            'startColumn' => 39,
            'endColumn' => 56,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'now' => 
          array (
            'name' => 'now',
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
            'startLine' => 127,
            'endLine' => 127,
            'startColumn' => 59,
            'endColumn' => 66,
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
 * Reap one folder tree.
 *
 * @param Folder $folder        The tree to sweep, which is filinq\'s documents folder.
 * @param int    $maxAgeSeconds How old a fragment must be before it is reaped.
 * @param int    $now           The moment to measure age against, as a unix timestamp.
 *
 * @return array{removed: int, bytes: int, kept: int, refused: array<int, array{path: string, reason: string}>} What the sweep did.
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
        'startLine' => 127,
        'endLine' => 173,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\UploadFragmentReaper',
        'implementingClassName' => 'OCA\\Filinq\\Service\\UploadFragmentReaper',
        'currentClassName' => 'OCA\\Filinq\\Service\\UploadFragmentReaper',
        'aliasName' => NULL,
      ),
      'fragments' => 
      array (
        'name' => 'fragments',
        'parameters' => 
        array (
          'folder' => 
          array (
            'name' => 'folder',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Files\\Folder',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 184,
            'endLine' => 184,
            'startColumn' => 29,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Every fragment in a tree, depth first.
 *
 * @param Folder $folder The tree.
 *
 * @return array<int, File> The fragments.
 *
 * @spec exclude Tree walk with no decision of its own; the decision is isFragmentName().
 */',
        'startLine' => 184,
        'endLine' => 211,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\UploadFragmentReaper',
        'implementingClassName' => 'OCA\\Filinq\\Service\\UploadFragmentReaper',
        'currentClassName' => 'OCA\\Filinq\\Service\\UploadFragmentReaper',
        'aliasName' => NULL,
      ),
      'pathOf' => 
      array (
        'name' => 'pathOf',
        'parameters' => 
        array (
          'node' => 
          array (
            'name' => 'node',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Files\\Node',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 222,
            'endLine' => 222,
            'startColumn' => 26,
            'endColumn' => 35,
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
 * A node\'s path, for the report, without letting a broken node stop the sweep.
 *
 * @param Node $node The node.
 *
 * @return string The path, or its name when the path cannot be read.
 *
 * @spec exclude Reporting helper.
 */',
        'startLine' => 222,
        'endLine' => 237,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\UploadFragmentReaper',
        'implementingClassName' => 'OCA\\Filinq\\Service\\UploadFragmentReaper',
        'currentClassName' => 'OCA\\Filinq\\Service\\UploadFragmentReaper',
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